@extends('layouts.mayor')

@section('title', 'Resolve ai')
@section('topbar-title', 'Resolve ai')

@push('styles')
    <style>
        .resolve-layout {
            padding: 1.75rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            max-width: 1180px;
        }

        .hero,
        .card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 16px;
        }

        .hero {
            padding: 1.4rem 1.6rem;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
        }

        .hero h1 {
            font-family: "Outfit", sans-serif;
            font-size: 1.45rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .hero p {
            font-size: .86rem;
            color: var(--ink-muted);
            max-width: 780px;
            line-height: 1.55;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .75rem;
        }

        .summary-card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 1rem 1.05rem;
        }

        .summary-card .label {
            font-size: .72rem;
            text-transform: uppercase;
            color: var(--ink-muted);
            letter-spacing: .06em;
            font-weight: 700;
        }

        .summary-card .value {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--ink);
            margin-top: .25rem;
        }

        .summary-card .meta {
            margin-top: .3rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.45;
        }

        .split-grid {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 1rem;
        }

        .card {
            padding: 1.2rem 1.3rem;
        }

        .card h2 {
            font-family: "Outfit", sans-serif;
            font-size: 1rem;
            color: var(--ink);
            margin-bottom: .35rem;
        }

        .card p.section-copy {
            font-size: .8rem;
            color: var(--ink-muted);
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .voice-hero {
            background: var(--ink);
            border-radius: 14px;
            padding: 1.3rem 1.35rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
        }

        .vh-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            flex-shrink: 0;
            background: rgba(255, 255, 255, .08);
            border: 2px solid rgba(255, 255, 255, .12);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .vh-btn svg {
            width: 26px;
            height: 26px;
            color: #fff;
        }

        .vh-btn.recording {
            background: var(--red);
            border-color: var(--red);
        }

        .vh-text h3 {
            color: #fff;
            font-size: 1rem;
            margin-bottom: .2rem;
        }

        .vh-text p,
        .vh-status {
            font-size: .78rem;
            color: rgba(255, 255, 255, .65);
            line-height: 1.45;
        }

        .vh-status {
            color: var(--gold-lt);
            margin-top: .4rem;
            min-height: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
            margin-bottom: .85rem;
        }

        .full {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .form-label {
            font-size: .73rem;
            font-weight: 700;
            color: var(--ink-soft);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: .62rem .8rem;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--ink);
            font-size: .84rem;
            outline: none;
        }

        .form-textarea {
            min-height: 92px;
            resize: vertical;
        }

        .transcript-preview {
            display: none;
            margin-top: .85rem;
            background: #fdfaf4;
            border: 1.5px solid var(--gold);
            border-radius: 12px;
            padding: 1rem 1.05rem;
        }

        .transcript-preview.visible {
            display: block;
        }

        .actions-row {
            display: flex;
            justify-content: flex-end;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .56rem .95rem;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1.5px solid transparent;
        }

        .btn-dark {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .btn-outline {
            background: var(--white);
            color: var(--ink);
            border-color: var(--border);
        }

        .btn-gold {
            background: var(--gold);
            color: #fff;
            border-color: var(--gold);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .75rem;
            align-items: end;
        }

        .area-bars {
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .area-row {
            display: grid;
            grid-template-columns: 130px 1fr 40px;
            gap: .55rem;
            align-items: center;
            font-size: .76rem;
            color: var(--ink-soft);
        }

        .bar {
            height: 10px;
            background: #ece7df;
            border-radius: 999px;
            overflow: hidden;
        }

        .bar>span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, var(--gold), var(--gold-lt));
        }

        .demand-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .demand-row {
            display: grid;
            grid-template-columns: 1.5fr .9fr .8fr auto;
            gap: 1rem;
            align-items: start;
            padding: 1rem 1.05rem;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            background: var(--white);
        }

        .demand-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.5;
        }

        .demand-copy {
            font-size: .77rem;
            color: var(--ink-muted);
            line-height: 1.45;
            margin-top: .3rem;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-top: .5rem;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
        }

        .meta-stack {
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .empty {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--ink-muted);
            font-size: .85rem;
        }

        .territory-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .territory-list {
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }

        .territory-item {
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: .85rem .9rem;
            background: #fff;
        }

        .territory-item-head {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: baseline;
        }

        .territory-item-title {
            font-size: .85rem;
            font-weight: 700;
            color: var(--ink);
        }

        .territory-item-count {
            font-size: .78rem;
            font-weight: 700;
            color: var(--gold);
        }

        .territory-item-meta {
            margin-top: .35rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .performance-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .performance-item {
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: .95rem 1rem;
            background: #fff;
        }

        .performance-head {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: center;
        }

        .performance-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        .performance-score {
            min-width: 74px;
            text-align: center;
            border-radius: 999px;
            padding: .28rem .6rem;
            font-size: .78rem;
            font-weight: 800;
        }

        .performance-score.good {
            background: #ecfdf5;
            color: #047857;
        }

        .performance-score.neutral {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .performance-score.risk {
            background: #fef2f2;
            color: #b91c1c;
        }

        .performance-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .55rem;
            margin-top: .7rem;
        }

        .metric-box {
            border-radius: 12px;
            background: #faf7f2;
            padding: .7rem .75rem;
        }

        .metric-box .label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--ink-muted);
            font-weight: 700;
        }

        .metric-box .value {
            margin-top: .22rem;
            font-size: 1rem;
            font-weight: 800;
            color: var(--ink);
        }

        .metric-box .meta {
            margin-top: .18rem;
            font-size: .72rem;
            color: var(--ink-muted);
            line-height: 1.45;
        }

        .delta-chip {
            display: inline-flex;
            align-items: center;
            padding: .2rem .5rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
        }

        .delta-chip.up {
            background: #ecfdf5;
            color: #047857;
        }

        .delta-chip.down {
            background: #fef2f2;
            color: #b91c1c;
        }

        .delta-chip.stable {
            background: #f3f4f6;
            color: #6b7280;
        }

        @media (max-width: 980px) {

            .summary-grid,
            .territory-grid,
            .split-grid,
            .filter-grid,
            .demand-row,
            .form-grid,
            .performance-metrics {
                grid-template-columns: 1fr;
            }

            .resolve-layout {
                padding: 1rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $statusMeta = function ($status) {
            return match ($status) {
                'registered', 'pending' => ['label' => 'Registrada', 'bg' => '#fffbeb', 'color' => '#b45309'],
                'in_progress' => ['label' => 'Em andamento', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
                'overdue' => ['label' => 'Atrasada', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
                'awaiting_confirmation' => [
                    'label' => 'Aguardando confirmação',
                    'bg' => '#f5f3ff',
                    'color' => '#7c3aed',
                ],
                'completed', 'resolved' => ['label' => 'Concluída', 'bg' => '#ecfdf5', 'color' => '#047857'],
                'reopened' => ['label' => 'Reaberta', 'bg' => '#fff7ed', 'color' => '#c2410c'],
                default => [
                    'label' => ucfirst(str_replace('_', ' ', $status)),
                    'bg' => '#f3f4f6',
                    'color' => '#6b7280',
                ],
            };
        };
        $priorityMeta = function ($priority) {
            return match ($priority) {
                'alta' => ['label' => 'Alta', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
                'baixa' => ['label' => 'Baixa', 'bg' => '#ecfdf5', 'color' => '#047857'],
                default => ['label' => 'Média', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
            };
        };
        $dueLabel = function ($demand) {
            $dueAt =
                $demand->due_at ?? ($demand->due_date ? \Carbon\Carbon::parse($demand->due_date)->endOfDay() : null);
            if (!$dueAt) {
                return 'Prazo não  definido';
            }
            if (in_array($demand->status, ['completed', 'resolved'], true)) {
                return 'Concluída';
            }
            if ($dueAt->isPast()) {
                return 'Atrasada há ' . $dueAt->diffForHumans(null, true);
            }
            return 'Vence ' . $dueAt->diffForHumans();
        };
        $areaMax = max(1, collect($summary['by_area'] ?? [])->max('total'));
        $scoreClass = fn($tone) => match ($tone) {
            'good' => 'good',
            'risk' => 'risk',
            default => 'neutral',
        };
    @endphp

    <div class="resolve-layout">
        <div class="hero">
            <div>
                <h1>{{ $isSecretaryPanel ? 'Minha fila da secretaria' : 'Resolve ai' }}</h1>
                <p>
                    {{ $isSecretaryPanel
                        ? 'Acompanhe as demandas da sua secretaria, atualize o andamento e conclua entregas com rastreabilidade operacional.'
                        : 'Registre, encaminhe e acompanhe demandas da gestão com prazo, prioridade, histórico e rastreabilidade operacional.' }}
                </p>
            </div>
        </div>

        @if (session('success'))
            <div
                style="background:var(--green-bg);border:1px solid #cfe9d9;color:var(--green);border-radius:12px;padding:.9rem 1rem;font-size:.84rem">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                style="background:var(--red-bg);border:1px solid #f3caca;color:var(--red);border-radius:12px;padding:.9rem 1rem;font-size:.84rem;line-height:1.55">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Demandas abertas</div>
                <div class="value">{{ $summary['open_total'] }}</div>
                <div class="meta">Registradas, em andamento, reabertas, atrasadas e aguardando confirmação.</div>
            </div>
            <div class="summary-card">
                <div class="label">Atrasadas</div>
                <div class="value" style="color:#b91c1c">{{ $summary['overdue_total'] }}</div>
                <div class="meta">Itens com prazo vencido e ainda sem encerramento confirmado.</div>
            </div>
            <div class="summary-card">
                <div class="label">Aguardando confirmação</div>
                <div class="value" style="color:#7c3aed">{{ $summary['awaiting_confirmation_total'] }}</div>
                <div class="meta">Demandas concluídas pela execução e aguardando validação do criador.</div>
            </div>
            <div class="summary-card">
                <div class="label">Concluídas no mês</div>
                <div class="value" style="color:#047857">{{ $summary['completed_month'] }}</div>
                <div class="meta">Fechadas com confirmação no mês corrente.</div>
            </div>
            <div class="summary-card">
                <div class="label">Sem andamento</div>
                <div class="value" style="color:#c2410c">{{ $summary['stalled_total'] ?? 0 }}</div>
                <div class="meta">Demandas abertas já elegíveis para cobrança automática por inatividade.</div>
            </div>
            <div class="summary-card">
                <div class="label">Cobrança recorrente</div>
                <div class="value" style="color:#b91c1c">{{ $summary['overdue_repeat_due_total'] ?? 0 }}</div>
                <div class="meta">Demandas atrasadas que já entraram na régua repetida de acompanhamento.</div>
            </div>
        </div>

        <!--<div class="card" style="padding:1rem 1.1rem">
                                        <h2>Regras operacionais</h2>
                                        <p class="section-copy" style="margin-bottom:.65rem">
                                            Prazos e canais ativos configurados para este município no Resolve ai.
                                        </p>
                                        <div
                                            style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;font-size:.8rem;color:var(--ink-soft)">
                                            <div><strong>Alta:</strong> {{ $resolveAiSettings['priority_hours']['alta'] ?? 48 }}h</div>
                                            <div><strong>Média:</strong> {{ $resolveAiSettings['priority_hours']['media'] ?? 168 }}h</div>
                                            <div><strong>Baixa:</strong> {{ $resolveAiSettings['priority_hours']['baixa'] ?? 360 }}h</div>
                                            <div><strong>Alerta:</strong> {{ $resolveAiSettings['alert_lead_hours'] ?? 24 }}h antes</div>
                                            <div><strong>Sem andamento:</strong> {{ $resolveAiSettings['inactivity_followup_hours'] ?? 48 }}h</div>
                                            <div><strong>Repetição atraso:</strong> {{ $resolveAiSettings['overdue_repeat_hours'] ?? 24 }}h</div>
                                            <div><strong>Janela recente:</strong> {{ $resolveAiSettings['comparative_recent_window_days'] ?? 90 }} dias
                                            </div>
                                            <div><strong>Janela anterior:</strong> {{ $resolveAiSettings['comparative_previous_window_days'] ?? 90 }}
                                                dias</div>
                                        </div>
                                    </div>-->

        <!--<div class="card">
                                    <h2>Desempenho por secretaria</h2>
                                    <p class="section-copy">
                                        Governança comparativa em {{ $secretariatPerformance['window_label'] }} com leitura de execução,
                                        atraso, tempo médio de fechamento e tendência em {{ $secretariatPerformance['comparison_label'] }}.
                                    </p>
                                    <div class="performance-list">
                                        @forelse ($secretariatPerformance['areas'] as $areaPerformance)
    <div class="performance-item">
                                                <div class="performance-head">
                                                    <div>
                                                        <div class="performance-title">{{ $areaPerformance['area_name'] }}</div>
                                                        <div class="territory-item-meta" style="margin-top:.2rem">
                                                            Hotspot principal: {{ $areaPerformance['top_locality'] }} · Tema dominante:
                                                            {{ $areaPerformance['top_theme'] }}
                                                        </div>
                                                    </div>
                                                    <div class="performance-score {{ $scoreClass($areaPerformance['score_tone']) }}">
                                                        {{ number_format($areaPerformance['score'], 1, ',', '.') }}
                                                    </div>
                                                </div>
                                                <div class="territory-item-meta" style="margin-top:.55rem">
                                                    <span class="delta-chip {{ $areaPerformance['trend_direction'] }}">
                                                        {{ $areaPerformance['trend_label'] }}
                                                    </span>
                                                    {{ $areaPerformance['score_label'] }} ·
                                                    {{ $areaPerformance['comparison_label'] ?? $secretariatPerformance['comparison_label'] }}
                                                </div>
                                                <div class="performance-metrics">
                                                    <div class="metric-box">
                                                        <div class="label">Volume</div>
                                                        <div class="value">{{ $areaPerformance['total'] }}</div>
                                                        <div class="meta">
                                                            Abertas: {{ $areaPerformance['open_total'] }} · Atrasadas:
                                                            {{ $areaPerformance['overdue_total'] }}
                                                        </div>
                                                    </div>
                                                    <div class="metric-box">
                                                        <div class="label">Resolução</div>
                                                        <div class="value">{{ number_format($areaPerformance['resolution_rate'], 1, ',', '.') }}%
                                                        </div>
                                                        <div class="meta">
                                                            Concluídas: {{ $areaPerformance['completed_total'] }} · No prazo:
                                                            {{ number_format($areaPerformance['on_time_rate'], 1, ',', '.') }}%
                                                        </div>
                                                    </div>
                                                    <div class="metric-box">
                                                        <div class="label">Tempo médio</div>
                                                        <div class="value">{{ $areaPerformance['avg_resolution_label'] }}</div>
                                                        <div class="meta">
                                                            Janela recente: {{ $areaPerformance['recent_total'] }} · Anterior:
                                                            {{ $areaPerformance['previous_total'] }}
                                                        </div>
                                                    </div>
                                                    <div class="metric-box">
                                                        <div class="label">Tendência</div>
                                                        <div class="value">
                                                            {{ $areaPerformance['score_delta'] > 0 ? '+' : '' }}{{ number_format($areaPerformance['score_delta'], 1, ',', '.') }}
                                                        </div>
                                                        <div class="meta">
                                                            Resolução
                                                            {{ number_format($areaPerformance['previous_resolution_rate'], 1, ',', '.') }}% ->
                                                            {{ number_format($areaPerformance['recent_resolution_rate'], 1, ',', '.') }}% · Atraso
                                                            {{ number_format($areaPerformance['previous_overdue_rate'], 1, ',', '.') }}% ->
                                                            {{ number_format($areaPerformance['recent_overdue_rate'], 1, ',', '.') }}% · Backlog
                                                            {{ number_format($areaPerformance['previous_backlog_rate'], 1, ',', '.') }}% ->
                                                            {{ number_format($areaPerformance['recent_backlog_rate'], 1, ',', '.') }}%
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                @empty
                                            <div class="empty">Ainda não há dados suficientes para comparar o desempenho entre secretarias.</div>
    @endforelse
                                    </div>
                                </div>-->

        <!--<div class="territory-grid">
                                <div class="card">
                                    <h2>Reincidência em alta</h2>
                                    <p class="section-copy">
                                        Localidade + tema onde o problema está subindo na janela recente e exige cobrança mais firme.
                                    </p>
                                    <div class="territory-list">
                                        @forelse ($territorialTrend['rising'] as $trendItem)
    <div class="territory-item">
                                                <div class="territory-item-head">
                                                    <div class="territory-item-title">{{ $trendItem['locality'] }}</div>
                                                    <div class="territory-item-count">{{ $trendItem['delta_label'] }}</div>
                                                </div>
                                                <div class="territory-item-meta">
                                                    Tema: {{ $trendItem['theme'] }}<br>
                                                    Secretaria dominante: {{ $trendItem['area_name'] }}<br>
                                                    {{ $trendItem['meta_label'] }} · Atrasadas na janela recente:
                                                    {{ $trendItem['recent_overdue_total'] }}
                                                </div>
                                            </div>
                    @empty
                                            <div class="empty" style="padding:1rem 0">Sem sinais relevantes de aumento de reincidência.</div>
    @endforelse
                                    </div>
                                </div>

                                <div class="card">
                                    <h2>Reincidência em queda</h2>
                                    <p class="section-copy">
                                        Territórios onde a pressão caiu, ajudando a identificar bolsões com redução de recorrência.
                                    </p>
                                    <div class="territory-list">
                                        @forelse ($territorialTrend['falling'] as $trendItem)
    <div class="territory-item">
                                                <div class="territory-item-head">
                                                    <div class="territory-item-title">{{ $trendItem['locality'] }}</div>
                                                    <div class="territory-item-count">{{ $trendItem['delta_label'] }}</div>
                                                </div>
                                                <div class="territory-item-meta">
                                                    Tema: {{ $trendItem['theme'] }}<br>
                                                    Secretaria dominante: {{ $trendItem['area_name'] }}<br>
                                                    {{ $trendItem['meta_label'] }} · Resolução recente:
                                                    {{ number_format($trendItem['recent_resolution_rate'], 1, ',', '.') }}%
                                                </div>
                                            </div>
                    @empty
                                            <div class="empty" style="padding:1rem 0">Ainda não há queda relevante para destacar.</div>
    @endforelse
                                    </div>
                                </div>

                                <div class="card">
                                    <h2>Execução no território</h2>
                                    <p class="section-copy">
                                        Leitura evolutiva do que está melhorando ou piorando no campo em
                                        {{ $territorialTrend['comparison_label'] }}.
                                    </p>
                                    <div class="territory-list">
                                        @forelse ($territorialTrend['execution'] as $trendItem)
    <div class="territory-item">
                                                <div class="territory-item-head">
                                                    <div class="territory-item-title">{{ $trendItem['locality'] }}</div>
                                                    <div class="territory-item-count">
                                                        {{ number_format($trendItem['execution_delta'], 1, ',', '.') }}</div>
                                                </div>
                                                <div class="territory-item-meta">
                                                    Tema: {{ $trendItem['theme'] }}<br>
                                                    {{ $trendItem['trend_label'] }} · Secretaria dominante: {{ $trendItem['area_name'] }}<br>
                                                    Resolução: {{ number_format($trendItem['previous_resolution_rate'], 1, ',', '.') }}% ->
                                                    {{ number_format($trendItem['recent_resolution_rate'], 1, ',', '.') }}% · Atraso:
                                                    {{ number_format($trendItem['previous_overdue_rate'], 1, ',', '.') }}% ->
                                                    {{ number_format($trendItem['recent_overdue_rate'], 1, ',', '.') }}% · Backlog:
                                                    {{ number_format($trendItem['previous_backlog_rate'], 1, ',', '.') }}% ->
                                                    {{ number_format($trendItem['recent_backlog_rate'], 1, ',', '.') }}%
                                                </div>
                                            </div>
                    @empty
                                            <div class="empty" style="padding:1rem 0">Sem variação operacional forte o suficiente para leitura
                                                evolutiva.</div>
    @endforelse
                                    </div>
                                </div>
                            </div>-->

        <!--<div class="territory-grid">
                            <div class="card">
                                <h2>Hotspots territoriais</h2>
                                <p class="section-copy">
                                    Bairros e localidades com maior recorrência operacional em
                                    {{ $territorialIntelligence['window_label'] }}.
                                </p>
                                <div class="territory-list">
                                    @forelse ($territorialIntelligence['hotspots'] as $hotspot)
    <div class="territory-item">
                                            <div class="territory-item-head">
                                                <div class="territory-item-title">{{ $hotspot['locality'] }}</div>
                                                <div class="territory-item-count">{{ $hotspot['total'] }} demanda(s)</div>
                                            </div>
                                            <div class="territory-item-meta">
                                                Tema dominante: {{ $hotspot['top_theme'] }}<br>
                                                Abertas: {{ $hotspot['open_total'] }} · Atrasadas: {{ $hotspot['overdue_total'] }} ·
                                                Concluídas: {{ $hotspot['completed_total'] }}<br>
                                                Último registro: {{ $hotspot['last_seen_at']?->format('d/m/Y') ?? '—' }}
                                            </div>
                                        </div>
                    @empty
                                        <div class="empty" style="padding:1rem 0">Ainda não há massa crítica para leitura territorial.
                                        </div>
    @endforelse
                                </div>
                            </div>

                            <div class="card">
                                <h2>Temas recorrentes</h2>
                                <p class="section-copy">
                                    Principais tipos de problema que mais reaparecem no território e onde pressionam mais.
                                </p>
                                <div class="territory-list">
                                    @forelse ($territorialIntelligence['themes'] as $theme)
    <div class="territory-item">
                                            <div class="territory-item-head">
                                                <div class="territory-item-title">{{ $theme['theme'] }}</div>
                                                <div class="territory-item-count">{{ $theme['total'] }} caso(s)</div>
                                            </div>
                                            <div class="territory-item-meta">
                                                Principal localidade: {{ $theme['top_locality'] }}<br>
                                                Secretaria mais acionada: {{ $theme['top_area'] }}<br>
                                                Abertos: {{ $theme['open_total'] }} · Atrasados: {{ $theme['overdue_total'] }}
                                            </div>
                                        </div>
                    @empty
                                        <div class="empty" style="padding:1rem 0">Os temas recorrentes aparecerão conforme a base
                                            crescer.</div>
    @endforelse
                                </div>
                            </div>

                            <div class="card">
                                <h2>Histórico por secretaria</h2>
                                <p class="section-copy">
                                    Memória operacional por pasta, cruzando volume, hotspot principal e temas mais frequentes.
                                </p>
                                <div class="territory-list">
                                    @forelse ($territorialIntelligence['areas'] as $areaHistory)
    <div class="territory-item">
                                            <div class="territory-item-head">
                                                <div class="territory-item-title">{{ $areaHistory['area_name'] }}</div>
                                                <div class="territory-item-count">{{ $areaHistory['total'] }} registro(s)</div>
                                            </div>
                                            <div class="territory-item-meta">
                                                Hotspot: {{ $areaHistory['top_locality'] }}<br>
                                                Temas: {{ implode(' · ', $areaHistory['top_themes']) ?: 'Atendimento geral' }}<br>
                                                Abertos: {{ $areaHistory['open_total'] }} · Atrasados: {{ $areaHistory['overdue_total'] }}
                                                · Concluídos: {{ $areaHistory['completed_total'] }}
                                            </div>
                                        </div>
                    @empty
                                        <div class="empty" style="padding:1rem 0">O histórico por secretaria será consolidado com novas
                                            demandas.</div>
    @endforelse
                                </div>
                            </div>
                        </div>-->

        <div class="split-grid">
            @if ($canCreateDemand)
                <div class="card">
                    <h2>Registro rápido da demanda</h2>
                    <p class="section-copy">
                        Capture por voz ou registre manualmente para iniciar o fluxo do Resolve ai com prioridade,
                        secretaria responsável e prazo.
                    </p>

                    <div class="voice-hero" id="voiceHero" onclick="toggleVoice()">
                        <div class="vh-btn" id="vhBtn">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm6-3c0 3.53-2.61 6.43-6 6.92V21h-2v-3.08C6.61 17.43 4 14.53 4 11h2c0 2.76 2.24 5 5 5s5-2.24 5-5h2z" />
                            </svg>
                        </div>
                        <div class="vh-text">
                            <h3 id="vhTitle">Gravar por voz</h3>
                            <p>Use o navegador para transcrever a demanda de campo e revisar antes de salvar.</p>
                            <div class="vh-status" id="vhStatus"></div>
                        </div>
                    </div>

                    <div class="transcript-preview" id="transcriptPreview">
                        <div
                            style="font-size:.72rem;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:.06em">
                            Transcrição capturada</div>
                        <div id="transcriptText"
                            style="margin-top:.45rem;font-size:.86rem;color:var(--ink);line-height:1.55">
                        </div>
                        <div class="actions-row" style="margin-top:.85rem;justify-content:flex-start">
                            <button class="btn btn-gold" type="button" onclick="sendToAssistant()">Enviar ao
                                assistente</button>
                            <button class="btn btn-outline" type="button" onclick="clearTranscript()">Descartar</button>
                        </div>
                    </div>

                    <form id="manualForm" method="POST" action="{{ route($routeBase . '.store') }}"
                        style="margin-top:1rem">
                        @csrf
                        <div class="form-grid">
                            <div class="form-group full">
                                <label class="form-label">Descrição</label>
                                <textarea class="form-textarea" id="manualText" name="raw_input"
                                    placeholder="Descreva a demanda recebida em campo..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bairro ou localidade</label>
                                <input class="form-input" id="manualLocation" name="locality" list="resolveAiLocalities"
                                    placeholder="Ex: Jardim América">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Endereço complementar</label>
                                <input class="form-input" name="address" placeholder="Rua, número ou referência">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Secretaria responsável</label>
                                @if ($lockedContactArea)
                                    <input class="form-input" value="{{ $lockedContactArea->name }}" disabled>
                                    <input type="hidden" name="contact_area_id" value="{{ $lockedContactArea->id }}">
                                @elseif ($contactAreas->count())
                                    <select class="form-select" id="manualArea" name="contact_area_id">
                                        <option value="">Selecione</option>
                                        @foreach ($contactAreas as $a)
                                            <option value="{{ $a->id }}">
                                                {{ $a->name }}{{ $a->contact_name ? ' — ' . $a->contact_name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input class="form-input" id="manualArea" name="area"
                                        placeholder="Ex: Secretaria de Obras">
                                @endif
                            </div>
                            <div class="form-group">
                                <label class="form-label">Prioridade</label>
                                <select class="form-select" name="priority" id="manualPriority">
                                    <option value="alta">Alta</option>
                                    <option value="media" selected>Média</option>
                                    <option value="baixa">Baixa</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Prazo manual</label>
                                <input class="form-input" type="date" name="due_date" id="manualDueDate">
                            </div>
                        </div>
                        <div class="actions-row">
                            <button type="button" class="btn btn-outline" onclick="sendManualToAssistant()">Pedir para o
                                assistente organizar</button>
                            <button type="submit" class="btn btn-dark">Salvar demanda</button>
                        </div>
                    </form>
                    @if (!empty($localities) && count($localities) > 0)
                        <datalist id="resolveAiLocalities">
                            @foreach ($localities as $locality)
                                <option value="{{ $locality->name }}">
                                    {{ ucfirst($locality->type) }}{{ $locality->zone ? ' · ' . $locality->zone : '' }}
                                </option>
                            @endforeach
                        </datalist>
                    @endif
                </div>
            @else
                <div class="card">
                    <h2>Minha fila operacional</h2>
                    <p class="section-copy">
                        Seu perfil está configurado apenas para acompanhamento e atualização do fluxo das demandas da
                        secretaria.
                    </p>
                </div>
            @endif

            <div class="card">
                <h2>Distribuição por executor</h2>
                <p class="section-copy">
                    Leitura rápida do volume atual por pasta para apoiar o acompanhamento do gabinete.
                </p>
                <div class="area-bars">
                    @forelse ($summary['by_area'] as $area)
                        <div class="area-row">
                            <div>{{ $area['name'] }}</div>
                            <div class="bar"><span
                                    style="width:{{ max(8, (int) round(($area['total'] / $areaMax) * 100)) }}%"></span>
                            </div>
                            <div style="text-align:right;font-weight:700;color:var(--ink)">{{ $area['total'] }}</div>
                        </div>
                    @empty
                        <div class="empty" style="padding:1rem 0">Nenhuma secretaria com demandas registradas ainda.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Painel de demandas</h2>
            <p class="section-copy">
                Filtre por status, prioridade, secretaria, criador e período para navegar pelo backlog do Resolve ai.
            </p>

            <form method="GET" action="{{ route($routeBase . '.index') }}" class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        @foreach (['all' => 'Todos', 'registered' => 'Registrada', 'in_progress' => 'Em andamento', 'overdue' => 'Atrasada', 'awaiting_confirmation' => 'Aguardando confirmação', 'completed' => 'Concluída', 'reopened' => 'Reaberta'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Prioridade</label>
                    <select class="form-select" name="priority">
                        @foreach (['all' => 'Todas', 'alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['priority'] === $value)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Secretaria</label>
                    <select class="form-select" name="contact_area_id">
                        <option value="">Todas</option>
                        @foreach ($contactAreas as $area)
                            <option value="{{ $area->id }}" @selected($filters['contact_area_id'] !== '' && (int) $filters['contact_area_id'] === (int) $area->id)>{{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Criador</label>
                    <select class="form-select" name="creator_id">
                        <option value="">Todos</option>
                        @foreach ($creators as $creator)
                            <option value="{{ $creator->id }}" @selected($filters['creator_id'] !== '' && (int) $filters['creator_id'] === (int) $creator->id)>{{ $creator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Localidade</label>
                    <input class="form-input" name="locality" value="{{ $filters['locality'] }}"
                        placeholder="Buscar bairro, endereço ou título">
                </div>
                <div class="form-group">
                    <label class="form-label">Período</label>
                    <select class="form-select" name="period">
                        @foreach (['all' => 'Todo período', '7d' => 'Últimos 7 dias', '30d' => 'Últimos 30 dias', '90d' => 'Últimos 90 dias'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="actions-row" style="grid-column:1/-1">
                    <a class="btn btn-outline" href="{{ route($routeBase . '.index') }}">Limpar filtros</a>
                    <button class="btn btn-dark" type="submit">Filtrar</button>
                </div>
            </form>

            <div class="demand-list" style="margin-top:1rem">
                @forelse ($demands as $demand)
                    @php
                        $status = $statusMeta($demand->status);
                        $priority = $priorityMeta($demand->priority);
                    @endphp
                    <div class="demand-row" style="background:{{ $demand->status === 'overdue' ? '#fff7f7' : '#fff' }}">
                        <div>
                            <div class="demand-title">
                                {{ $demand->title ?: \Illuminate\Support\Str::limit($demand->raw_input, 110) }}</div>
                            <div class="demand-copy">{{ \Illuminate\Support\Str::limit($demand->raw_input, 180) }}</div>
                            <div class="chips">
                                <span class="chip"
                                    style="background:{{ $status['bg'] }};color:{{ $status['color'] }}">{{ $status['label'] }}</span>
                                <span class="chip"
                                    style="background:{{ $priority['bg'] }};color:{{ $priority['color'] }}">{{ $priority['label'] }}</span>
                                @if ($demand->input_type === 'voice')
                                    <span class="chip" style="background:#fdfaf4;color:#b8902a">Voz</span>
                                @endif
                                @if ($demand->is_urgent)
                                    <span class="chip" style="background:#fef2f2;color:#b91c1c">Urgente</span>
                                @endif
                            </div>
                        </div>
                        <div class="meta-stack">
                            <div><strong>Secretaria:</strong>
                                {{ $demand->contactArea?->name ?? ($demand->area ?: 'Não definida') }}</div>
                            <div><strong>Local:</strong> {{ $demand->locality ?: 'Não informado' }}</div>
                            @if ($demand->address)
                                <div><strong>Endereço:</strong> {{ $demand->address }}</div>
                            @endif
                        </div>
                        <div class="meta-stack">
                            <div><strong>Criada:</strong> {{ $demand->created_at?->format('d/m/Y H:i') }}</div>
                            <div><strong>Criador:</strong> {{ $demand->registeredBy?->name ?? 'Sistema' }}</div>
                            <div><strong>Prazo:</strong> {{ $dueLabel($demand) }}</div>
                        </div>
                        <div>
                            <a class="btn btn-outline" href="{{ route($routeBase . '.show', $demand) }}">Abrir
                                demanda</a>
                        </div>
                    @empty
                        <div class="empty">Nenhuma demanda encontrada com os filtros atuais.</div>
                @endforelse
            </div>

            @if (method_exists($demands, 'links'))
                <div style="margin-top:1rem">{{ $demands->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let recognition = null;
        let isRecording = false;

        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('keydown', (e) => {
                if (e.code === 'Space' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    e.preventDefault();
                    toggleVoice();
                }
            });
        });

        function toggleVoice() {
            if (isRecording) {
                stopVoice();
                return;
            }

            if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                alert('Seu navegador não  suporta reconhecimento de voz. Use o Chrome.');
                return;
            }

            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SR();
            recognition.lang = 'pt-BR';
            recognition.continuous = true;
            recognition.interimResults = true;

            const btn = document.getElementById('vhBtn');
            const title = document.getElementById('vhTitle');
            const status = document.getElementById('vhStatus');

            btn.classList.add('recording');
            title.textContent = 'Gravando... toque para parar';
            status.textContent = 'Aguardando fala...';
            isRecording = true;

            let finalTranscript = '';

            recognition.onresult = (e) => {
                let interim = '';
                for (let i = e.resultIndex; i < e.results.length; i++) {
                    const transcript = e.results[i][0].transcript;
                    if (e.results[i].isFinal) {
                        finalTranscript += transcript + ' ';
                    } else {
                        interim += transcript;
                    }
                }
                status.textContent = interim || finalTranscript || '...';
            };

            recognition.onerror = () => stopVoice();
            recognition.onend = () => {
                stopVoice();
                if (finalTranscript.trim()) {
                    showTranscript(finalTranscript.trim());
                }
            };

            recognition.start();
        }

        function stopVoice() {
            if (recognition) {
                try {
                    recognition.stop();
                } catch (error) {}
            }
            isRecording = false;
            document.getElementById('vhBtn').classList.remove('recording');
            document.getElementById('vhTitle').textContent = 'Gravar por voz';
            document.getElementById('vhStatus').textContent = '';
        }

        function showTranscript(text) {
            document.getElementById('transcriptText').textContent = text;
            document.getElementById('transcriptPreview').classList.add('visible');
            document.getElementById('manualText').value = text;
        }

        function clearTranscript() {
            document.getElementById('transcriptPreview').classList.remove('visible');
            document.getElementById('transcriptText').textContent = '';
        }

        function sendToAssistant() {
            const text = document.getElementById('transcriptText').textContent;
            sessionStorage.setItem('chatPrefill',
                `Registre esta demanda no Resolve ai: "${text}". Organize por tema, localidade, secretaria responsável, prioridade e próximos passos.`
            );
            window.location.href = '{{ route('mayor.chat.index') }}';
        }

        function sendManualToAssistant() {
            const text = document.getElementById('manualText').value.trim();
            const location = document.getElementById('manualLocation').value.trim();
            const areaEl = document.getElementById('manualArea');
            const area = areaEl ? (areaEl.tagName === 'SELECT' ? areaEl.options[areaEl.selectedIndex]?.text : areaEl
                .value) : '';

            if (!text) {
                document.getElementById('manualText').focus();
                return;
            }

            const context = [text, location ? `Localidade: ${location}` : '', area ? `Secretaria: ${area}` : '']
                .filter(Boolean)
                .join('. ');

            sessionStorage.setItem('chatPrefill',
                `Registre e organize esta demanda no Resolve ai: "${context}". Sugira prioridade, secretaria responsável e próximos passos.`
            );
            window.location.href = '{{ route('mayor.chat.index') }}';
        }
    </script>
@endpush
