@extends('layouts.admin')
@section('title', 'Central de Alertas')
@section('content')
    <div style="padding:2rem">
        @if (session('success'))
            <div
                style="background:#d1fae5;border:1px solid #6ee7b7;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#065f46">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                style="background:#fef2f2;border:1px solid #fca5a5;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#991b1b">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                style="background:#fff7ed;border:1px solid #fdba74;padding:1rem;border-radius:8px;margin-bottom:1rem;color:#9a3412">
                {{ $errors->first() }}
            </div>
        @endif

        <div
            style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap">
            <div>
                <h1 style="font-size:1.45rem;font-weight:700">Central Executiva de Alertas</h1>
                <p style="font-size:.88rem;color:#6b7280;margin-top:.35rem;max-width:760px">
                    Monitora perdas de cobertura em Menções, Pra hoje e Configurações, com leitura executiva por
                    município, reincidência, SLA, snapshots gerenciais e ações operacionais.
                </p>
            </div>
            <div style="display:flex;gap:.55rem;flex-wrap:wrap">
                <a href="{{ route('admin.coverage-alerts.export.csv', request()->query()) }}"
                    style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;color:#374151;text-decoration:none">
                    Exportar CSV
                </a>
                <a href="{{ route('admin.coverage-alerts.export.xlsx', request()->query()) }}"
                    style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;color:#374151;text-decoration:none">
                    Exportar XLSX
                </a>
                <a href="{{ route('admin.coverage-alerts.ranking.export.csv') }}"
                    style="padding:.6rem 1rem;border:1px solid #bfdbfe;border-radius:8px;font-size:.84rem;color:#1d4ed8;text-decoration:none;background:#eff6ff">
                    Ranking CSV
                </a>
                <a href="{{ route('admin.coverage-alerts.ranking.export.xlsx') }}"
                    style="padding:.6rem 1rem;border:1px solid #bfdbfe;border-radius:8px;font-size:.84rem;color:#1d4ed8;text-decoration:none;background:#eff6ff">
                    Ranking XLSX
                </a>
                <a href="{{ route('admin.coverage-alerts.ranking.export.pdf') }}"
                    style="padding:.6rem 1rem;border:1px solid #bfdbfe;border-radius:8px;font-size:.84rem;color:#1d4ed8;text-decoration:none;background:#eff6ff">
                    Ranking PDF
                </a>
                <a href="{{ route('admin.settings.index') }}"
                    style="padding:.6rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;color:#374151;text-decoration:none">
                    Abrir Configurações
                </a>
            </div>
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem">
            <a href="{{ route('admin.coverage-alerts.index', ['preset' => 'critical_active']) }}"
                style="padding:.55rem .9rem;border-radius:999px;text-decoration:none;font-size:.82rem;font-weight:600;border:1px solid {{ $filters['preset'] === 'critical_active' ? '#b91c1c' : '#d1d5db' }};background:{{ $filters['preset'] === 'critical_active' ? '#fef2f2' : '#fff' }};color:{{ $filters['preset'] === 'critical_active' ? '#b91c1c' : '#374151' }}">
                Ativos críticos
            </a>
            <a href="{{ route('admin.coverage-alerts.index', ['status' => 'active']) }}"
                style="padding:.55rem .9rem;border-radius:999px;text-decoration:none;font-size:.82rem;font-weight:600;border:1px solid #d1d5db;background:#fff;color:#374151">
                Somente ativos
            </a>
            <a href="{{ route('admin.coverage-alerts.index', ['status' => 'resolved']) }}"
                style="padding:.55rem .9rem;border-radius:999px;text-decoration:none;font-size:.82rem;font-weight:600;border:1px solid #d1d5db;background:#fff;color:#374151">
                Somente resolvidos
            </a>
            @foreach ($savedFilters as $filterKey => $savedFilter)
                <a href="{{ route('admin.coverage-alerts.index', ['preset' => 'saved:' . $filterKey]) }}"
                    style="padding:.55rem .9rem;border-radius:999px;text-decoration:none;font-size:.82rem;font-weight:600;border:1px solid {{ $filters['preset'] === 'saved:' . $filterKey ? '#1d4ed8' : '#d1d5db' }};background:{{ $filters['preset'] === 'saved:' . $filterKey ? '#eff6ff' : '#fff' }};color:{{ $filters['preset'] === 'saved:' . $filterKey ? '#1d4ed8' : '#374151' }}">
                    {{ $savedFilter['name'] ?? $filterKey }}
                </a>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1rem;margin-bottom:1.25rem">
            @foreach ([
            ['label' => 'Alertas ativos', 'value' => $summary['active'], 'note' => 'Precisam de ação', 'color' => '#b45309'],
            ['label' => 'SLA estourado', 'value' => $summary['sla_breaches_total'], 'note' => 'Ainda sem resolução', 'color' => '#b91c1c'],
            ['label' => 'Score médio config.', 'value' => $summary['average_configuration_score'] . '%', 'note' => 'Cobertura estrutural', 'color' => '#1d4ed8'],
            ['label' => 'Score executivo', 'value' => $summary['average_executive_score'] . '%', 'note' => 'Score composto', 'color' => '#111827'],
            ['label' => 'Municípios monitorados', 'value' => $summary['tracked_municipalities'], 'note' => 'Base ativa', 'color' => '#047857'],
            ['label' => 'Minha fila', 'value' => $summary['my_owned_alerts'], 'note' => 'Alertas assumidos por você', 'color' => '#7c3aed'],
            ['label' => 'Meu SLA owner', 'value' => $summary['my_owner_sla_breached'], 'note' => 'Assumidos fora do prazo', 'color' => '#dc2626'],
            ['label' => 'Alertas totais', 'value' => $summary['total'], 'note' => 'Histórico completo', 'color' => '#111827'],
            ['label' => 'Alta severidade', 'value' => $summary['high'], 'note' => 'Impacto crítico', 'color' => '#b91c1c'],
            ['label' => 'Resolvidos', 'value' => $summary['resolved'], 'note' => 'Cobertura restabelecida', 'color' => '#166534'],
        ] as $card)
                <div style="background:#fff;padding:1rem;border-radius:12px;border:1px solid #e5e7eb">
                    <div style="font-size:.74rem;color:#6b7280;text-transform:uppercase;font-weight:700">
                        {{ $card['label'] }}</div>
                    <div style="font-size:1.45rem;font-weight:700;margin-top:.2rem;color:{{ $card['color'] }}">
                        {{ $card['value'] }}</div>
                    <div style="font-size:.77rem;color:#6b7280;margin-top:.25rem">{{ $card['note'] }}</div>
                </div>
            @endforeach
        </div>

        <div style="display:grid;grid-template-columns:1.15fr .85fr;gap:1rem;margin-bottom:1.25rem">
            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:.9rem">
                    <div>
                        <div style="font-size:.92rem;font-weight:700;color:#111827">Painel de assinaturas e aprovação do
                            mailing</div>
                        <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Controla a liberação do próximo mailing
                            periódico e exibe a identidade institucional do PDF.</div>
                    </div>
                    <a href="{{ route('admin.settings.index') }}"
                        style="padding:.45rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.76rem;color:#374151;text-decoration:none">
                        Ajustar identidade
                    </a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem">
                    @foreach (['daily' => 'Diário', 'weekly' => 'Semanal'] as $periodKey => $periodLabel)
                        @php
                            $approval = $mailingGovernance['approval'][$periodKey] ?? [];
                            $approved = (bool) ($approval['approved'] ?? false);
                            $levelOne = $approval['level_one'] ?? [];
                            $levelTwo = $approval['level_two'] ?? [];
                            $twoLevel = (bool) ($mailingGovernance['settings']['two_level_approval'] ?? false);
                        @endphp
                        <div style="padding:.9rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.84rem;font-weight:700;color:#111827">{{ $periodLabel }}</div>
                                    <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">
                                        {{ $periodKey === 'daily' ? $mailingGovernance['settings']['daily_time'] ?? '—' : 'Dia ' . ($mailingGovernance['settings']['weekly_day'] ?? '—') . ' · ' . ($mailingGovernance['settings']['weekly_time'] ?? '—') }}
                                    </div>
                                </div>
                                <span
                                    style="padding:.22rem .55rem;border-radius:999px;background:{{ $approved ? '#ecfdf5' : '#fff7ed' }};color:{{ $approved ? '#166534' : '#b45309' }};font-size:.72rem;font-weight:700">
                                    {{ $approved ? 'Aprovado' : 'Pendente' }}
                                </span>
                            </div>
                            <div style="font-size:.76rem;color:#6b7280;line-height:1.5;margin-top:.55rem">
                                @if ($approved)
                                    {{ $approval['approved_by_name'] ?? '—' }} ·
                                    {{ $approval['approved_by_role'] ?? 'Admin' }}<br>
                                    válido até
                                    {{ \Illuminate\Support\Carbon::parse($approval['approved_until'])->format('d/m/Y H:i') }}
                                @else
                                    Aguardando liberação manual para o próximo disparo agendado.
                                @endif
                            </div>
                            <div style="display:grid;gap:.45rem;margin-top:.7rem">
                                <div
                                    style="padding:.55rem .6rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff">
                                    <div style="font-size:.72rem;font-weight:700;color:#111827">
                                        {{ $mailingGovernance['levels']['level_one'] ?? 'Nível 1' }}
                                    </div>
                                    <div style="font-size:.73rem;color:#6b7280;margin-top:.2rem">
                                        @if (
                                            !empty($levelOne['approved']) &&
                                                !empty($levelOne['approved_until']) &&
                                                \Illuminate\Support\Carbon::parse($levelOne['approved_until'])->isFuture())
                                            {{ $levelOne['approved_by_name'] ?? '—' }} ·
                                            {{ $levelOne['approved_by_role'] ?? 'Admin' }}
                                        @else
                                            Pendente
                                        @endif
                                    </div>
                                </div>
                                @if ($twoLevel)
                                    <div
                                        style="padding:.55rem .6rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff">
                                        <div style="font-size:.72rem;font-weight:700;color:#111827">
                                            {{ $mailingGovernance['levels']['level_two'] ?? 'Nível 2' }}
                                        </div>
                                        <div style="font-size:.73rem;color:#6b7280;margin-top:.2rem">
                                            @if (
                                                !empty($levelTwo['approved']) &&
                                                    !empty($levelTwo['approved_until']) &&
                                                    \Illuminate\Support\Carbon::parse($levelTwo['approved_until'])->isFuture())
                                                {{ $levelTwo['approved_by_name'] ?? '—' }} ·
                                                {{ $levelTwo['approved_by_role'] ?? 'Admin' }}
                                            @else
                                                Pendente
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.75rem">
                                <a href="{{ route('admin.coverage-alerts.mailing.preview', $periodKey) }}"
                                    style="padding:.42rem .75rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:7px;font-size:.76rem;font-weight:600;cursor:pointer;text-decoration:none">
                                    Preview
                                </a>
                                <form method="POST"
                                    action="{{ route('admin.coverage-alerts.mailing.approve', $periodKey) }}">
                                    @csrf
                                    <input type="hidden" name="level" value="level_one">
                                    <button type="submit"
                                        style="padding:.42rem .75rem;background:#111827;color:#fff;border:none;border-radius:7px;font-size:.76rem;font-weight:600;cursor:pointer">
                                        Aprovar {{ $mailingGovernance['levels']['level_one'] ?? 'Nível 1' }}
                                    </button>
                                </form>
                                @if ($twoLevel)
                                    <form method="POST"
                                        action="{{ route('admin.coverage-alerts.mailing.approve', $periodKey) }}">
                                        @csrf
                                        <input type="hidden" name="level" value="level_two">
                                        <button type="submit"
                                            style="padding:.42rem .75rem;background:#fff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:7px;font-size:.76rem;font-weight:600;cursor:pointer">
                                            Aprovar {{ $mailingGovernance['levels']['level_two'] ?? 'Nível 2' }}
                                        </button>
                                    </form>
                                @endif
                                <form method="POST"
                                    action="{{ route('admin.coverage-alerts.mailing.revoke', $periodKey) }}">
                                    @csrf
                                    <button type="submit"
                                        style="padding:.42rem .75rem;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:7px;font-size:.76rem;font-weight:600;cursor:pointer">
                                        Revogar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.25rem">Identidade institucional
                </div>
                <div style="font-size:.79rem;color:#6b7280;margin-bottom:.9rem">Assinaturas e visual aplicados no PDF
                    executivo e no mailing periódico.</div>
                <div
                    style="padding:.9rem;border-radius:10px;background:{{ $mailingGovernance['identity']['accent_color'] ?? '#111827' }};color:#fff">
                    <div style="font-size:.74rem;letter-spacing:.08em;text-transform:uppercase;opacity:.82">
                        {{ $mailingGovernance['identity']['department'] ?? 'Central Executiva' }}</div>
                    <div style="font-size:1.05rem;font-weight:700;margin-top:.3rem">
                        {{ $mailingGovernance['identity']['institution_name'] ?? 'Meu Marqueteiro' }}</div>
                    <div style="font-size:.76rem;opacity:.88;margin-top:.25rem">
                        {{ $mailingGovernance['identity']['tagline'] ?? '' }}</div>
                </div>
                <div style="display:grid;gap:.65rem;margin-top:.9rem">
                    @foreach ($mailingGovernance['signatures'] as $signature)
                        <div
                            style="display:flex;align-items:center;gap:.7rem;padding:.75rem;border:1px solid #e5e7eb;border-radius:10px;background:#fafafa">
                            <div
                                style="width:38px;height:38px;border-radius:999px;background:{{ $mailingGovernance['identity']['secondary_color'] ?? '#1d4ed8' }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:700">
                                {{ $signature['initials'] }}
                            </div>
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:#111827">
                                    {{ $signature['name'] ?: 'Assinatura não definida' }}</div>
                                <div style="font-size:.75rem;color:#6b7280">
                                    {{ $signature['role'] ?: 'Defina no painel de configurações' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:.85rem;font-size:.76rem;color:#6b7280">
                    Aprovação manual:
                    <strong>{{ $mailingGovernance['settings']['requires_approval'] ?? false ? 'obrigatória' : 'não obrigatória' }}</strong>
                </div>
            </div>
        </div>

        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem;margin-bottom:1.25rem">
            <div
                style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:.9rem">
                <div>
                    <div style="font-size:.92rem;font-weight:700;color:#111827">Fila pessoal de alertas assumidos</div>
                    <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Alertas ativos atribuídos a você, com SLA
                        do owner e urgência operacional.</div>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <span
                        style="padding:.22rem .55rem;border-radius:999px;background:#f5f3ff;color:#7c3aed;font-size:.72rem;font-weight:700">
                        {{ $myQueueSummary['total'] }} assumido(s)
                    </span>
                    <span
                        style="padding:.22rem .55rem;border-radius:999px;background:#fef2f2;color:#b91c1c;font-size:.72rem;font-weight:700">
                        {{ $myQueueSummary['breached'] }} fora do SLA
                    </span>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem">
                @forelse ($myQueue as $queueAlert)
                    @php
                        $queueWorkflow = $queueAlert->workflow_snapshot ?? [];
                        $queueSlaStatus = $queueWorkflow['owner_sla_status'] ?? 'unassigned';
                        $queueSlaColor =
                            $queueSlaStatus === 'breached'
                                ? '#b91c1c'
                                : ($queueSlaStatus === 'warning'
                                    ? '#b45309'
                                    : '#166534');
                        $queueSlaBg =
                            $queueSlaStatus === 'breached'
                                ? '#fef2f2'
                                : ($queueSlaStatus === 'warning'
                                    ? '#fff7ed'
                                    : '#ecfdf5');
                    @endphp
                    <div style="padding:.9rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                        <div style="display:flex;justify-content:space-between;gap:.65rem;align-items:flex-start">
                            <div>
                                <div style="font-size:.83rem;font-weight:700;color:#111827">
                                    {{ $queueAlert->municipality?->name ?? '—' }}</div>
                                <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">{{ $queueAlert->title }}
                                </div>
                            </div>
                            <span
                                style="padding:.2rem .5rem;border-radius:999px;background:{{ $queueSlaBg }};color:{{ $queueSlaColor }};font-size:.71rem;font-weight:700">
                                {{ $queueSlaStatus === 'breached' ? 'SLA estourado' : ($queueSlaStatus === 'warning' ? 'SLA em risco' : 'No SLA') }}
                            </span>
                        </div>
                        <div style="font-size:.75rem;color:#6b7280;line-height:1.55;margin-top:.5rem">
                            Meta owner: {{ $queueWorkflow['owner_sla_target_hours'] ?? '—' }}h
                            @if (($queueWorkflow['owner_sla_overdue_hours'] ?? 0) > 0)
                                · atraso de {{ number_format($queueWorkflow['owner_sla_overdue_hours'], 1, ',', '.') }}h
                            @endif
                        </div>
                        <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.65rem">
                            <a href="{{ route('admin.coverage-alerts.municipality', $queueAlert->municipality) }}"
                                style="font-size:.75rem;color:#1d4ed8;text-decoration:none">Abrir linha do tempo</a>
                            @if ($queueAlert->action_url)
                                <a href="{{ $queueAlert->action_url }}"
                                    style="font-size:.75rem;color:#374151;text-decoration:none">Abrir ação</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="grid-column:1/-1;font-size:.83rem;color:#9ca3af">
                        Você ainda não possui alertas assumidos na fila pessoal.
                    </div>
                @endforelse
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.9rem">
                    <div>
                        <div style="font-size:.92rem;font-weight:700;color:#111827">Comparação entre municípios</div>
                        <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Melhores coberturas no momento.</div>
                    </div>
                    <span style="font-size:.74rem;color:#166534;font-weight:700">Líderes</span>
                </div>
                <div style="display:grid;gap:.7rem">
                    @forelse ($comparison['leaders'] as $row)
                        <div style="padding:.85rem .95rem;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.85rem;font-weight:700;color:#111827">
                                        {{ $row['municipality_name'] }}</div>
                                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">
                                        Config.: {{ $row['score'] }}% · Reincidência 30d: {{ $row['recurrence_30d'] }} ·
                                        SLA: {{ $row['sla_breaches_total'] }}
                                    </div>
                                </div>
                                <div style="font-size:1rem;font-weight:700;color:#166534">{{ $row['executive_score'] }}
                                </div>
                            </div>
                            <a href="{{ route('admin.coverage-alerts.municipality', $row['municipality']) }}"
                                style="display:inline-block;margin-top:.45rem;font-size:.76rem;color:#166534;text-decoration:none">
                                Ver drill-down
                            </a>
                        </div>
                    @empty
                        <div style="font-size:.83rem;color:#9ca3af">Sem municípios suficientes para comparação.</div>
                    @endforelse
                </div>
            </div>

            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.9rem">
                    <div>
                        <div style="font-size:.92rem;font-weight:700;color:#111827">Municípios em atenção</div>
                        <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Maiores riscos combinando score,
                            reincidência e SLA.</div>
                    </div>
                    <span style="font-size:.74rem;color:#b91c1c;font-weight:700">Risco</span>
                </div>
                <div style="display:grid;gap:.7rem">
                    @forelse ($comparison['attention'] as $row)
                        <div style="padding:.85rem .95rem;border-radius:10px;background:#fef2f2;border:1px solid #fecaca">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.85rem;font-weight:700;color:#111827">
                                        {{ $row['municipality_name'] }}</div>
                                    <div style="font-size:.76rem;color:#6b7280;margin-top:.2rem">
                                        Ativos: {{ $row['active_alerts_total'] }} · Alta:
                                        {{ $row['active_high_alerts_total'] }} · Maior atraso:
                                        {{ number_format($row['sla_breach_max_overdue_hours'], 1, ',', '.') }}h
                                    </div>
                                </div>
                                <div style="font-size:1rem;font-weight:700;color:#b91c1c">{{ $row['risk_score'] }}</div>
                            </div>
                            <a href="{{ route('admin.coverage-alerts.municipality', $row['municipality']) }}"
                                style="display:inline-block;margin-top:.45rem;font-size:.76rem;color:#b91c1c;text-decoration:none">
                                Abrir linha do tempo
                            </a>
                        </div>
                    @empty
                        <div style="font-size:.83rem;color:#9ca3af">Sem municípios suficientes para leitura de risco.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.9rem;flex-wrap:wrap">
                    <div>
                        <div style="font-size:.92rem;font-weight:700;color:#111827">Comparativo temporal entre snapshots
                        </div>
                        <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Leitura atual versus o último snapshot
                            persistido da central.</div>
                    </div>
                    <div style="font-size:.75rem;color:#6b7280;text-align:right">
                        <div>Atual:
                            {{ \Illuminate\Support\Carbon::parse(data_get($temporalComparison, 'current.captured_at'))->format('d/m/Y H:i') }}
                        </div>
                        <div>Último snapshot:
                            {{ data_get($temporalComparison, 'latest_snapshot.captured_at')?->format('d/m/Y H:i') ?? '—' }}
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;margin-bottom:.95rem">
                    @foreach ([['label' => 'Alertas ativos', 'value' => data_get($temporalComparison, 'current.active_alerts', 0), 'delta' => data_get($temporalComparison, 'deltas.active_alerts', 0), 'inverse' => true], ['label' => 'Breaches SLA', 'value' => data_get($temporalComparison, 'current.sla_breaches_total', 0), 'delta' => data_get($temporalComparison, 'deltas.sla_breaches_total', 0), 'inverse' => true], ['label' => 'Score config.', 'value' => data_get($temporalComparison, 'current.average_configuration_score', 0) . '%', 'delta' => data_get($temporalComparison, 'deltas.average_configuration_score', 0), 'inverse' => false], ['label' => 'Score executivo', 'value' => data_get($temporalComparison, 'current.average_executive_score', 0) . '%', 'delta' => data_get($temporalComparison, 'deltas.average_executive_score', 0), 'inverse' => false]] as $metric)
                        @php
                            $delta = (int) $metric['delta'];
                            $isPositive = $delta > 0;
                            $isNeutral = $delta === 0;
                            $isGood = $metric['inverse'] ? !$isPositive && !$isNeutral : $isPositive;
                            $chipBg = $isNeutral ? '#f3f4f6' : ($isGood ? '#ecfdf5' : '#fef2f2');
                            $chipColor = $isNeutral ? '#6b7280' : ($isGood ? '#166534' : '#b91c1c');
                        @endphp
                        <div style="padding:.85rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                            <div style="font-size:.74rem;color:#6b7280;font-weight:700;text-transform:uppercase">
                                {{ $metric['label'] }}</div>
                            <div style="font-size:1.2rem;font-weight:700;color:#111827;margin-top:.28rem">
                                {{ $metric['value'] }}</div>
                            <div style="margin-top:.38rem">
                                <span
                                    style="padding:.2rem .48rem;border-radius:999px;background:{{ $chipBg }};color:{{ $chipColor }};font-size:.72rem;font-weight:700">
                                    {{ $delta > 0 ? '+' : '' }}{{ $delta }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @php
                    $temporalSeries = collect(data_get($temporalComparison, 'series', []));
                    $maxExecutiveTemporal = max(1, (int) $temporalSeries->max('average_executive_score'));
                @endphp
                @if ($temporalSeries->isNotEmpty())
                    <div
                        style="display:grid;grid-template-columns:repeat({{ max($temporalSeries->count(), 1) }},minmax(0,1fr));gap:.45rem;align-items:end;min-height:150px">
                        @foreach ($temporalSeries as $point)
                            <div
                                style="display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:.35rem">
                                <div title="Score executivo médio: {{ $point['average_executive_score'] }}%"
                                    style="width:22px;height:{{ max(16, (int) round(($point['average_executive_score'] / $maxExecutiveTemporal) * 110)) }}px;background:#1d4ed8;border-radius:999px 999px 6px 6px">
                                </div>
                                <div style="font-size:.7rem;color:#6b7280">{{ $point['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:.8rem;font-size:.77rem;color:#6b7280">
                        <span>Anterior:
                            {{ data_get($temporalComparison, 'previous_snapshot.summary.average_executive_score', 0) }}%</span>
                        <span>Último snapshot:
                            {{ data_get($temporalComparison, 'latest_snapshot.summary.average_executive_score', 0) }}%</span>
                        <span>Atual: {{ data_get($temporalComparison, 'current.average_executive_score', 0) }}%</span>
                    </div>
                @else
                    <div style="font-size:.83rem;color:#9ca3af">
                        Ainda não há snapshots suficientes para gerar o comparativo temporal.
                    </div>
                @endif
            </div>

            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.9rem">
                    <div>
                        <div style="font-size:.92rem;font-weight:700;color:#111827">Curva de melhora/piora por município
                        </div>
                        <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Maior variação de score executivo ao
                            longo dos snapshots recentes.</div>
                    </div>
                    <span style="font-size:.74rem;color:#374151;font-weight:700">Top variação</span>
                </div>
                <div style="display:grid;gap:.7rem">
                    @forelse ($improvementCurve as $entry)
                        @php
                            $curveColor =
                                $entry['trend_direction'] === 'up'
                                    ? '#166534'
                                    : ($entry['trend_direction'] === 'down'
                                        ? '#b91c1c'
                                        : '#6b7280');
                            $curveBg =
                                $entry['trend_direction'] === 'up'
                                    ? '#ecfdf5'
                                    : ($entry['trend_direction'] === 'down'
                                        ? '#fef2f2'
                                        : '#f3f4f6');
                            $points = collect($entry['points']);
                        @endphp
                        <div style="padding:.85rem .9rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.84rem;font-weight:700;color:#111827">
                                        {{ $entry['municipality_name'] }}</div>
                                    <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">
                                        {{ $entry['first_score'] }}% → {{ $entry['last_score'] }}% ·
                                        {{ $points->count() }} ponto(s)
                                    </div>
                                </div>
                                <span
                                    style="padding:.22rem .55rem;border-radius:999px;background:{{ $curveBg }};color:{{ $curveColor }};font-size:.72rem;font-weight:700">
                                    {{ $entry['delta'] > 0 ? '+' : '' }}{{ $entry['delta'] }}
                                </span>
                            </div>
                            <div
                                style="display:grid;grid-template-columns:repeat({{ max($points->count(), 1) }},minmax(0,1fr));gap:.35rem;align-items:end;height:72px;margin-top:.65rem">
                                @php $maxCurveScore = max(1, (int) $points->max('score')); @endphp
                                @foreach ($points as $point)
                                    <div
                                        style="display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:.28rem">
                                        <div title="{{ $point['label'] }}: {{ $point['score'] }}%"
                                            style="width:14px;height:{{ max(10, (int) round(($point['score'] / $maxCurveScore) * 48)) }}px;background:{{ $curveColor }};border-radius:999px 999px 4px 4px;opacity:.85">
                                        </div>
                                        <div style="font-size:.65rem;color:#9ca3af">{{ $point['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div style="font-size:.83rem;color:#9ca3af">
                            Ainda não há snapshots suficientes para traçar curva de evolução municipal.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1.3fr .9fr;gap:1rem;margin-bottom:1.25rem">
            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:.9rem">
                    <div>
                        <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.25rem">Ranking executivo
                        </div>
                        <div style="font-size:.79rem;color:#6b7280">Score composto por configuração, reincidência,
                            violações de SLA e variação temporal.</div>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <a href="{{ route('admin.coverage-alerts.ranking.export.csv') }}"
                            style="padding:.45rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.76rem;color:#374151;text-decoration:none">
                            Exportar CSV
                        </a>
                        <a href="{{ route('admin.coverage-alerts.ranking.export.xlsx') }}"
                            style="padding:.45rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.76rem;color:#374151;text-decoration:none">
                            Exportar XLSX
                        </a>
                        <a href="{{ route('admin.coverage-alerts.ranking.export.pdf') }}"
                            style="padding:.45rem .8rem;border:1px solid #d1d5db;border-radius:8px;font-size:.76rem;color:#374151;text-decoration:none">
                            Exportar PDF
                        </a>
                    </div>
                </div>
                <div style="overflow:auto">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                                <th style="padding:.75rem;text-align:left;font-size:.78rem;color:#6b7280">Pos.</th>
                                <th style="padding:.75rem;text-align:left;font-size:.78rem;color:#6b7280">Município</th>
                                <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">Score</th>
                                <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">Tendência</th>
                                <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">Config.</th>
                                <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">Reinc.</th>
                                <th style="padding:.75rem;text-align:center;font-size:.78rem;color:#6b7280">SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($executiveRanking as $row)
                                @php
                                    $trendColor =
                                        $row['trend_direction'] === 'up'
                                            ? '#166534'
                                            : ($row['trend_direction'] === 'down'
                                                ? '#b91c1c'
                                                : '#6b7280');
                                    $trendBg =
                                        $row['trend_direction'] === 'up'
                                            ? '#ecfdf5'
                                            : ($row['trend_direction'] === 'down'
                                                ? '#fef2f2'
                                                : '#f3f4f6');
                                @endphp
                                <tr style="border-bottom:1px solid #f3f4f6">
                                    <td style="padding:.75rem">
                                        <div style="font-size:.82rem;font-weight:700;color:#374151">
                                            {{ $row['position'] }}</div>
                                        @if (!is_null($row['position_delta']))
                                            <div
                                                style="font-size:.72rem;color:{{ ($row['position_delta'] ?? 0) >= 0 ? '#166534' : '#b91c1c' }}">
                                                {{ ($row['position_delta'] ?? 0) > 0 ? '+' : '' }}{{ $row['position_delta'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding:.75rem">
                                        <div style="font-size:.84rem;font-weight:700;color:#111827">
                                            {{ $row['municipality_name'] }}</div>
                                        <a href="{{ route('admin.coverage-alerts.municipality', $row['municipality']) }}"
                                            style="font-size:.76rem;color:#1d4ed8;text-decoration:none">Abrir
                                            drill-down</a>
                                    </td>
                                    <td style="padding:.75rem;text-align:center">
                                        <div style="font-size:.84rem;font-weight:700;color:#111827">
                                            {{ $row['executive_score'] }}</div>
                                        <div
                                            style="font-size:.72rem;color:{{ ($row['executive_score_delta'] ?? 0) >= 0 ? '#166534' : '#b91c1c' }}">
                                            {{ ($row['executive_score_delta'] ?? 0) > 0 ? '+' : '' }}{{ $row['executive_score_delta'] ?? 0 }}
                                        </div>
                                    </td>
                                    <td style="padding:.75rem;text-align:center">
                                        <span
                                            style="padding:.22rem .55rem;border-radius:999px;background:{{ $trendBg }};color:{{ $trendColor }};font-size:.72rem;font-weight:700">
                                            {{ $row['trend_direction'] === 'up' ? 'Melhora' : ($row['trend_direction'] === 'down' ? 'Piora' : 'Estável') }}
                                        </span>
                                    </td>
                                    <td style="padding:.75rem;text-align:center;font-size:.82rem;color:#374151">
                                        {{ $row['score'] }}%</td>
                                    <td style="padding:.75rem;text-align:center;font-size:.82rem;color:#374151">
                                        {{ $row['recurrence_30d'] }}</td>
                                    <td style="padding:.75rem;text-align:center;font-size:.82rem;color:#374151">
                                        {{ $row['sla_breaches_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.25rem">Snapshots gerenciais</div>
                <div style="font-size:.79rem;color:#6b7280;margin-bottom:.9rem">Histórico periódico da central executiva.
                </div>
                <div style="display:grid;gap:.7rem">
                    @forelse ($snapshotHistory as $snapshot)
                        <div style="padding:.85rem .95rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.84rem;font-weight:700;color:#111827">
                                        Snapshot {{ $snapshot->period === 'weekly' ? 'semanal' : 'diário' }}
                                    </div>
                                    <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">
                                        {{ $snapshot->captured_at?->format('d/m/Y H:i') ?? '—' }}
                                    </div>
                                </div>
                                <div style="font-size:.92rem;font-weight:700;color:#111827">
                                    {{ data_get($snapshot->summary, 'average_executive_score', 0) }}%
                                </div>
                            </div>
                            <div style="font-size:.76rem;color:#6b7280;margin-top:.45rem">
                                Ativos: {{ data_get($snapshot->summary, 'active_alerts', 0) }} · SLA:
                                {{ data_get($snapshot->summary, 'sla_breaches_total', 0) }} · Municípios:
                                {{ data_get($snapshot->summary, 'tracked_municipalities', 0) }}
                            </div>
                        </div>
                    @empty
                        <div style="font-size:.83rem;color:#9ca3af">
                            Ainda não há snapshots persistidos. Após rodar a migration, o scheduler passa a registrar o
                            histórico gerencial.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem">
            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div
                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.85rem">
                    <div>
                        <div style="font-size:.92rem;font-weight:700;color:#111827">Histórico e tendência de resolução
                        </div>
                        <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Últimos 14 dias de novos alertas x
                            alertas resolvidos.</div>
                    </div>
                    <div
                        style="font-size:.78rem;color:{{ $trendSummary['resolution_balance'] >= 0 ? '#166534' : '#b91c1c' }};font-weight:700">
                        Saldo
                        {{ $trendSummary['resolution_balance'] >= 0 ? '+' : '' }}{{ $trendSummary['resolution_balance'] }}
                    </div>
                </div>
                <div
                    style="display:grid;grid-template-columns:repeat({{ max($trend->count(), 1) }},minmax(0,1fr));gap:.45rem;align-items:end;min-height:160px">
                    @php
                        $maxTrendValue = max(1, $trend->max(fn($row) => max($row['created'], $row['resolved'])));
                    @endphp
                    @foreach ($trend as $row)
                        <div
                            style="display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:.35rem">
                            <div style="display:flex;align-items:flex-end;gap:.18rem;height:110px">
                                <div title="Novos: {{ $row['created'] }}"
                                    style="width:12px;height:{{ max(6, (int) round(($row['created'] / $maxTrendValue) * 100)) }}px;background:#fca5a5;border-radius:999px 999px 4px 4px">
                                </div>
                                <div title="Resolvidos: {{ $row['resolved'] }}"
                                    style="width:12px;height:{{ max(6, (int) round(($row['resolved'] / $maxTrendValue) * 100)) }}px;background:#86efac;border-radius:999px 999px 4px 4px">
                                </div>
                            </div>
                            <div style="font-size:.7rem;color:#6b7280">{{ $row['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:.85rem;font-size:.77rem;color:#6b7280">
                    <span><span
                            style="display:inline-block;width:10px;height:10px;background:#fca5a5;border-radius:999px;margin-right:.35rem"></span>Novos:
                        {{ $trendSummary['created_last_14d'] }}</span>
                    <span><span
                            style="display:inline-block;width:10px;height:10px;background:#86efac;border-radius:999px;margin-right:.35rem"></span>Resolvidos:
                        {{ $trendSummary['resolved_last_14d'] }}</span>
                </div>
            </div>

            <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem">
                <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.25rem">Reincidência por município
                </div>
                <div style="font-size:.79rem;color:#6b7280;margin-bottom:.9rem">Municípios com mais ocorrências registradas
                    e recorrência recente.</div>
                <div style="display:grid;gap:.7rem">
                    @forelse ($recurrenceByMunicipality as $entry)
                        <div style="padding:.85rem .9rem;border-radius:10px;background:#fafafa;border:1px solid #e5e7eb">
                            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                                <div>
                                    <div style="font-size:.84rem;font-weight:700;color:#111827">
                                        {{ $entry->municipality?->name ?? '—' }}</div>
                                    <div style="font-size:.75rem;color:#6b7280;margin-top:.2rem">
                                        30 dias: {{ $entry->last_30d_total }} · ativos: {{ $entry->active_total }} ·
                                        alta: {{ $entry->high_total }}
                                    </div>
                                    @if ($entry->municipality)
                                        <a href="{{ route('admin.coverage-alerts.municipality', $entry->municipality) }}"
                                            style="display:inline-block;margin-top:.35rem;font-size:.75rem;color:#1d4ed8;text-decoration:none">
                                            Ver linha do tempo
                                        </a>
                                    @endif
                                </div>
                                <div style="font-size:.92rem;font-weight:700;color:#374151">{{ $entry->alerts_total }}
                                </div>
                            </div>
                            <div
                                style="margin-top:.55rem;height:8px;background:#f3f4f6;border-radius:999px;overflow:hidden">
                                @php $recurrenceWidth = max(8, min(100, (int) round(($entry->last_30d_total / max(1, $entry->alerts_total)) * 100))); @endphp
                                <div
                                    style="width:{{ $recurrenceWidth }}%;height:100%;background:{{ $entry->high_total > 0 ? '#f59e0b' : '#60a5fa' }}">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="font-size:.83rem;color:#9ca3af">Ainda não há dados suficientes para mapear
                            reincidência.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem;margin-bottom:1.25rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-size:.92rem;font-weight:700;color:#111827">Filtros salvos por admin</div>
                    <div style="font-size:.79rem;color:#6b7280;margin-top:.2rem">Salve combinações frequentes de busca para
                        reuso rápido na central.</div>
                </div>
                <form method="POST" action="{{ route('admin.coverage-alerts.filters.save') }}"
                    style="display:flex;gap:.55rem;align-items:center;flex-wrap:wrap">
                    @csrf
                    <input type="hidden" name="severity" value="{{ $filters['severity'] }}">
                    <input type="hidden" name="municipality_id" value="{{ $filters['municipality_id'] }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <input type="hidden" name="event_type" value="{{ $filters['event_type'] }}">
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    <input type="hidden" name="preset" value="{{ $filters['preset'] }}">
                    <input type="text" name="name" placeholder="Nome do filtro"
                        style="padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.83rem">
                    <button type="submit"
                        style="padding:.62rem .95rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.83rem;font-weight:600;cursor:pointer">
                        Salvar filtro
                    </button>
                </form>
            </div>
            @if (!empty($savedFilters))
                <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.95rem">
                    @foreach ($savedFilters as $filterKey => $savedFilter)
                        <div
                            style="display:flex;align-items:center;gap:.35rem;padding:.35rem .45rem;border:1px solid #e5e7eb;border-radius:999px;background:#fafafa">
                            <a href="{{ route('admin.coverage-alerts.index', ['preset' => 'saved:' . $filterKey]) }}"
                                style="font-size:.8rem;color:#374151;text-decoration:none">{{ $savedFilter['name'] ?? $filterKey }}</a>
                            <form method="POST"
                                action="{{ route('admin.coverage-alerts.filters.delete', $filterKey) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    style="border:none;background:transparent;color:#9ca3af;font-size:.85rem;cursor:pointer">×</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem 1.1rem;margin-bottom:1.25rem">
            <div style="font-size:.92rem;font-weight:700;color:#111827;margin-bottom:.25rem">SLA de resolução por tipo de
                alerta</div>
            <div style="font-size:.79rem;color:#6b7280;margin-bottom:.9rem">Tempo médio de resolução comparado à meta
                operacional definida para cada frente.</div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem">
                @foreach ($slaByType as $sla)
                    <div style="padding:1rem;border-radius:12px;border:1px solid #e5e7eb;background:#fafafa">
                        <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                            <div>
                                <div style="font-size:.84rem;font-weight:700;color:#111827">{{ $sla['label'] }}</div>
                                <div style="font-size:.75rem;color:#6b7280;margin-top:.18rem">Meta:
                                    {{ $sla['target_hours'] }}h</div>
                            </div>
                            <span
                                style="padding:.22rem .55rem;border-radius:999px;background:{{ $sla['sla_status'] === 'ok' ? '#ecfdf5' : ($sla['sla_status'] === 'warning' ? '#fff7ed' : '#f3f4f6') }};color:{{ $sla['sla_status'] === 'ok' ? '#166534' : ($sla['sla_status'] === 'warning' ? '#b45309' : '#6b7280') }};font-size:.72rem;font-weight:700">
                                {{ $sla['sla_status'] === 'ok' ? 'No SLA' : ($sla['sla_status'] === 'warning' ? 'Fora do SLA' : 'Sem base') }}
                            </span>
                        </div>
                        <div style="font-size:1.35rem;font-weight:700;margin-top:.55rem;color:#111827">
                            {{ $sla['avg_resolution_hours'] !== null ? number_format($sla['avg_resolution_hours'], 1, ',', '.') . 'h' : '—' }}
                        </div>
                        <div style="font-size:.76rem;color:#6b7280;margin-top:.25rem">
                            {{ $sla['resolved_total'] }} resolvido(s) de {{ $sla['total'] }} total
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem;margin-bottom:1.25rem">
            <form method="GET" action="{{ route('admin.coverage-alerts.index') }}"
                style="display:grid;grid-template-columns:1.1fr 1fr 1fr 1fr 1.2fr auto;gap:.75rem;align-items:end">
                @if ($filters['preset'] !== '')
                    <input type="hidden" name="preset" value="{{ $filters['preset'] }}">
                @endif
                <div>
                    <label
                        style="display:block;font-size:.78rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Município</label>
                    <select name="municipality_id"
                        style="width:100%;padding:.65rem .8rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.85rem">
                        <option value="">Todos</option>
                        @foreach ($municipalityOptions as $municipalityOption)
                            <option value="{{ $municipalityOption->id }}" @selected($filters['municipality_id'] == (string) $municipalityOption->id)>
                                {{ $municipalityOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label
                        style="display:block;font-size:.78rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Severidade</label>
                    <select name="severity"
                        style="width:100%;padding:.65rem .8rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.85rem">
                        <option value="all" @selected($filters['severity'] === 'all')>Todas</option>
                        <option value="high" @selected($filters['severity'] === 'high')>Alta</option>
                        <option value="medium" @selected($filters['severity'] === 'medium')>Média</option>
                    </select>
                </div>
                <div>
                    <label
                        style="display:block;font-size:.78rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Status</label>
                    <select name="status"
                        style="width:100%;padding:.65rem .8rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.85rem">
                        <option value="all" @selected($filters['status'] === 'all')>Todos</option>
                        <option value="active" @selected($filters['status'] === 'active')>Ativos</option>
                        <option value="resolved" @selected($filters['status'] === 'resolved')>Resolvidos</option>
                    </select>
                </div>
                <div>
                    <label
                        style="display:block;font-size:.78rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Frente</label>
                    <select name="event_type"
                        style="width:100%;padding:.65rem .8rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.85rem">
                        <option value="all" @selected($filters['event_type'] === 'all')>Todas</option>
                        @foreach ($eventTypeOptions as $eventTypeKey => $eventTypeLabel)
                            <option value="{{ $eventTypeKey }}" @selected($filters['event_type'] === $eventTypeKey)>{{ $eventTypeLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label
                        style="display:block;font-size:.78rem;font-weight:600;color:#6b7280;margin-bottom:.35rem">Busca</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                        placeholder="Título, mensagem ou município"
                        style="width:100%;padding:.65rem .8rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.85rem">
                </div>
                <div style="display:flex;gap:.5rem">
                    <button type="submit"
                        style="padding:.68rem 1rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.84rem;font-weight:600;cursor:pointer">Filtrar</button>
                    <a href="{{ route('admin.coverage-alerts.index') }}"
                        style="padding:.68rem 1rem;border:1px solid #d1d5db;border-radius:8px;font-size:.84rem;color:#374151;text-decoration:none">Limpar</a>
                </div>
            </form>
        </div>

        <form id="bulk-alert-form" method="POST" action="{{ route('admin.coverage-alerts.bulk') }}">
            @csrf
            <div
                style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:1rem;margin-bottom:.9rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                <div>
                    <div style="font-size:.88rem;font-weight:700;color:#111827">Ações rápidas em lote</div>
                    <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">Selecione alertas abaixo para assumir,
                        reconhecer, resolver manualmente ou revalidar a cobertura agora.</div>
                </div>
                <div style="display:flex;gap:.55rem;align-items:center;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:.45rem;font-size:.8rem;color:#374151">
                        <input type="checkbox" id="select-all-alerts">
                        Selecionar página
                    </label>
                    <select name="action" form="bulk-alert-form"
                        style="padding:.62rem .75rem;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:.83rem">
                        <option value="recheck_selected">Revalidar cobertura</option>
                        <option value="assign_me_selected">Assumir selecionados</option>
                        <option value="acknowledge_selected">Acknowledge selecionados</option>
                        <option value="resolve_selected">Marcar como resolvido</option>
                    </select>
                    <button type="submit" form="bulk-alert-form"
                        style="padding:.62rem .95rem;background:#111827;color:#fff;border:none;border-radius:8px;font-size:.83rem;font-weight:600;cursor:pointer">
                        Aplicar
                    </button>
                </div>
            </div>
        </form>

        <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;overflow:hidden">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <th
                            style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600;width:48px">
                        </th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600">
                            ALERTA</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600">
                            MUNICÍPIO</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600">
                            FRENTE</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600">
                            SEVERIDADE</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600">
                            STATUS</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600">
                            ÚLTIMA DETECÇÃO</th>
                        <th style="padding:.9rem 1rem;text-align:left;font-size:.79rem;color:#6b7280;font-weight:600">AÇÕES
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        @php
                            $severityBg = $alert->severity === 'high' ? '#fef2f2' : '#fff7ed';
                            $severityColor = $alert->severity === 'high' ? '#b91c1c' : '#b45309';
                            $statusBg = $alert->status === 'active' ? '#eff6ff' : '#ecfdf5';
                            $statusColor = $alert->status === 'active' ? '#1d4ed8' : '#166534';
                            $eventLabel = $eventTypeOptions[$alert->event_type] ?? $alert->event_type;
                            $workflow = $alert->workflow_snapshot ?? [];
                            $ownerName = $workflow['owner_name'] ?? null;
                            $acknowledged = (bool) ($workflow['acknowledged'] ?? false);
                            $ackBy = $workflow['acknowledged_by_name'] ?? null;
                            $ackAt = $workflow['acknowledged_at'] ?? null;
                            $ownerSlaStatus = $workflow['owner_sla_status'] ?? 'unassigned';
                            $ownerSlaColor =
                                $ownerSlaStatus === 'breached'
                                    ? '#b91c1c'
                                    : ($ownerSlaStatus === 'warning'
                                        ? '#b45309'
                                        : ($ownerSlaStatus === 'ok'
                                            ? '#166534'
                                            : '#6b7280'));
                            $ownerSlaBg =
                                $ownerSlaStatus === 'breached'
                                    ? '#fef2f2'
                                    : ($ownerSlaStatus === 'warning'
                                        ? '#fff7ed'
                                        : ($ownerSlaStatus === 'ok'
                                            ? '#ecfdf5'
                                            : '#f3f4f6'));
                            $history = collect($workflow['history'] ?? [])->take(3);
                            $comments = collect($workflow['comments'] ?? [])->take(2);
                        @endphp
                        <tr style="border-bottom:1px solid #f3f4f6;vertical-align:top">
                            <td style="padding:1rem">
                                <input type="checkbox" name="selected_alert_ids[]" value="{{ $alert->id }}"
                                    class="alert-checkbox" form="bulk-alert-form">
                            </td>
                            <td style="padding:1rem">
                                <div style="font-size:.88rem;font-weight:700;color:#111827">{{ $alert->title }}</div>
                                <div
                                    style="font-size:.79rem;color:#6b7280;line-height:1.55;margin-top:.35rem;max-width:460px">
                                    {{ $alert->message }}
                                </div>
                                <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.55rem">
                                    <span
                                        style="padding:.22rem .55rem;border-radius:999px;background:#f3f4f6;color:#374151;font-size:.72rem;font-weight:700">
                                        Owner: {{ $ownerName ?: 'Não definido' }}
                                    </span>
                                    <span
                                        style="padding:.22rem .55rem;border-radius:999px;background:{{ $ownerSlaBg }};color:{{ $ownerSlaColor }};font-size:.72rem;font-weight:700">
                                        {{ $ownerSlaStatus === 'breached' ? 'Owner SLA estourado' : ($ownerSlaStatus === 'warning' ? 'Owner SLA em risco' : ($ownerSlaStatus === 'ok' ? 'Owner no SLA' : 'Sem owner')) }}
                                    </span>
                                    <span
                                        style="padding:.22rem .55rem;border-radius:999px;background:{{ $acknowledged ? '#ecfdf5' : '#f3f4f6' }};color:{{ $acknowledged ? '#166534' : '#6b7280' }};font-size:.72rem;font-weight:700">
                                        {{ $acknowledged ? 'Ack por ' . $ackBy : 'Sem acknowledge' }}
                                    </span>
                                </div>
                                @if ($acknowledged && $ackAt)
                                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.3rem">
                                        {{ \Illuminate\Support\Carbon::parse($ackAt)->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                                @if ($history->isNotEmpty())
                                    <div
                                        style="margin-top:.55rem;padding:.65rem .75rem;border-radius:8px;background:#fafafa;border:1px dashed #e5e7eb">
                                        <div
                                            style="font-size:.7rem;color:#6b7280;text-transform:uppercase;font-weight:700;margin-bottom:.35rem">
                                            Trilha auditável · {{ $workflow['history_count'] ?? $history->count() }}
                                            transição(ões)
                                        </div>
                                        <div style="display:grid;gap:.28rem">
                                            @foreach ($history as $event)
                                                <div style="font-size:.73rem;color:#4b5563;line-height:1.45">
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $event['transition'] ?? 'evento')) }}</strong>
                                                    · {{ $event['actor_name'] ?? 'Sistema' }}
                                                    ·
                                                    {{ !empty($event['at']) ? \Illuminate\Support\Carbon::parse($event['at'])->format('d/m H:i') : '—' }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <div
                                    style="margin-top:.55rem;padding:.65rem .75rem;border-radius:8px;background:#fff;border:1px solid #e5e7eb">
                                    <div
                                        style="font-size:.7rem;color:#6b7280;text-transform:uppercase;font-weight:700;margin-bottom:.35rem">
                                        Comentários internos · {{ $workflow['comments_count'] ?? $comments->count() }}
                                    </div>
                                    @if ($comments->isNotEmpty())
                                        <div style="display:grid;gap:.28rem;margin-bottom:.45rem">
                                            @foreach ($comments as $comment)
                                                <div style="font-size:.73rem;color:#4b5563;line-height:1.45">
                                                    <strong>{{ $comment['author_name'] ?? 'Operação' }}</strong>
                                                    ·
                                                    {{ !empty($comment['at']) ? \Illuminate\Support\Carbon::parse($comment['at'])->format('d/m H:i') : '—' }}
                                                    <br>{{ $comment['message'] ?? '' }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <form method="POST"
                                        action="{{ route('admin.coverage-alerts.comments.store', $alert) }}">
                                        @csrf
                                        <textarea name="comment" rows="2" placeholder="Registrar comentário interno deste alerta"
                                            style="width:100%;padding:.45rem .55rem;border:1px solid #d1d5db;border-radius:7px;font-size:.74rem;box-sizing:border-box;resize:vertical"></textarea>
                                        <div style="display:flex;justify-content:flex-end;margin-top:.4rem">
                                            <button type="submit"
                                                style="padding:.36rem .62rem;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:7px;font-size:.73rem;font-weight:600;cursor:pointer">
                                                Comentar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                            <td style="padding:1rem">
                                <div style="font-size:.86rem;font-weight:600;color:#111827">
                                    {{ $alert->municipality?->name ?? '—' }}</div>
                                @if ($alert->municipality)
                                    <div style="display:flex;flex-direction:column;gap:.2rem;margin-top:.25rem">
                                        <a href="{{ route('admin.municipalities.show', $alert->municipality) }}"
                                            style="font-size:.78rem;color:#6b7280;text-decoration:none;display:inline-block">
                                            Abrir município
                                        </a>
                                        <a href="{{ route('admin.coverage-alerts.municipality', $alert->municipality) }}"
                                            style="font-size:.78rem;color:#1d4ed8;text-decoration:none;display:inline-block">
                                            Drill-down de cobertura
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td style="padding:1rem;font-size:.84rem;color:#374151">{{ $eventLabel }}</td>
                            <td style="padding:1rem">
                                <span
                                    style="padding:.25rem .65rem;border-radius:999px;background:{{ $severityBg }};color:{{ $severityColor }};font-size:.74rem;font-weight:700">
                                    {{ strtoupper($alert->severity) }}
                                </span>
                            </td>
                            <td style="padding:1rem">
                                <span
                                    style="padding:.25rem .65rem;border-radius:999px;background:{{ $statusBg }};color:{{ $statusColor }};font-size:.74rem;font-weight:700">
                                    {{ $alert->status === 'active' ? 'ATIVO' : 'RESOLVIDO' }}
                                </span>
                            </td>
                            <td style="padding:1rem;font-size:.82rem;color:#374151">
                                <div>{{ optional($alert->last_detected_at)->format('d/m/Y H:i') ?: '—' }}</div>
                                @if ($alert->resolved_at)
                                    <div style="font-size:.75rem;color:#9ca3af;margin-top:.25rem">Resolvido em
                                        {{ $alert->resolved_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td style="padding:1rem">
                                <div
                                    style="display:flex;flex-direction:column;gap:.55rem;align-items:flex-start;min-width:220px">
                                    @if ($alert->action_url)
                                        <a href="{{ $alert->action_url }}"
                                            style="padding:.35rem .7rem;border:1px solid #d1d5db;border-radius:7px;font-size:.77rem;color:#374151;text-decoration:none">
                                            Abrir ação
                                        </a>
                                    @endif
                                    @if ($alert->municipality)
                                        <a href="{{ route('admin.municipalities.onboarding.show', $alert->municipality) }}"
                                            style="padding:.35rem .7rem;border:1px solid #d4af37;border-radius:7px;font-size:.77rem;color:#92400e;text-decoration:none">
                                            Revisar onboarding
                                        </a>
                                    @endif
                                    @if ($alert->status === 'active')
                                        <form method="POST" action="{{ route('admin.coverage-alerts.owner', $alert) }}"
                                            style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
                                            @csrf
                                            <select name="owner_user_id"
                                                style="padding:.38rem .45rem;border:1px solid #d1d5db;border-radius:7px;background:#fff;font-size:.75rem;min-width:130px">
                                                <option value="">Sem owner</option>
                                                @foreach ($adminOptions as $admin)
                                                    <option value="{{ $admin->id }}" @selected((int) ($workflow['owner_user_id'] ?? 0) === (int) $admin->id)>
                                                        {{ $admin->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit"
                                                style="padding:.38rem .55rem;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:7px;font-size:.74rem;font-weight:600;cursor:pointer">
                                                Owner
                                            </button>
                                        </form>
                                        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                                            <form method="POST"
                                                action="{{ route('admin.coverage-alerts.owner', $alert) }}">
                                                @csrf
                                                <input type="hidden" name="owner_user_id" value="{{ auth()->id() }}">
                                                <button type="submit"
                                                    style="padding:.38rem .55rem;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:7px;font-size:.74rem;font-weight:600;cursor:pointer">
                                                    Assumir
                                                </button>
                                            </form>
                                            @if ($acknowledged)
                                                <form method="POST"
                                                    action="{{ route('admin.coverage-alerts.unacknowledge', $alert) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        style="padding:.38rem .55rem;background:#fff;color:#6b7280;border:1px solid #d1d5db;border-radius:7px;font-size:.74rem;font-weight:600;cursor:pointer">
                                                        Remover ack
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('admin.coverage-alerts.acknowledge', $alert) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        style="padding:.38rem .55rem;background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;border-radius:7px;font-size:.74rem;font-weight:600;cursor:pointer">
                                                        Acknowledge
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:2rem;text-align:center;color:#9ca3af;font-size:.9rem">
                                Nenhum alerta encontrado para os filtros selecionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem">
            {{ $alerts->links() }}
        </div>
    </div>

    <script>
        (() => {
            const toggle = document.getElementById('select-all-alerts');
            if (!toggle) return;
            toggle.addEventListener('change', () => {
                document.querySelectorAll('.alert-checkbox').forEach((checkbox) => {
                    checkbox.checked = toggle.checked;
                });
            });
        })();
    </script>
@endsection
