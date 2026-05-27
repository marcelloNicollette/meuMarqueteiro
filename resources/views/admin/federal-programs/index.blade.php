@extends('layouts.admin')
@section('title', 'Radar de Recursos — Admin')
@section('content')

    <div style="padding:2rem;max-width:1320px">

        {{-- ── Header ─── --}}
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
            <div>
                <h1 style="font-size:1.4rem;font-weight:700">Radar de Recursos</h1>
                <p style="font-size:.85rem;color:#6b7280;margin-top:.3rem">
                    Base atual do radar de recursos com sincronismo oficial e normalizacao do novo workflow de status
                </p>
            </div>
            <div style="display:flex;gap:.75rem;align-items:center">
                <a href="{{ route('admin.settings.integrations') }}"
                    style="padding:.5rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;color:#374151;text-decoration:none;background:#fff">
                    ⚙️ Configurar APIs
                </a>
                <button onclick="runBackfill(true)"
                    style="padding:.5rem 1rem;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer">
                    ⌁ Simular Backfill
                </button>
                <button onclick="runBackfill(false)"
                    style="padding:.5rem 1rem;background:#fff7ed;color:#c2410c;border:1px solid #fdba74;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer">
                    ⇪ Aplicar Backfill
                </button>
                <button onclick="syncAll(false)"
                    style="padding:.5rem 1.2rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer">
                    ↻ Sincronizar Todos
                </button>
            </div>
        </div>

        {{-- ── Stats globais ─── --}}
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:.75rem;margin-bottom:1.5rem">
            @foreach ([['l' => 'Total oportunidades', 'v' => $stats['total'], 'c' => '#0f1117'], ['l' => 'Publicadas', 'v' => $stats['published'], 'c' => '#1a5fa8'], ['l' => 'Encerrando', 'v' => $stats['closing_soon'], 'c' => '#e65100'], ['l' => 'Monitoramento', 'v' => $stats['monitoring'], 'c' => '#059669'], ['l' => 'Encerradas 60d', 'v' => $stats['closed_recently'], 'c' => '#6b7280'], ['l' => 'Alta compatib.', 'v' => $stats['high_match'], 'c' => '#b8902a'], ['l' => 'Última sync', 'v' => $stats['last_sync'] ? \Carbon\Carbon::parse($stats['last_sync'])->diffForHumans() : 'Nunca', 'c' => '#6b7280']] as $s)
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.9rem 1rem">
                    <div style="font-size:1.3rem;font-weight:700;color:{{ $s['c'] }}">{{ $s['v'] }}</div>
                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">{{ $s['l'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ── Chave Transparência ─── --}}
        @php
            $tpKey =
                \App\Models\SystemSetting::get('integration_transparencia_chave') ?:
                \App\Models\SystemSetting::get('transparencia_api_key');
        @endphp
        @if (!$tpKey)
            <div
                style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem">
                <span style="font-size:1.2rem">⚠️</span>
                <div style="flex:1">
                    <strong style="font-size:.88rem;color:#92400e">Chave do Portal da Transparência não configurada</strong>
                    <p style="font-size:.8rem;color:#b45309;margin-top:.2rem">
                        Sem ela, a sincronização de captação e convênios não conseguirá consultar a fonte oficial. Obtenha
                        grátis em
                        <a href="https://portaldatransparencia.gov.br/api" target="_blank"
                            style="color:#b45309">portaldatransparencia.gov.br/api</a>
                    </p>
                </div>
                <a href="{{ route('admin.settings.integrations') }}"
                    style="padding:.45rem .9rem;background:#f59e0b;color:#fff;border-radius:7px;font-size:.8rem;text-decoration:none;white-space:nowrap">
                    Configurar
                </a>
            </div>
        @endif

        {{-- ── Toast de feedback ─── --}}
        <div id="toast"
            style="display:none;background:#0f1117;color:#fff;padding:.75rem 1.1rem;border-radius:9px;
         font-size:.84rem;margin-bottom:1rem;border-left:3px solid #b8902a"
            id="toast"></div>

        @if (session('status'))
            <div
                style="background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:.8rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.82rem;font-weight:600">
                {{ session('status') }}
            </div>
        @endif

        <div id="backfill-output"
            style="display:none;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.1rem;margin-bottom:1rem">
            <div style="font-size:.78rem;font-weight:700;color:#374151;margin-bottom:.45rem">Backfill de fontes</div>
            <pre id="backfill-output-text"
                style="margin:0;font-size:.75rem;line-height:1.6;color:#4b5563;white-space:pre-wrap;font-family:ui-monospace, SFMono-Regular, Menlo, monospace"></pre>
        </div>

        {{-- ── Observabilidade da fila ─── --}}
        @php
            $healthBg = match ($queueHealth['tone']) {
                'danger' => '#fef2f2',
                'warning' => '#fffbeb',
                'info' => '#eff6ff',
                default => '#ecfdf5',
            };
            $healthBorder = match ($queueHealth['tone']) {
                'danger' => '#fecaca',
                'warning' => '#fde68a',
                'info' => '#bfdbfe',
                default => '#a7f3d0',
            };
            $healthColor = match ($queueHealth['tone']) {
                'danger' => '#b91c1c',
                'warning' => '#b45309',
                'info' => '#1d4ed8',
                default => '#047857',
            };
        @endphp
        <div
            style="background:{{ $healthBg }};border:1px solid {{ $healthBorder }};border-radius:12px;padding:1rem 1.1rem;margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                <div>
                    <div style="font-size:.92rem;font-weight:700;color:{{ $healthColor }}">{{ $queueHealth['headline'] }}
                    </div>
                    <div style="font-size:.8rem;color:{{ $healthColor }};margin-top:.2rem">
                        {{ $queueHealth['message'] }}
                    </div>
                </div>
                <div style="text-align:right;min-width:220px">
                    <div style="font-size:.75rem;color:#6b7280">Conexão resolvida</div>
                    <div style="font-size:.88rem;font-weight:700;color:#111">
                        {{ $queueHealth['resolved_connection'] ?? 'nenhuma' }}</div>
                    <div style="font-size:.74rem;color:#9ca3af;margin-top:.2rem">Fila do Radar:
                        {{ $queueHealth['radar_queue_name'] }}</div>
                    <div style="font-size:.74rem;color:#9ca3af;margin-top:.2rem">`queue.default`:
                        {{ $queueHealth['queue_default'] }}</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:.75rem;margin-top:1rem">
                @foreach ([['l' => 'Jobs do Radar', 'v' => $queueHealth['radar_jobs_pending'], 'c' => '#111827'], ['l' => 'Backlog externo', 'v' => $queueHealth['other_jobs_pending'], 'c' => '#6b7280'], ['l' => 'Na fila', 'v' => $queueHealth['queued_count'], 'c' => '#b45309'], ['l' => 'Em execução', 'v' => $queueHealth['running_count'], 'c' => '#1d4ed8'], ['l' => 'Falhas', 'v' => $queueHealth['failed_count'], 'c' => '#b91c1c'], ['l' => 'Reprocessáveis', 'v' => $queueHealth['retryable_count'], 'c' => '#3730a3'], ['l' => 'Duração média', 'v' => $queueHealth['avg_duration_ms'] > 0 ? number_format($queueHealth['avg_duration_ms'] / 1000, 1, ',', '.') . 's' : '—', 'c' => '#047857']] as $card)
                    <div
                        style="background:#fff;border:1px solid rgba(255,255,255,.65);border-radius:10px;padding:.85rem .9rem">
                        <div style="font-size:1.1rem;font-weight:700;color:{{ $card['c'] }}">{{ $card['v'] }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">{{ $card['l'] }}</div>
                    </div>
                @endforeach
            </div>
            <div style="display:flex;gap:.6rem;justify-content:flex-end;align-items:center;margin-top:1rem;flex-wrap:wrap">
                <button onclick="reconcileExecutions()"
                    style="padding:.5rem .9rem;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer">
                    Ajustar stale agora
                </button>
                <button onclick="retryEligibleExecutions()"
                    style="padding:.5rem .9rem;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:8px;font-size:.8rem;font-weight:700;cursor:pointer">
                    Reenfileirar elegíveis
                </button>
                <button onclick="sendSnapshotEmail('daily')"
                    style="padding:.5rem .9rem;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;border-radius:8px;font-size:.8rem;font-weight:700;cursor:pointer">
                    Enviar snapshot diário
                </button>
                <button onclick="sendSnapshotEmail('weekly')"
                    style="padding:.5rem .9rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.8rem;font-weight:700;cursor:pointer">
                    Enviar snapshot semanal
                </button>
            </div>
            <div
                style="margin-top:1rem;padding:.8rem .9rem;border-radius:10px;background:#fff;border:1px solid rgba(255,255,255,.65)">
                <div style="font-size:.76rem;font-weight:700;color:#374151">Worker recomendado para o Radar</div>
                <div style="font-size:.76rem;color:#6b7280;margin-top:.25rem">
                    O ambiente deve ouvir ao menos as filas <code
                        style="background:#e5e7eb;padding:.1rem .35rem;border-radius:4px">{{ $queueHealth['radar_worker_queues'] }}</code>.
                </div>
                <pre
                    style="margin:.55rem 0 0;background:#0f1117;color:#e2e8f0;padding:.75rem .85rem;border-radius:8px;font-size:.75rem;overflow-x:auto">php artisan queue:work {{ $queueHealth['resolved_connection'] ?? 'database' }} --queue={{ $queueHealth['radar_worker_queues'] }} --tries=1 --timeout=900 --sleep=3</pre>
            </div>
            <div
                style="margin-top:1rem;padding:.8rem .9rem;border-radius:10px;background:#fff;border:1px solid rgba(255,255,255,.65)">
                <div style="font-size:.76rem;font-weight:700;color:#374151">Snapshots por e-mail</div>
                <div style="font-size:.76rem;color:#6b7280;margin-top:.25rem">
                    Mailer atual: <strong>{{ $snapshotMailConfig['mailer'] }}</strong>
                    • rotina global {{ $snapshotMailConfig['enabled'] ? 'ativada' : 'desativada' }}
                    • diário {{ $snapshotMailConfig['daily_enabled'] ? 'ativo' : 'desativado' }}
                    • semanal {{ $snapshotMailConfig['weekly_enabled'] ? 'ativo' : 'desativado' }}
                </div>
                <div style="font-size:.76rem;color:#6b7280;margin-top:.35rem;line-height:1.6">
                    Destinatários:
                    @if (!empty($snapshotMailConfig['recipients']))
                        {{ implode(', ', $snapshotMailConfig['recipients']) }}
                    @else
                        nenhum configurado
                    @endif
                </div>
                <div style="font-size:.74rem;color:#9ca3af;margin-top:.35rem">
                    Configure por `SystemSetting` com a chave `radar_sync_snapshot_recipients` ou via
                    `RADAR_SYNC_SNAPSHOT_RECIPIENTS`.
                </div>
            </div>
            @if ($queueHealth['last_failure'])
                <div
                    style="margin-top:1rem;padding:.8rem .9rem;border-radius:10px;background:#fff;border:1px solid #fee2e2">
                    <div style="font-size:.76rem;font-weight:700;color:#991b1b">Última falha registrada</div>
                    <div style="font-size:.8rem;color:#374151;margin-top:.25rem">
                        {{ $queueHealth['last_failure']['error_message'] ?: 'Falha sem mensagem adicional.' }}
                    </div>
                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem">
                        {{ $queueHealth['last_failure']['finished_at_human'] ?? ($queueHealth['last_failure']['updated_at_human'] ?? 'agora') }}
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Catálogo operacional das fontes ─── --}}
        <div id="curation-queue"
            style="margin-bottom:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                    <div>
                        <div style="font-size:.95rem;font-weight:700;color:#111827">Catálogo operacional das 16 fontes</div>
                        <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">
                            Base operacional do Radar com grupo de pipeline, estágio, curadoria e orientação de captura
                            persistidos.
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                        @foreach ($sourceCatalogSummary['groups'] as $group)
                            <span
                                style="padding:.3rem .65rem;border-radius:999px;background:#f3f4f6;color:#4b5563;font-size:.72rem;font-weight:700">
                                {{ $group['label'] }} · {{ $group['count'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem">
                    @foreach ([['l' => 'Total fontes', 'v' => $sourceCatalogSummary['total'], 'c' => '#111827'], ['l' => 'Ativas', 'v' => $sourceCatalogSummary['active'], 'c' => '#047857'], ['l' => 'Em produção', 'v' => $sourceCatalogSummary['live'], 'c' => '#1d4ed8'], ['l' => 'Exigem curadoria', 'v' => $sourceCatalogSummary['requires_curation'], 'c' => '#b45309'], ['l' => 'Com sync municipal', 'v' => $sourceCatalogSummary['supports_sync'], 'c' => '#3730a3']] as $card)
                        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.85rem .95rem">
                            <div style="font-size:1.15rem;font-weight:800;color:{{ $card['c'] }}">{{ $card['v'] }}
                            </div>
                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">{{ $card['l'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                Fonte</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                Grupo</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                Operação</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                Captura</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                Cobertura</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                Base atual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sourceCatalog as $source)
                            @php
                                $sourceTone = match ($source['operational_status_tone']) {
                                    'success' => ['bg' => '#ecfdf5', 'color' => '#047857'],
                                    'info' => ['bg' => '#eff6ff', 'color' => '#1d4ed8'],
                                    'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                    default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                };
                                $latestSourceRun = $source['latest_source_run'];
                                $latestRunTone = match (data_get($latestSourceRun, 'status_tone')) {
                                    'success' => ['bg' => '#ecfdf5', 'color' => '#047857'],
                                    'info' => ['bg' => '#eff6ff', 'color' => '#1d4ed8'],
                                    'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                    'danger' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                                    default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                };
                            @endphp
                            <tr style="border-bottom:1px solid #f3f4f6">
                                <td style="padding:.9rem 1rem;vertical-align:top">
                                    <div style="font-size:.86rem;font-weight:700;color:#111827">{{ $source['name'] }}</div>
                                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.18rem">
                                        {{ $source['key'] }} · escopo {{ $source['resource_scope'] ?: 'n/d' }}
                                    </div>
                                    @if ($source['is_priority_focus'] && $source['focus_badge_label'])
                                        <div style="margin-top:.35rem">
                                            <span
                                                style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:.68rem;font-weight:700">
                                                {{ $source['focus_badge_label'] }}
                                            </span>
                                        </div>
                                    @endif
                                    @if (!empty($source['operational_tags']))
                                        <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.45rem">
                                            @foreach ($source['operational_tags'] as $tag)
                                                <span
                                                    style="padding:.18rem .45rem;border-radius:999px;background:#f3f4f6;color:#4b5563;font-size:.68rem;font-weight:600">
                                                    {{ $tag }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td style="padding:.9rem 1rem;vertical-align:top;font-size:.78rem;color:#374151">
                                    <div style="font-weight:600">{{ $source['pipeline_group_label'] }}</div>
                                    <div style="color:#9ca3af;margin-top:.2rem">{{ $source['current_readiness'] }}</div>
                                </td>
                                <td style="padding:.9rem 1rem;vertical-align:top">
                                    <span
                                        style="display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:700;background:{{ $sourceTone['bg'] }};color:{{ $sourceTone['color'] }}">
                                        {{ $source['operational_status_label'] }}
                                    </span>
                                    <div style="font-size:.72rem;color:#6b7280;margin-top:.35rem;line-height:1.5">
                                        Prioridade: {{ $source['operational_priority'] }}<br>
                                        {{ $source['requires_human_curation'] ? 'Exige curadoria humana' : 'Publicação automatizável' }}<br>
                                        {{ $source['supports_municipality_sync'] ? 'Compatível com sync por município' : 'Não opera por município' }}
                                    </div>
                                    @if ($source['is_priority_focus'] && $source['focus_note'])
                                        <div style="font-size:.72rem;color:#3730a3;margin-top:.35rem;line-height:1.45">
                                            {{ $source['focus_note'] }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding:.9rem 1rem;vertical-align:top;font-size:.78rem;color:#374151">
                                    <div><strong>Método:</strong> {{ $source['capture_method'] }}</div>
                                    <div style="margin-top:.25rem"><strong>Frequência:</strong>
                                        {{ $source['refresh_frequency'] }}</div>
                                    <div style="margin-top:.25rem"><strong>Entrada:</strong>
                                        {{ $source['primary_entrypoint'] }}</div>
                                    @if ($source['source_url'])
                                        <div style="margin-top:.35rem">
                                            <a href="{{ $source['source_url'] }}" target="_blank"
                                                style="font-size:.72rem;color:#1d4ed8">
                                                Abrir fonte
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td
                                    style="padding:.9rem 1rem;vertical-align:top;font-size:.76rem;color:#4b5563;line-height:1.55;max-width:320px">
                                    <div>{{ $source['coverage_scope'] }}</div>
                                    <div style="margin-top:.4rem;color:#6b7280">{{ $source['access_guide'] }}</div>
                                    @if (!empty($source['index_fields']))
                                        <div style="margin-top:.45rem;color:#9ca3af">
                                            Índice: {{ implode(', ', $source['index_fields']) }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding:.9rem 1rem;vertical-align:top;font-size:.76rem;color:#374151">
                                    <div><strong>Oportunidades:</strong> {{ $source['opportunities_count'] }}</div>
                                    <div style="margin-top:.25rem"><strong>Fila curadoria:</strong>
                                        {{ $source['curation_queue_count'] }}</div>
                                    @if ($latestSourceRun)
                                        <div style="margin-top:.55rem">
                                            <span
                                                style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.7rem;font-weight:700;background:{{ $latestRunTone['bg'] }};color:{{ $latestRunTone['color'] }}">
                                                {{ $latestSourceRun['status_label'] }}
                                            </span>
                                        </div>
                                        <div style="margin-top:.3rem;color:#6b7280;line-height:1.5">
                                            {{ $latestSourceRun['records_fetched'] }} registro(s)
                                            @if ($latestSourceRun['finished_at_human'] || $latestSourceRun['started_at_human'])
                                                •
                                                {{ $latestSourceRun['finished_at_human'] ?: $latestSourceRun['started_at_human'] }}
                                            @endif
                                        </div>
                                        @if (!empty($latestSourceRun['message']))
                                            <div style="margin-top:.25rem;color:#9ca3af;line-height:1.45">
                                                {{ $latestSourceRun['message'] }}
                                            </div>
                                        @endif
                                    @endif
                                    <div style="margin-top:.25rem"><strong>Status:</strong>
                                        {{ $source['is_active'] ? 'Ativa' : 'Inativa' }}</div>
                                    <div style="margin-top:.45rem;color:#6b7280;line-height:1.5">
                                        {{ $source['maintenance_notes'] }}
                                    </div>
                                    <details style="margin-top:.7rem">
                                        <summary style="cursor:pointer;color:#3730a3;font-size:.72rem;font-weight:700">
                                            Configurar fonte
                                        </summary>
                                        <form method="POST"
                                            action="{{ route('admin.federal-programs.sources.config', $source['id']) }}"
                                            style="margin-top:.6rem;padding:.8rem;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb">
                                            @csrf
                                            <label style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                URL base da fonte
                                            </label>
                                            <input type="url" name="source_url" value="{{ $source['source_url'] }}"
                                                style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">

                                            <label style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                Guia de captura
                                            </label>
                                            <textarea name="access_guide" rows="3"
                                                style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['access_guide'] }}</textarea>

                                            @if ($source['pipeline_group'] === 'group_c_diary_monitor')
                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Pontos de monitoramento
                                                </label>
                                                <textarea name="diary_entrypoints" rows="4" placeholder="Uma URL por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['diary_entrypoints_text'] }}</textarea>
                                                <div
                                                    style="font-size:.68rem;color:#9ca3af;line-height:1.45;margin-bottom:.55rem">
                                                    Use para configurar buscas do DOU, DOE ou páginas estaduais por UF.
                                                </div>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Palavras-chave de caminho
                                                </label>
                                                <textarea name="diary_path_keywords" rows="3" placeholder="Uma por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['diary_path_keywords_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Hosts permitidos
                                                </label>
                                                <textarea name="diary_allowed_hosts" rows="2" placeholder="Um host por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['diary_allowed_hosts_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Termos obrigatórios
                                                </label>
                                                <textarea name="diary_required_terms" rows="3" placeholder="Um termo por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['diary_required_terms_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Termos fortes de título
                                                </label>
                                                <textarea name="diary_title_terms" rows="3" placeholder="Um termo por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['diary_title_terms_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Termos de descarte
                                                </label>
                                                <textarea name="diary_ignore_terms" rows="3" placeholder="Um termo por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['diary_ignore_terms_text'] }}</textarea>
                                            @else
                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Pontos de captura adicionais
                                                </label>
                                                <textarea name="scraping_entrypoints" rows="4" placeholder="Uma URL por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['scraping_entrypoints_text'] }}</textarea>
                                                <div
                                                    style="font-size:.68rem;color:#9ca3af;line-height:1.45;margin-bottom:.55rem">
                                                    Use para corrigir páginas quebradas ou priorizar seções específicas da
                                                    fonte.
                                                </div>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Palavras-chave de caminho
                                                </label>
                                                <textarea name="scraping_path_keywords" rows="3" placeholder="Uma por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['scraping_path_keywords_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Hosts permitidos
                                                </label>
                                                <textarea name="scraping_allowed_hosts" rows="2" placeholder="Um host por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['scraping_allowed_hosts_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Caminhos excluídos
                                                </label>
                                                <textarea name="scraping_excluded_path_keywords" rows="3" placeholder="Um termo de caminho por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['scraping_excluded_path_keywords_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Termos obrigatórios
                                                </label>
                                                <textarea name="scraping_required_terms" rows="3" placeholder="Um termo por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['scraping_required_terms_text'] }}</textarea>

                                                <label
                                                    style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                    Termos fortes de título
                                                </label>
                                                <textarea name="scraping_title_terms" rows="3" placeholder="Um termo por linha"
                                                    style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['scraping_title_terms_text'] }}</textarea>
                                            @endif

                                            <label style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                Score mínimo
                                            </label>
                                            <input type="number" name="minimum_score" min="0" max="100"
                                                value="{{ $source['minimum_score'] }}"
                                                style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">

                                            <label
                                                style="display:flex;align-items:center;gap:.4rem;font-size:.72rem;color:#374151;margin-bottom:.65rem">
                                                <input type="hidden" name="require_strong_signal" value="0">
                                                <input type="checkbox" name="require_strong_signal" value="1"
                                                    {{ $source['require_strong_signal'] ? 'checked' : '' }}>
                                                Exigir sinal forte em título ou caminho
                                            </label>

                                            <label style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.2rem">
                                                Observações operacionais
                                            </label>
                                            <textarea name="maintenance_notes" rows="3"
                                                style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;margin-bottom:.55rem">{{ $source['maintenance_notes'] }}</textarea>

                                            <label
                                                style="display:flex;align-items:center;gap:.4rem;font-size:.72rem;color:#374151;margin-bottom:.65rem">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1"
                                                    {{ $source['is_active'] ? 'checked' : '' }}>
                                                Fonte ativa para captura
                                            </label>

                                            <button type="submit"
                                                style="padding:.45rem .75rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.74rem;font-weight:700;cursor:pointer">
                                                Salvar configuração
                                            </button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Fechamento operacional do Grupo B ─── --}}
        @php
            $groupBTone = match ($groupBOperationalSummary['tone']) {
                'success' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'color' => '#047857'],
                'info' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'color' => '#1d4ed8'],
                default => ['bg' => '#fffbeb', 'border' => '#fde68a', 'color' => '#b45309'],
            };
        @endphp
        <div
            style="margin-bottom:1.5rem;background:{{ $groupBTone['bg'] }};border:1px solid {{ $groupBTone['border'] }};border-radius:12px;overflow:hidden">
            <div style="padding:1rem 1.1rem;border-bottom:1px solid {{ $groupBTone['border'] }}">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                    <div>
                        <div style="font-size:.95rem;font-weight:700;color:{{ $groupBTone['color'] }}">
                            {{ $groupBOperationalSummary['headline'] }}
                        </div>
                        <div style="font-size:.78rem;color:{{ $groupBTone['color'] }};margin-top:.2rem">
                            {{ $groupBOperationalSummary['message'] }}
                        </div>
                    </div>
                    <div style="font-size:.75rem;color:{{ $groupBTone['color'] }};font-weight:700">
                        {{ $groupBOperationalSummary['label'] }}
                    </div>
                </div>
            </div>
            <div style="padding:1rem 1.1rem;border-bottom:1px solid {{ $groupBTone['border'] }};background:#fff">
                <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:.75rem">
                    @foreach ([['l' => 'Fontes do grupo', 'v' => $groupBOperationalSummary['total_sources'], 'c' => '#111827'], ['l' => 'Fontes ativas', 'v' => $groupBOperationalSummary['active_sources'], 'c' => '#047857'], ['l' => 'Foco atual', 'v' => $groupBOperationalSummary['priority_sources'], 'c' => '#1d4ed8'], ['l' => 'Estaveis', 'v' => $groupBOperationalSummary['mature_sources'], 'c' => '#047857'], ['l' => 'Pedem atencao', 'v' => $groupBOperationalSummary['attention_sources'], 'c' => '#b45309'], ['l' => 'Sem sinal util', 'v' => $groupBOperationalSummary['zero_signal_sources'], 'c' => '#b91c1c']] as $card)
                        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                            <div style="font-size:1.05rem;font-weight:800;color:{{ $card['c'] }}">{{ $card['v'] }}
                            </div>
                            <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                {{ $card['l'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="padding:1rem 1.1rem;background:#fff">
                <div style="font-size:.78rem;font-weight:700;color:#374151;margin-bottom:.75rem">
                    Maturidade por fonte do scraping estruturado
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem">
                    @foreach ($groupBOperationalSummary['rows'] as $source)
                        @php
                            $maturityTone = match ($source['maturity_tone']) {
                                'success' => ['bg' => '#ecfdf5', 'color' => '#047857'],
                                'danger' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                                'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                            };
                        @endphp
                        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .9rem;background:#fff">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.82rem;font-weight:700;color:#111827">{{ $source['name'] }}
                                    </div>
                                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.18rem">
                                        {{ $source['key'] }}
                                        @if ($source['latest_source_run'])
                                            · {{ $source['latest_source_run']['pipeline_group_label'] }}
                                        @endif
                                    </div>
                                </div>
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap;justify-content:flex-end">
                                    @if ($source['is_priority_focus'])
                                        <span
                                            style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:#eff6ff;color:#1d4ed8">
                                            Foco atual
                                        </span>
                                    @endif
                                    <span
                                        style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:{{ $maturityTone['bg'] }};color:{{ $maturityTone['color'] }}">
                                        {{ $source['maturity_label'] }}
                                    </span>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:space-between;gap:.75rem;margin-top:.65rem">
                                <div style="font-size:.74rem;color:#4b5563;line-height:1.45">
                                    {{ $source['current_readiness'] }}
                                </div>
                                <div style="text-align:right;min-width:120px">
                                    <div style="font-size:.9rem;font-weight:800;color:#111827">
                                        {{ $source['records_fetched'] }}</div>
                                    <div style="font-size:.68rem;color:#9ca3af">registros na ultima coleta</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Fechamento operacional do Grupo C ─── --}}
        @php
            $groupCTone = match ($groupCOperationalSummary['tone']) {
                'success' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'color' => '#047857'],
                'info' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'color' => '#1d4ed8'],
                default => ['bg' => '#fffbeb', 'border' => '#fde68a', 'color' => '#b45309'],
            };
        @endphp
        <div
            style="margin-bottom:1.5rem;background:{{ $groupCTone['bg'] }};border:1px solid {{ $groupCTone['border'] }};border-radius:12px;overflow:hidden">
            <div style="padding:1rem 1.1rem;border-bottom:1px solid {{ $groupCTone['border'] }}">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                    <div>
                        <div style="font-size:.95rem;font-weight:700;color:{{ $groupCTone['color'] }}">
                            {{ $groupCOperationalSummary['headline'] }}
                        </div>
                        <div style="font-size:.78rem;color:{{ $groupCTone['color'] }};margin-top:.2rem">
                            {{ $groupCOperationalSummary['message'] }}
                        </div>
                    </div>
                    <div style="font-size:.75rem;color:{{ $groupCTone['color'] }};font-weight:700">
                        {{ $groupCOperationalSummary['label'] }}
                    </div>
                </div>
            </div>
            <div style="padding:1rem 1.1rem;border-bottom:1px solid {{ $groupCTone['border'] }};background:#fff">
                <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:.75rem">
                    @foreach ([['l' => 'Fontes do grupo', 'v' => $groupCOperationalSummary['total_sources'], 'c' => '#111827'], ['l' => 'Fontes ativas', 'v' => $groupCOperationalSummary['active_sources'], 'c' => '#047857'], ['l' => 'Foco atual', 'v' => $groupCOperationalSummary['priority_sources'], 'c' => '#1d4ed8'], ['l' => 'Estaveis', 'v' => $groupCOperationalSummary['mature_sources'], 'c' => '#047857'], ['l' => 'Pedem atencao', 'v' => $groupCOperationalSummary['attention_sources'], 'c' => '#b45309'], ['l' => 'Sem sinal util', 'v' => $groupCOperationalSummary['zero_signal_sources'], 'c' => '#b91c1c']] as $card)
                        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                            <div style="font-size:1.05rem;font-weight:800;color:{{ $card['c'] }}">
                                {{ $card['v'] }}
                            </div>
                            <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                {{ $card['l'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div style="padding:1rem 1.1rem;background:#fff">
                <div style="font-size:.78rem;font-weight:700;color:#374151;margin-bottom:.75rem">
                    {{ $groupCOperationalSummary['detail_label'] }}
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem">
                    @foreach ($groupCOperationalSummary['rows'] as $source)
                        @php
                            $maturityTone = match ($source['maturity_tone']) {
                                'success' => ['bg' => '#ecfdf5', 'color' => '#047857'],
                                'danger' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                                'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                            };
                        @endphp
                        <div style="border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .9rem;background:#fff">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.82rem;font-weight:700;color:#111827">{{ $source['name'] }}
                                    </div>
                                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.18rem">
                                        {{ $source['key'] }}
                                        @if ($source['latest_source_run'])
                                            · {{ $source['latest_source_run']['pipeline_group_label'] }}
                                        @endif
                                    </div>
                                </div>
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap;justify-content:flex-end">
                                    @if ($source['is_priority_focus'])
                                        <span
                                            style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:#eff6ff;color:#1d4ed8">
                                            Foco atual
                                        </span>
                                    @endif
                                    <span
                                        style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:{{ $maturityTone['bg'] }};color:{{ $maturityTone['color'] }}">
                                        {{ $source['maturity_label'] }}
                                    </span>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:space-between;gap:.75rem;margin-top:.65rem">
                                <div style="font-size:.74rem;color:#4b5563;line-height:1.45">
                                    {{ $source['current_readiness'] }}
                                </div>
                                <div style="text-align:right;min-width:120px">
                                    <div style="font-size:.9rem;font-weight:800;color:#111827">
                                        {{ $source['records_fetched'] }}</div>
                                    <div style="font-size:.68rem;color:#9ca3af">registros na ultima coleta</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Curadoria humana — fila operacional ─── --}}
        <div style="margin-bottom:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                    <div>
                        <div style="font-size:.95rem;font-weight:700;color:#111827">Grupo D · Curadoria humana</div>
                        <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">
                            Primeira camada operacional de revisão das oportunidades canônicas por fila, responsável e
                            decisão.
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                        <a href="{{ route('admin.federal-programs.exports.curation-queue.csv', request()->query()) }}"
                            style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;text-decoration:none;background:#fff">
                            Exportar fila CSV
                        </a>
                        <a href="{{ route('admin.federal-programs.exports.curation-queue.xlsx', request()->query()) }}"
                            style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;text-decoration:none;background:#fff">
                            Exportar fila XLSX
                        </a>
                        @foreach ([['l' => 'Backlog aberto', 'v' => $curationSummary['backlog_open'], 'c' => '#111827'], ['l' => 'Na fila', 'v' => $curationSummary['pending'], 'c' => '#b45309'], ['l' => 'Em revisão', 'v' => $curationSummary['in_review'], 'c' => '#1d4ed8'], ['l' => 'Aprovadas', 'v' => $curationSummary['approved'], 'c' => '#047857'], ['l' => 'SLA vencido', 'v' => $curationSummary['overdue'], 'c' => '#b91c1c'], ['l' => 'Vence em 24h', 'v' => $curationSummary['due_soon'], 'c' => '#b45309'], ['l' => 'Sem responsável', 'v' => $curationSummary['unassigned'], 'c' => '#6b7280'], ['l' => 'Alta prioridade', 'v' => $curationSummary['high_priority'], 'c' => '#7c3aed']] as $card)
                            <div
                                style="min-width:110px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.7rem .8rem">
                                <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                    {{ $card['v'] }}</div>
                                <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                    {{ $card['l'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div style="margin-top:.8rem;display:flex;gap:.6rem;flex-wrap:wrap">
                    <span
                        style="display:inline-flex;align-items:center;padding:.22rem .6rem;border-radius:999px;font-size:.71rem;font-weight:700;background:#f3f4f6;color:#374151">
                        Backlog aberto: {{ $curationSummary['backlog_open'] }}
                    </span>
                    @if ($curationSummary['overdue'] > 0)
                        <span
                            style="display:inline-flex;align-items:center;padding:.22rem .6rem;border-radius:999px;font-size:.71rem;font-weight:700;background:#fef2f2;color:#b91c1c">
                            {{ $curationSummary['overdue'] }} item(ns) com SLA vencido
                        </span>
                    @endif
                    @if ($curationSummary['due_soon'] > 0)
                        <span
                            style="display:inline-flex;align-items:center;padding:.22rem .6rem;border-radius:999px;font-size:.71rem;font-weight:700;background:#fffbeb;color:#b45309">
                            {{ $curationSummary['due_soon'] }} item(ns) vencem em 24h
                        </span>
                    @endif
                </div>
            </div>
            <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fff">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                    <div>
                        <div style="font-size:.82rem;font-weight:700;color:#111827">
                            Minha fila{{ $currentOperator ? ' · ' . $currentOperatorCurationSummary['name'] : '' }}
                        </div>
                        <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                            Atalhos operacionais para o operador atual assumir, revisar e publicar sem reconfigurar os
                            filtros manualmente.
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                        @foreach ([['l' => 'Meu backlog', 'v' => $currentOperatorCurationSummary['my_total'], 'c' => '#111827'], ['l' => 'Minhas revisões', 'v' => $currentOperatorCurationSummary['my_in_review'], 'c' => '#1d4ed8'], ['l' => 'Minhas aprovações', 'v' => $currentOperatorCurationSummary['my_approved'], 'c' => '#047857'], ['l' => 'Meu SLA vencido', 'v' => $currentOperatorCurationSummary['my_overdue'], 'c' => '#b91c1c'], ['l' => 'Decisões em 7 dias', 'v' => $currentOperatorCurationSummary['recent_decisions'], 'c' => '#7c3aed']] as $card)
                            <div
                                style="min-width:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                    {{ $card['v'] }}</div>
                                <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                    {{ $card['l'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.85rem">
                    @if ($currentOperator)
                        <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $currentOperator->id, 'curation_queue_status' => 'all', 'curation_sort' => 'priority_score_recent', 'curation_page' => 1])) }}#curation-queue"
                            style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;text-decoration:none;background:#fff">
                            Minha fila
                        </a>
                        <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $currentOperator->id, 'curation_queue_status' => 'in_review', 'curation_sort' => 'oldest_first', 'curation_page' => 1])) }}#curation-queue"
                            style="padding:.45rem .75rem;border:1px solid #dbeafe;border-radius:8px;font-size:.74rem;color:#1d4ed8;text-decoration:none;background:#eff6ff">
                            Minhas revisões abertas
                        </a>
                        <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $currentOperator->id, 'curation_queue_status' => 'approved', 'curation_sort' => 'oldest_first', 'curation_page' => 1])) }}#curation-queue"
                            style="padding:.45rem .75rem;border:1px solid #c7d2fe;border-radius:8px;font-size:.74rem;color:#3730a3;text-decoration:none;background:#eef2ff">
                            Minhas aprovações
                        </a>
                        <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $currentOperator->id, 'curation_sla_bucket' => 'overdue', 'curation_sort' => 'sla_then_score', 'curation_page' => 1])) }}#curation-queue"
                            style="padding:.45rem .75rem;border:1px solid #fecaca;border-radius:8px;font-size:.74rem;color:#b91c1c;text-decoration:none;background:#fef2f2">
                            Meu SLA vencido
                        </a>
                    @endif
                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => 'unassigned', 'curation_sla_bucket' => 'overdue', 'curation_sort' => 'sla_then_score', 'curation_page' => 1])) }}#curation-queue"
                        style="padding:.45rem .75rem;border:1px solid #fde68a;border-radius:8px;font-size:.74rem;color:#b45309;text-decoration:none;background:#fffbeb">
                        Sem responsável + SLA vencido
                    </a>
                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => 'unassigned', 'curation_sort' => 'match_score_desc', 'curation_min_score' => '0.70', 'curation_page' => 1])) }}#curation-queue"
                        style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;text-decoration:none;background:#fff">
                        Sem responsável com score alto
                    </a>
                </div>
            </div>
            <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                    <div>
                        <div style="font-size:.82rem;font-weight:700;color:#111827">Balanceamento da fila entre operadores
                        </div>
                        <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                            Distribui itens sem responsável com base na carga atual do time, mantendo o rebalanceamento
                            auditável e controlado.
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                        @foreach ([['l' => 'Operadores ativos', 'v' => $curationLoadBalancing['reviewers_count'], 'c' => '#111827'], ['l' => 'Fila aberta', 'v' => $curationLoadBalancing['open_total'], 'c' => '#1d4ed8'], ['l' => 'Sem responsável', 'v' => $curationLoadBalancing['unassigned_open'], 'c' => '#6b7280'], ['l' => 'Sem responsável + SLA vencido', 'v' => $curationLoadBalancing['unassigned_overdue'], 'c' => '#b91c1c'], ['l' => 'Carga alvo', 'v' => $curationLoadBalancing['target_load'], 'c' => '#047857']] as $card)
                            <div
                                style="min-width:120px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                    {{ $card['v'] }}</div>
                                <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                    {{ $card['l'] }}</div>
                            </div>
                        @endforeach
                        @if ($curationLoadBalancing['suggested_target_name'] !== '')
                            <div style="margin-top:.8rem;font-size:.74rem;color:#4b5563">
                                Destino sugerido agora:
                                <span
                                    style="font-weight:700;color:#111827">{{ $curationLoadBalancing['suggested_target_name'] }}</span>
                                para absorver itens sem responsável.
                            </div>
                        @endif
                        <div style="overflow-x:auto;margin-top:.85rem">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Operador</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Carga</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            SLA / prioridade</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Rebalanceamento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($curationLoadBalancing['reviewers'] as $reviewerLoad)
                                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                            <td
                                                style="padding:.85rem .9rem;font-size:.78rem;color:#111827;min-width:220px">
                                                <div style="font-weight:700">{{ $reviewerLoad['name'] }}</div>
                                                <div
                                                    style="margin-top:.28rem;font-size:.72rem;color:{{ $reviewerLoad['load_state_tone'] }}">
                                                    {{ $reviewerLoad['load_state_label'] }}
                                                </div>
                                                @if ($reviewerLoad['is_suggested_target'])
                                                    <div
                                                        style="margin-top:.28rem;font-size:.7rem;color:#047857;font-weight:700">
                                                        Destino sugerido para receber novos itens
                                                    </div>
                                                @endif
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;font-size:.76rem;color:#4b5563;min-width:210px">
                                                <div>Abertos: <strong>{{ $reviewerLoad['open_count'] }}</strong></div>
                                                <div style="margin-top:.22rem">Na fila
                                                    {{ $reviewerLoad['pending_count'] }} ·
                                                    Em
                                                    revisão
                                                    {{ $reviewerLoad['in_review_count'] }} · Aprovados
                                                    {{ $reviewerLoad['approved_count'] }}</div>
                                                <div style="margin-top:.22rem;color:#9ca3af">Decisões em 7 dias:
                                                    {{ $reviewerLoad['recent_decisions'] }}</div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;font-size:.76rem;color:#4b5563;min-width:180px">
                                                <div>SLA vencido: {{ $reviewerLoad['overdue_count'] }}</div>
                                                <div style="margin-top:.22rem">Vence em 24h:
                                                    {{ $reviewerLoad['due_soon_count'] }}</div>
                                                <div style="margin-top:.22rem">Alta prioridade:
                                                    {{ $reviewerLoad['high_priority_count'] }}</div>
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:320px">
                                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $reviewerLoad['id'], 'curation_queue_status' => 'all', 'curation_sort' => 'priority_score_recent', 'curation_page' => 1])) }}#curation-queue"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Abrir fila
                                                    </a>
                                                    @if ($curationLoadBalancing['unassigned_overdue'] > 0 && $reviewerLoad['suggested_intake'] > 0)
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.rebalance') }}">
                                                            @csrf
                                                            <input type="hidden" name="target_user_id"
                                                                value="{{ $reviewerLoad['id'] }}">
                                                            <input type="hidden" name="mode"
                                                                value="critical_unassigned">
                                                            <input type="hidden" name="limit"
                                                                value="{{ min(max($reviewerLoad['suggested_intake'], 1), 5) }}">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Enviar críticos
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if ($curationLoadBalancing['unassigned_open'] > 0 && $reviewerLoad['suggested_intake'] > 0)
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.rebalance') }}">
                                                            @csrf
                                                            <input type="hidden" name="target_user_id"
                                                                value="{{ $reviewerLoad['id'] }}">
                                                            <input type="hidden" name="mode"
                                                                value="high_score_unassigned">
                                                            <input type="hidden" name="limit"
                                                                value="{{ min(max($reviewerLoad['suggested_intake'], 1), 5) }}">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Enviar score alto
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if ($curationLoadBalancing['unassigned_open'] > 0)
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.rebalance') }}">
                                                            @csrf
                                                            <input type="hidden" name="target_user_id"
                                                                value="{{ $reviewerLoad['id'] }}">
                                                            <input type="hidden" name="mode"
                                                                value="oldest_unassigned">
                                                            <input type="hidden" name="limit" value="3">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;cursor:pointer">
                                                                Enviar mais antigos
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4"
                                                style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                                Nenhum operador ativo encontrado para balanceamento da fila.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fff">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#111827">Atribuição sugerida inteligente
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    Recomenda responsáveis para itens sem dono combinando carga do time, histórico por fonte
                                    e
                                    histórico por município.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                <div
                                    style="min-width:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                    <div style="font-size:1rem;font-weight:800;color:#111827">
                                        {{ $curationSuggestedAssignments['available_count'] }}</div>
                                    <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                        Sugestões abertas</div>
                                </div>
                                <div
                                    style="min-width:140px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                    <div style="font-size:.8rem;font-weight:800;color:#047857">
                                        {{ $curationSuggestedAssignments['suggested_target_name'] !== '' ? $curationSuggestedAssignments['suggested_target_name'] : 'Sem sugestão' }}
                                    </div>
                                    <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                        Melhor destino geral agora</div>
                                </div>
                            </div>
                        </div>
                        <form id="bulk-suggested-assignments-form" method="POST"
                            action="{{ route('admin.federal-programs.curation.suggestions.confirm') }}"
                            style="display:grid;grid-template-columns:1.6fr auto;gap:.65rem;align-items:end;margin-top:.85rem">
                            @csrf
                            <div>
                                <label
                                    style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">
                                    Nota da confirmação
                                </label>
                                <input type="text" name="decision_notes"
                                    placeholder="Contexto opcional para a confirmação em lote"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                            </div>
                            <button type="submit"
                                style="padding:.6rem .95rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer">
                                Confirmar selecionadas
                            </button>
                        </form>
                        <div style="font-size:.73rem;color:#6b7280;margin-top:.55rem;line-height:1.5">
                            Selecione as sugestões validadas e confirme em lote mantendo o responsável recomendado para
                            cada item.
                        </div>
                        <div style="overflow-x:auto;margin-top:.85rem">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                        <th
                                            style="padding:.75rem .7rem;text-align:center;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase;width:46px">
                                            <input type="checkbox" id="curation-suggestions-select-all"
                                                onclick="toggleSuggestedAssignmentsSelection(this)"
                                                style="width:15px;height:15px;cursor:pointer">
                                        </th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Item</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Contexto</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Sugestão</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($curationSuggestedAssignments['items'] as $entry)
                                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                            <td style="padding:.85rem .7rem;text-align:center">
                                                <input type="checkbox" name="entry_ids[]" value="{{ $entry['id'] }}"
                                                    form="bulk-suggested-assignments-form"
                                                    class="curation-suggestion-checkbox"
                                                    style="width:15px;height:15px;cursor:pointer">
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:280px">
                                                <div
                                                    style="font-size:.78rem;font-weight:700;color:#111827;line-height:1.45">
                                                    {{ $entry['title'] }}
                                                </div>
                                                <div style="margin-top:.28rem;font-size:.72rem;color:#6b7280">
                                                    {{ $entry['source_name'] }} · {{ $entry['municipality_name'] }}
                                                    @if ($entry['municipality_uf'] !== '')
                                                        / {{ $entry['municipality_uf'] }}
                                                    @endif
                                                </div>
                                                <div style="margin-top:.28rem;font-size:.72rem;color:#9ca3af">
                                                    Score {{ number_format((float) $entry['match_score'], 2, ',', '.') }}
                                                    ·
                                                    {{ $entry['priority_label'] }} · {{ $entry['sla_label'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:240px;font-size:.76rem;color:#4b5563">
                                                <div>
                                                    {{ $entry['match_reason'] !== '' ? $entry['match_reason'] : 'Sem razão detalhada do match.' }}
                                                </div>
                                                <div style="margin-top:.28rem;color:#9ca3af">
                                                    Entrou {{ $entry['entered_queue_at_human'] ?? 'agora' }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:240px;font-size:.76rem;color:#4b5563">
                                                <div style="font-weight:700;color:#111827">
                                                    {{ $entry['suggested_reviewer_name'] !== '' ? $entry['suggested_reviewer_name'] : 'Sem sugestão' }}
                                                </div>
                                                <div style="margin-top:.24rem">
                                                    {{ $entry['suggestion_reason'] !== '' ? $entry['suggestion_reason'] : 'Sem contexto suficiente para sugerir.' }}
                                                </div>
                                                @if (!empty($entry['suggestion_candidates']))
                                                    <div style="margin-top:.32rem;font-size:.72rem;color:#9ca3af">
                                                        Alternativas:
                                                        {{ collect($entry['suggestion_candidates'])->pluck('reviewer_name')->implode(' · ') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:220px">
                                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                                    @if (($entry['suggested_reviewer_id'] ?? 0) > 0)
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.assign', $entry['id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="assigned_to_user_id"
                                                                value="{{ $entry['suggested_reviewer_id'] }}">
                                                            <input type="hidden" name="priority"
                                                                value="{{ $entry['priority'] }}">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Aplicar sugestão
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_search' => $entry['title'], 'curation_page' => 1])) }}#curation-queue"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Abrir item
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"
                                                style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                                Nenhum item sem responsável elegível para sugestão inteligente neste
                                                momento.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fff">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#111827">
                                    Metas operacionais por operador
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    Metas sugeridas para cadência, backlog e SLA usando a carga atual e o ritmo recente
                                    de decisão do time.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                @foreach ([['l' => 'Meta base 7d', 'v' => $curationOperatorGoals['decision_goal_base'] . ' decisões', 'c' => '#111827'], ['l' => 'Backlog alvo', 'v' => $curationOperatorGoals['backlog_goal'] . ' itens', 'c' => '#1d4ed8'], ['l' => 'No alvo', 'v' => $curationOperatorGoals['on_track'], 'c' => '#047857'], ['l' => 'Pede ajuste', 'v' => $curationOperatorGoals['attention'], 'c' => '#b45309'], ['l' => 'Risco crítico', 'v' => $curationOperatorGoals['critical'], 'c' => '#b91c1c'], ['l' => 'Gap de decisões', 'v' => $curationOperatorGoals['throughput_gap_total'], 'c' => '#7c3aed']] as $card)
                                    <div
                                        style="min-width:122px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                        <div style="font-size:.92rem;font-weight:800;color:{{ $card['c'] }}">
                                            {{ $card['v'] }}</div>
                                        <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                            {{ $card['l'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div style="overflow-x:auto;margin-top:.85rem">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Operador</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Meta de cadência</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Meta de fila e SLA</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Status</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Atalhos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($curationOperatorGoals['reviewers'] as $reviewerGoal)
                                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                            <td style="padding:.85rem .9rem;min-width:190px">
                                                <div style="font-size:.78rem;font-weight:700;color:#111827">
                                                    {{ $reviewerGoal['name'] }}
                                                </div>
                                                <div style="margin-top:.24rem;font-size:.72rem;color:#6b7280">
                                                    {{ $reviewerGoal['focus_label'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:220px;font-size:.76rem;color:#4b5563">
                                                <div>Meta: <strong>{{ $reviewerGoal['throughput_goal'] }}</strong>
                                                    decisões / 7d</div>
                                                <div style="margin-top:.22rem">Realizado recente:
                                                    <strong>{{ $reviewerGoal['recent_decisions'] }}</strong>
                                                </div>
                                                <div style="margin-top:.22rem;color:#9ca3af">
                                                    Atingimento {{ $reviewerGoal['throughput_progress'] }}% · Gap
                                                    {{ $reviewerGoal['throughput_gap'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:220px;font-size:.76rem;color:#4b5563">
                                                <div>Backlog alvo: <strong>{{ $reviewerGoal['backlog_goal'] }}</strong>
                                                    itens</div>
                                                <div style="margin-top:.22rem">Abertos {{ $reviewerGoal['open_count'] }}
                                                    · SLA vencido
                                                    {{ $reviewerGoal['overdue_count'] }}</div>
                                                <div style="margin-top:.22rem;color:#9ca3af">
                                                    Vence em 24h {{ $reviewerGoal['due_soon_count'] }} · Cobertura da fila
                                                    {{ $reviewerGoal['backlog_progress'] }}%
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:200px;font-size:.76rem;color:#4b5563">
                                                <div
                                                    style="font-weight:700;color:{{ $reviewerGoal['goal_state_tone'] }}">
                                                    {{ $reviewerGoal['goal_state_label'] }}
                                                </div>
                                                <div style="margin-top:.22rem">
                                                    {{ $reviewerGoal['load_state_label'] }}
                                                </div>
                                                <div style="margin-top:.22rem;color:#9ca3af">
                                                    Melhor encaixe: {{ $reviewerGoal['best_fit_label'] }}
                                                </div>
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:220px">
                                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $reviewerGoal['id'], 'curation_queue_status' => 'all', 'curation_sort' => 'priority_score_recent', 'curation_page' => 1])) }}#curation-queue"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Abrir fila
                                                    </a>
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_audit_causer_id' => $reviewerGoal['id']])) }}"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Ver auditoria
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"
                                                style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                                Ainda não há operadores suficientes para montar metas operacionais
                                                sugeridas.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#111827">
                                    Visão executiva do time
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    Leitura resumida da saúde operacional da curadoria humana para coordenação do time.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                <div
                                    style="min-width:150px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                    <div
                                        style="font-size:.84rem;font-weight:800;color:{{ $curationExecutiveTeam['state_tone'] }}">
                                        {{ $curationExecutiveTeam['state_label'] }}
                                    </div>
                                    <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                        Estado operacional consolidado
                                    </div>
                                </div>
                                @foreach ([['l' => 'Time ativo', 'v' => $curationExecutiveTeam['team_size'], 'c' => '#111827'], ['l' => 'Backlog aberto', 'v' => $curationExecutiveTeam['open_backlog'], 'c' => '#1d4ed8'], ['l' => 'Cobertura', 'v' => $curationExecutiveTeam['assignment_coverage'] . '%', 'c' => '#047857'], ['l' => 'Taxa de publicação', 'v' => $curationExecutiveTeam['team_publish_rate'] . '%', 'c' => '#7c3aed'], ['l' => 'SLA vencido aberto', 'v' => $curationExecutiveTeam['overdue_open'], 'c' => '#b91c1c']] as $card)
                                    <div
                                        style="min-width:120px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                        <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                            {{ $card['v'] }}</div>
                                        <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                            {{ $card['l'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div
                            style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;margin-top:.85rem">
                            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                                <div style="font-size:.72rem;color:#9ca3af;text-transform:uppercase;font-weight:700">
                                    Capacidade</div>
                                <div style="font-size:.9rem;font-weight:800;color:#111827;margin-top:.25rem">
                                    {{ number_format((float) $curationExecutiveTeam['avg_open_per_reviewer'], 1, ',', '.') }}
                                    item(ns) por operador
                                </div>
                                <div style="font-size:.73rem;color:#6b7280;margin-top:.28rem">
                                    {{ $curationExecutiveTeam['available_receivers'] }} podem receber ·
                                    {{ $curationExecutiveTeam['overflow_reviewers'] }} em overflow
                                </div>
                            </div>
                            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                                <div style="font-size:.72rem;color:#9ca3af;text-transform:uppercase;font-weight:700">
                                    Velocidade</div>
                                <div style="font-size:.9rem;font-weight:800;color:#111827;margin-top:.25rem">
                                    {{ number_format((float) $curationExecutiveTeam['avg_decision_hours'], 1, ',', '.') }}h
                                </div>
                                <div style="font-size:.73rem;color:#6b7280;margin-top:.28rem">
                                    Média até decisão · Publicação em
                                    {{ number_format((float) $curationExecutiveTeam['avg_publish_hours'], 1, ',', '.') }}h
                                </div>
                            </div>
                            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                                <div style="font-size:.72rem;color:#9ca3af;text-transform:uppercase;font-weight:700">Top
                                    publicação</div>
                                <div style="font-size:.9rem;font-weight:800;color:#111827;margin-top:.25rem">
                                    {{ $curationExecutiveTeam['top_publisher_name'] !== '' ? $curationExecutiveTeam['top_publisher_name'] : 'Sem destaque' }}
                                </div>
                                <div style="font-size:.73rem;color:#6b7280;margin-top:.28rem">
                                    {{ $curationExecutiveTeam['top_publisher_count'] }} publicada(s) no período
                                </div>
                            </div>
                            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                                <div style="font-size:.72rem;color:#9ca3af;text-transform:uppercase;font-weight:700">Melhor
                                    tempo</div>
                                <div style="font-size:.9rem;font-weight:800;color:#111827;margin-top:.25rem">
                                    {{ $curationExecutiveTeam['fastest_reviewer_name'] !== '' ? $curationExecutiveTeam['fastest_reviewer_name'] : 'Sem destaque' }}
                                </div>
                                <div style="font-size:.73rem;color:#6b7280;margin-top:.28rem">
                                    {{ number_format((float) $curationExecutiveTeam['fastest_reviewer_hours'], 1, ',', '.') }}h
                                    até decisão
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fff">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#111827">
                                    Políticas de distribuição
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    Regras operacionais derivadas da fila atual para orientar distribuição, overflow e
                                    cobertura do time.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                @foreach ([['l' => 'Políticas', 'v' => $curationDistributionPolicies['policy_count'], 'c' => '#111827'], ['l' => 'Ação imediata', 'v' => $curationDistributionPolicies['action_required'], 'c' => '#b91c1c'], ['l' => 'Atenção', 'v' => $curationDistributionPolicies['warnings'], 'c' => '#b45309'], ['l' => 'Saudáveis', 'v' => $curationDistributionPolicies['healthy'], 'c' => '#047857'], ['l' => 'Backlog aberto', 'v' => $curationDistributionPolicies['open_backlog'], 'c' => '#1d4ed8']] as $card)
                                    <div
                                        style="min-width:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                        <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                            {{ $card['v'] }}</div>
                                        <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                            {{ $card['l'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div style="overflow-x:auto;margin-top:.85rem">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Política</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Estado</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Diretriz</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($curationDistributionPolicies['items'] as $policy)
                                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                            <td style="padding:.85rem .9rem;min-width:220px">
                                                <div style="font-size:.78rem;font-weight:700;color:#111827">
                                                    {{ $policy['title'] }}
                                                </div>
                                                <div style="margin-top:.24rem;font-size:.72rem;color:#9ca3af">
                                                    Métrica atual: {{ $policy['metric'] }}
                                                </div>
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:160px">
                                                <div
                                                    style="font-size:.76rem;font-weight:700;color:{{ $policy['tone'] }}">
                                                    {{ $policy['status_label'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:320px;font-size:.76rem;color:#4b5563">
                                                {{ $policy['description'] }}
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:180px">
                                                <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), $policy['filters'])) }}#curation-queue"
                                                    style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none;display:inline-block">
                                                    Abrir recorte
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#111827">
                                    Visão comparativa por operador e afinidade operacional
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    Compara carga atual, produtividade recente e afinidade histórica por fonte e município
                                    para orientar a distribuição do time.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                <div
                                    style="min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                    <div style="font-size:.72rem;font-weight:800;color:#111827;line-height:1.4">
                                        {{ implode(' · ', $curationOperatorComparison['top_source_names']) ?: 'Sem fontes destacadas' }}
                                    </div>
                                    <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                        Fontes com maior afinidade recente
                                    </div>
                                </div>
                                <div
                                    style="min-width:140px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                    <div style="font-size:.72rem;font-weight:800;color:#111827;line-height:1.4">
                                        {{ implode(' · ', $curationOperatorComparison['top_municipality_names']) ?: 'Sem municípios destacados' }}
                                    </div>
                                    <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                        Municípios com maior histórico recente
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="overflow-x:auto;margin-top:.85rem">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Operador</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Carga atual</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Histórico 90d</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Afinidade operacional</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Atalhos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($curationOperatorComparison['reviewers'] as $reviewerComparison)
                                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                            <td style="padding:.85rem .9rem;min-width:180px">
                                                <div style="font-size:.78rem;font-weight:700;color:#111827">
                                                    {{ $reviewerComparison['name'] }}
                                                </div>
                                                <div
                                                    style="margin-top:.28rem;font-size:.72rem;color:{{ $reviewerComparison['load_state_tone'] }}">
                                                    {{ $reviewerComparison['load_state_label'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:220px;font-size:.76rem;color:#4b5563">
                                                <div>Abertos: <strong>{{ $reviewerComparison['open_count'] }}</strong>
                                                </div>
                                                <div style="margin-top:.22rem">Na fila
                                                    {{ $reviewerComparison['pending_count'] }} ·
                                                    Em revisão {{ $reviewerComparison['in_review_count'] }} · Aprovados
                                                    {{ $reviewerComparison['approved_count'] }}</div>
                                                <div style="margin-top:.22rem;color:#9ca3af">
                                                    SLA vencido {{ $reviewerComparison['overdue_count'] }} · Vence em 24h
                                                    {{ $reviewerComparison['due_soon_count'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:210px;font-size:.76rem;color:#4b5563">
                                                <div>Revisados:
                                                    <strong>{{ $reviewerComparison['reviewed_count'] }}</strong>
                                                </div>
                                                <div style="margin-top:.22rem">Publicados
                                                    {{ $reviewerComparison['published_count'] }} ·
                                                    Rejeitados {{ $reviewerComparison['rejected_count'] }}</div>
                                                <div style="margin-top:.22rem;color:#9ca3af">
                                                    Taxa de publicação {{ $reviewerComparison['publish_rate'] }}% ·
                                                    Decisão média
                                                    {{ number_format((float) $reviewerComparison['avg_decision_hours'], 1, ',', '.') }}h
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:260px;font-size:.76rem;color:#4b5563">
                                                <div style="font-weight:700;color:#111827">
                                                    {{ $reviewerComparison['best_fit_label'] }}
                                                </div>
                                                <div style="margin-top:.25rem;color:#6b7280">
                                                    Score médio analisado
                                                    {{ number_format((float) $reviewerComparison['avg_match_score'], 2, ',', '.') }}
                                                </div>
                                                @if (!empty($reviewerComparison['top_sources']))
                                                    <div style="margin-top:.28rem;color:#9ca3af">
                                                        Fontes: {{ implode(' · ', $reviewerComparison['top_sources']) }}
                                                    </div>
                                                @endif
                                                @if (!empty($reviewerComparison['top_municipalities']))
                                                    <div style="margin-top:.22rem;color:#9ca3af">
                                                        Municípios:
                                                        {{ implode(' · ', $reviewerComparison['top_municipalities']) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:220px">
                                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $reviewerComparison['id'], 'curation_queue_status' => 'all', 'curation_sort' => 'priority_score_recent', 'curation_page' => 1])) }}#curation-queue"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Abrir fila
                                                    </a>
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_audit_causer_id' => $reviewerComparison['id']])) }}"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Ver auditoria
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"
                                                style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                                Ainda não há histórico suficiente para montar a visão comparativa por
                                                operador.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#111827">
                                    Limites de carga e overflow operacional
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    Define faixas recomendadas por operador e oferece alívio controlado para cargas acima
                                    do limite, sem mover revisão ativa.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                @foreach ([['l' => 'Soft limit', 'v' => $curationCapacityLimits['soft_limit'], 'c' => '#1d4ed8'], ['l' => 'Hard limit', 'v' => $curationCapacityLimits['hard_limit'], 'c' => '#b91c1c'], ['l' => 'Operadores em overflow', 'v' => $curationCapacityLimits['overflow_reviewers'], 'c' => '#b91c1c'], ['l' => 'Acima do recomendado', 'v' => $curationCapacityLimits['warning_reviewers'], 'c' => '#b45309'], ['l' => 'Podem receber', 'v' => $curationCapacityLimits['available_receivers'], 'c' => '#047857']] as $card)
                                    <div
                                        style="min-width:120px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem">
                                        <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                            {{ $card['v'] }}</div>
                                        <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                            {{ $card['l'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div style="overflow-x:auto;margin-top:.85rem">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Operador</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Limites</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Estado</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Overflow</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($curationCapacityLimits['reviewers'] as $reviewerLimit)
                                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                            <td style="padding:.85rem .9rem;min-width:180px">
                                                <div style="font-size:.78rem;font-weight:700;color:#111827">
                                                    {{ $reviewerLimit['name'] }}
                                                </div>
                                                <div style="margin-top:.25rem;font-size:.72rem;color:#6b7280">
                                                    Abertos {{ $reviewerLimit['open_count'] }} · Pode receber
                                                    {{ $reviewerLimit['recommended_receive'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:200px;font-size:.76rem;color:#4b5563">
                                                <div>Soft limit: <strong>{{ $reviewerLimit['soft_limit'] }}</strong></div>
                                                <div style="margin-top:.22rem">Hard limit:
                                                    <strong>{{ $reviewerLimit['hard_limit'] }}</strong>
                                                </div>
                                                <div style="margin-top:.22rem;color:#9ca3af">
                                                    Excesso leve {{ $reviewerLimit['soft_excess'] }} · Overflow
                                                    {{ $reviewerLimit['overflow_count'] }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;min-width:220px;font-size:.76rem;color:#4b5563">
                                                <div
                                                    style="font-weight:700;color:{{ $reviewerLimit['limit_state_tone'] }}">
                                                    {{ $reviewerLimit['limit_state_label'] }}
                                                </div>
                                                <div style="margin-top:.22rem">
                                                    Carga atual: {{ $reviewerLimit['open_count'] }} item(ns)
                                                </div>
                                                @if ($reviewerLimit['suggested_overflow_target_name'] !== '')
                                                    <div style="margin-top:.22rem;color:#9ca3af">
                                                        Destino sugerido:
                                                        {{ $reviewerLimit['suggested_overflow_target_name'] }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:260px">
                                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_assigned_to_user_id' => $reviewerLimit['id'], 'curation_queue_status' => 'all', 'curation_sort' => 'oldest_first', 'curation_page' => 1])) }}#curation-queue"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Abrir fila
                                                    </a>
                                                    @if (($reviewerLimit['overflow_count'] ?? 0) > 0 && ($reviewerLimit['suggested_overflow_target_id'] ?? 0) > 0)
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.overflow') }}">
                                                            @csrf
                                                            <input type="hidden" name="source_user_id"
                                                                value="{{ $reviewerLimit['id'] }}">
                                                            <input type="hidden" name="target_user_id"
                                                                value="{{ $reviewerLimit['suggested_overflow_target_id'] }}">
                                                            <input type="hidden" name="limit"
                                                                value="{{ min(max($reviewerLimit['overflow_count'], 1), 5) }}">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Aliviar overflow
                                                            </button>
                                                        </form>
                                                    @elseif (($reviewerLimit['soft_excess'] ?? 0) > 0 && ($reviewerLimit['suggested_overflow_target_id'] ?? 0) > 0)
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.overflow') }}">
                                                            @csrf
                                                            <input type="hidden" name="source_user_id"
                                                                value="{{ $reviewerLimit['id'] }}">
                                                            <input type="hidden" name="target_user_id"
                                                                value="{{ $reviewerLimit['suggested_overflow_target_id'] }}">
                                                            <input type="hidden" name="limit"
                                                                value="{{ min(max($reviewerLimit['soft_excess'], 1), 3) }}">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#fffbeb;color:#b45309;border:1px solid #fde68a;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Reduzir carga
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4"
                                                style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                                Nenhum operador disponível para avaliar limites de carga neste momento.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                        <form method="GET" action="{{ route('admin.federal-programs.index') }}"
                            style="display:grid;grid-template-columns:1.2fr repeat(9,minmax(0,1fr));gap:.7rem;align-items:end">
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Buscar
                                </label>
                                <input type="text" name="curation_search" value="{{ $curationFilters['search'] }}"
                                    placeholder="Título, fonte ou município"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Fila
                                </label>
                                <select name="curation_queue_status"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['all' => 'Todas', 'pending' => 'Na fila', 'in_review' => 'Em revisão', 'approved' => 'Aprovadas', 'published' => 'Publicadas', 'rejected' => 'Rejeitadas'] as $value => $label)
                                        <option value="{{ $value }}" @selected($curationFilters['queue_status'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Curadoria
                                </label>
                                <select name="curation_status"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['all' => 'Todas', 'pending_review' => 'Pendente', 'auto_published' => 'Auto-publicada', 'curated' => 'Curada', 'rejected' => 'Rejeitada'] as $value => $label)
                                        <option value="{{ $value }}" @selected($curationFilters['curation_status'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Fonte
                                </label>
                                <select name="curation_source_id"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Todas</option>
                                    @foreach ($sourceCatalog as $source)
                                        <option value="{{ $source['id'] }}" @selected($curationFilters['source_id'] !== '' && (int) $curationFilters['source_id'] === (int) $source['id'])>
                                            {{ $source['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Município
                                </label>
                                <select name="curation_municipality_id"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Todos</option>
                                    @foreach ($municipalities as $municipality)
                                        <option value="{{ $municipality->id }}" @selected($curationFilters['municipality_id'] !== '' && (int) $curationFilters['municipality_id'] === (int) $municipality->id)>
                                            {{ $municipality->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Responsável
                                </label>
                                <select name="curation_assigned_to_user_id"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Todos</option>
                                    <option value="unassigned" @selected($curationFilters['assigned_to_user_id'] === 'unassigned')>Sem responsável</option>
                                    @foreach ($reviewers as $reviewer)
                                        <option value="{{ $reviewer->id }}" @selected($curationFilters['assigned_to_user_id'] !== '' && $curationFilters['assigned_to_user_id'] !== 'unassigned' && (int) $curationFilters['assigned_to_user_id'] === (int) $reviewer->id)>
                                            {{ $reviewer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Prioridade
                                </label>
                                <select name="curation_priority"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['all' => 'Todas', 'urgent' => 'Urgente', 'high' => 'Alta', 'normal' => 'Normal', 'low' => 'Baixa'] as $value => $label)
                                        <option value="{{ $value }}" @selected($curationFilters['priority'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    SLA
                                </label>
                                <select name="curation_sla_bucket"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['all' => 'Todos', 'overdue' => 'SLA vencido', 'due_soon' => 'Vence em 24h', 'on_track' => 'No prazo', 'no_sla' => 'Sem SLA'] as $value => $label)
                                        <option value="{{ $value }}" @selected($curationFilters['sla_bucket'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Score mínimo
                                </label>
                                <input type="number" min="0" max="1" step="0.01"
                                    name="curation_min_score" value="{{ $curationFilters['min_score'] }}"
                                    placeholder="0,55"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Ordenação
                                </label>
                                <select name="curation_sort"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['priority_score_recent' => 'Prioridade + score + recentes', 'match_score_desc' => 'Maior score primeiro', 'recent_first' => 'Mais recentes primeiro', 'oldest_first' => 'Mais antigas primeiro', 'source_then_score' => 'Fonte + score', 'sla_then_score' => 'SLA + score'] as $value => $label)
                                        <option value="{{ $value }}" @selected($curationFilters['sort'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="grid-column:1/-1;display:flex;gap:.55rem;justify-content:flex-end">
                                <a href="{{ route('admin.federal-programs.index') }}"
                                    style="padding:.5rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem;color:#4b5563;text-decoration:none;background:#fff">
                                    Limpar
                                </a>
                                <button type="submit"
                                    style="padding:.5rem .95rem;background:#0f1117;color:#fff;border:none;border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer">
                                    Filtrar revisão
                                </button>
                            </div>
                        </form>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fff">
                        <form id="bulk-curation-form" method="POST"
                            action="{{ route('admin.federal-programs.curation.bulk-update') }}"
                            style="display:grid;grid-template-columns:1.1fr 1fr 1fr 1.4fr auto;gap:.65rem;align-items:end">
                            @csrf
                            <div>
                                <label
                                    style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">
                                    Ação em lote
                                </label>
                                <select name="bulk_action"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['assign' => 'Atribuir responsável', 'reprioritize' => 'Repriorizar', 'start_review' => 'Iniciar revisão', 'approve' => 'Aprovar', 'publish' => 'Publicar', 'reject' => 'Rejeitar'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">
                                    Responsável
                                </label>
                                <select name="assigned_to_user_id"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Manter atual</option>
                                    @foreach ($reviewers as $reviewer)
                                        <option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">
                                    Prioridade
                                </label>
                                <select name="priority"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Manter atual</option>
                                    @foreach (['low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">
                                    Nota da ação
                                </label>
                                <input type="text" name="decision_notes"
                                    placeholder="Justificativa opcional para o lote"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                            </div>
                            <button type="submit"
                                style="padding:.6rem .95rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer">
                                Aplicar em lote
                            </button>
                        </form>
                        <div style="font-size:.73rem;color:#6b7280;margin-top:.55rem;line-height:1.5">
                            Selecione vários itens da fila e use a ordenação para puxar primeiro oportunidades com maior
                            score
                            ou
                            entrada mais recente.
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead>
                                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                    <th
                                        style="padding:.8rem .75rem;text-align:center;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase;width:46px">
                                        <input type="checkbox" id="curation-select-all"
                                            onclick="toggleCurationSelection(this)"
                                            style="width:15px;height:15px;cursor:pointer">
                                    </th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Oportunidade</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Fila</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Fonte / município</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Responsável</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($curationQueue as $entry)
                                    @php
                                        $queueTone = match ($entry['queue_status_tone']) {
                                            'success' => ['bg' => '#ecfdf5', 'color' => '#047857'],
                                            'danger' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                                            'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                            'info' => ['bg' => '#eff6ff', 'color' => '#1d4ed8'],
                                            default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                        };
                                        $curationTone = match ($entry['curation_status_tone']) {
                                            'success' => ['bg' => '#ecfdf5', 'color' => '#047857'],
                                            'danger' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                                            'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                            'info' => ['bg' => '#eff6ff', 'color' => '#1d4ed8'],
                                            default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                        };
                                        $priorityTone = match ($entry['priority_tone']) {
                                            'danger' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                                            'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                            'info' => ['bg' => '#eff6ff', 'color' => '#1d4ed8'],
                                            default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                        };
                                        $slaTone = match ($entry['sla_tone']) {
                                            'success' => [
                                                'bg' => '#ecfdf5',
                                                'color' => '#047857',
                                                'border' => '#a7f3d0',
                                            ],
                                            'danger' => [
                                                'bg' => '#fef2f2',
                                                'color' => '#b91c1c',
                                                'border' => '#fecaca',
                                            ],
                                            'warning' => [
                                                'bg' => '#fffbeb',
                                                'color' => '#b45309',
                                                'border' => '#fde68a',
                                            ],
                                            'info' => ['bg' => '#eff6ff', 'color' => '#1d4ed8', 'border' => '#bfdbfe'],
                                            default => [
                                                'bg' => '#f3f4f6',
                                                'color' => '#6b7280',
                                                'border' => '#e5e7eb',
                                            ],
                                        };
                                    @endphp
                                    <tr
                                        style="border-bottom:1px solid #f3f4f6;vertical-align:top;background:{{ $entry['sla_state'] === 'overdue' ? '#fff7f7' : ($entry['sla_state'] === 'due_soon' ? '#fffdf5' : '#fff') }}">
                                        <td style="padding:1rem .75rem;text-align:center">
                                            <input type="checkbox" name="entry_ids[]" value="{{ $entry['id'] }}"
                                                form="bulk-curation-form" class="curation-entry-checkbox"
                                                style="width:15px;height:15px;cursor:pointer">
                                        </td>
                                        <td style="padding:1rem">
                                            <div style="font-size:.84rem;font-weight:700;color:#111827;line-height:1.4">
                                                {{ $entry['title'] }}
                                            </div>
                                            <div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.45rem">
                                                <span
                                                    style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:{{ $queueTone['bg'] }};color:{{ $queueTone['color'] }}">
                                                    {{ $entry['queue_status_label'] }}
                                                </span>
                                                <span
                                                    style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:{{ $curationTone['bg'] }};color:{{ $curationTone['color'] }}">
                                                    {{ $entry['curation_status_label'] }}
                                                </span>
                                                <span
                                                    style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:{{ $priorityTone['bg'] }};color:{{ $priorityTone['color'] }}">
                                                    {{ $entry['priority_label'] }}
                                                </span>
                                                <span
                                                    style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.68rem;font-weight:700;background:{{ $slaTone['bg'] }};color:{{ $slaTone['color'] }};border:1px solid {{ $slaTone['border'] }}">
                                                    {{ $entry['sla_label'] }}
                                                </span>
                                            </div>
                                            @if ($entry['summary'])
                                                <div
                                                    style="font-size:.75rem;color:#6b7280;line-height:1.5;margin-top:.55rem">
                                                    {{ $entry['summary'] }}
                                                </div>
                                            @endif
                                            @if ($entry['match_reason'])
                                                <div
                                                    style="font-size:.74rem;color:#4b5563;line-height:1.5;margin-top:.55rem">
                                                    {{ $entry['match_reason'] }}
                                                </div>
                                            @endif
                                            @if ($entry['source_url'])
                                                <a href="{{ $entry['source_url'] }}" target="_blank"
                                                    style="display:inline-block;margin-top:.55rem;font-size:.72rem;color:#1d4ed8;text-decoration:none">
                                                    Abrir publicação
                                                </a>
                                            @endif
                                        </td>
                                        <td style="padding:1rem;min-width:155px">
                                            <div style="font-size:.78rem;font-weight:700;color:#111827">
                                                {{ $entry['status_label'] }}</div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                score {{ number_format($entry['match_score'], 2, ',', '.') }}
                                            </div>
                                            @if ($entry['sla_due_at_human'])
                                                <div
                                                    style="font-size:.72rem;color:{{ $slaTone['color'] }};margin-top:.2rem;font-weight:700">
                                                    {{ $entry['sla_label'] }} · {{ $entry['sla_due_at_human'] }}
                                                </div>
                                            @endif
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.45rem">
                                                Entrou {{ $entry['entered_queue_at_human'] ?? 'agora' }}
                                            </div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                Atualizado {{ $entry['updated_at_human'] ?? 'agora' }}
                                            </div>
                                            @if ($entry['review_started_at_human'])
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                    Revisão {{ $entry['review_started_at_human'] }}
                                                </div>
                                            @endif
                                            @if ($entry['reviewed_at_human'])
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                    Decisão {{ $entry['reviewed_at_human'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:1rem;min-width:190px">
                                            <div style="font-size:.79rem;font-weight:700;color:#111827">
                                                {{ $entry['source_name'] }}</div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                {{ $entry['source_key'] }} · {{ $entry['pipeline_group_label'] }}
                                            </div>
                                            <div style="font-size:.76rem;color:#4b5563;margin-top:.55rem">
                                                {{ $entry['municipality_name'] }}
                                                @if ($entry['municipality_uf'])
                                                    · {{ $entry['municipality_uf'] }}
                                                @endif
                                            </div>
                                        </td>
                                        <td style="padding:1rem;min-width:180px">
                                            <div style="font-size:.78rem;color:#111827">
                                                {{ $entry['assigned_to_name'] ?: 'Sem responsável' }}
                                            </div>
                                            @if ($entry['reviewed_by_name'])
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem">
                                                    Revisado por {{ $entry['reviewed_by_name'] }}
                                                </div>
                                            @endif
                                            @if ($entry['published_at_human'])
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem">
                                                    Publicado {{ $entry['published_at_human'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:1rem;min-width:360px">
                                            <details>
                                                <summary
                                                    style="cursor:pointer;font-size:.75rem;font-weight:700;color:#3730a3">
                                                    Abrir revisão
                                                </summary>
                                                <div style="margin-top:.65rem;display:grid;gap:.65rem">
                                                    <form method="POST"
                                                        action="{{ route('admin.federal-programs.curation.assign', $entry['id']) }}"
                                                        style="display:grid;grid-template-columns:1fr 1fr auto;gap:.5rem;align-items:end">
                                                        @csrf
                                                        <input type="hidden" name="curation_page"
                                                            value="{{ $curationQueue->currentPage() }}">
                                                        <div>
                                                            <label
                                                                style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">Responsável</label>
                                                            <select name="assigned_to_user_id"
                                                                style="width:100%;padding:.5rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.76rem">
                                                                <option value="">Sem responsável</option>
                                                                @foreach ($reviewers as $reviewer)
                                                                    <option value="{{ $reviewer->id }}"
                                                                        @selected((int) ($entry['assigned_to_user_id'] ?? 0) === (int) $reviewer->id)>
                                                                        {{ $reviewer->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label
                                                                style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">Prioridade</label>
                                                            <select name="priority"
                                                                style="width:100%;padding:.5rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.76rem">
                                                                @foreach (['low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $value => $label)
                                                                    <option value="{{ $value }}"
                                                                        @selected($entry['priority'] === $value)>{{ $label }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <button type="submit"
                                                            style="padding:.5rem .75rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;font-weight:700;color:#374151;cursor:pointer">
                                                            Salvar
                                                        </button>
                                                    </form>
                                                    <form method="POST"
                                                        action="{{ route('admin.federal-programs.curation.transition', $entry['id']) }}">
                                                        @csrf
                                                        <input type="hidden" name="curation_page"
                                                            value="{{ $curationQueue->currentPage() }}">
                                                        <label
                                                            style="display:block;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.25rem">Notas
                                                            da decisão</label>
                                                        <textarea name="decision_notes" rows="3"
                                                            style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.76rem;resize:vertical"
                                                            placeholder="Registrar contexto, ajuste fino ou justificativa da decisão">{{ $entry['decision_notes'] }}</textarea>
                                                        <div
                                                            style="display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.55rem">
                                                            <button type="submit" name="action" value="start_review"
                                                                style="padding:.5rem .75rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.74rem;font-weight:700;cursor:pointer">
                                                                Iniciar revisão
                                                            </button>
                                                            <button type="submit" name="action" value="approve"
                                                                style="padding:.5rem .75rem;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;border-radius:8px;font-size:.74rem;font-weight:700;cursor:pointer">
                                                                Aprovar
                                                            </button>
                                                            <button type="submit" name="action" value="publish"
                                                                style="padding:.5rem .75rem;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:8px;font-size:.74rem;font-weight:700;cursor:pointer">
                                                                Publicar
                                                            </button>
                                                            <button type="submit" name="action" value="reject"
                                                                style="padding:.5rem .75rem;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;font-size:.74rem;font-weight:700;cursor:pointer">
                                                                Rejeitar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                            Nenhum item encontrado na fila de curadoria com os filtros atuais.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (method_exists($curationQueue, 'links'))
                        <div
                            style="padding:1rem 1.1rem;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap">
                            <div style="font-size:.76rem;color:#6b7280">
                                Mostrando
                                <strong>{{ $curationQueue->firstItem() ?? 0 }}-{{ $curationQueue->lastItem() ?? 0 }}</strong>
                                de <strong>{{ $curationQueue->total() }}</strong> item(ns) em revisão
                            </div>
                            <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
                                @if ($curationQueue->onFirstPage())
                                    <span
                                        style="padding:.45rem .75rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.74rem;color:#9ca3af;background:#f9fafb">
                                        Anterior
                                    </span>
                                @else
                                    <a href="{{ $curationQueue->previousPageUrl() }}"
                                        style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;background:#fff;text-decoration:none">
                                        Anterior
                                    </a>
                                @endif

                                <span
                                    style="padding:.45rem .75rem;border:1px solid #c7d2fe;border-radius:8px;font-size:.74rem;font-weight:700;color:#3730a3;background:#eef2ff">
                                    Página {{ $curationQueue->currentPage() }} de {{ $curationQueue->lastPage() }}
                                </span>

                                @if ($curationQueue->hasMorePages())
                                    <a href="{{ $curationQueue->nextPageUrl() }}"
                                        style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;background:#fff;text-decoration:none">
                                        Próxima
                                    </a>
                                @else
                                    <span
                                        style="padding:.45rem .75rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.74rem;color:#9ca3af;background:#f9fafb">
                                        Próxima
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ── Auditoria e KPIs da curadoria ─── --}}
                <div
                    style="margin-bottom:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.95rem;font-weight:700;color:#111827">Auditoria e KPIs da curadoria
                                </div>
                                <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">
                                    Leitura operacional das decisões humanas, produtividade do time e trilha recente de
                                    mudanças
                                    na fila.
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                <a href="{{ route('admin.federal-programs.exports.curation-audit.csv', request()->query()) }}"
                                    style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;text-decoration:none;background:#fff">
                                    Exportar auditoria CSV
                                </a>
                                <a href="{{ route('admin.federal-programs.exports.curation-audit.xlsx', request()->query()) }}"
                                    style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;font-size:.74rem;color:#374151;text-decoration:none;background:#fff">
                                    Exportar auditoria XLSX
                                </a>
                                <div
                                    style="display:inline-flex;align-items:center;padding:.28rem .7rem;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:.72rem;font-weight:700">
                                    Base: {{ $curationKpis['period_label'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                        <div style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.7rem">
                            @foreach ([['l' => 'Backlog aberto', 'v' => $curationKpis['open_backlog'], 'c' => '#111827'], ['l' => 'Cobertura de responsável', 'v' => $curationKpis['assignment_coverage'] . '%', 'c' => '#1d4ed8'], ['l' => 'Revisadas no período', 'v' => $curationKpis['reviewed_in_period'], 'c' => '#047857'], ['l' => 'Publicadas no período', 'v' => $curationKpis['published_in_period'], 'c' => '#047857'], ['l' => 'Rejeitadas no período', 'v' => $curationKpis['rejected_in_period'], 'c' => '#b91c1c'], ['l' => 'SLA vencido aberto', 'v' => $curationKpis['overdue_open'], 'c' => '#b91c1c']] as $card)
                                <div
                                    style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                                    <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                        {{ $card['v'] }}
                                    </div>
                                    <div style="font-size:.69rem;color:#9ca3af;margin-top:.2rem;line-height:1.35">
                                        {{ $card['l'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div style="display:flex;gap:.7rem;flex-wrap:wrap;margin-top:.8rem">
                            <div
                                style="padding:.55rem .75rem;border:1px solid #e5e7eb;border-radius:10px;background:#fff;font-size:.76rem;color:#374151">
                                Tempo médio até decisão:
                                <strong>{{ number_format($curationKpis['avg_decision_hours'], 1, ',', '.') }}h</strong>
                            </div>
                            <div
                                style="padding:.55rem .75rem;border:1px solid #e5e7eb;border-radius:10px;background:#fff;font-size:.76rem;color:#374151">
                                Tempo médio até publicação:
                                <strong>{{ number_format($curationKpis['avg_publish_hours'], 1, ',', '.') }}h</strong>
                            </div>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fff">
                        <form method="GET" action="{{ route('admin.federal-programs.index') }}"
                            style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.7rem;align-items:end">
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Período
                                </label>
                                <select name="curation_audit_period"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['24h' => 'Últimas 24h', '7d' => 'Últimos 7 dias', '30d' => 'Últimos 30 dias', '90d' => 'Últimos 90 dias', 'all' => 'Período total'] as $value => $label)
                                        <option value="{{ $value }}" @selected($curationAuditFilters['period'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Evento
                                </label>
                                <select name="curation_audit_event"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    @foreach (['all' => 'Todos', 'assign' => 'Atribuição', 'apply_suggestion' => 'Confirmação de sugestão', 'reprioritize' => 'Repriorização', 'start_review' => 'Início de revisão', 'approve' => 'Aprovação', 'publish' => 'Publicação', 'reject' => 'Rejeição'] as $value => $label)
                                        <option value="{{ $value }}" @selected($curationAuditFilters['event'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Operador
                                </label>
                                <select name="curation_audit_causer_id"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Todos</option>
                                    @foreach ($reviewers as $reviewer)
                                        <option value="{{ $reviewer->id }}" @selected($curationAuditFilters['causer_id'] !== '' && (int) $curationAuditFilters['causer_id'] === (int) $reviewer->id)>
                                            {{ $reviewer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Fonte
                                </label>
                                <select name="curation_audit_source_id"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Todas</option>
                                    @foreach ($sourceCatalog as $source)
                                        <option value="{{ $source['id'] }}" @selected($curationAuditFilters['source_id'] !== '' && (int) $curationAuditFilters['source_id'] === (int) $source['id'])>
                                            {{ $source['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:.3rem">
                                    Município
                                </label>
                                <select name="curation_audit_municipality_id"
                                    style="width:100%;padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem">
                                    <option value="">Todos</option>
                                    @foreach ($municipalities as $municipality)
                                        <option value="{{ $municipality->id }}" @selected($curationAuditFilters['municipality_id'] !== '' && (int) $curationAuditFilters['municipality_id'] === (int) $municipality->id)>
                                            {{ $municipality->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="display:flex;gap:.55rem;justify-content:flex-end">
                                <a href="{{ route('admin.federal-programs.index') }}"
                                    style="padding:.5rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-size:.78rem;color:#4b5563;text-decoration:none;background:#fff">
                                    Limpar
                                </a>
                                <button type="submit"
                                    style="padding:.5rem .95rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer">
                                    Filtrar auditoria
                                </button>
                            </div>
                        </form>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                        <div style="font-size:.8rem;font-weight:700;color:#111827;margin-bottom:.6rem">Produtividade por
                            operador
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem">
                            @forelse ($curationOperatorSummary as $operator)
                                <div
                                    style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.8rem .85rem">
                                    <div style="font-size:.82rem;font-weight:700;color:#111827">
                                        {{ $operator['reviewer_name'] }}
                                    </div>
                                    <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.45rem">
                                        <span
                                            style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:.68rem;font-weight:700">
                                            {{ $operator['decisions_count'] }} decisões
                                        </span>
                                        <span
                                            style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;background:#ecfdf5;color:#047857;font-size:.68rem;font-weight:700">
                                            {{ $operator['published_count'] }} publicadas
                                        </span>
                                        <span
                                            style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;background:#fef2f2;color:#b91c1c;font-size:.68rem;font-weight:700">
                                            {{ $operator['rejected_count'] }} rejeitadas
                                        </span>
                                    </div>
                                    <div style="font-size:.73rem;color:#6b7280;margin-top:.5rem">
                                        Tempo médio até decisão:
                                        {{ number_format($operator['avg_decision_hours'], 1, ',', '.') }}h
                                    </div>
                                </div>
                            @empty
                                <div
                                    style="grid-column:1/-1;padding:.9rem;border:1px dashed #d1d5db;border-radius:10px;font-size:.78rem;color:#6b7280;background:#fff">
                                    Nenhuma decisão humana registrada no período filtrado.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead>
                                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Quando</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Operador</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Evento</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Item</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Mudança</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($curationAuditHistory as $audit)
                                    <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                        <td style="padding:1rem;min-width:120px;font-size:.76rem;color:#4b5563">
                                            {{ $audit['created_at_human'] }}
                                        </td>
                                        <td style="padding:1rem;min-width:160px">
                                            <div style="font-size:.78rem;font-weight:700;color:#111827">
                                                {{ $audit['causer_name'] }}</div>
                                            @if ($audit['bulk_operation'])
                                                <div style="font-size:.7rem;color:#9ca3af;margin-top:.25rem">
                                                    Lote ·
                                                    {{ $audit['selected_count'] > 0 ? $audit['selected_count'] . ' item(ns)' : 'ação coletiva' }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:1rem;min-width:170px">
                                            <div
                                                style="display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:.68rem;font-weight:700">
                                                {{ $audit['event_label'] }}
                                            </div>
                                            <div style="font-size:.73rem;color:#6b7280;margin-top:.35rem">
                                                {{ $audit['description'] }}</div>
                                        </td>
                                        <td style="padding:1rem;min-width:280px">
                                            <div style="font-size:.8rem;font-weight:700;color:#111827">
                                                {{ $audit['title'] }}
                                            </div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem">
                                                {{ $audit['source_name'] }} · {{ $audit['municipality_name'] }}
                                            </div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem">
                                                score {{ number_format($audit['match_score'], 2, ',', '.') }}
                                            </div>
                                            @if ($audit['decision_notes'] !== '')
                                                <div
                                                    style="font-size:.73rem;color:#4b5563;margin-top:.4rem;line-height:1.45">
                                                    {{ $audit['decision_notes'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:1rem;min-width:220px">
                                            <div style="font-size:.74rem;color:#4b5563;line-height:1.5">
                                                @if ($audit['before_queue_status'] !== '' || $audit['after_queue_status'] !== '')
                                                    Fila:
                                                    {{ $audit['before_queue_status'] !== '' ? $audit['before_queue_status'] : '—' }}
                                                    ->
                                                    {{ $audit['after_queue_status'] !== '' ? $audit['after_queue_status'] : '—' }}
                                                @endif
                                            </div>
                                            <div style="font-size:.74rem;color:#4b5563;line-height:1.5;margin-top:.2rem">
                                                @if ($audit['before_priority'] !== '' || $audit['after_priority'] !== '')
                                                    Prioridade:
                                                    {{ $audit['before_priority'] !== '' ? $audit['before_priority'] : '—' }}
                                                    ->
                                                    {{ $audit['after_priority'] !== '' ? $audit['after_priority'] : '—' }}
                                                @endif
                                            </div>
                                            <div style="font-size:.74rem;color:#4b5563;line-height:1.5;margin-top:.2rem">
                                                @if ($audit['before_assigned_to_name'] !== '' || $audit['after_assigned_to_name'] !== '')
                                                    Responsável:
                                                    {{ $audit['before_assigned_to_name'] !== '' ? $audit['before_assigned_to_name'] : '—' }}
                                                    ->
                                                    {{ $audit['after_assigned_to_name'] !== '' ? $audit['after_assigned_to_name'] : '—' }}
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                            Nenhum registro de auditoria encontrado com os filtros atuais.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Últimas coletas por fonte ─── --}}
                <div
                    style="margin-bottom:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                            <div>
                                <div style="font-size:.95rem;font-weight:700;color:#111827">Últimas coletas por fonte</div>
                                <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">
                                    Histórico recente dos grupos ativos para validar o pipeline multi-fonte em produção
                                    local.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                @foreach ([['l' => 'Fontes rastreadas', 'v' => $sourceRunSummary['tracked_sources'], 'c' => '#111827'], ['l' => 'Saudáveis', 'v' => $sourceRunSummary['healthy_sources'], 'c' => '#047857'], ['l' => 'Com falha', 'v' => $sourceRunSummary['failed_sources'], 'c' => '#b91c1c'], ['l' => 'Registros nas últimas coletas', 'v' => $sourceRunSummary['records_fetched'], 'c' => '#1d4ed8'], ['l' => 'Falhas recentes', 'v' => $sourceRunSummary['recent_failures'], 'c' => '#b45309']] as $card)
                                    <div
                                        style="min-width:118px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.7rem .8rem">
                                        <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                            {{ $card['v'] }}
                                        </div>
                                        <div style="font-size:.68rem;color:#9ca3af;margin-top:.18rem;line-height:1.35">
                                            {{ $card['l'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fff">
                        <div
                            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:#111827">Qualidade e exceções da
                                    curadoria
                                </div>
                                <div style="font-size:.74rem;color:#6b7280;margin-top:.2rem">
                                    Itens críticos que pedem correção operacional imediata antes de ampliar o backlog.
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap;justify-content:flex-end">
                                @foreach ([
            ['l' => 'SLA vencido sem responsável', 'v' => $curationExceptionsSummary['overdue_unassigned'], 'c' => '#b91c1c', 'filters' => ['curation_sla_bucket' => 'overdue', 'curation_assigned_to_user_id' => 'unassigned', 'curation_queue_status' => 'pending', 'curation_sort' => 'sla_then_score']],
            ['l' => 'Sem SLA', 'v' => $curationExceptionsSummary['no_sla_open'], 'c' => '#b45309', 'filters' => ['curation_sla_bucket' => 'no_sla', 'curation_sort' => 'recent_first']],
            ['l' => 'Revisão parada >48h', 'v' => $curationExceptionsSummary['stale_in_review'], 'c' => '#b45309', 'filters' => ['curation_queue_status' => 'in_review', 'curation_sort' => 'oldest_first']],
            ['l' => 'Aprovado sem publicar', 'v' => $curationExceptionsSummary['approved_waiting_publish'], 'c' => '#1d4ed8', 'filters' => ['curation_queue_status' => 'approved', 'curation_sort' => 'oldest_first']],
            ['l' => 'Alta prioridade com score baixo', 'v' => $curationExceptionsSummary['high_priority_low_score'], 'c' => '#7c3aed', 'filters' => ['curation_priority' => 'high', 'curation_sort' => 'match_score_desc', 'curation_min_score' => '0']],
        ] as $card)
                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), $card['filters'], ['curation_page' => 1])) }}#curation-queue"
                                        style="min-width:132px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:.72rem .8rem;text-decoration:none;display:block">
                                        <div style="font-size:1rem;font-weight:800;color:{{ $card['c'] }}">
                                            {{ $card['v'] }}</div>
                                        <div style="font-size:.68rem;color:#9ca3af;margin-top:.2rem;line-height:1.35">
                                            {{ $card['l'] }}</div>
                                        <div style="font-size:.66rem;color:#6b7280;margin-top:.35rem;font-weight:700">
                                            Abrir lista
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div style="overflow-x:auto;margin-top:.85rem">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Item</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Exceções</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Fila</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Responsável</th>
                                        <th
                                            style="padding:.75rem .9rem;text-align:left;font-size:.7rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                            Ações rápidas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($curationExceptionRows as $entry)
                                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                            <td style="padding:.85rem .9rem;min-width:280px">
                                                <div style="font-size:.8rem;font-weight:700;color:#111827">
                                                    {{ $entry['title'] }}
                                                </div>
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.25rem">
                                                    {{ $entry['source_name'] }} ·
                                                    {{ $entry['municipality_name'] }}{{ $entry['municipality_uf'] ? ' / ' . $entry['municipality_uf'] : '' }}
                                                </div>
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                    score {{ number_format($entry['match_score'], 2, ',', '.') }}
                                                </div>
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:230px">
                                                <div style="display:flex;gap:.35rem;flex-wrap:wrap">
                                                    @foreach ($entry['exceptions'] as $exception)
                                                        @php
                                                            $tone = match ($exception['tone']) {
                                                                'danger' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                                                                'warning' => ['bg' => '#fffbeb', 'color' => '#b45309'],
                                                                'info' => ['bg' => '#eff6ff', 'color' => '#1d4ed8'],
                                                                default => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                                            };
                                                        @endphp
                                                        <span
                                                            style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;background:{{ $tone['bg'] }};color:{{ $tone['color'] }};font-size:.68rem;font-weight:700">
                                                            {{ $exception['label'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;font-size:.76rem;color:#4b5563;min-width:160px">
                                                {{ $entry['queue_status_label'] }}
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                    {{ $entry['sla_label'] }}{{ $entry['sla_due_at_human'] ? ' · ' . $entry['sla_due_at_human'] : '' }}
                                                </div>
                                            </td>
                                            <td
                                                style="padding:.85rem .9rem;font-size:.76rem;color:#4b5563;min-width:170px">
                                                {{ $entry['assigned_to_name'] ?: 'Sem responsável' }}
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                    Prioridade {{ $entry['priority_label'] }}
                                                </div>
                                            </td>
                                            <td style="padding:.85rem .9rem;min-width:250px">
                                                <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                                    <a href="{{ route('admin.federal-programs.index', array_merge(request()->query(), ['curation_search' => $entry['title'], 'curation_page' => 1])) }}#curation-queue"
                                                        style="padding:.42rem .65rem;background:#fff;border:1px solid #d1d5db;border-radius:8px;font-size:.72rem;font-weight:700;color:#374151;text-decoration:none">
                                                        Abrir na fila
                                                    </a>
                                                    @if (!$entry['assigned_to_name'])
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.assign', $entry['id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="assigned_to_user_id"
                                                                value="{{ auth()->id() }}">
                                                            <input type="hidden" name="priority"
                                                                value="{{ $entry['priority'] }}">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Assumir item
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if ($entry['queue_status'] === 'pending')
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.transition', $entry['id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="action" value="start_review">
                                                            <input type="hidden" name="decision_notes"
                                                                value="Revisão iniciada a partir do bloco de exceções críticas.">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Iniciar revisão
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if ($entry['queue_status'] === 'approved')
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.transition', $entry['id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="action" value="publish">
                                                            <input type="hidden" name="decision_notes"
                                                                value="Publicação acionada a partir do bloco de exceções críticas.">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Publicar agora
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if (in_array($entry['priority'], ['high', 'urgent'], true) && (float) $entry['match_score'] < 0.55)
                                                        <form method="POST"
                                                            action="{{ route('admin.federal-programs.curation.assign', $entry['id']) }}">
                                                            @csrf
                                                            <input type="hidden" name="assigned_to_user_id"
                                                                value="{{ $entry['assigned_to_user_id'] }}">
                                                            <input type="hidden" name="priority" value="normal">
                                                            <button type="submit"
                                                                style="padding:.42rem .65rem;background:#fffbeb;color:#b45309;border:1px solid #fde68a;border-radius:8px;font-size:.72rem;font-weight:700;cursor:pointer">
                                                                Rebaixar prioridade
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"
                                                style="padding:1rem;text-align:center;font-size:.8rem;color:#6b7280">
                                                Nenhuma exceção crítica encontrada na curadoria neste momento.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead>
                                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Fonte</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Município</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Status</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Resultado</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Tempo</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Mensagem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sourceRunHistory as $run)
                                    @php
                                        $runBg = match ($run['status_tone']) {
                                            'warning' => '#fffbeb',
                                            'info' => '#eff6ff',
                                            'success' => '#ecfdf5',
                                            'danger' => '#fef2f2',
                                            default => '#f3f4f6',
                                        };
                                        $runColor = match ($run['status_tone']) {
                                            'warning' => '#b45309',
                                            'info' => '#1d4ed8',
                                            'success' => '#047857',
                                            'danger' => '#b91c1c',
                                            default => '#6b7280',
                                        };
                                    @endphp
                                    <tr style="border-bottom:1px solid #f3f4f6">
                                        <td style="padding:.85rem 1rem">
                                            <div style="font-size:.82rem;font-weight:700;color:#111827">
                                                {{ $run['source_name'] }}
                                            </div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.18rem">
                                                {{ $run['pipeline_group_label'] }} · {{ $run['source'] }}
                                            </div>
                                            @if ($run['parent_sync_log_id'])
                                                <div style="font-size:.7rem;color:#9ca3af;margin-top:.2rem">
                                                    Sync pai #{{ $run['parent_sync_log_id'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:.85rem 1rem;font-size:.78rem;color:#374151">
                                            {{ $run['municipality_name'] }}
                                        </td>
                                        <td style="padding:.85rem 1rem">
                                            <span
                                                style="display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:700;background:{{ $runBg }};color:{{ $runColor }}">
                                                {{ $run['status_label'] }}
                                            </span>
                                        </td>
                                        <td style="padding:.85rem 1rem;font-size:.78rem;color:#374151">
                                            {{ $run['records_fetched'] }} registro(s)
                                        </td>
                                        <td style="padding:.85rem 1rem;font-size:.78rem;color:#374151">
                                            {{ $run['duration_ms'] > 0 ? number_format($run['duration_ms'] / 1000, 1, ',', '.') . 's' : '—' }}
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                {{ $run['finished_at_human'] ?: ($run['started_at_human'] ?: 'agora') }}
                                            </div>
                                        </td>
                                        <td
                                            style="padding:.85rem 1rem;font-size:.76rem;color:#4b5563;line-height:1.5;max-width:360px">
                                            {{ $run['message'] ?: 'Sem mensagem adicional.' }}
                                            @php
                                                $debug = is_array($run['debug'] ?? null) ? $run['debug'] : [];
                                                $rejectedSamples = is_array($debug['rejected_samples'] ?? null)
                                                    ? $debug['rejected_samples']
                                                    : [];
                                                $passedFilterSamples = is_array($debug['passed_filter_samples'] ?? null)
                                                    ? $debug['passed_filter_samples']
                                                    : [];
                                                $qualifiedSamples = is_array($debug['qualified_samples'] ?? null)
                                                    ? $debug['qualified_samples']
                                                    : [];
                                            @endphp
                                            @if (!empty($rejectedSamples) || !empty($passedFilterSamples) || !empty($qualifiedSamples))
                                                <details style="margin-top:.6rem">
                                                    <summary
                                                        style="cursor:pointer;color:#3730a3;font-size:.72rem;font-weight:700">
                                                        Debug operacional
                                                    </summary>
                                                    <div
                                                        style="margin-top:.55rem;padding:.7rem .75rem;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb">
                                                        @if (!empty($qualifiedSamples))
                                                            <div
                                                                style="font-size:.7rem;font-weight:700;color:#047857;margin-bottom:.3rem">
                                                                Qualificados
                                                            </div>
                                                            @foreach ($qualifiedSamples as $sample)
                                                                <div
                                                                    style="font-size:.72rem;color:#374151;line-height:1.45;margin-bottom:.35rem">
                                                                    <strong>{{ $sample['title'] ?? 'Sem título' }}</strong>
                                                                    <span style="color:#9ca3af">· score
                                                                        {{ $sample['candidate_score'] ?? 0 }}</span>
                                                                    <div style="color:#6b7280">
                                                                        {{ $sample['reason'] ?? 'qualified' }}</div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                        @if (!empty($passedFilterSamples))
                                                            <div
                                                                style="font-size:.7rem;font-weight:700;color:#1d4ed8;margin:{{ !empty($qualifiedSamples) ? '.55rem' : '0' }} 0 .3rem 0">
                                                                Passaram no filtro
                                                            </div>
                                                            @foreach ($passedFilterSamples as $sample)
                                                                <div
                                                                    style="font-size:.72rem;color:#374151;line-height:1.45;margin-bottom:.35rem">
                                                                    <strong>{{ $sample['title'] ?? 'Sem título' }}</strong>
                                                                    <div style="color:#6b7280">
                                                                        {{ $sample['reason'] ?? 'passed_filter' }}</div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                        @if (!empty($rejectedSamples))
                                                            <div
                                                                style="font-size:.7rem;font-weight:700;color:#b45309;margin:{{ !empty($qualifiedSamples) || !empty($passedFilterSamples) ? '.55rem' : '0' }} 0 .3rem 0">
                                                                Rejeitados
                                                            </div>
                                                            @foreach ($rejectedSamples as $sample)
                                                                <div
                                                                    style="font-size:.72rem;color:#374151;line-height:1.45;margin-bottom:.35rem">
                                                                    <strong>{{ $sample['title'] ?? 'Sem título' }}</strong>
                                                                    <div style="color:#6b7280">
                                                                        {{ $sample['reason'] ?? 'rejected' }}</div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </details>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            style="padding:1.2rem;text-align:center;font-size:.82rem;color:#9ca3af">
                                            Nenhuma coleta por fonte registrada ainda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Tabela por município ─── --}}
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                <th
                                    style="padding:.85rem 1rem;text-align:left;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                                    Município</th>
                                <th
                                    style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                                    Oportunidades</th>
                                <th
                                    style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                                    Ativas</th>
                                <th
                                    style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                                    Match médio</th>
                                <th
                                    style="padding:.85rem 1rem;text-align:center;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                                    Última sync</th>
                                <th
                                    style="padding:.85rem 1rem;text-align:right;font-size:.75rem;color:#6b7280;font-weight:600;letter-spacing:.05em;text-transform:uppercase">
                                    Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($municipalities as $m)
                                @php
                                    $ps = $programStats->get($m->id);
                                    $execution = $syncExecutions->get($m->id);
                                    $total = $ps->total ?? 0;
                                    $open = $ps->active_count ?? 0;
                                    $avgScore = $ps ? round($ps->avg_score * 100) : null;
                                    $lastUpd = $ps ? \Carbon\Carbon::parse($ps->last_updated)->diffForHumans() : null;
                                    $scoreColor =
                                        $avgScore >= 80 ? '#059669' : ($avgScore >= 60 ? '#e65100' : '#9ca3af');
                                    $syncTone = $execution['status_tone'] ?? 'neutral';
                                    $syncBg = match ($syncTone) {
                                        'warning' => '#fffbeb',
                                        'info' => '#eff6ff',
                                        'success' => '#ecfdf5',
                                        'danger' => '#fef2f2',
                                        default => '#f3f4f6',
                                    };
                                    $syncColor = match ($syncTone) {
                                        'warning' => '#b45309',
                                        'info' => '#1d4ed8',
                                        'success' => '#047857',
                                        'danger' => '#b91c1c',
                                        default => '#6b7280',
                                    };
                                    $syncDetail = 'Sem execução recente.';

                                    if ($execution) {
                                        if (!empty($execution['error_message'])) {
                                            $syncDetail = $execution['error_message'];
                                        } elseif (!empty($execution['result'])) {
                                            $syncDetail =
                                                ($execution['result']['novos'] ?? 0) .
                                                ' novos • ' .
                                                ($execution['result']['atualizados'] ?? 0) .
                                                ' atualizados • ' .
                                                ($execution['result']['descartados'] ?? 0) .
                                                ' descartados';
                                        } elseif (!empty($execution['started_at_human'])) {
                                            $syncDetail = 'Iniciado ' . $execution['started_at_human'];
                                        }
                                    }
                                @endphp
                                <tr style="border-bottom:1px solid #f3f4f6" id="row-{{ $m->id }}">
                                    <td style="padding:.9rem 1rem">
                                        <div style="font-weight:500;font-size:.9rem;color:#111">{{ $m->name }}
                                        </div>
                                        <div style="font-size:.75rem;color:#9ca3af">{{ $m->state_code }} • IBGE
                                            {{ $m->ibge_code }}
                                        </div>
                                    </td>
                                    <td style="padding:.9rem 1rem;text-align:center">
                                        <button onclick="showPrograms({{ $m->id }}, '{{ $m->name }}')"
                                            style="font-size:.9rem;font-weight:600;color:#1a5fa8;background:none;border:none;cursor:pointer;text-decoration:underline">
                                            {{ $total }}
                                        </button>
                                    </td>
                                    <td
                                        style="padding:.9rem 1rem;text-align:center;font-size:.88rem;color:#{{ $open > 0 ? '059669' : '9ca3af' }};font-weight:{{ $open > 0 ? '600' : '400' }}">
                                        {{ $open }}
                                    </td>
                                    <td style="padding:.9rem 1rem;text-align:center">
                                        @if ($avgScore !== null)
                                            <span
                                                style="font-size:.85rem;font-weight:600;color:{{ $scoreColor }}">{{ $avgScore }}%</span>
                                        @else
                                            <span style="font-size:.8rem;color:#d1d5db">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:.9rem 1rem;text-align:center;font-size:.8rem;color:#9ca3af">
                                        <div id="sync-time-{{ $m->id }}">{{ $lastUpd ?? 'Nunca' }}</div>
                                        <div style="margin-top:.35rem">
                                            <span id="sync-status-{{ $m->id }}"
                                                style="display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;font-size:.7rem;font-weight:600;background:{{ $syncBg }};color:{{ $syncColor }}">
                                                {{ $execution['status_label'] ?? 'Sem execução' }}{{ !empty($execution['force']) ? ' • Forcado' : '' }}
                                            </span>
                                        </div>
                                        <div id="sync-details-{{ $m->id }}"
                                            style="margin-top:.35rem;font-size:.72rem;line-height:1.4;color:#9ca3af">
                                            {{ $syncDetail }}
                                        </div>
                                    </td>
                                    <td style="padding:.9rem 1rem;text-align:right">
                                        <div style="display:flex;gap:.5rem;justify-content:flex-end;align-items:center">
                                            <button
                                                onclick="syncMunicipality({{ $m->id }}, '{{ $m->name }}', false)"
                                                id="btn-sync-{{ $m->id }}"
                                                {{ !empty($execution['is_busy']) ? 'disabled' : '' }}
                                                style="padding:.38rem .8rem;background:#0f1117;color:#fff;border:none;border-radius:7px;font-size:.78rem;cursor:pointer;white-space:nowrap">
                                                {{ !empty($execution['is_busy']) ? '⏳ Em andamento' : '↻ Sync' }}
                                            </button>
                                            <button
                                                onclick="syncMunicipality({{ $m->id }}, '{{ $m->name }}', true)"
                                                id="btn-force-sync-{{ $m->id }}"
                                                title="Reanalisar todas as oportunidades"
                                                {{ !empty($execution['is_busy']) ? 'disabled' : '' }}
                                                style="padding:.38rem .7rem;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:.78rem;cursor:pointer">
                                                🔄 Forçar
                                            </button>
                                            <button
                                                onclick="retryLatestExecution({{ $m->id }}, '{{ $m->name }}')"
                                                id="btn-retry-sync-{{ $m->id }}"
                                                data-execution-id="{{ $execution['id'] ?? '' }}"
                                                style="display:{{ !empty($execution['can_retry']) && empty($execution['is_busy']) ? 'inline-flex' : 'none' }};padding:.38rem .7rem;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:7px;font-size:.78rem;cursor:pointer;white-space:nowrap">
                                                ↺ Reenfileirar
                                            </button>
                                            <button onclick="clearClosed({{ $m->id }}, '{{ $m->name }}')"
                                                title="Remover oportunidades arquivadas"
                                                style="padding:.38rem .7rem;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:7px;font-size:.78rem;cursor:pointer">
                                                🗑
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ── Histórico do sync ─── --}}
                <div
                    style="margin-top:1.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start">
                            <div>
                                <div style="font-size:.95rem;font-weight:700;color:#111827">Histórico operacional do sync
                                </div>
                                <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">
                                    Execuções recentes do Radar com filtros por município, status e modo de disparo.
                                </div>
                                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.7rem">
                                    <a href="{{ route('admin.federal-programs.exports.history.csv', request()->query()) }}"
                                        style="padding:.4rem .7rem;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;font-size:.76rem;font-weight:600;text-decoration:none">
                                        Exportar histórico CSV
                                    </a>
                                    <a href="{{ route('admin.federal-programs.exports.history.xlsx', request()->query()) }}"
                                        style="padding:.4rem .7rem;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:8px;font-size:.76rem;font-weight:700;text-decoration:none">
                                        Exportar histórico XLSX
                                    </a>
                                    <a href="{{ route('admin.federal-programs.exports.summary.csv', request()->query()) }}"
                                        style="padding:.4rem .7rem;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;border-radius:8px;font-size:.76rem;font-weight:600;text-decoration:none">
                                        Exportar resumo CSV
                                    </a>
                                    <a href="{{ route('admin.federal-programs.exports.summary.xlsx', request()->query()) }}"
                                        style="padding:.4rem .7rem;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;border-radius:8px;font-size:.76rem;font-weight:700;text-decoration:none">
                                        Exportar resumo XLSX
                                    </a>
                                </div>
                            </div>
                            <form method="GET" action="{{ route('admin.federal-programs.index') }}"
                                style="display:flex;gap:.6rem;align-items:end;flex-wrap:wrap;justify-content:flex-end">
                                <label style="display:flex;flex-direction:column;gap:.25rem">
                                    <span style="font-size:.72rem;color:#6b7280">Município</span>
                                    <select name="municipality_id"
                                        style="min-width:210px;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem">
                                        <option value="">Todos</option>
                                        @foreach ($municipalities as $m)
                                            <option value="{{ $m->id }}"
                                                {{ (string) $filters['municipality_id'] === (string) $m->id ? 'selected' : '' }}>
                                                {{ $m->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <label style="display:flex;flex-direction:column;gap:.25rem">
                                    <span style="font-size:.72rem;color:#6b7280">Status</span>
                                    <select name="status"
                                        style="min-width:140px;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem">
                                        @foreach (['all' => 'Todos', 'queued' => 'Na fila', 'running' => 'Em execução', 'success' => 'Concluído', 'failed' => 'Falhou'] as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ $filters['status'] === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <label style="display:flex;flex-direction:column;gap:.25rem">
                                    <span style="font-size:.72rem;color:#6b7280">Modo</span>
                                    <select name="mode"
                                        style="min-width:140px;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem">
                                        @foreach (['all' => 'Todos', 'normal' => 'Normal', 'forced' => 'Forçado'] as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ $filters['mode'] === $value ? 'selected' : '' }}>{{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <label style="display:flex;flex-direction:column;gap:.25rem">
                                    <span style="font-size:.72rem;color:#6b7280">Operador</span>
                                    <input type="text" name="operator" value="{{ $filters['operator'] }}"
                                        placeholder="nome ou e-mail"
                                        style="min-width:180px;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem">
                                </label>
                                <label style="display:flex;flex-direction:column;gap:.25rem">
                                    <span style="font-size:.72rem;color:#6b7280">Motivo</span>
                                    <input type="text" name="reason" value="{{ $filters['reason'] }}"
                                        placeholder="buscar no motivo"
                                        style="min-width:180px;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem">
                                </label>
                                <label style="display:flex;flex-direction:column;gap:.25rem">
                                    <span style="font-size:.72rem;color:#6b7280">Recorte</span>
                                    <select name="operational_state"
                                        style="min-width:170px;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:8px;font-size:.82rem">
                                        @foreach (['all' => 'Todos', 'auto_closed' => 'Autoencerradas', 'retried' => 'Reenfileiradas', 'retry_requested' => 'Novos retries', 'retry_source' => 'Origens retry'] as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ $filters['operational_state'] === $value ? 'selected' : '' }}>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="submit"
                                    style="padding:.5rem .9rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer">
                                    Filtrar
                                </button>
                                <a href="{{ route('admin.federal-programs.index') }}"
                                    style="padding:.5rem .9rem;background:#f9fafb;color:#374151;border:1px solid #d1d5db;border-radius:8px;font-size:.8rem;font-weight:600;text-decoration:none">
                                    Limpar
                                </a>
                            </form>
                        </div>
                    </div>
                    <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fcfcfd">
                        <div style="font-size:.82rem;font-weight:700;color:#374151;margin-bottom:.65rem">
                            Resumo por município
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
                            @forelse ($municipalitySummary as $summary)
                                <div
                                    style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:.85rem .95rem">
                                    <div
                                        style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                        <div>
                                            <div style="font-size:.84rem;font-weight:700;color:#111827">
                                                {{ $summary['municipality_name'] }}</div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">
                                                {{ $summary['latest_status_label'] }}
                                                @if ($summary['latest_updated_at_human'])
                                                    • {{ $summary['latest_updated_at_human'] }}
                                                @endif
                                            </div>
                                        </div>
                                        <div style="font-size:1rem;font-weight:800;color:#0f172a">
                                            {{ $summary['total'] }}
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:.55rem;flex-wrap:wrap;margin-top:.6rem">
                                        <span style="font-size:.72rem;color:#047857">Sucesso
                                            {{ $summary['success'] }}</span>
                                        <span style="font-size:.72rem;color:#b91c1c">Falha
                                            {{ $summary['failed'] }}</span>
                                        <span style="font-size:.72rem;color:#1d4ed8">Exec
                                            {{ $summary['running'] }}</span>
                                        <span style="font-size:.72rem;color:#b45309">Fila
                                            {{ $summary['queued'] }}</span>
                                        <span style="font-size:.72rem;color:#991b1b">Auto
                                            {{ $summary['auto_closed'] }}</span>
                                        <span style="font-size:.72rem;color:#3730a3">Retry
                                            {{ $summary['retried'] }}</span>
                                    </div>
                                    @if ($summary['latest_operator_name'] || $summary['latest_reason'])
                                        <div style="margin-top:.55rem;font-size:.72rem;color:#6b7280;line-height:1.45">
                                            @if ($summary['latest_operator_name'])
                                                Operador: {{ $summary['latest_operator_name'] }}<br>
                                            @endif
                                            @if ($summary['latest_reason'])
                                                Motivo: {{ $summary['latest_reason'] }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div
                                    style="grid-column:1/-1;padding:1rem;border:1px dashed #d1d5db;border-radius:10px;text-align:center;font-size:.8rem;color:#9ca3af">
                                    Nenhum município encontrado para os filtros operacionais aplicados.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead>
                                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        ID</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Município</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Status</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Resultado</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Disparo</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Tempo</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Erro / detalhe</th>
                                    <th
                                        style="padding:.8rem 1rem;text-align:right;font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                        Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($history as $item)
                                    @php
                                        $historyBg = match ($item['status_tone']) {
                                            'warning' => '#fffbeb',
                                            'info' => '#eff6ff',
                                            'success' => '#ecfdf5',
                                            'danger' => '#fef2f2',
                                            default => '#f3f4f6',
                                        };
                                        $historyColor = match ($item['status_tone']) {
                                            'warning' => '#b45309',
                                            'info' => '#1d4ed8',
                                            'success' => '#047857',
                                            'danger' => '#b91c1c',
                                            default => '#6b7280',
                                        };
                                    @endphp
                                    <tr style="border-bottom:1px solid #f3f4f6">
                                        <td style="padding:.85rem 1rem;font-size:.8rem;color:#6b7280">
                                            #{{ $item['id'] }}
                                        </td>
                                        <td style="padding:.85rem 1rem">
                                            <div style="font-size:.84rem;font-weight:600;color:#111827">
                                                {{ $item['municipality_name'] }}
                                            </div>
                                            <div style="font-size:.72rem;color:#9ca3af">ID
                                                {{ $item['municipality_id'] }}
                                            </div>
                                        </td>
                                        <td style="padding:.85rem 1rem">
                                            <span
                                                style="display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:700;background:{{ $historyBg }};color:{{ $historyColor }}">
                                                {{ $item['status_label'] }}
                                            </span>
                                            @if ($item['is_stale'])
                                                <div style="font-size:.72rem;color:#b91c1c;margin-top:.25rem">Possível
                                                    travado
                                                </div>
                                            @endif
                                            @if ($item['was_auto_closed_stale'])
                                                <div style="font-size:.72rem;color:#b91c1c;margin-top:.25rem">
                                                    Autoencerrado
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding:.85rem 1rem;font-size:.78rem;color:#374151">
                                            {{ data_get($item, 'result.novos', 0) }} novos •
                                            {{ data_get($item, 'result.atualizados', 0) }} atualizados •
                                            {{ data_get($item, 'result.descartados', 0) }} descartados
                                        </td>
                                        <td style="padding:.85rem 1rem;font-size:.78rem;color:#374151">
                                            {{ $item['force'] ? 'Forçado' : 'Normal' }}
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                {{ $item['queued_via'] }}</div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">Fila:
                                                {{ $item['queue_name'] }}</div>
                                            <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">Operador:
                                                {{ $item['operator_name'] ?: 'sistema' }}</div>
                                            @if ($item['operator_email'])
                                                <div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem">
                                                    {{ $item['operator_email'] }}</div>
                                            @endif
                                        </td>
                                        <td style="padding:.85rem 1rem;font-size:.78rem;color:#374151">
                                            <div>Início: {{ $item['started_at_human'] ?? '—' }}</div>
                                            <div style="color:#9ca3af;margin-top:.2rem">
                                                Fim: {{ $item['finished_at_human'] ?? '—' }}</div>
                                            <div style="color:#9ca3af;margin-top:.2rem">
                                                Duração:
                                                {{ $item['duration_ms'] ? number_format($item['duration_ms'] / 1000, 1, ',', '.') . 's' : '—' }}
                                            </div>
                                        </td>
                                        <td style="padding:.85rem 1rem;font-size:.78rem;color:#4b5563;max-width:320px">
                                            {{ $item['error_message'] ?: ($item['stale_reason'] ?: 'Sem erro registrado.') }}
                                            @if ($item['operation_reason'])
                                                <div style="margin-top:.35rem;font-size:.72rem;color:#6b7280">
                                                    Motivo: {{ $item['operation_reason'] }}
                                                </div>
                                            @endif
                                            @if (!empty($item['audit_events']))
                                                <details style="margin-top:.45rem">
                                                    <summary
                                                        style="cursor:pointer;color:#3730a3;font-size:.72rem;font-weight:600">
                                                        Ver timeline
                                                    </summary>
                                                    <div style="margin-top:.45rem;display:grid;gap:.45rem">
                                                        @foreach ($item['audit_events'] as $event)
                                                            <div
                                                                style="padding:.45rem .55rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px">
                                                                <div
                                                                    style="font-size:.72rem;font-weight:700;color:#111827">
                                                                    {{ $event['label'] }}</div>
                                                                <div
                                                                    style="font-size:.7rem;color:#6b7280;margin-top:.15rem">
                                                                    {{ $event['actor_name'] ?: 'sistema' }}
                                                                    @if ($event['at_human'])
                                                                        • {{ $event['at_human'] }}
                                                                    @endif
                                                                </div>
                                                                @if (!empty($event['context']['reason']))
                                                                    <div
                                                                        style="font-size:.7rem;color:#4b5563;margin-top:.2rem">
                                                                        Motivo: {{ $event['context']['reason'] }}
                                                                    </div>
                                                                @endif
                                                                @if (!empty($event['context']['stale_reason']))
                                                                    <div
                                                                        style="font-size:.7rem;color:#4b5563;margin-top:.2rem">
                                                                        Critério stale:
                                                                        {{ $event['context']['stale_reason'] }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </td>
                                        <td style="padding:.85rem 1rem;text-align:right">
                                            @if ($item['can_retry'])
                                                <button
                                                    onclick="retryExecutionByLog({{ $item['id'] }}, {{ $item['municipality_id'] }}, '{{ $item['municipality_name'] }}', true)"
                                                    style="padding:.4rem .75rem;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:7px;font-size:.76rem;font-weight:600;cursor:pointer">
                                                    ↺ Reenfileirar
                                                </button>
                                            @elseif ($item['retried_to_log_id'])
                                                <span style="font-size:.72rem;color:#9ca3af">Reenfileirado</span>
                                            @else
                                                <span style="font-size:.72rem;color:#d1d5db">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"
                                            style="padding:1.4rem;text-align:center;font-size:.82rem;color:#9ca3af">
                                            Nenhuma execução encontrada para os filtros selecionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div style="padding:1rem 1.1rem;border-top:1px solid #e5e7eb">
                        {{ $history->links() }}
                    </div>
                </div>

                {{-- ── Histórico do cron ─── --}}
                <div
                    style="margin-top:1.5rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem">
                    <div style="font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.5rem">⏰ Agendamento
                        automático
                    </div>
                    <div style="font-size:.8rem;color:#6b7280;line-height:1.7">
                        O comando <code
                            style="background:#e5e7eb;padding:.1rem .4rem;border-radius:4px">marqueteiro:sync-federal-programs</code>
                        é executado automaticamente toda <strong>segunda-feira às 03h00</strong> (BRT).<br>
                        Para rodar manualmente via terminal:
                    </div>
                    <pre
                        style="background:#0f1117;color:#e2e8f0;padding:.85rem 1rem;border-radius:8px;font-size:.78rem;margin-top:.6rem;overflow-x:auto">docker exec marqueteiro_app php artisan marqueteiro:sync-federal-programs
docker exec marqueteiro_app php artisan marqueteiro:sync-federal-programs --municipality=1 --force
docker exec marqueteiro_app php artisan marqueteiro:sync-federal-programs --dry-run
php artisan queue:work {{ $queueHealth['resolved_connection'] ?? 'database' }} --queue={{ $queueHealth['radar_worker_queues'] }} --tries=1 --timeout=900 --sleep=3</pre>
                </div>

            </div>

            {{-- ── Modal de programas do município ─── --}}
            <div id="programsModal"
                style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;overflow-y:auto">
                <div style="background:#fff;border-radius:14px;max-width:800px;margin:2rem auto;overflow:hidden">
                    <div
                        style="display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.4rem;border-bottom:1px solid #e5e7eb">
                        <h2 style="font-size:1.1rem;font-weight:600" id="modalTitle">Oportunidades</h2>
                        <button onclick="closeModal()"
                            style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#9ca3af">✕</button>
                    </div>
                    <div id="modalBody" style="padding:1.25rem;max-height:70vh;overflow-y:auto">
                        <div style="text-align:center;padding:2rem;color:#9ca3af">Carregando...</div>
                    </div>
                </div>
            </div>

        @endsection

        @push('scripts')
            <script>
                const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const syncPollers = new Map();
                const initialBusyMunicipalityIds = @json($busyMunicipalityIds ?? []);
                const syncToneStyles = {
                    warning: {
                        background: '#fffbeb',
                        color: '#b45309'
                    },
                    info: {
                        background: '#eff6ff',
                        color: '#1d4ed8'
                    },
                    success: {
                        background: '#ecfdf5',
                        color: '#047857'
                    },
                    danger: {
                        background: '#fef2f2',
                        color: '#b91c1c'
                    },
                    neutral: {
                        background: '#f3f4f6',
                        color: '#6b7280'
                    }
                };

                // ── Toast ───────────────────────────────────────────────────────────────
                function toast(msg, ok = true) {
                    const el = document.getElementById('toast');
                    el.style.display = 'block';
                    el.style.borderLeftColor = ok ? '#059669' : '#dc2626';
                    el.innerHTML = msg;
                    setTimeout(() => el.style.display = 'none', 6000);
                }

                function setBackfillOutput(text) {
                    const wrapper = document.getElementById('backfill-output');
                    const pre = document.getElementById('backfill-output-text');
                    if (!text) {
                        wrapper.style.display = 'none';
                        pre.textContent = '';
                        return;
                    }

                    pre.textContent = text;
                    wrapper.style.display = 'block';
                }

                function toggleCurationSelection(master) {
                    const checkboxes = document.querySelectorAll('.curation-entry-checkbox');
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = !!master.checked;
                    });
                }

                function toggleSuggestedAssignmentsSelection(master) {
                    const checkboxes = document.querySelectorAll('.curation-suggestion-checkbox');
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = !!master.checked;
                    });
                }

                function syncToneStyle(tone) {
                    return syncToneStyles[tone] || syncToneStyles.neutral;
                }

                function setSyncButtonsState(id, busy, label = '↻ Sync') {
                    const syncBtn = document.getElementById(`btn-sync-${id}`);
                    const forceBtn = document.getElementById(`btn-force-sync-${id}`);

                    if (syncBtn) {
                        syncBtn.disabled = busy;
                        syncBtn.textContent = busy ? label : '↻ Sync';
                        syncBtn.style.cursor = busy ? 'not-allowed' : 'pointer';
                        syncBtn.style.opacity = busy ? '.7' : '1';
                    }

                    if (forceBtn) {
                        forceBtn.disabled = busy;
                        forceBtn.textContent = busy ? '⏳ Forcar' : '🔄 Forçar';
                        forceBtn.style.cursor = busy ? 'not-allowed' : 'pointer';
                        forceBtn.style.opacity = busy ? '.7' : '1';
                    }
                }

                function updateRetryButton(id, execution) {
                    const retryBtn = document.getElementById(`btn-retry-sync-${id}`);

                    if (!retryBtn) {
                        return;
                    }

                    const canRetry = !!execution && !!execution.can_retry && !execution.is_busy;
                    retryBtn.dataset.executionId = execution?.id ? String(execution.id) : '';
                    retryBtn.style.display = canRetry ? 'inline-flex' : 'none';
                    retryBtn.disabled = !canRetry;
                    retryBtn.style.opacity = canRetry ? '1' : '.6';
                    retryBtn.style.cursor = canRetry ? 'pointer' : 'not-allowed';
                }

                function renderSyncExecution(id, execution) {
                    const statusEl = document.getElementById(`sync-status-${id}`);
                    const detailsEl = document.getElementById(`sync-details-${id}`);
                    const timeEl = document.getElementById(`sync-time-${id}`);
                    const row = document.getElementById(`row-${id}`);

                    if (!statusEl || !detailsEl || !timeEl || !row) {
                        return;
                    }

                    if (!execution) {
                        const tone = syncToneStyle('neutral');
                        statusEl.textContent = 'Sem execução';
                        statusEl.style.background = tone.background;
                        statusEl.style.color = tone.color;
                        detailsEl.textContent = 'Sem execução recente.';
                        row.style.opacity = '1';
                        setSyncButtonsState(id, false);
                        updateRetryButton(id, null);
                        return;
                    }

                    const tone = syncToneStyle(execution.status_tone);
                    statusEl.textContent = execution.force ? `${execution.status_label} • Forcado` : execution.status_label;
                    statusEl.style.background = tone.background;
                    statusEl.style.color = tone.color;

                    if (execution.error_message) {
                        detailsEl.textContent = execution.error_message;
                    } else if (execution.result) {
                        detailsEl.textContent =
                            `${execution.result.novos || 0} novos • ${execution.result.atualizados || 0} atualizados • ${execution.result.descartados || 0} descartados`;
                    } else if (execution.is_busy) {
                        detailsEl.textContent = execution.started_at_human ?
                            `Iniciado ${execution.started_at_human}` :
                            'Aguardando processamento na fila.';
                    } else if (execution.updated_at_human) {
                        detailsEl.textContent = `Atualizado ${execution.updated_at_human}`;
                    } else {
                        detailsEl.textContent = 'Sem detalhes.';
                    }

                    if (execution.status === 'success' && execution.finished_at_human) {
                        timeEl.textContent = execution.finished_at_human;
                    } else if (execution.status === 'failed' && execution.finished_at_human) {
                        timeEl.textContent = execution.finished_at_human;
                    } else if (execution.is_busy && execution.started_at_human) {
                        timeEl.textContent = execution.started_at_human;
                    }

                    row.style.opacity = execution.is_busy ? '.65' : '1';
                    setSyncButtonsState(
                        id,
                        execution.is_busy,
                        execution.status === 'queued' ? '⏳ Na fila' : '⏳ Em execução'
                    );
                    updateRetryButton(id, execution);
                }

                function stopSyncPolling(id) {
                    const poller = syncPollers.get(id);
                    if (poller) {
                        clearInterval(poller);
                        syncPollers.delete(id);
                    }
                }

                async function pollSyncStatus(id) {
                    try {
                        const res = await fetch(`/admin/federal-programs/${id}/sync-status`);
                        const data = await res.json();
                        renderSyncExecution(id, data.execution || null);

                        if (!data.execution || !data.execution.is_busy) {
                            stopSyncPolling(id);
                        }
                    } catch (e) {
                        stopSyncPolling(id);
                        toast('Nao foi possivel consultar o status do sync.', false);
                    }
                }

                function startSyncPolling(id) {
                    if (syncPollers.has(id)) {
                        return;
                    }

                    pollSyncStatus(id);

                    const poller = setInterval(() => {
                        pollSyncStatus(id);
                    }, 3000);

                    syncPollers.set(id, poller);
                }

                async function runBackfill(dryRun) {
                    const action = dryRun ? 'simular' : 'aplicar';
                    const confirmText = dryRun ?
                        'Executar uma simulacao do backfill de fontes do Radar de Recursos?' :
                        'Executar o backfill real de fontes do Radar de Recursos agora?';

                    if (!confirm(confirmText)) return;

                    const limitRaw = prompt('Limite de registros (0 = sem limite):', '100');
                    if (limitRaw === null) return;

                    const limit = Number(limitRaw);
                    if (!Number.isInteger(limit) || limit < 0) {
                        toast('Informe um limite inteiro maior ou igual a zero.', false);
                        return;
                    }

                    setBackfillOutput('Executando ' + action + ' do backfill...');

                    try {
                        const res = await fetch('/admin/federal-programs/backfill-sources', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({
                                dry_run: dryRun,
                                limit
                            }),
                        });

                        const data = await res.json();
                        setBackfillOutput(data.output || data.message || 'Comando executado sem saida adicional.');
                        toast(data.message, data.ok);
                    } catch (e) {
                        setBackfillOutput('');
                        toast('Erro ao executar backfill: ' + e.message, false);
                    }
                }

                // ── Sync de um município ─────────────────────────────────────────────────
                async function syncMunicipality(id, name, force) {
                    const row = document.getElementById(`row-${id}`);
                    setSyncButtonsState(id, true, '⏳ Enfileirando...');
                    updateRetryButton(id, null);
                    row.style.opacity = '.8';

                    try {
                        const res = await fetch(`/admin/federal-programs/${id}/sync`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({
                                force
                            }),
                        });
                        const data = await res.json();
                        toast(data.message, data.ok);

                        if (data.ok && data.execution) {
                            renderSyncExecution(id, data.execution);

                            if (data.execution.is_busy) {
                                startSyncPolling(id);
                            }
                        } else if (!data.ok) {
                            setSyncButtonsState(id, false);
                            row.style.opacity = '1';
                        }
                    } catch (e) {
                        toast('Erro de comunicação: ' + e.message, false);
                        setSyncButtonsState(id, false);
                        row.style.opacity = '1';
                    }
                }

                async function retryExecutionByLog(syncLogId, municipalityId, municipalityName, shouldReload = false) {
                    if (!syncLogId) {
                        toast('Nenhuma execução disponível para reenfileirar.', false);
                        return;
                    }

                    const reason = prompt('Motivo do reenfileiramento:', 'Retomar processamento após falha operacional');
                    if (reason === null) return;

                    const trimmedReason = reason.trim();
                    if (!trimmedReason) {
                        toast('Informe um motivo para registrar a auditoria do reenfileiramento.', false);
                        return;
                    }

                    if (!confirm(`Reenfileirar a execução do Radar para ${municipalityName}?`)) return;

                    try {
                        const res = await fetch(`/admin/federal-programs/executions/${syncLogId}/retry`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({
                                reason: trimmedReason
                            })
                        });
                        const data = await res.json();
                        toast(data.message, data.ok);

                        if (data.ok && data.execution) {
                            renderSyncExecution(municipalityId, data.execution);

                            if (data.execution.is_busy) {
                                startSyncPolling(municipalityId);
                            }

                            if (shouldReload) {
                                setTimeout(() => window.location.reload(), 1200);
                            }
                        }
                    } catch (e) {
                        toast('Erro ao reenfileirar: ' + e.message, false);
                    }
                }

                function retryLatestExecution(id, name) {
                    const retryBtn = document.getElementById(`btn-retry-sync-${id}`);
                    const executionId = retryBtn?.dataset?.executionId;

                    if (!executionId) {
                        toast('Nenhuma execução disponível para reenfileirar.', false);
                        return;
                    }

                    retryExecutionByLog(executionId, id, name, false);
                }

                async function reconcileExecutions() {
                    const reason = prompt('Motivo da reconciliação manual:',
                        'Encerrar execuções stale identificadas no monitor');
                    if (reason === null) return;

                    const trimmedReason = reason.trim();
                    if (!trimmedReason) {
                        toast('Informe um motivo para registrar a auditoria da reconciliação.', false);
                        return;
                    }

                    if (!confirm('Encerrar agora execuções stale do Radar de Recursos?')) return;

                    try {
                        const res = await fetch('/admin/federal-programs/executions/reconcile', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({
                                reason: trimmedReason
                            })
                        });
                        const data = await res.json();
                        toast(data.message, data.ok);

                        if (data.ok) {
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    } catch (e) {
                        toast('Erro ao reconciliar execuções: ' + e.message, false);
                    }
                }

                async function retryEligibleExecutions() {
                    const reason = prompt('Motivo do reenfileiramento em lote:',
                        'Reprocessar execuções elegíveis após verificação operacional');
                    if (reason === null) return;

                    const trimmedReason = reason.trim();
                    if (!trimmedReason) {
                        toast('Informe um motivo para registrar a auditoria do reenfileiramento em lote.', false);
                        return;
                    }

                    if (!confirm('Reenfileirar em lote todas as execuções elegíveis do Radar?')) return;

                    try {
                        const res = await fetch('/admin/federal-programs/executions/retry-eligible', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({
                                reason: trimmedReason
                            })
                        });
                        const data = await res.json();
                        toast(data.message, data.ok);

                        if (data.ok && Array.isArray(data.municipality_ids)) {
                            data.municipality_ids.forEach(id => startSyncPolling(id));
                        }

                        if (data.ok) {
                            setTimeout(() => window.location.reload(), 1200);
                        }
                    } catch (e) {
                        toast('Erro ao reenfileirar em lote: ' + e.message, false);
                    }
                }

                async function sendSnapshotEmail(period) {
                    const label = period === 'weekly' ? 'semanal' : 'diário';

                    if (!confirm(`Enviar agora o snapshot ${label} do Radar para o time interno?`)) return;

                    try {
                        const res = await fetch('/admin/federal-programs/snapshots/send', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({
                                period
                            })
                        });
                        const data = await res.json();
                        toast(data.message, data.ok);
                    } catch (e) {
                        toast('Erro ao enviar snapshot do Radar: ' + e.message, false);
                    }
                }

                // ── Sync geral ───────────────────────────────────────────────────────────
                async function syncAll(force) {
                    if (!confirm('Enfileirar sincronização para todos os municípios ativos?')) return;

                    try {
                        const res = await fetch('/admin/federal-programs/sync-all', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF
                            },
                            body: JSON.stringify({
                                force
                            }),
                        });
                        const data = await res.json();
                        toast(data.message, data.ok);

                        if (data.ok && Array.isArray(data.municipality_ids)) {
                            data.municipality_ids.forEach(id => startSyncPolling(id));
                        }
                    } catch (e) {
                        toast('Erro: ' + e.message, false);
                    }
                }

                // ── Limpar encerrados ────────────────────────────────────────────────────
                async function clearClosed(id, name) {
                    if (!confirm(`Remover programas encerrados de ${name}?`)) return;

                    const res = await fetch(`/admin/federal-programs/${id}/clear`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': CSRF
                        },
                    });
                    const data = await res.json();
                    toast(data.message, data.ok);
                }

                // ── Modal de programas ───────────────────────────────────────────────────
                async function showPrograms(id, name) {
                    document.getElementById('programsModal').style.display = 'block';
                    document.getElementById('modalTitle').textContent = `${name} — Radar de Recursos`;

                    const res = await fetch(`/admin/federal-programs/${id}/programs`);
                    const data = await res.json();

                    const AREA_COLORS = {
                        saude: '#fce4ec',
                        educacao: '#e3f2fd',
                        infraestrutura: '#f3e5f5',
                        saneamento: '#e0f2f1',
                        habitacao: '#fff8e1',
                        social: '#f0fdf4',
                        outros: '#f3f4f6'
                    };
                    const STATUS = {
                        published: '✅ Publicado',
                        closing_soon: '⚠️ Encerrando em breve',
                        monitoring: '🔎 Em monitoramento',
                        closed_recently: '🕘 Encerrado recentemente',
                        archived: '🗂 Arquivado',
                        pending_review: '📝 Pendente de validacao',
                        reopened: '♻️ Reaberto',
                        rejected: '⛔ Rejeitado'
                    };

                    const html = data.programs.length === 0 ?
                        '<p style="text-align:center;color:#9ca3af;padding:2rem">Nenhuma oportunidade cadastrada.</p>' :
                        data.programs.map(p => `
            <div style="border:1px solid #e5e7eb;border-radius:10px;padding:1rem;margin-bottom:.75rem">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;margin-bottom:.5rem">
                    <div>
                        <span style="font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;
                              padding:.18rem .55rem;border-radius:4px;background:${AREA_COLORS[p.area]||'#f3f4f6'}">
                            ${p.area||'outros'}
                        </span>
                        <span style="font-size:.65rem;font-weight:600;letter-spacing:.04em;
                              padding:.18rem .55rem;border-radius:4px;background:#eef2ff;color:#3730a3;margin-left:.35rem">
                            ${p.source_name || p.source_key || p.source_platform || 'fonte não informada'}
                        </span>
                        <div style="font-size:.9rem;font-weight:500;color:#111;margin-top:.4rem">${p.program_name}</div>
                        <div style="font-size:.75rem;color:#9ca3af">${p.ministry||''}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:.85rem;font-weight:700;color:#b8902a">${Math.round((p.match_score||0)*100)}%</div>
                        <div style="font-size:.7rem;color:#9ca3af">compatível</div>
                    </div>
                </div>
                <div style="font-size:.78rem;color:#6b7280;margin-bottom:.5rem">${p.match_reason||''}</div>
                <div style="display:flex;align-items:center;gap:1rem">
                    <span style="font-size:.75rem">${STATUS[p.status]||p.status}</span>
                    ${p.max_value ? `<span style="font-size:.75rem;color:#374151">R$ ${Number(p.max_value).toLocaleString('pt-BR')}</span>` : ''}
                    ${p.deadline ? `<span style="font-size:.75rem;color:#6b7280">Prazo: ${new Date(p.deadline).toLocaleDateString('pt-BR')}</span>` : ''}
                    ${p.source_url ? `<a href="${p.source_url}" target="_blank" style="font-size:.75rem;color:#1a5fa8;margin-left:auto">Edital ↗</a>` : ''}
                </div>
            </div>`).join('');

                    document.getElementById('modalBody').innerHTML = html;
                }

                function closeModal() {
                    document.getElementById('programsModal').style.display = 'none';
                }

                document.getElementById('programsModal').addEventListener('click', function(e) {
                    if (e.target === this) closeModal();
                });

                initialBusyMunicipalityIds.forEach(id => startSyncPolling(id));
            </script>
        @endpush
