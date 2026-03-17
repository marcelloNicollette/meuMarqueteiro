@extends('layouts.mayor')
@section('title', 'Situação do Mandato')
@section('topbar-title', 'Situação do Mandato')

@push('styles')
    <style>
        .situacao-wrap {
            padding: 1.75rem 2rem;
            max-width: 1100px;
        }

        /* ── Cabeçalho ── */
        .situacao-header {
            background: linear-gradient(135deg, var(--ink) 0%, #1e2740 100%);
            border-radius: 16px;
            padding: 1.6rem 2rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .situacao-header-left h1 {
            font-family: 'Lora', serif;
            font-size: 1.25rem;
            color: #fff;
            margin-bottom: .3rem;
        }

        .situacao-header-left p {
            font-size: .8rem;
            color: rgba(255, 255, 255, .5);
        }

        .situacao-header-right {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        /* Progresso circular do mandato */
        .mandate-ring {
            position: relative;
            width: 90px;
            height: 90px;
        }

        .mandate-ring svg {
            transform: rotate(-90deg);
        }

        .mandate-ring-bg {
            fill: none;
            stroke: rgba(255, 255, 255, .12);
            stroke-width: 6;
        }

        .mandate-ring-fill {
            fill: none;
            stroke: var(--gold);
            stroke-width: 6;
            stroke-linecap: round;
            transition: stroke-dashoffset .8s ease;
        }

        .mandate-ring-text {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .mandate-ring-pct {
            font-family: 'Lora', serif;
            font-size: 1.3rem;
            font-weight: 600;
            color: #fff;
            line-height: 1;
        }

        .mandate-ring-label {
            font-size: .6rem;
            color: rgba(255, 255, 255, .5);
            text-align: center;
        }

        /* ── Grid de KPIs principais ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: .85rem;
            margin-bottom: 1.75rem;
        }

        .kpi-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.1rem 1rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s;
        }

        .kpi-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, .07);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .kpi-card.green::before {
            background: #1e7e48;
        }

        .kpi-card.blue::before {
            background: #1a5fa8;
        }

        .kpi-card.orange::before {
            background: #d97706;
        }

        .kpi-card.red::before {
            background: #b52b2b;
        }

        .kpi-card.gold::before {
            background: var(--gold);
        }

        .kpi-value {
            font-family: 'Lora', serif;
            font-size: 2rem;
            font-weight: 600;
            line-height: 1;
            margin-bottom: .25rem;
        }

        .kpi-value.green {
            color: #1e7e48;
        }

        .kpi-value.blue {
            color: #1a5fa8;
        }

        .kpi-value.orange {
            color: #d97706;
        }

        .kpi-value.red {
            color: #b52b2b;
        }

        .kpi-value.gold {
            color: var(--gold);
        }

        .kpi-label {
            font-size: .72rem;
            color: var(--ink-muted);
        }

        /* ── Layout de 2 colunas ── */
        .situacao-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .situacao-cols-3 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        /* ── Cards de seção ── */
        .section-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .section-card-header {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid var(--border-lt);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-card-header h3 {
            font-family: 'Lora', serif;
            font-size: .95rem;
            color: var(--ink);
            display: flex;
            align-items: center;
            gap: .45rem;
        }

        .section-card-header h3 svg {
            width: 15px;
            height: 15px;
        }

        .section-card-body {
            padding: 1.1rem 1.25rem;
        }

        /* ── Barras por área ── */
        .area-row {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: .85rem;
        }

        .area-row:last-child {
            margin-bottom: 0;
        }

        .area-name {
            font-size: .8rem;
            color: var(--ink-soft);
            width: 130px;
            flex-shrink: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .area-bar-wrap {
            flex: 1;
            background: var(--surface);
            border-radius: 4px;
            height: 7px;
            overflow: hidden;
        }

        .area-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #1a5fa8, var(--gold));
            transition: width .8s ease;
        }

        .area-bar-fill.risco {
            background: linear-gradient(90deg, #d97706, #b52b2b);
        }

        .area-pct {
            font-size: .76rem;
            font-weight: 600;
            color: var(--ink-muted);
            width: 35px;
            text-align: right;
            flex-shrink: 0;
        }

        .area-risco-badge {
            font-size: .65rem;
            padding: .1rem .4rem;
            border-radius: 10px;
            background: #fff3e0;
            color: #e65100;
            font-weight: 600;
        }

        /* ── Itens de compromisso ── */
        .commit-item {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            padding: .65rem 0;
            border-bottom: 1px solid var(--border-lt);
        }

        .commit-item:last-child {
            border-bottom: none;
        }

        .commit-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 5px;
        }

        .commit-dot.risco {
            background: #e65100;
        }

        .commit-dot.andamento {
            background: #1a5fa8;
        }

        .commit-dot.entregue {
            background: #1e7e48;
        }

        .commit-dot.prometido {
            background: var(--gold);
        }

        .commit-title {
            font-size: .84rem;
            color: var(--ink);
            font-weight: 500;
        }

        .commit-meta {
            font-size: .73rem;
            color: var(--ink-muted);
            margin-top: .1rem;
        }

        .commit-badge {
            font-size: .66rem;
            padding: .15rem .5rem;
            border-radius: 10px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .commit-badge.risco {
            background: #fff3e0;
            color: #e65100;
        }

        .commit-badge.andamento {
            background: #e3f2fd;
            color: #1565c0;
        }

        .commit-badge.entregue {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .commit-badge.prometido {
            background: var(--gold-bg);
            color: var(--gold);
        }

        /* ── Programas federais ── */
        .programa-item {
            padding: .75rem 0;
            border-bottom: 1px solid var(--border-lt);
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .programa-item:last-child {
            border-bottom: none;
        }

        .programa-score {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .programa-score.high {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .programa-score.med {
            background: #e3f2fd;
            color: #1565c0;
        }

        .programa-name {
            font-size: .83rem;
            color: var(--ink);
            font-weight: 500;
        }

        .programa-area {
            font-size: .73rem;
            color: var(--ink-muted);
            margin-top: .1rem;
        }

        /* ── Atividade (uso do app) ── */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .75rem;
        }

        .activity-item {
            background: var(--surface);
            border-radius: 10px;
            padding: .85rem 1rem;
            text-align: center;
        }

        .activity-value {
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: var(--ink);
            font-weight: 600;
            margin-bottom: .2rem;
        }

        .activity-label {
            font-size: .72rem;
            color: var(--ink-muted);
        }

        /* ── Recém entregues ── */
        .entregue-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .6rem 0;
            border-bottom: 1px solid var(--border-lt);
        }

        .entregue-item:last-child {
            border-bottom: none;
        }

        .entregue-check {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e8f5e9;
            color: #2e7d32;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .entregue-check svg {
            width: 13px;
            height: 13px;
        }

        .entregue-title {
            font-size: .83rem;
            color: var(--ink);
            flex: 1;
        }

        .entregue-date {
            font-size: .72rem;
            color: var(--ink-muted);
            white-space: nowrap;
        }

        /* Briefing do dia badge */
        .briefing-badge-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .8rem;
            border-radius: 20px;
            background: var(--gold-bg);
            color: var(--gold);
            font-size: .75rem;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s;
        }

        .briefing-badge-link:hover {
            background: var(--gold);
            color: white;
        }

        /* Ações no footer da seção */
        .section-link {
            font-size: .78rem;
            color: var(--ink-muted);
            text-decoration: none;
            transition: color .15s;
        }

        .section-link:hover {
            color: var(--gold);
        }

        @media (max-width: 900px) {
            .kpi-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .situacao-cols {
                grid-template-columns: 1fr;
            }

            .situacao-cols-3 {
                grid-template-columns: 1fr;
            }

            .situacao-wrap {
                padding: 1.25rem 1rem;
            }
        }

        @media (max-width: 600px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
@endpush

@section('content')
    <div class="situacao-wrap">

        {{-- ══ Cabeçalho com progresso ══ --}}
        <div class="situacao-header">
            <div class="situacao-header-left">
                <h1>{{ $municipality->name }}, {{ $municipality->state }}</h1>
                <p>
                    Atualizado em {{ now()->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
                    @if ($briefingHoje)
                        · <a href="{{ route('mayor.mandato.briefings.show', $briefingHoje) }}" class="briefing-badge-link">
                            ☀️ Briefing do dia disponível
                        </a>
                    @endif
                </p>
            </div>
            <div class="situacao-header-right">
                <div class="mandate-ring" title="{{ $pctConcluido }}% do mandato cumprido">
                    <svg width="90" height="90" viewBox="0 0 90 90">
                        <circle class="mandate-ring-bg" cx="45" cy="45" r="38" />
                        <circle class="mandate-ring-fill" cx="45" cy="45" r="38"
                            stroke-dasharray="{{ round(2 * 3.14159 * 38) }}"
                            stroke-dashoffset="{{ round(2 * 3.14159 * 38 * (1 - $pctConcluido / 100)) }}" />
                    </svg>
                    <div class="mandate-ring-text">
                        <div class="mandate-ring-pct">{{ $pctConcluido }}%</div>
                        <div class="mandate-ring-label">cumprido</div>
                    </div>
                </div>
                <div style="color:rgba(255,255,255,.6);font-size:.82rem;line-height:1.6">
                    <div style="color:#fff;font-weight:600;font-size:1rem">{{ $entregues }} entregues</div>
                    de {{ $totalGeral }} compromissos
                </div>
            </div>
        </div>

        {{-- ══ KPIs principais ══ --}}
        <div class="kpi-grid">
            <div class="kpi-card green">
                <div class="kpi-value green">{{ $entregues }}</div>
                <div class="kpi-label">Entregues</div>
            </div>
            <div class="kpi-card blue">
                <div class="kpi-value blue">{{ $emAndamento }}</div>
                <div class="kpi-label">Em andamento</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-value orange">{{ $emRisco }}</div>
                <div class="kpi-label">Em risco</div>
            </div>
            <div class="kpi-card gold">
                <div class="kpi-value gold">{{ $programasAbertos }}</div>
                <div class="kpi-label">Programas federais abertos</div>
            </div>
            <div class="kpi-card blue">
                <div class="kpi-value blue">{{ $totalDemandas }}</div>
                <div class="kpi-label">Demandas recebidas</div>
            </div>
        </div>

        {{-- ══ Linha 1: Progresso por área + Em risco ══ --}}
        <div class="situacao-cols">

            {{-- Progresso por área --}}
            <div class="section-card">
                <div class="section-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z" />
                        </svg>
                        Compromissos por área
                    </h3>
                    <a href="{{ route('mayor.mandato.commitments.index') }}" class="section-link">Ver todos →</a>
                </div>
                <div class="section-card-body">
                    @forelse($porArea as $area => $data)
                        <div class="area-row">
                            <div class="area-name" title="{{ ucfirst(str_replace('_', ' ', $area)) }}">
                                {{ ucfirst(str_replace('_', ' ', $area)) }}
                            </div>
                            <div class="area-bar-wrap">
                                <div class="area-bar-fill {{ $data['em_risco'] > 0 && $data['pct'] < 50 ? 'risco' : '' }}"
                                    style="width:{{ $data['pct'] }}%"></div>
                            </div>
                            <div class="area-pct">{{ $data['pct'] }}%</div>
                            @if ($data['em_risco'] > 0)
                                <div class="area-risco-badge">{{ $data['em_risco'] }} risco</div>
                            @endif
                        </div>
                    @empty
                        <p style="font-size:.84rem;color:var(--ink-muted);text-align:center;padding:.5rem 0">
                            Nenhum compromisso cadastrado.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Em risco --}}
            <div class="section-card">
                <div class="section-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="#e65100">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                        </svg>
                        Precisam de atenção
                    </h3>
                    @if ($prazosProximos->count() > 0)
                        <span style="font-size:.72rem;color:#e65100;font-weight:600">
                            {{ $prazosProximos->count() }} prazo(s) próximo(s)
                        </span>
                    @endif
                </div>
                <div class="section-card-body">
                    @forelse($emRiscoItems as $c)
                        <div class="commit-item">
                            <div class="commit-dot risco"></div>
                            <div style="flex:1">
                                <div class="commit-title">{{ $c->title }}</div>
                                <div class="commit-meta">
                                    {{ ucfirst(str_replace('_', ' ', $c->area)) }}
                                    @if ($c->deadline)
                                        · prazo: {{ $c->deadline->format('d/m/Y') }}
                                    @endif
                                </div>
                            </div>
                            <div class="commit-badge risco">em risco</div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:1.5rem 0">
                            <div style="font-size:1.5rem;margin-bottom:.4rem">✅</div>
                            <div style="font-size:.84rem;color:var(--ink-muted)">Nenhum compromisso em risco</div>
                        </div>
                    @endforelse

                    @if ($prazosProximos->count() > 0)
                        <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--border-lt)">
                            <div
                                style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-muted);margin-bottom:.5rem">
                                Prazos nos próximos 30 dias
                            </div>
                            @foreach ($prazosProximos as $c)
                                <div class="commit-item">
                                    <div class="commit-dot andamento"></div>
                                    <div style="flex:1">
                                        <div class="commit-title">{{ Str::limit($c->title, 40) }}</div>
                                        <div class="commit-meta">{{ $c->deadline->format('d/m/Y') }} ·
                                            {{ abs($c->deadline->diffInDays(now())) }} dias</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══ Linha 2: Recém entregues + Programas Federais ══ --}}
        <div class="situacao-cols">

            {{-- Recém entregues --}}
            <div class="section-card">
                <div class="section-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="#1e7e48">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                        </svg>
                        Entregas recentes (60 dias)
                    </h3>
                    <span style="font-size:.78rem;color:#1e7e48;font-weight:600">
                        {{ $recentesEntregues->count() }} entrega(s)
                    </span>
                </div>
                <div class="section-card-body">
                    @forelse($recentesEntregues as $c)
                        <div class="entregue-item">
                            <div class="entregue-check">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                </svg>
                            </div>
                            <div class="entregue-title">{{ $c->title }}</div>
                            <div class="entregue-date">{{ $c->delivered_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p style="font-size:.84rem;color:var(--ink-muted);text-align:center;padding:.5rem 0">
                            Nenhuma entrega nos últimos 60 dias.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Radar Federal --}}
            <div class="section-card">
                <div class="section-card-header">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="var(--gold)">
                            <path
                                d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                        </svg>
                        Radar federal
                    </h3>
                    <a href="{{ route('mayor.mandato.federal-programs') }}" class="section-link">Ver todos →</a>
                </div>
                <div class="section-card-body">
                    <div style="display:flex;gap:.75rem;margin-bottom:1rem">
                        <div style="flex:1;text-align:center;background:var(--surface);border-radius:8px;padding:.65rem">
                            <div style="font-family:'Lora',serif;font-size:1.4rem;color:#1e7e48">{{ $programasAbertos }}
                            </div>
                            <div style="font-size:.7rem;color:var(--ink-muted)">abertos</div>
                        </div>
                        <div style="flex:1;text-align:center;background:var(--surface);border-radius:8px;padding:.65rem">
                            <div style="font-family:'Lora',serif;font-size:1.4rem;color:#1a5fa8">{{ $programasMonitor }}
                            </div>
                            <div style="font-size:.7rem;color:var(--ink-muted)">monitorando</div>
                        </div>
                        <div style="flex:1;text-align:center;background:var(--surface);border-radius:8px;padding:.65rem">
                            <div style="font-family:'Lora',serif;font-size:1.4rem;color:var(--ink)">{{ $totalProgramas }}
                            </div>
                            <div style="font-size:.7rem;color:var(--ink-muted)">total</div>
                        </div>
                    </div>

                    @foreach ($topProgramas as $p)
                        <div class="programa-item">
                            <div class="programa-score {{ $p->match_score >= 0.85 ? 'high' : 'med' }}">
                                {{ round($p->match_score * 100) }}%
                            </div>
                            <div>
                                <div class="programa-name">{{ Str::limit($p->program_name, 45) }}</div>
                                <div class="programa-area">{{ ucfirst(str_replace('_', ' ', $p->area)) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══ Linha 3: Uso do app ══ --}}
        <div class="section-card" style="margin-bottom:1.25rem">
            <div class="section-card-header">
                <h3>
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z" />
                    </svg>
                    Uso do assistente
                </h3>
            </div>
            <div class="section-card-body">
                <div class="activity-grid">
                    <div class="activity-item">
                        <div class="activity-value">{{ $totalConversas }}</div>
                        <div class="activity-label">Conversas realizadas</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-value">{{ $totalMensagens }}</div>
                        <div class="activity-label">Mensagens trocadas</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-value">{{ $totalBriefings }}</div>
                        <div class="activity-label">Briefings gerados</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-value">
                            @if ($totalDemandas > 0)
                                {{ round(($demandasResolvidas / $totalDemandas) * 100) }}%
                            @else
                                —
                            @endif
                        </div>
                        <div class="activity-label">Demandas resolvidas</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Card de Notificações Push --}}
    <div class="section-card" style="margin-bottom:1.25rem" id="push-card">
        <div class="section-card-header">
            <h3>
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                </svg>
                Notificações push
            </h3>
            <span id="push-status-badge" style="font-size:.72rem;color:var(--ink-muted)">Verificando...</span>
        </div>
        <div class="section-card-body">
            <p style="font-size:.84rem;color:var(--ink-soft);margin-bottom:1rem;line-height:1.6">
                Receba alertas do briefing matinal, programas federais e compromissos em risco
                diretamente no seu dispositivo — mesmo com o app fechado.
            </p>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap">
                <button id="btnAtivarPush" class="btn-secondary" onclick="ativarPush()" style="display:none">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                        <path
                            d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                    </svg>
                    Ativar notificações
                </button>
                <button id="btnTestarPush" class="btn-secondary" onclick="testarPush()" style="display:none">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                        <path
                            d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                    </svg>
                    Testar agora
                </button>
            </div>
            <div id="push-msg" style="margin-top:.75rem;font-size:.82rem;display:none"></div>
        </div>
    </div>

    </div>
@endsection

@push('scripts')
    <script>
        const CSRF_PUSH = document.querySelector('meta[name="csrf-token"]').content;

        async function checkPushStatus() {
            const badge = document.getElementById('push-status-badge');
            const btnAtivar = document.getElementById('btnAtivarPush');
            const btnTestar = document.getElementById('btnTestarPush');

            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                badge.textContent = 'Não suportado neste browser';
                return;
            }

            const permission = Notification.permission;

            if (permission === 'granted') {
                const reg = await navigator.serviceWorker.getRegistration('/');
                const sub = reg ? await reg.pushManager.getSubscription() : null;

                if (sub) {
                    badge.textContent = '✅ Ativas';
                    badge.style.color = '#1e7e48';
                    btnTestar.style.display = 'inline-flex';
                } else {
                    badge.textContent = 'Registrado mas sem subscription';
                    btnAtivar.style.display = 'inline-flex';
                }
            } else if (permission === 'denied') {
                badge.textContent = '🚫 Bloqueadas — habilite nas configurações do browser';
                badge.style.color = '#b52b2b';
            } else {
                badge.textContent = 'Não ativadas';
                btnAtivar.style.display = 'inline-flex';
            }
        }

        async function ativarPush() {
            const btn = document.getElementById('btnAtivarPush');
            btn.disabled = true;
            btn.textContent = 'Aguarde...';

            const permission = await Notification.requestPermission();

            if (permission === 'granted') {
                // Aguardar o SW registrar a subscription (já feito pelo layout)
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showPushMsg('Permissão negada. Habilite nas configurações do browser.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Ativar notificações';
            }
        }

        async function testarPush() {
            const btn = document.getElementById('btnTestarPush');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const res = await fetch('/mayor/push/test', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_PUSH,
                        'Accept': 'application/json'
                    },
                });
                const data = await res.json();

                if (data.ok) {
                    showPushMsg('✅ Notificação enviada! Verifique seu dispositivo.', 'success');
                } else {
                    showPushMsg('Erro: ' + (data.msg || 'Tente novamente.'), 'error');
                }
            } catch (e) {
                showPushMsg('Não foi possível enviar. Verifique sua conexão.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML =
                    '<svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg> Testar agora';
            }
        }

        function showPushMsg(msg, type) {
            const el = document.getElementById('push-msg');
            el.textContent = msg;
            el.style.display = 'block';
            el.style.color = type === 'success' ? '#1e7e48' : '#b52b2b';
            el.style.fontWeight = '500';
        }

        // Checar status ao carregar
        checkPushStatus();
    </script>
@endpush
