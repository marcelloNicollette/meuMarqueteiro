@extends('layouts.mayor')
@section('title', 'Mandato')
@section('topbar-title', 'Mandato')

@push('styles')
    <style>
        .mandate-shell {
            padding: 1.8rem 2rem 2.25rem;
            display: flex;
            flex-direction: column;
            gap: 1.4rem;
        }

        .mandate-shell-hero {
            background: linear-gradient(135deg, var(--ink) 0%, #1d2841 100%);
            color: #fff;
            border-radius: 20px;
            padding: 1.7rem 1.85rem;
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .mandate-shell-kicker {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .55);
            margin-bottom: .45rem;
        }

        .mandate-shell-hero h1 {
            font-family: "Outfit", sans-serif;
            font-size: 1.5rem;
            margin-bottom: .35rem;
        }

        .mandate-shell-hero p {
            color: rgba(255, 255, 255, .72);
            max-width: 760px;
            line-height: 1.65;
            font-size: .92rem;
        }

        .mandate-shell-hero-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .mandate-shell-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            padding: .7rem 1rem;
            border-radius: 10px;
            border: 1px solid transparent;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 600;
            transition: .18s ease;
            cursor: pointer;
        }

        .mandate-shell-btn.primary {
            background: var(--gold);
            color: var(--ink);
        }

        .mandate-shell-btn.secondary {
            background: rgba(255, 255, 255, .08);
            color: #fff;
            border-color: rgba(255, 255, 255, .14);
        }

        .mandate-shell-btn.light {
            background: #fff;
            color: var(--ink);
            border-color: var(--border);
        }

        .mandate-shell-btn:hover {
            transform: translateY(-1px);
            opacity: .94;
        }

        .mandate-shell-tabs {
            display: flex;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .mandate-shell-tab {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .75rem 1.05rem;
            border-radius: 12px;
            background: #fff;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--ink-soft);
            font-size: .84rem;
            font-weight: 600;
        }

        .mandate-shell-tab.is-active {
            background: rgba(184, 144, 42, .14);
            border-color: rgba(184, 144, 42, .4);
            color: var(--ink);
        }

        .mandate-panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.3rem 1.35rem;
        }

        .mandate-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .mandate-panel-title {
            font-family: "Outfit", sans-serif;
            font-size: 1.05rem;
            color: var(--ink);
        }

        .mandate-panel-subtitle {
            color: var(--ink-muted);
            font-size: .82rem;
            margin-top: .25rem;
            line-height: 1.55;
        }

        .mandate-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .85rem;
        }

        .mandate-kpi-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem;
        }

        .mandate-kpi-label {
            font-size: .74rem;
            color: var(--ink-muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .45rem;
        }

        .mandate-kpi-value {
            font-family: "Outfit", sans-serif;
            font-size: 1.55rem;
            color: var(--ink);
            line-height: 1;
        }

        .mandate-kpi-meta {
            margin-top: .55rem;
            color: var(--ink-muted);
            font-size: .78rem;
            line-height: 1.55;
        }

        .mandate-grid-2,
        .mandate-grid-3 {
            display: grid;
            gap: 1rem;
        }

        .mandate-grid-2 {
            grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr);
        }

        .mandate-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .mandate-axis-list,
        .mandate-stack-list,
        .mandate-commitment-axis-list,
        .mandate-action-list {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .mandate-axis-card,
        .mandate-mini-card,
        .mandate-action-card,
        .mandate-briefing-card,
        .mandate-commitment-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fff;
        }

        .mandate-axis-card {
            padding: 1rem 1.05rem;
        }

        .mandate-axis-top,
        .mandate-card-top {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .mandate-axis-name,
        .mandate-card-title {
            font-size: .94rem;
            font-weight: 700;
            color: var(--ink);
        }

        .mandate-axis-desc,
        .mandate-card-desc {
            margin-top: .25rem;
            color: var(--ink-muted);
            font-size: .8rem;
            line-height: 1.55;
        }

        .mandate-score-pill,
        .mandate-status-pill,
        .mandate-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .32rem .7rem;
            font-size: .74rem;
            font-weight: 700;
            border: 1px solid rgba(17, 19, 24, .08);
            background: #f8fafc;
            color: var(--ink-soft);
        }

        .mandate-axis-metrics,
        .mandate-chip-row,
        .mandate-card-meta,
        .mandate-briefing-meta {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-top: .8rem;
        }

        .mandate-axis-metric {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: var(--surface);
            font-size: .74rem;
            color: var(--ink-soft);
        }

        .mandate-mini-card,
        .mandate-action-card,
        .mandate-briefing-card,
        .mandate-commitment-card {
            padding: 1rem 1.05rem;
        }

        .mandate-card-actions {
            display: flex;
            gap: .55rem;
            flex-wrap: wrap;
            margin-top: .95rem;
        }

        .mandate-link-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .5rem .78rem;
            border-radius: 9px;
            background: #fff;
            border: 1px solid var(--border);
            color: var(--ink-soft);
            text-decoration: none;
            font-size: .78rem;
            font-weight: 600;
        }

        .mandate-link-btn:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .mandate-link-btn.primary {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
        }

        .mandate-empty {
            border: 1px dashed var(--border);
            border-radius: 14px;
            padding: 1.4rem;
            text-align: center;
            color: var(--ink-muted);
            background: var(--surface);
            font-size: .84rem;
            line-height: 1.6;
        }

        .mandate-toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) repeat(2, minmax(180px, .5fr)) auto;
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .mandate-toolbar input,
        .mandate-toolbar select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .72rem .85rem;
            font-size: .84rem;
            background: #fff;
        }

        .mandate-toolbar button {
            border: none;
        }

        .mandate-commitment-axis {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
            background: #fff;
        }

        .mandate-promise-list {
            display: flex;
            flex-direction: column;
            gap: .7rem;
            margin-top: .9rem;
        }

        .mandate-promise-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: .9rem;
            background: #fafafa;
        }

        .mandate-promise-item p {
            color: var(--ink);
            font-size: .84rem;
            line-height: 1.6;
        }

        .mandate-briefing-content {
            color: var(--ink-soft);
            font-size: .88rem;
            line-height: 1.75;
            margin-top: .75rem;
        }

        .mandate-pagination {
            margin-top: 1rem;
        }

        @media (max-width: 1180px) {

            .mandate-kpi-grid,
            .mandate-grid-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .mandate-grid-2,
            .mandate-toolbar {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .mandate-shell {
                padding: 1.1rem;
            }

            .mandate-kpi-grid,
            .mandate-grid-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $dashboardTotals = $dashboardBoard['totals'] ?? [];
        $dashboardAxes = $dashboardBoard['axis_rows'] ?? [];
        $dashboardPending = $dashboardBoard['pending_without_actions'] ?? [];
        $dashboardRecentActions = $dashboardBoard['recent_actions'] ?? [];
        $dashboardProjection = $dashboardBoard['projection'] ?? [];
        $commitmentTotals = $commitmentsBoard['totals'] ?? [];
        $commitmentAxes = $commitmentsBoard['axes'] ?? [];
        $commitmentPendingFocusAxes = $commitmentsBoard['pending_focus_axes'] ?? [];
        $actionFilters = $actionsBoard['filters'] ?? [];
        $actionOptions = $actionsBoard['options'] ?? [];
        $actionTotals = $actionsBoard['totals'] ?? [];
        $actionItems = $actionsBoard['items'] ?? collect();
        $actionReviewPromise = $actionsBoard['review_promise'] ?? null;
        $todayBriefing = $briefingsBoard['today'] ?? null;
        $recentBriefings = $briefingsBoard['recent'] ?? collect();
    @endphp

    <div class="mandate-shell">
        <section class="mandate-shell-hero">
            <div>
                <div class="mandate-shell-kicker">Módulo Mandato</div>
                <h1>Plano de governo, ações e leitura executiva em um único shell</h1>
                <p>
                    Esta primeira iteração unifica o núcleo do módulo em uma entrada principal com
                    `Dashboard`, `Compromissos do Plano`, `Ações de Governo` e `Briefings`, preparando a base do
                    `Mandato` para as próximas camadas do PDF.
                </p>
            </div>
            <div class="mandate-shell-hero-actions">
                <a href="{{ route('mayor.mandato.acao.create') }}" class="mandate-shell-btn primary">Nova ação</a>
                <a href="{{ route('mayor.mandato.eixos') }}" class="mandate-shell-btn secondary">Gerenciar eixos</a>
                <a href="{{ route('mayor.mandato.federal-programs') }}" class="mandate-shell-btn secondary">Radar de
                    Recursos</a>
            </div>
        </section>

        <nav class="mandate-shell-tabs">
            <a href="{{ route('mayor.mandato.painel', ['area' => 'dashboard']) }}"
                class="mandate-shell-tab {{ $activeArea === 'dashboard' ? 'is-active' : '' }}">Dashboard</a>
            <a href="{{ route('mayor.mandato.painel', ['area' => 'commitments']) }}"
                class="mandate-shell-tab {{ $activeArea === 'commitments' ? 'is-active' : '' }}">Compromissos do Plano</a>
            <a href="{{ route('mayor.mandato.painel', ['area' => 'actions']) }}"
                class="mandate-shell-tab {{ $activeArea === 'actions' ? 'is-active' : '' }}">Ações de Governo</a>
            <a href="{{ route('mayor.mandato.painel', ['area' => 'briefings']) }}"
                class="mandate-shell-tab {{ $activeArea === 'briefings' ? 'is-active' : '' }}">Briefings</a>
        </nav>

        @if ($activeArea === 'dashboard')
            @if (!empty($dashboardProjection['needs_alert']))
                <section class="mandate-panel"
                    style="border:1px solid #f5c2c7;background:linear-gradient(180deg,#fff7f7 0%,#fff 100%)">
                    <div class="mandate-panel-head">
                        <div>
                            <div class="mandate-panel-title" style="color:#991b1b">Alerta operacional do mandato</div>
                            <div class="mandate-panel-subtitle">
                                O ritmo atual esta abaixo do necessário para entregar toda a base de compromissos no prazo.
                            </div>
                        </div>
                    </div>
                    <div style="font-size:.98rem;font-weight:700;color:#7f1d1d;margin-top:-.2rem">
                        {{ $dashboardProjection['alert_message'] ?? 'Sem alerta operacional no momento.' }}
                    </div>
                    @if (!empty($dashboardProjection['axis_alerts']))
                        <div style="display:flex;flex-direction:column;gap:.7rem;margin-top:1rem">
                            @foreach ($dashboardProjection['axis_alerts'] as $axisAlert)
                                <div
                                    style="display:flex;justify-content:space-between;gap:.85rem;flex-wrap:wrap;padding:.85rem .95rem;border:1px solid #fecaca;border-radius:12px;background:#fff">
                                    <div>
                                        <div style="font-weight:700;color:var(--ink)">
                                            {{ trim(($axisAlert['axis_icon'] ?? '') . ' ' . ($axisAlert['axis_name'] ?? 'Eixo')) }}
                                        </div>
                                        <div style="font-size:.8rem;color:var(--ink-muted);margin-top:.18rem">
                                            Gap projetado de {{ $axisAlert['gap'] ?? 0 }} compromisso(s) |
                                            projeção de
                                            {{ $axisAlert['projected_fulfilled'] ?? 0 }}/{{ $axisAlert['total_promises'] ?? 0 }}
                                            entregues no prazo.
                                        </div>
                                    </div>
                                    <div class="mandate-card-actions" style="margin-top:0">
                                        <a href="{{ route('mayor.mandato.painel', ['area' => 'commitments']) }}"
                                            class="mandate-link-btn">Ver compromissos</a>
                                        <a href="{{ route('mayor.mandato.painel', ['area' => 'actions', 'action_axis' => $axisAlert['axis_id']]) }}"
                                            class="mandate-link-btn primary">Abrir ações do eixo</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            <section class="mandate-kpi-grid">
                @foreach ([['label' => 'Atendimento global', 'value' => ($dashboardTotals['global_attendance'] ?? 0) . '%', 'meta' => 'Compromissos com algum nível de atendimento na base atual.'], ['label' => 'Compromissos atendidos', 'value' => $dashboardTotals['fulfilled_promises'] ?? 0, 'meta' => 'Promessas já entregues com score pleno.'], ['label' => 'Parciais', 'value' => $dashboardTotals['partial_promises'] ?? 0, 'meta' => 'Compromissos com atendimento intermediário.'], ['label' => 'Pendentes', 'value' => $dashboardTotals['pending_promises'] ?? 0, 'meta' => 'Compromissos ainda sem atendimento suficiente.'], ['label' => 'Ações concluídas', 'value' => $dashboardTotals['completed_actions'] ?? 0, 'meta' => 'Ações marcadas como concluídas no mandato.']] as $card)
                    <div class="mandate-kpi-card">
                        <div class="mandate-kpi-label">{{ $card['label'] }}</div>
                        <div class="mandate-kpi-value">{{ $card['value'] }}</div>
                        <div class="mandate-kpi-meta">{{ $card['meta'] }}</div>
                    </div>
                @endforeach
            </section>

            <section class="mandate-grid-2">
                <div class="mandate-panel">
                    <div class="mandate-panel-head">
                        <div>
                            <div class="mandate-panel-title">Projeção até o fim do mandato</div>
                            <div class="mandate-panel-subtitle">
                                Ritmo calculado pelos últimos {{ $dashboardProjection['window_days'] ?? 60 }} dias,
                                olhando o avanço das ações em andamento e o calendário do mandato.
                            </div>
                        </div>
                    </div>
                    <div class="mandate-kpi-grid" style="grid-template-columns:repeat(4,minmax(0,1fr))">
                        @foreach ([['label' => 'Projeção de entregas', 'value' => $dashboardProjection['projected_fulfilled_promises'] ?? 0, 'meta' => 'Compromissos que o sistema projeta como entregues até o fim do mandato.'], ['label' => 'Compromissos em risco', 'value' => $dashboardProjection['projected_pending_promises'] ?? 0, 'meta' => 'Itens que não  chegam a 100% no ritmo atual.'], ['label' => 'Ações com projeção de conclusão', 'value' => $dashboardProjection['projected_actions_completed'] ?? 0, 'meta' => 'Ações em andamento que ainda devem terminar no prazo estimado.'], ['label' => 'Velocidade média diária', 'value' => number_format((float) ($dashboardProjection['portfolio_daily_progress_rate'] ?? 0), 2, ',', '.') . '%', 'meta' => 'Avanço percentual médio das ações em andamento na janela observada.']] as $card)
                            <div class="mandate-kpi-card">
                                <div class="mandate-kpi-label">{{ $card['label'] }}</div>
                                <div class="mandate-kpi-value">{{ $card['value'] }}</div>
                                <div class="mandate-kpi-meta">{{ $card['meta'] }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div
                        style="margin-top:1rem;padding:1rem 1.05rem;border-radius:12px;border:1px solid {{ !empty($dashboardProjection['needs_alert']) ? '#f5c2c7' : '#bbf7d0' }};background:{{ !empty($dashboardProjection['needs_alert']) ? '#fff5f5' : '#f0fdf4' }};">
                        <div style="font-size:.9rem;font-weight:700;color:var(--ink);margin-bottom:.25rem">
                            {{ $dashboardProjection['alert_message'] ?? 'Sem projeção disponível.' }}
                        </div>
                        <div style="font-size:.8rem;color:var(--ink-muted)">
                            Fim do mandato considerado: {{ $dashboardProjection['term_end_label'] ?? 'n/d' }} |
                            dias restantes: {{ $dashboardProjection['days_remaining'] ?? 0 }}
                        </div>
                        @if (!empty($dashboardProjection['axis_alerts']))
                            <div style="display:flex;flex-wrap:wrap;gap:.55rem;margin-top:.85rem">
                                @foreach ($dashboardProjection['axis_alerts'] as $axisAlert)
                                    <span class="mandate-chip"
                                        style="border-color:#f59e0b33;background:#f59e0b12;color:#92400e">
                                        {{ trim(($axisAlert['axis_icon'] ?? '') . ' ' . ($axisAlert['axis_name'] ?? 'Eixo')) }}
                                        · gap {{ $axisAlert['gap'] ?? 0 }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mandate-panel">
                    <div class="mandate-panel-head">
                        <div>
                            <div class="mandate-panel-title">Leitura por eixo</div>
                            <div class="mandate-panel-subtitle">Termômetro rápido do plano por eixo temático, com score
                                atual e distribuição de compromissos.</div>
                        </div>
                    </div>
                    <div class="mandate-axis-list">
                        @forelse ($dashboardAxes as $axis)
                            <div class="mandate-axis-card">
                                <div class="mandate-axis-top">
                                    <div>
                                        <div class="mandate-axis-name">
                                            {{ trim(($axis['icon'] ?? '') . ' ' . ($axis['name'] ?? 'Eixo')) }}</div>
                                        @if (!empty($axis['description']))
                                            <div class="mandate-axis-desc">{{ $axis['description'] }}</div>
                                        @endif
                                    </div>
                                    <span class="mandate-score-pill"
                                        style="background:{{ $axis['score_color'] ?? '#f8fafc' }}15;color:{{ $axis['score_color'] ?? '#334155' }};border-color:{{ $axis['score_color'] ?? '#334155' }}33">
                                        Score {{ $axis['score'] ?? 0 }}
                                    </span>
                                </div>
                                <div class="mandate-axis-metrics">
                                    <span class="mandate-axis-metric">Atendidos: {{ $axis['fulfilled'] ?? 0 }}</span>
                                    <span class="mandate-axis-metric">Parciais: {{ $axis['partial'] ?? 0 }}</span>
                                    <span class="mandate-axis-metric">Pendentes: {{ $axis['pending'] ?? 0 }}</span>
                                    <span class="mandate-axis-metric">Total: {{ $axis['promise_total'] ?? 0 }}</span>
                                    @if (($axis['projected_gap'] ?? 0) > 0)
                                        <span class="mandate-axis-metric"
                                            style="background:#fff7ed;color:#9a3412;border-color:#fdba74">
                                            Alerta de ritmo: {{ $axis['projected_gap'] }} gap
                                        </span>
                                    @endif
                                </div>
                                <div class="mandate-card-actions">
                                    <a href="{{ route('mayor.mandato.eixo', $axis['id']) }}" class="mandate-link-btn">Abrir
                                        eixo</a>
                                    <a href="{{ route('mayor.mandato.acao.create', ['axis' => $axis['id']]) }}"
                                        class="mandate-link-btn primary">Nova ação no eixo</a>
                                </div>
                            </div>
                        @empty
                            <div class="mandate-empty">Nenhum eixo ativo encontrado para este município.</div>
                        @endforelse
                    </div>
                </div>

                <div class="mandate-panel">
                    <div class="mandate-panel-head">
                        <div>
                            <div class="mandate-panel-title">Compromissos sem ação vinculada</div>
                            <div class="mandate-panel-subtitle">Instrumento principal de foco do módulo, organizado por
                                eixo temático para acelerar a abertura ou revisão de vínculos.</div>
                        </div>
                        <a href="{{ route('mayor.mandato.painel', ['area' => 'commitments']) }}"
                            class="mandate-link-btn">Abrir lista completa</a>
                    </div>
                    <div class="mandate-stack-list">
                        @forelse ($dashboardPending as $axisGroup)
                            <div class="mandate-mini-card">
                                <div class="mandate-card-top">
                                    <div class="mandate-card-title">
                                        {{ trim(($axisGroup['axis_icon'] ?? '') . ' ' . ($axisGroup['axis_name'] ?? 'Eixo do plano')) }}
                                    </div>
                                    <span class="mandate-chip">{{ $axisGroup['count'] ?? 0 }} pendente(s)</span>
                                </div>
                                <div class="mandate-stack-list" style="margin-top:.75rem">
                                    @foreach (array_slice($axisGroup['items'] ?? [], 0, 3) as $promise)
                                        <div class="mandate-mini-card" style="background:#fff">
                                            <div class="mandate-card-top">
                                                <div class="mandate-card-title">
                                                    {{ $promise['status_label'] ?? 'Pendente' }}</div>
                                                <span class="mandate-chip">Score {{ $promise['score'] ?? 0 }}</span>
                                            </div>
                                            <div class="mandate-card-desc">{{ $promise['text'] }}</div>
                                            @if (!empty($promise['radar_suggestions']))
                                                @php($topRadarSuggestion = $promise['radar_suggestions'][0])
                                                <div class="mandate-chip-row" style="margin-top:.7rem">
                                                    <span class="mandate-chip">Radar sugerido</span>
                                                    <span
                                                        class="mandate-chip">{{ $topRadarSuggestion['status_label'] ?? 'Ativa' }}</span>
                                                    <span class="mandate-chip">Aderência
                                                        {{ $topRadarSuggestion['score'] ?? 0 }}</span>
                                                </div>
                                                <div class="mandate-card-desc" style="margin-top:.55rem">
                                                    {{ $topRadarSuggestion['title'] ?? 'Oportunidade do Radar' }}
                                                </div>
                                                <div class="mandate-card-desc" style="font-size:.79rem;color:#64748b">
                                                    {{ $topRadarSuggestion['summary'] ?? 'Oportunidade ativa compativel com este compromisso.' }}
                                                </div>
                                            @endif
                                            @if (!empty($promise['resolve_ai_suggestions']))
                                                @php($topResolveSuggestion = $promise['resolve_ai_suggestions'][0])
                                                <div class="mandate-chip-row" style="margin-top:.7rem">
                                                    <span class="mandate-chip">Resolve ai</span>
                                                    <span
                                                        class="mandate-chip">{{ $topResolveSuggestion['theme'] ?? 'Entrega concluida' }}</span>
                                                    <span
                                                        class="mandate-chip">{{ $topResolveSuggestion['recurrence_total'] ?? 0 }}
                                                        recorrencias</span>
                                                </div>
                                                <div class="mandate-card-desc" style="margin-top:.55rem">
                                                    Evidencia concluida:
                                                    {{ $topResolveSuggestion['title'] ?? 'Demanda concluida' }}
                                                </div>
                                                <div class="mandate-card-desc" style="font-size:.79rem;color:#64748b">
                                                    {{ $topResolveSuggestion['summary'] ?? 'Entrega concluida e recorrente que pode ser registrada como acao de governo.' }}
                                                </div>
                                            @endif
                                            <div class="mandate-card-actions">
                                                <a href="{{ route('mayor.mandato.acao.create', ['axis' => $promise['axis_id'], 'promise' => $promise['id']]) }}"
                                                    class="mandate-link-btn primary">Criar ação vinculada</a>
                                                <a href="{{ route('mayor.mandato.painel', ['area' => 'actions', 'action_axis' => $promise['axis_id'], 'promise_review' => $promise['id']]) }}"
                                                    class="mandate-link-btn">Verificar ações existentes</a>
                                                @if (!empty($promise['radar_suggestions']))
                                                    <a href="{{ route('mayor.mandato.federal-programs') }}"
                                                        class="mandate-link-btn">Abrir Radar de Recursos</a>
                                                @endif
                                                @if (!empty($promise['resolve_ai_suggestions']))
                                                    <a href="{{ route('resolve-ai.demands.show', $promise['resolve_ai_suggestions'][0]['demand_id']) }}"
                                                        class="mandate-link-btn">Abrir demanda concluída</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="mandate-empty">Todos os compromissos ativos já têm ao menos uma ação vinculada
                                nesta
                                base.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="mandate-panel">
                <div class="mandate-panel-head">
                    <div>
                        <div class="mandate-panel-title">Ações recentes do mandato</div>
                        <div class="mandate-panel-subtitle">Últimas ações registradas, mantendo o painel executivo
                            conectado
                            à operação diária.</div>
                    </div>
                    <a href="{{ route('mayor.mandato.painel', ['area' => 'actions']) }}" class="mandate-link-btn">Abrir
                        área de ações</a>
                </div>
                <div class="mandate-grid-3">
                    @forelse ($dashboardRecentActions as $action)
                        <div class="mandate-action-card">
                            <div class="mandate-card-top">
                                <div class="mandate-card-title">{{ $action['title'] }}</div>
                                <span class="mandate-status-pill"
                                    style="background:{{ $action['status_color'] ?? '#f8fafc' }}15;color:{{ $action['status_color'] ?? '#475569' }};border-color:{{ $action['status_color'] ?? '#475569' }}33">
                                    {{ $action['status_label'] ?? 'Em andamento' }}
                                </span>
                            </div>
                            <div class="mandate-card-desc">
                                {{ \Illuminate\Support\Str::limit($action['description'] ?? 'Sem descrição detalhada.', 150) }}
                            </div>
                            <div class="mandate-card-meta">
                                @if (!empty($action['axis_name']))
                                    <span
                                        class="mandate-chip">{{ trim(($action['axis_icon'] ?? '') . ' ' . $action['axis_name']) }}</span>
                                @endif
                                <span class="mandate-chip">{{ $action['physical_progress'] ?? 0 }}% físico</span>
                                @if (($action['milestones_total'] ?? 0) > 0)
                                    <span class="mandate-chip">
                                        {{ $action['milestones_completed'] ?? 0 }}/{{ $action['milestones_total'] }}
                                        marcos
                                    </span>
                                @endif
                                @if (!empty($action['secretaria']))
                                    <span class="mandate-chip">{{ $action['secretaria'] }}</span>
                                @endif
                            </div>
                            <div class="mandate-card-actions">
                                <a href="{{ route('mayor.mandato.acao.edit', $action['id']) }}"
                                    class="mandate-link-btn primary">Abrir ação</a>
                            </div>
                        </div>
                    @empty
                        <div class="mandate-empty">Nenhuma ação recente cadastrada.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($activeArea === 'commitments')
            <section class="mandate-kpi-grid">
                @foreach ([['label' => 'Compromissos do plano', 'value' => $commitmentTotals['total'] ?? 0, 'meta' => 'Base ativa de promessas do mandato.'], ['label' => 'Atendidos', 'value' => $commitmentTotals['fulfilled'] ?? 0, 'meta' => 'Promessas com score pleno.'], ['label' => 'Parciais', 'value' => $commitmentTotals['partial'] ?? 0, 'meta' => 'Promessas parcialmente atendidas.'], ['label' => 'Pendentes', 'value' => $commitmentTotals['pending'] ?? 0, 'meta' => 'Promessas sem entrega suficiente.'], ['label' => 'Sem ação vinculada', 'value' => $commitmentTotals['without_actions'] ?? 0, 'meta' => 'Compromissos ainda sem desdobramento operacional.']] as $card)
                    <div class="mandate-kpi-card">
                        <div class="mandate-kpi-label">{{ $card['label'] }}</div>
                        <div class="mandate-kpi-value">{{ $card['value'] }}</div>
                        <div class="mandate-kpi-meta">{{ $card['meta'] }}</div>
                    </div>
                @endforeach
            </section>

            <section class="mandate-panel">
                <div class="mandate-panel-head">
                    <div>
                        <div class="mandate-panel-title">Lista de compromissos pendentes</div>
                        <div class="mandate-panel-subtitle">Seção dedicada aos compromissos ainda sem ação vinculada,
                            ordenados por eixo temático e com os dois atalhos operacionais do PDF.</div>
                    </div>
                </div>

                <div class="mandate-stack-list">
                    @forelse ($commitmentPendingFocusAxes as $axisGroup)
                        <div class="mandate-mini-card">
                            <div class="mandate-card-top">
                                <div class="mandate-card-title">
                                    {{ trim(($axisGroup['axis_icon'] ?? '') . ' ' . ($axisGroup['axis_name'] ?? 'Eixo')) }}
                                </div>
                                <span class="mandate-chip">{{ $axisGroup['count'] ?? 0 }} compromisso(s)</span>
                            </div>
                            @if (!empty($axisGroup['axis_description']))
                                <div class="mandate-card-desc" style="margin-bottom:.8rem">
                                    {{ $axisGroup['axis_description'] }}</div>
                            @endif

                            <div class="mandate-stack-list">
                                @foreach ($axisGroup['items'] ?? [] as $promise)
                                    <div class="mandate-mini-card" style="background:#fff">
                                        <div class="mandate-card-top">
                                            <div class="mandate-card-title">{{ $promise['text'] }}</div>
                                            <span class="mandate-status-pill"
                                                style="background:{{ $promise['status_color'] ?? '#f8fafc' }}15;color:{{ $promise['status_color'] ?? '#475569' }};border-color:{{ $promise['status_color'] ?? '#475569' }}33">
                                                {{ $promise['status_label'] ?? 'Pendente' }}
                                            </span>
                                        </div>
                                        <div class="mandate-chip-row">
                                            <span class="mandate-chip">Score {{ $promise['score'] ?? 0 }}</span>
                                            <span class="mandate-chip">{{ $promise['actions_count'] ?? 0 }}
                                                ação(ões)</span>
                                            @foreach (array_slice($promise['keywords'] ?? [], 0, 3) as $keyword)
                                                <span class="mandate-chip">{{ $keyword }}</span>
                                            @endforeach
                                        </div>
                                        @if (!empty($promise['radar_suggestions']))
                                            @php($topRadarSuggestion = $promise['radar_suggestions'][0])
                                            <div class="mandate-card-desc" style="margin-top:.7rem">
                                                Sugestao do Radar:
                                                {{ $topRadarSuggestion['title'] ?? 'Oportunidade ativa' }}
                                            </div>
                                            <div class="mandate-chip-row">
                                                <span
                                                    class="mandate-chip">{{ $topRadarSuggestion['status_label'] ?? 'Ativa' }}</span>
                                                <span class="mandate-chip">Aderência
                                                    {{ $topRadarSuggestion['score'] ?? 0 }}</span>
                                                @if (!empty($topRadarSuggestion['area']))
                                                    <span class="mandate-chip">{{ $topRadarSuggestion['area'] }}</span>
                                                @endif
                                            </div>
                                            <div class="mandate-card-desc" style="font-size:.79rem;color:#64748b">
                                                {{ $topRadarSuggestion['summary'] ?? 'Oportunidade ativa compativel com este compromisso.' }}
                                            </div>
                                        @endif
                                        @if (!empty($promise['resolve_ai_suggestions']))
                                            @php($topResolveSuggestion = $promise['resolve_ai_suggestions'][0])
                                            <div class="mandate-card-desc" style="margin-top:.7rem">
                                                Evidencia do Resolve ai:
                                                {{ $topResolveSuggestion['title'] ?? 'Demanda concluida' }}
                                            </div>
                                            <div class="mandate-chip-row">
                                                <span
                                                    class="mandate-chip">{{ $topResolveSuggestion['theme'] ?? 'Entrega concluida' }}</span>
                                                <span
                                                    class="mandate-chip">{{ $topResolveSuggestion['recurrence_total'] ?? 0 }}
                                                    recorrencias</span>
                                                @if (!empty($topResolveSuggestion['locality']))
                                                    <span
                                                        class="mandate-chip">{{ $topResolveSuggestion['locality'] }}</span>
                                                @endif
                                            </div>
                                            <div class="mandate-card-desc" style="font-size:.79rem;color:#64748b">
                                                {{ $topResolveSuggestion['summary'] ?? 'Entrega concluida e recorrente que pode ser registrada como acao de governo.' }}
                                            </div>
                                        @endif
                                        <div class="mandate-card-actions">
                                            <a href="{{ route('mayor.mandato.acao.create', ['axis' => $promise['axis_id'], 'promise' => $promise['id']]) }}"
                                                class="mandate-link-btn primary">Criar ação vinculada</a>
                                            <a href="{{ route('mayor.mandato.painel', ['area' => 'actions', 'action_axis' => $promise['axis_id'], 'promise_review' => $promise['id']]) }}"
                                                class="mandate-link-btn">Verificar ações existentes</a>
                                            @if (!empty($promise['radar_suggestions']))
                                                <a href="{{ route('mayor.mandato.federal-programs') }}"
                                                    class="mandate-link-btn">Abrir Radar de Recursos</a>
                                            @endif
                                            @if (!empty($promise['resolve_ai_suggestions']))
                                                <a href="{{ route('resolve-ai.demands.show', $promise['resolve_ai_suggestions'][0]['demand_id']) }}"
                                                    class="mandate-link-btn">Abrir demanda concluída</a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="mandate-empty">Todos os compromissos pendentes já possuem alguma ação vinculada na base
                            atual.</div>
                    @endforelse
                </div>
            </section>

            <section class="mandate-panel">
                <div class="mandate-panel-head">
                    <div>
                        <div class="mandate-panel-title">Compromissos do plano por eixo</div>
                        <div class="mandate-panel-subtitle">Esta área passa a ser a leitura principal do plano de governo
                            dentro do shell do `Mandato`.</div>
                    </div>
                    <div class="mandate-card-actions" style="margin-top:0">
                        <a href="{{ route('mayor.mandato.eixos') }}" class="mandate-link-btn">Gerenciar eixos</a>
                        <a href="{{ route('mayor.mandato.acao.create') }}" class="mandate-link-btn primary">Nova
                            ação</a>
                    </div>
                </div>

                <div class="mandate-commitment-axis-list">
                    @forelse ($commitmentAxes as $axis)
                        <div class="mandate-commitment-axis">
                            <div class="mandate-axis-top">
                                <div>
                                    <div class="mandate-axis-name">
                                        {{ trim(($axis['icon'] ?? '') . ' ' . ($axis['name'] ?? 'Eixo')) }}</div>
                                    @if (!empty($axis['description']))
                                        <div class="mandate-axis-desc">{{ $axis['description'] }}</div>
                                    @endif
                                </div>
                                <span class="mandate-score-pill"
                                    style="background:{{ $axis['score_color'] ?? '#f8fafc' }}15;color:{{ $axis['score_color'] ?? '#334155' }};border-color:{{ $axis['score_color'] ?? '#334155' }}33">
                                    Score {{ $axis['score'] ?? 0 }}
                                </span>
                            </div>
                            <div class="mandate-axis-metrics">
                                <span class="mandate-axis-metric">Atendidos:
                                    {{ $axis['promise_counts']['fulfilled'] ?? 0 }}</span>
                                <span class="mandate-axis-metric">Parciais:
                                    {{ ($axis['promise_counts']['partial_25'] ?? 0) + ($axis['promise_counts']['partial_50'] ?? 0) + ($axis['promise_counts']['partial_75'] ?? 0) }}</span>
                                <span class="mandate-axis-metric">Pendentes:
                                    {{ $axis['promise_counts']['pending'] ?? 0 }}</span>
                            </div>
                            <div class="mandate-promise-list">
                                @foreach ($axis['promises'] as $promise)
                                    <div class="mandate-promise-item">
                                        <div class="mandate-card-top">
                                            <p>{{ $promise['text'] }}</p>
                                            <span class="mandate-status-pill"
                                                style="background:{{ $promise['status_color'] ?? '#f8fafc' }}15;color:{{ $promise['status_color'] ?? '#475569' }};border-color:{{ $promise['status_color'] ?? '#475569' }}33">
                                                {{ $promise['status_label'] ?? 'Pendente' }}
                                            </span>
                                        </div>
                                        <div class="mandate-chip-row">
                                            <span class="mandate-chip">Score {{ $promise['score'] ?? 0 }}</span>
                                            <span class="mandate-chip">{{ $promise['actions_count'] ?? 0 }} ação(ões)
                                                vinculadas</span>
                                        </div>
                                        <div class="mandate-card-actions">
                                            <a href="{{ route('mayor.mandato.acao.create', ['axis' => $promise['axis_id'], 'promise' => $promise['id']]) }}"
                                                class="mandate-link-btn primary">Nova ação para este compromisso</a>
                                            <a href="{{ route('mayor.mandato.painel', ['area' => 'actions', 'action_axis' => $promise['axis_id']]) }}"
                                                class="mandate-link-btn">Ver ações do eixo</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="mandate-empty">Nenhum compromisso ativo cadastrado. Use `Gerenciar eixos` para
                            estruturar o plano de governo.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($activeArea === 'actions')
            <section class="mandate-kpi-grid">
                @foreach ([['label' => 'Ações totais', 'value' => $actionTotals['total'] ?? 0, 'meta' => 'Base ativa de ações de governo.'], ['label' => 'Concluídas', 'value' => $actionTotals['completed'] ?? 0, 'meta' => 'Ações finalizadas pela equipe.'], ['label' => 'Em andamento', 'value' => $actionTotals['running'] ?? 0, 'meta' => 'Execução em curso.'], ['label' => 'Não iniciadas', 'value' => $actionTotals['not_started'] ?? 0, 'meta' => 'Ações mapeadas sem início formal.'], ['label' => 'Suspensas', 'value' => $actionTotals['suspended'] ?? 0, 'meta' => 'Ações bloqueadas ou pausadas.']] as $card)
                    <div class="mandate-kpi-card">
                        <div class="mandate-kpi-label">{{ $card['label'] }}</div>
                        <div class="mandate-kpi-value">{{ $card['value'] }}</div>
                        <div class="mandate-kpi-meta">{{ $card['meta'] }}</div>
                    </div>
                @endforeach
            </section>

            <section class="mandate-panel">
                <div class="mandate-panel-head">
                    <div>
                        <div class="mandate-panel-title">Ações de governo</div>
                        <div class="mandate-panel-subtitle">Lista operacional unificada do mandato, preparada para ser a
                            base do fluxo diário da gestão.</div>
                    </div>
                    <a href="{{ route('mayor.mandato.acao.create') }}" class="mandate-link-btn primary">Nova ação</a>
                </div>

                <form method="GET" action="{{ route('mayor.mandato.painel') }}" class="mandate-toolbar">
                    <input type="hidden" name="area" value="actions">
                    @if (!empty($actionReviewPromise['id']))
                        <input type="hidden" name="promise_review" value="{{ $actionReviewPromise['id'] }}">
                    @endif
                    <input type="text" name="action_search" value="{{ $actionFilters['search'] ?? '' }}"
                        placeholder="Buscar por título, secretaria, descrição ou região">
                    <select name="action_axis">
                        <option value="all">Todos os eixos</option>
                        @foreach ($actionOptions['axes'] ?? [] as $axis)
                            <option value="{{ $axis['id'] }}" @selected(($actionFilters['axis'] ?? 'all') == $axis['id'])>
                                {{ trim(($axis['icon'] ?? '') . ' ' . $axis['name']) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="action_status">
                        <option value="all">Todos os status</option>
                        @foreach (['planejado' => 'Planejado', 'nao_iniciado' => 'Não iniciado', 'em_andamento' => 'Em andamento', 'concluido' => 'Concluído', 'suspenso' => 'Suspenso'] as $value => $label)
                            <option value="{{ $value }}" @selected(($actionFilters['status'] ?? 'all') === $value)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="mandate-shell-btn light">Filtrar</button>
                </form>

                @if (!empty($actionReviewPromise))
                    <div
                        style="margin-bottom:1rem;padding:1rem 1.05rem;border-radius:12px;border:1px solid #fde68a;background:#fffbeb">
                        <div style="font-size:.9rem;font-weight:700;color:#92400e;margin-bottom:.25rem">
                            Revisão de vínculo pendente
                        </div>
                        <div style="font-size:.84rem;color:var(--ink)">
                            Verifique se alguma ação deste eixo deveria estar vinculada ao compromisso:
                            <strong>{{ $actionReviewPromise['text'] }}</strong>
                        </div>
                        <div class="mandate-card-actions" style="margin-top:.85rem">
                            <a href="{{ route('mayor.mandato.acao.create', ['axis' => $actionReviewPromise['axis_id'], 'promise' => $actionReviewPromise['id']]) }}"
                                class="mandate-link-btn primary">Criar nova ação para este compromisso</a>
                            <a href="{{ route('mayor.mandato.painel', ['area' => 'commitments']) }}"
                                class="mandate-link-btn">Voltar para pendências</a>
                        </div>
                    </div>
                @endif

                <div class="mandate-action-list">
                    @forelse ($actionItems as $action)
                        <div class="mandate-action-card">
                            <div class="mandate-card-top">
                                <div>
                                    <div class="mandate-card-title">{{ $action['title'] }}</div>
                                    <div class="mandate-card-desc">
                                        {{ \Illuminate\Support\Str::limit($action['description'] ?? 'Sem descrição detalhada.', 190) }}
                                    </div>
                                </div>
                                <span class="mandate-status-pill"
                                    style="background:{{ $action['status_color'] ?? '#f8fafc' }}15;color:{{ $action['status_color'] ?? '#475569' }};border-color:{{ $action['status_color'] ?? '#475569' }}33">
                                    {{ $action['status_label'] ?? 'Em andamento' }}
                                </span>
                            </div>
                            <div class="mandate-card-meta">
                                @if (!empty($action['axis_name']))
                                    <span
                                        class="mandate-chip">{{ trim(($action['axis_icon'] ?? '') . ' ' . $action['axis_name']) }}</span>
                                @endif
                                <span class="mandate-chip">{{ $action['physical_progress'] ?? 0 }}% físico</span>
                                @if (($action['milestones_total'] ?? 0) > 0)
                                    <span class="mandate-chip">
                                        {{ $action['milestones_completed'] ?? 0 }}/{{ $action['milestones_total'] }}
                                        marcos
                                    </span>
                                @endif
                                @if (!empty($action['secretaria']))
                                    <span class="mandate-chip">{{ $action['secretaria'] }}</span>
                                @endif
                                @if (!empty($action['investment_formatted']) && $action['investment_formatted'] !== '—')
                                    <span class="mandate-chip">{{ $action['investment_formatted'] }}</span>
                                @endif
                                @if (!empty($action['region']))
                                    <span class="mandate-chip">{{ $action['region'] }}</span>
                                @endif
                                @if (!empty($action['end_date']))
                                    <span class="mandate-chip">Conclusão: {{ $action['end_date'] }}</span>
                                @endif
                            </div>
                            @if (!empty($action['promises']))
                                <div class="mandate-chip-row">
                                    @foreach ($action['promises'] as $promise)
                                        <span
                                            class="mandate-chip">{{ \Illuminate\Support\Str::limit($promise['text'], 80) }}
                                            · {{ $promise['level'] }}%</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="mandate-card-actions">
                                <a href="{{ route('mayor.mandato.acao.edit', $action['id']) }}"
                                    class="mandate-link-btn primary">Editar ação</a>
                                @if (!empty($action['proof_url']))
                                    <a href="{{ $action['proof_url'] }}" target="_blank" rel="noopener"
                                        class="mandate-link-btn">Abrir evidência</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="mandate-empty">Nenhuma ação encontrada com os filtros atuais.</div>
                    @endforelse
                </div>

                @if (method_exists($actionItems, 'links'))
                    <div class="mandate-pagination">{{ $actionItems->links() }}</div>
                @endif
            </section>
        @endif

        @if ($activeArea === 'briefings')
            <section class="mandate-grid-2">
                <div class="mandate-panel">
                    <div class="mandate-panel-head">
                        <div>
                            <div class="mandate-panel-title">Briefing do dia</div>
                            <div class="mandate-panel-subtitle">Leitura rápida da agenda, riscos e sinais estratégicos do
                                mandato dentro do shell unificado.</div>
                        </div>
                        <button type="button" class="mandate-shell-btn light"
                            onclick="generateMandatoBriefing(this)">Gerar briefing</button>
                    </div>

                    @if ($todayBriefing)
                        <div class="mandate-briefing-card">
                            <div class="mandate-card-top">
                                <div class="mandate-card-title">{{ $todayBriefing->date?->format('d/m/Y') }}</div>
                                <span class="mandate-status-pill">{{ $todayBriefing->is_read ? 'Lido' : 'Novo' }}</span>
                            </div>
                            <div class="mandate-briefing-content">
                                {{ \Illuminate\Support\Str::limit(strip_tags($todayBriefing->content), 950) }}
                            </div>
                            <div class="mandate-card-actions">
                                <a href="{{ route('mayor.mandato.briefings.show', $todayBriefing) }}"
                                    class="mandate-link-btn primary">Abrir briefing completo</a>
                            </div>
                        </div>
                    @else
                        <div class="mandate-empty">Nenhum briefing gerado para hoje ainda. Use o botão acima para criar a
                            leitura matinal do dia.</div>
                    @endif
                </div>

                <div class="mandate-panel">
                    <div class="mandate-panel-head">
                        <div>
                            <div class="mandate-panel-title">Histórico recente</div>
                            <div class="mandate-panel-subtitle">Últimos briefings disponíveis para consulta rápida da
                                gestão.</div>
                        </div>
                    </div>

                    <div class="mandate-stack-list">
                        @forelse ($recentBriefings as $briefing)
                            <a href="{{ route('mayor.mandato.briefings.show', $briefing) }}"
                                class="mandate-briefing-card" style="text-decoration:none">
                                <div class="mandate-card-top">
                                    <div class="mandate-card-title">{{ $briefing->date?->format('d/m/Y') }}</div>
                                    <span class="mandate-status-pill">{{ $briefing->is_read ? 'Lido' : 'Novo' }}</span>
                                </div>
                                <div class="mandate-card-desc">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($briefing->content), 180) }}</div>
                            </a>
                        @empty
                            <div class="mandate-empty">Ainda não há histórico de briefing para este município.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        async function generateMandatoBriefing(button) {
            const original = button.textContent;
            button.disabled = true;
            button.textContent = 'Gerando...';

            try {
                const response = await fetch('{{ route('mayor.mandato.briefings.generate') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({})
                });

                const data = await response.json();
                if (!response.ok || !data.id) {
                    throw new Error(data.error || 'Não foi possível gerar o briefing.');
                }

                window.location.href = '{{ route('mayor.mandato.painel', ['area' => 'briefings']) }}';
            } catch (error) {
                alert(error.message || 'Erro ao gerar o briefing.');
            } finally {
                button.disabled = false;
                button.textContent = original;
            }
        }
    </script>
@endpush
