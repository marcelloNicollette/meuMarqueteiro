@extends('layouts.admin')
@section('title', 'Radar de Recursos - Admin')
@section('content')
    @php
        $statusOptions = [
            'all' => 'Todos os status',
            'queued' => 'Na fila',
            'running' => 'Em execucao',
            'success' => 'Concluido',
            'failed' => 'Falhou',
        ];
    @endphp

    <div style="padding:2rem;max-width:1280px;margin:0 auto">
        <div
            style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:1.5rem">
            <div>
                <h1 style="margin:0;font-size:1.45rem;font-weight:700;color:#111827">Radar de Recursos</h1>
                <p style="margin:.35rem 0 0;font-size:.88rem;line-height:1.6;color:#6b7280;max-width:760px">
                    Painel enxuto do Radar alinhado ao documento funcional: fontes monitoradas, sincronizacao por municipio,
                    fila de curadoria e historico recente de coleta.
                </p>
            </div>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap">
                <a href="{{ route('admin.settings.integrations') }}"
                    style="padding:.68rem 1rem;border:1px solid #d1d5db;border-radius:10px;background:#fff;color:#374151;text-decoration:none;font-size:.84rem;font-weight:600">
                    Configurar integracoes
                </a>
                <button type="button" onclick="syncAllMunicipalities()"
                    style="padding:.68rem 1rem;border:none;border-radius:10px;background:#111827;color:#fff;font-size:.84rem;font-weight:700;cursor:pointer">
                    Sincronizar municipios ativos
                </button>
            </div>
        </div>

        <div id="radar-toast"
            style="display:none;margin-bottom:1rem;padding:.85rem 1rem;border-radius:10px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-size:.84rem;font-weight:600">
        </div>

        <nav aria-label="Navegacao da pagina"
            style="position:sticky;top:1rem;z-index:20;margin-bottom:1.25rem;padding:.8rem .9rem;border:1px solid #e5e7eb;border-radius:14px;background:rgba(255,255,255,.96);backdrop-filter:blur(10px);display:flex;gap:.6rem;flex-wrap:wrap;box-shadow:0 10px 30px rgba(15,23,42,.06)">
            <a href="#municipios-sync"
                style="display:inline-flex;align-items:center;padding:.55rem .85rem;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                Municipios e sync
            </a>
            <a href="#curadoria-resumo"
                style="display:inline-flex;align-items:center;padding:.55rem .85rem;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                Curadoria resumida
            </a>
            <a href="#fila-curadoria"
                style="display:inline-flex;align-items:center;padding:.55rem .85rem;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                Fila de curadoria
            </a>
            <a href="#fontes-monitoradas"
                style="display:inline-flex;align-items:center;padding:.55rem .85rem;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                Fontes monitoradas
            </a>
            <a href="#historico-recente"
                style="display:inline-flex;align-items:center;padding:.55rem .85rem;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                Historico recente
            </a>
        </nav>

        @if (session('status'))
            <div
                style="margin-bottom:1rem;padding:.85rem 1rem;border-radius:10px;border:1px solid #a7f3d0;background:#ecfdf5;color:#047857;font-size:.84rem;font-weight:600">
                {{ session('status') }}
            </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.9rem;margin-bottom:1.5rem">
            @foreach ([['label' => 'Oportunidades monitoradas', 'value' => $stats['total'], 'color' => '#111827'], ['label' => 'Publicadas', 'value' => $stats['published'], 'color' => '#1d4ed8'], ['label' => 'Encerrando em breve', 'value' => $stats['closing_soon'], 'color' => '#c2410c'], ['label' => 'Encerradas visiveis', 'value' => $stats['closed_recently'], 'color' => '#6b7280']] as $card)
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:1rem 1.05rem">
                    <div style="font-size:1.35rem;font-weight:800;color:{{ $card['color'] }}">{{ $card['value'] }}</div>
                    <div
                        style="margin-top:.2rem;font-size:.74rem;color:#9ca3af;letter-spacing:.04em;text-transform:uppercase">
                        {{ $card['label'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:1rem;margin-bottom:1.5rem">
            <section id="municipios-sync"
                style="scroll-margin-top:6.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
                <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                    <h2 style="margin:0;font-size:1rem;font-weight:700;color:#111827">Municipios e sincronizacao</h2>
                    <p style="margin:.3rem 0 0;font-size:.8rem;line-height:1.6;color:#6b7280">
                        Dispare a coleta por municipio e consulte rapidamente quantas oportunidades o radar ja montou para
                        cada prefeitura.
                    </p>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Municipio</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Radar atual</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Ultima sync</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Fila</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:right;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($municipalities as $municipality)
                                @php
                                    $execution = $syncExecutions[$municipality['id']] ?? null;
                                    $programStat = $programStats->get($municipality['id']);
                                    $isBusy = $busyMunicipalityIds->contains($municipality['id']);
                                @endphp
                                <tr style="border-bottom:1px solid #f3f4f6">
                                    <td style="padding:.9rem 1rem">
                                        <div style="font-size:.88rem;font-weight:700;color:#111827">
                                            {{ $municipality['name'] }}</div>
                                        <div style="font-size:.76rem;color:#9ca3af">
                                            {{ $municipality['state_code'] ?: 'UF' }}</div>
                                    </td>
                                    <td style="padding:.9rem 1rem;font-size:.8rem;color:#374151">
                                        <div>
                                            <strong>{{ (int) ($programStat->total ?? $municipality['federal_programs_count']) }}</strong>
                                            oportunidade(s)
                                        </div>
                                        <div style="margin-top:.15rem;color:#6b7280">
                                            {{ (int) ($programStat->active_count ?? 0) }} ativa(s)</div>
                                    </td>
                                    <td style="padding:.9rem 1rem;font-size:.8rem;color:#374151">
                                        {{ $municipality['data_last_synced_at_human'] ?? 'Nunca sincronizado' }}
                                    </td>
                                    <td style="padding:.9rem 1rem;font-size:.8rem;color:#374151">
                                        @if ($execution)
                                            <span
                                                style="display:inline-flex;align-items:center;padding:.25rem .55rem;border-radius:999px;background:#f3f4f6;color:#374151;font-weight:700">
                                                {{ $execution['status_label'] ?? 'Processando' }}
                                            </span>
                                        @else
                                            <span style="color:#9ca3af">Sem execucao recente</span>
                                        @endif
                                    </td>
                                    <td style="padding:.9rem 1rem;text-align:right">
                                        <div style="display:flex;justify-content:flex-end;gap:.45rem;flex-wrap:wrap">
                                            <button type="button"
                                                onclick="showMunicipalityPrograms({{ $municipality['id'] }}, @json($municipality['name']))"
                                                style="padding:.5rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font-size:.76rem;font-weight:700;cursor:pointer">
                                                Ver radar
                                            </button>
                                            <button type="button"
                                                onclick="syncMunicipality({{ $municipality['id'] }}, @json($municipality['name']))"
                                                {{ $isBusy ? 'disabled' : '' }}
                                                style="padding:.5rem .7rem;border:none;border-radius:8px;background:{{ $isBusy ? '#d1d5db' : '#111827' }};color:#fff;font-size:.76rem;font-weight:700;cursor:{{ $isBusy ? 'not-allowed' : 'pointer' }}">
                                                {{ $isBusy ? 'Em fila' : 'Sincronizar' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="curadoria-resumo"
                style="scroll-margin-top:6.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
                <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                    <h2 style="margin:0;font-size:1rem;font-weight:700;color:#111827">Curadoria resumida</h2>
                    <p style="margin:.3rem 0 0;font-size:.8rem;line-height:1.6;color:#6b7280">
                        O documento pede uma fila humana para validar e publicar oportunidades. Aqui ficam apenas os sinais
                        centrais dessa operacao.
                    </p>
                </div>
                <div style="padding:1rem 1.1rem;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem">
                    @foreach ([['label' => 'Na fila', 'value' => $curationSummary['pending'] ?? 0, 'color' => '#111827'], ['label' => 'Em revisao', 'value' => $curationSummary['in_review'] ?? 0, 'color' => '#1d4ed8'], ['label' => 'Aprovadas', 'value' => $curationSummary['approved'] ?? 0, 'color' => '#047857'], ['label' => 'Publicadas', 'value' => $curationSummary['published'] ?? 0, 'color' => '#7c3aed']] as $card)
                        <div style="border:1px solid #e5e7eb;border-radius:12px;padding:.9rem .95rem;background:#fcfcfd">
                            <div style="font-size:1.1rem;font-weight:800;color:{{ $card['color'] }}">{{ $card['value'] }}
                            </div>
                            <div style="margin-top:.15rem;font-size:.72rem;color:#9ca3af;text-transform:uppercase">
                                {{ $card['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div style="padding:0 1.1rem 1rem;font-size:.79rem;line-height:1.7;color:#6b7280">
                    Fontes ativas: <strong style="color:#111827">{{ $sourceCatalogSummary['active'] }}</strong><br>
                    Fontes com curadoria humana: <strong
                        style="color:#111827">{{ $sourceCatalogSummary['requires_curation'] }}</strong><br>
                    Ultima sincronizacao geral: <strong
                        style="color:#111827">{{ $stats['last_sync'] ? \Carbon\Carbon::parse($stats['last_sync'])->diffForHumans() : 'nunca' }}</strong>
                </div>
            </section>
        </div>

        <section id="fila-curadoria"
            style="scroll-margin-top:6.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:1.5rem">
            <div
                style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                <div>
                    <h2 style="margin:0;font-size:1rem;font-weight:700;color:#111827">Fila de curadoria</h2>
                    <p style="margin:.3rem 0 0;font-size:.8rem;line-height:1.6;color:#6b7280">
                        Validacao humana das oportunidades coletadas antes de publicar no radar do municipio.
                    </p>
                </div>
                <form method="GET" action="{{ route('admin.federal-programs.index') }}"
                    style="display:flex;gap:.6rem;align-items:end;flex-wrap:wrap">
                    <div>
                        <label for="curation_queue_status"
                            style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.25rem">Fila</label>
                        <select id="curation_queue_status" name="curation_queue_status"
                            style="padding:.58rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.8rem;color:#374151">
                            @foreach (['all' => 'Todas', 'pending' => 'Na fila', 'in_review' => 'Em revisao', 'approved' => 'Aprovadas', 'published' => 'Publicadas', 'rejected' => 'Rejeitadas'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ ($curationFilters['queue_status'] ?? 'all') === $value ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="curation_priority"
                            style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.25rem">Prioridade</label>
                        <select id="curation_priority" name="curation_priority"
                            style="padding:.58rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.8rem;color:#374151">
                            @foreach (['all' => 'Todas', 'urgent' => 'Urgente', 'high' => 'Alta', 'normal' => 'Normal', 'low' => 'Baixa'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ ($curationFilters['priority'] ?? 'all') === $value ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="curation_search"
                            style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.25rem">Busca</label>
                        <input id="curation_search" name="curation_search" value="{{ $curationFilters['search'] ?? '' }}"
                            style="padding:.58rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.8rem;color:#374151"
                            placeholder="Titulo, municipio ou fonte">
                    </div>
                    <button type="submit"
                        style="padding:.6rem .85rem;border:none;border-radius:8px;background:#111827;color:#fff;font-size:.8rem;font-weight:700;cursor:pointer">
                        Filtrar
                    </button>
                </form>
            </div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                Oportunidade</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                Municipio</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                Fila</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                Responsavel</th>
                            <th
                                style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($curationQueue as $entry)
                            <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                <td style="padding:.95rem 1rem;min-width:320px">
                                    <div style="font-size:.88rem;font-weight:700;color:#111827">{{ $entry['title'] }}
                                    </div>
                                    <div style="margin-top:.2rem;font-size:.78rem;line-height:1.6;color:#6b7280">
                                        {{ $entry['source_name'] }} · {{ $entry['pipeline_group_label'] }} ·
                                        compatibilidade {{ round(($entry['match_score'] ?? 0) * 100) }}%
                                    </div>
                                    @if (!empty($entry['summary']))
                                        <div style="margin-top:.35rem;font-size:.78rem;line-height:1.6;color:#4b5563">
                                            {{ \Illuminate\Support\Str::limit($entry['summary'], 160) }}
                                        </div>
                                    @endif
                                    @if (!empty($entry['source_url']))
                                        <a href="{{ $entry['source_url'] }}" target="_blank"
                                            style="display:inline-block;margin-top:.45rem;font-size:.76rem;color:#1d4ed8;text-decoration:none;font-weight:700">
                                            Abrir edital
                                        </a>
                                    @endif
                                </td>
                                <td style="padding:.95rem 1rem;font-size:.8rem;color:#374151;min-width:160px">
                                    <div>{{ $entry['municipality_name'] }}</div>
                                    <div style="margin-top:.25rem;color:#9ca3af">{{ $entry['sla_label'] }}</div>
                                </td>
                                <td style="padding:.95rem 1rem;font-size:.8rem;color:#374151;min-width:180px">
                                    <div>
                                        <span
                                            style="display:inline-flex;align-items:center;padding:.25rem .55rem;border-radius:999px;background:#f3f4f6;color:#374151;font-weight:700">
                                            {{ $entry['queue_status_label'] }}
                                        </span>
                                    </div>
                                    <div style="margin-top:.35rem;color:#6b7280">{{ $entry['priority_label'] }}</div>
                                    <div style="margin-top:.15rem;color:#9ca3af">Entrada
                                        {{ $entry['entered_queue_at_human'] ?? 'agora' }}</div>
                                </td>
                                <td style="padding:.95rem 1rem;min-width:220px">
                                    <form method="POST"
                                        action="{{ route('admin.federal-programs.curation.assign', $entry['id']) }}"
                                        style="display:grid;gap:.45rem">
                                        @csrf
                                        <select name="assigned_to_user_id"
                                            style="padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.78rem;color:#374151">
                                            <option value="">Sem responsavel</option>
                                            @foreach ($reviewers as $reviewer)
                                                <option value="{{ $reviewer->id }}"
                                                    {{ (int) ($entry['assigned_to_user_id'] ?? 0) === (int) $reviewer->id ? 'selected' : '' }}>
                                                    {{ $reviewer->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="priority"
                                            style="padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.78rem;color:#374151">
                                            @foreach (['low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ ($entry['priority'] ?? 'normal') === $value ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                            style="padding:.55rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font-size:.76rem;font-weight:700;cursor:pointer">
                                            Atualizar responsavel
                                        </button>
                                    </form>
                                </td>
                                <td style="padding:.95rem 1rem;min-width:240px">
                                    <form method="POST"
                                        action="{{ route('admin.federal-programs.curation.transition', $entry['id']) }}"
                                        style="display:grid;gap:.45rem">
                                        @csrf
                                        <select name="action"
                                            style="padding:.55rem .65rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.78rem;color:#374151">
                                            <option value="start_review">Iniciar revisao</option>
                                            <option value="approve">Aprovar</option>
                                            <option value="publish">Publicar</option>
                                            <option value="reject">Rejeitar</option>
                                        </select>
                                        <textarea name="decision_notes" rows="3"
                                            style="padding:.6rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.76rem;color:#374151;resize:vertical"
                                            placeholder="Observacoes de curadoria"></textarea>
                                        <button type="submit"
                                            style="padding:.6rem .8rem;border:none;border-radius:8px;background:#111827;color:#fff;font-size:.76rem;font-weight:700;cursor:pointer">
                                            Aplicar transicao
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    style="padding:1.1rem 1rem;text-align:center;font-size:.84rem;color:#6b7280">
                                    Nenhum item encontrado na fila de curadoria com os filtros atuais.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (method_exists($curationQueue, 'hasPages') && $curationQueue->hasPages())
                <div
                    style="padding:1rem 1.1rem;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:.85rem;flex-wrap:wrap;background:#fcfcfd">
                    <div style="font-size:.8rem;color:#6b7280">
                        Mostrando {{ $curationQueue->firstItem() }}-{{ $curationQueue->lastItem() }} de
                        {{ $curationQueue->total() }} itens da fila
                    </div>
                    <nav aria-label="Paginacao da fila de curadoria"
                        style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
                        @if ($curationQueue->onFirstPage())
                            <span
                                style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;color:#9ca3af;font-size:.78rem;font-weight:700">
                                Anterior
                            </span>
                        @else
                            <a href="{{ $curationQueue->previousPageUrl() }}"
                                style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                                Anterior
                            </a>
                        @endif

                        @foreach ($curationQueue->linkCollection() as $link)
                            @continue(($link['label'] ?? '') === '&laquo; Previous' || ($link['label'] ?? '') === 'Next &raquo;')

                            @if (($link['label'] ?? '') === '...')
                                <span
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .7rem;border-radius:10px;border:1px dashed #d1d5db;background:#fff;color:#9ca3af;font-size:.78rem;font-weight:700">
                                    ...
                                </span>
                            @elseif ($link['active'])
                                <span
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .7rem;border-radius:10px;border:1px solid #111827;background:#111827;color:#fff;font-size:.78rem;font-weight:700">
                                    {{ $link['label'] }}
                                </span>
                            @else
                                <a href="{{ $link['url'] }}"
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .7rem;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                                    {{ $link['label'] }}
                                </a>
                            @endif
                        @endforeach

                        @if ($curationQueue->hasMorePages())
                            <a href="{{ $curationQueue->nextPageUrl() }}"
                                style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                                Proxima
                            </a>
                        @else
                            <span
                                style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;color:#9ca3af;font-size:.78rem;font-weight:700">
                                Proxima
                            </span>
                        @endif
                    </nav>
                </div>
            @endif
        </section>

        <div style="display:grid;grid-template-columns:minmax(0,.95fr) minmax(0,1.05fr);gap:1rem">
            <section id="fontes-monitoradas"
                style="scroll-margin-top:6.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
                <div style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                    <h2 style="margin:0;font-size:1rem;font-weight:700;color:#111827">Fontes monitoradas</h2>
                    <p style="margin:.3rem 0 0;font-size:.8rem;line-height:1.6;color:#6b7280">
                        Catalogo operacional das fontes previstas no Radar: escopo, captura, frequencia e necessidade de
                        curadoria.
                    </p>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Fonte</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Escopo</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Captura</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Atualizacao</th>
                                <th
                                    style="padding:.8rem 1rem;text-align:left;font-size:.72rem;color:#6b7280;text-transform:uppercase">
                                    Base atual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sourceCatalog as $source)
                                <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                                    <td style="padding:.9rem 1rem;min-width:220px">
                                        <div style="font-size:.86rem;font-weight:700;color:#111827">{{ $source['name'] }}
                                        </div>
                                        <div style="margin-top:.2rem;font-size:.76rem;color:#6b7280">
                                            {{ $source['pipeline_group_label'] }}</div>
                                        <div
                                            style="margin-top:.25rem;font-size:.74rem;color:{{ $source['is_active'] ? '#047857' : '#9ca3af' }}">
                                            {{ $source['is_active'] ? 'Fonte ativa' : 'Fonte inativa' }}
                                        </div>
                                    </td>
                                    <td style="padding:.9rem 1rem;font-size:.79rem;color:#374151">
                                        {{ $source['resource_scope'] ?: 'Nao informado' }}</td>
                                    <td style="padding:.9rem 1rem;font-size:.79rem;color:#374151">
                                        {{ $source['capture_method'] ?: 'Nao informado' }}<br>
                                        <span
                                            style="color:#9ca3af">{{ $source['requires_human_curation'] ? 'Com curadoria humana' : 'Fluxo automatizado' }}</span>
                                    </td>
                                    <td style="padding:.9rem 1rem;font-size:.79rem;color:#374151">
                                        {{ $source['refresh_frequency'] ?: 'Nao informado' }}</td>
                                    <td style="padding:.9rem 1rem;font-size:.79rem;color:#374151">
                                        {{ $source['opportunities_count'] }} oportunidade(s)<br>
                                        <span style="color:#9ca3af">{{ $source['curation_queue_count'] }} na fila</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="historico-recente"
                style="scroll-margin-top:6.5rem;background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
                <div
                    style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                    <div>
                        <h2 style="margin:0;font-size:1rem;font-weight:700;color:#111827">Historico recente</h2>
                        <p style="margin:.3rem 0 0;font-size:.8rem;line-height:1.6;color:#6b7280">
                            Ultimas execucoes de sincronizacao do radar por municipio.
                        </p>
                    </div>
                    <form method="GET" action="{{ route('admin.federal-programs.index') }}"
                        style="display:flex;gap:.6rem;align-items:end;flex-wrap:wrap">
                        <div>
                            <label for="status"
                                style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.25rem">Status</label>
                            <select id="status" name="status"
                                style="padding:.58rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.8rem;color:#374151">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ ($filters['status'] ?? 'all') === $value ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="municipality_id"
                                style="display:block;font-size:.7rem;color:#6b7280;margin-bottom:.25rem">Municipio</label>
                            <select id="municipality_id" name="municipality_id"
                                style="padding:.58rem .7rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.8rem;color:#374151">
                                <option value="">Todos</option>
                                @foreach ($municipalities as $municipality)
                                    <option value="{{ $municipality['id'] }}"
                                        {{ (string) ($filters['municipality_id'] ?? '') === (string) $municipality['id'] ? 'selected' : '' }}>
                                        {{ $municipality['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                            style="padding:.6rem .85rem;border:none;border-radius:8px;background:#111827;color:#fff;font-size:.8rem;font-weight:700;cursor:pointer">
                            Filtrar
                        </button>
                    </form>
                </div>
                <div style="padding:1rem 1.1rem;display:grid;gap:.8rem">
                    @forelse ($history as $item)
                        <div style="border:1px solid #e5e7eb;border-radius:12px;padding:.9rem 1rem;background:#fcfcfd">
                            <div
                                style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap">
                                <div>
                                    <div style="font-size:.86rem;font-weight:700;color:#111827">
                                        {{ $item['municipality_name'] }}</div>
                                    <div style="margin-top:.2rem;font-size:.78rem;color:#6b7280">
                                        {{ $item['status_label'] }} · {{ $item['records_fetched'] }} coletados ·
                                        {{ $item['records_saved'] }} salvos
                                    </div>
                                </div>
                                <div style="font-size:.76rem;color:#9ca3af;text-align:right">
                                    {{ $item['updated_at_human'] ?? 'agora' }}<br>
                                    {{ $item['operator_name'] ?: 'sistema' }}
                                </div>
                            </div>
                            @if (!empty($item['error_message']))
                                <div style="margin-top:.55rem;font-size:.77rem;line-height:1.6;color:#b91c1c">
                                    {{ $item['error_message'] }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div style="padding:.6rem 0;font-size:.84rem;color:#6b7280">Sem execucoes recentes para os filtros
                            atuais.</div>
                    @endforelse
                </div>
                @if (method_exists($history, 'hasPages') && $history->hasPages())
                    <div
                        style="padding:1rem 1.1rem;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:.85rem;flex-wrap:wrap;background:#fcfcfd">
                        <div style="font-size:.8rem;color:#6b7280">
                            Mostrando {{ $history->firstItem() }}-{{ $history->lastItem() }} de
                            {{ $history->total() }} execucoes
                        </div>
                        <nav aria-label="Paginacao do historico recente"
                            style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
                            @if ($history->onFirstPage())
                                <span
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;color:#9ca3af;font-size:.78rem;font-weight:700">
                                    Anterior
                                </span>
                            @else
                                <a href="{{ $history->previousPageUrl() }}"
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                                    Anterior
                                </a>
                            @endif

                            @foreach ($history->linkCollection() as $link)
                                @continue(($link['label'] ?? '') === '&laquo; Previous' || ($link['label'] ?? '') === 'Next &raquo;')

                                @if (($link['label'] ?? '') === '...')
                                    <span
                                        style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .7rem;border-radius:10px;border:1px dashed #d1d5db;background:#fff;color:#9ca3af;font-size:.78rem;font-weight:700">
                                        ...
                                    </span>
                                @elseif ($link['active'])
                                    <span
                                        style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .7rem;border-radius:10px;border:1px solid #111827;background:#111827;color:#fff;font-size:.78rem;font-weight:700">
                                        {{ $link['label'] }}
                                    </span>
                                @else
                                    <a href="{{ $link['url'] }}"
                                        style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .7rem;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                                        {{ $link['label'] }}
                                    </a>
                                @endif
                            @endforeach

                            @if ($history->hasMorePages())
                                <a href="{{ $history->nextPageUrl() }}"
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#374151;text-decoration:none;font-size:.78rem;font-weight:700">
                                    Proxima
                                </a>
                            @else
                                <span
                                    style="display:inline-flex;align-items:center;justify-content:center;min-width:2.2rem;height:2.2rem;padding:0 .8rem;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;color:#9ca3af;font-size:.78rem;font-weight:700">
                                    Proxima
                                </span>
                            @endif
                        </nav>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <div id="municipality-programs-modal"
        style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:60;padding:2rem;overflow:auto">
        <div
            style="max-width:920px;margin:0 auto;background:#fff;border-radius:16px;border:1px solid #e5e7eb;overflow:hidden">
            <div
                style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb">
                <div>
                    <h3 id="municipality-programs-title" style="margin:0;font-size:1rem;font-weight:700;color:#111827">
                        Radar do municipio</h3>
                    <p id="municipality-programs-subtitle" style="margin:.3rem 0 0;font-size:.8rem;color:#6b7280">
                        Carregando oportunidades.</p>
                </div>
                <button type="button" onclick="closeMunicipalityPrograms()"
                    style="padding:.45rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font-size:.78rem;font-weight:700;cursor:pointer">
                    Fechar
                </button>
            </div>
            <div id="municipality-programs-body" style="padding:1rem 1.1rem"></div>
        </div>
    </div>

    <script>
        const radarSyncAllUrl = @json(route('admin.federal-programs.sync-all'));
        const radarSyncUrlTemplate = @json(route('admin.federal-programs.sync', ['municipality' => '__ID__']));
        const radarProgramsUrlTemplate = @json(route('admin.federal-programs.programs', ['municipality' => '__ID__']));
        const csrfToken = @json(csrf_token());

        function showToast(message, tone = 'info') {
            const toast = document.getElementById('radar-toast');
            if (!toast) return;

            toast.textContent = message;
            toast.style.display = 'block';
            toast.style.background = tone === 'error' ? '#fef2f2' : '#eff6ff';
            toast.style.borderColor = tone === 'error' ? '#fecaca' : '#bfdbfe';
            toast.style.color = tone === 'error' ? '#b91c1c' : '#1d4ed8';

            window.clearTimeout(window.__radarToastTimeout);
            window.__radarToastTimeout = window.setTimeout(() => {
                toast.style.display = 'none';
            }, 5000);
        }

        function postJson(url, payload = {}) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            }).then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Falha na requisicao.');
                }
                return data;
            });
        }

        function syncAllMunicipalities() {
            postJson(radarSyncAllUrl)
                .then(data => {
                    showToast(data.message || 'Sincronizacao enviada para a fila.');
                    window.setTimeout(() => window.location.reload(), 1200);
                })
                .catch(error => showToast(error.message, 'error'));
        }

        function syncMunicipality(id, name) {
            const url = radarSyncUrlTemplate.replace('__ID__', String(id));
            postJson(url)
                .then(data => {
                    showToast(data.message || `Sincronizacao enviada para ${name}.`);
                    window.setTimeout(() => window.location.reload(), 1200);
                })
                .catch(error => showToast(error.message, 'error'));
        }

        function closeMunicipalityPrograms() {
            const modal = document.getElementById('municipality-programs-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function showMunicipalityPrograms(id, name) {
            const modal = document.getElementById('municipality-programs-modal');
            const title = document.getElementById('municipality-programs-title');
            const subtitle = document.getElementById('municipality-programs-subtitle');
            const body = document.getElementById('municipality-programs-body');
            const url = radarProgramsUrlTemplate.replace('__ID__', String(id));

            title.textContent = `Radar de ${name}`;
            subtitle.textContent = 'Carregando oportunidades publicadas e monitoradas para este municipio.';
            body.innerHTML = '<div style="font-size:.84rem;color:#6b7280">Carregando...</div>';
            modal.style.display = 'block';

            fetch(url, {
                    headers: {
                        Accept: 'application/json'
                    }
                })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data.message || 'Falha ao carregar o radar do municipio.');
                    }
                    return data;
                })
                .then(data => {
                    const programs = Array.isArray(data.programs) ? data.programs : [];
                    subtitle.textContent = `${programs.length} oportunidade(s) encontradas no radar.`;

                    if (programs.length === 0) {
                        body.innerHTML =
                            '<div style="font-size:.84rem;color:#6b7280">Nenhuma oportunidade encontrada para este municipio.</div>';
                        return;
                    }

                    body.innerHTML = programs.map(program => {
                        const source = program.source_name || program.ministry || 'Fonte nao informada';
                        const title = program.program_name || program.title || 'Oportunidade sem titulo';
                        const status = program.status_label || program.status || 'Sem status';
                        const score = typeof program.match_score === 'number' ?
                            `${Math.round(program.match_score * 100)}%` : 'Nao informado';
                        const url = program.source_url || '';
                        const deadline = program.deadline ? new Date(program.deadline).toLocaleDateString(
                            'pt-BR') : 'Nao informado';
                        return `
                            <div style="border:1px solid #e5e7eb;border-radius:12px;padding:.9rem 1rem;background:#fcfcfd;margin-bottom:.75rem">
                                <div style="font-size:.86rem;font-weight:700;color:#111827">${title}</div>
                                <div style="margin-top:.2rem;font-size:.78rem;color:#6b7280">${source} · ${status} · compatibilidade ${score}</div>
                                <div style="margin-top:.35rem;font-size:.78rem;color:#4b5563">Prazo: ${deadline}</div>
                                ${url ? `<a href="${url}" target="_blank" style="display:inline-block;margin-top:.45rem;font-size:.76rem;color:#1d4ed8;text-decoration:none;font-weight:700">Abrir edital</a>` : ''}
                            </div>
                        `;
                    }).join('');
                })
                .catch(error => {
                    body.innerHTML = `<div style="font-size:.84rem;color:#b91c1c">${error.message}</div>`;
                });
        }

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeMunicipalityPrograms();
            }
        });
    </script>
@endsection
