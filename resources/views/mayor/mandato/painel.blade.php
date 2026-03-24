@extends('layouts.mayor')

@section('title', 'Painel de Mandato')
@section('topbar-title', 'Painel de Mandato')

@push('styles')
    <style>
        /* ── Botões — alinhados ao layout do projeto ─────────── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--ink);
            color: #fff;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }

        .btn-primary:hover {
            background: #1e2230;
        }

        .btn-primary svg {
            width: 14px;
            height: 14px;
        }

        .btn-primary:disabled {
            background: var(--border);
            cursor: not-allowed;
            color: var(--ink-muted);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--white);
            color: var(--ink-soft);
            border: 1.5px solid var(--border);
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }

        .btn-secondary:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .btn-secondary svg {
            width: 14px;
            height: 14px;
        }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: var(--gold);
            color: #fff;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
        }

        .btn-gold:hover {
            opacity: .88;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 500;
            background: none;
            color: var(--red);
            border: 1.5px solid var(--red-bg);
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }

        .btn-danger:hover {
            background: var(--red-bg);
        }

        /* ── Alertas ──────────────────────────────────────────── */
        .alert-success {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid #c3e6d0;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .alert-error {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
        }

        /* ── Inputs ───────────────────────────────────────────── */
        input[type=text],
        input[type=number],
        input[type=date],
        input[type=url],
        input[type=email],
        select,
        textarea {
            width: 100%;
            padding: .5rem .75rem;
            border: 1.5px solid var(--border);
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: .84rem;
            color: var(--ink);
            background: var(--white);
            transition: border-color .15s;
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--gold);
        }

        input::placeholder,
        textarea::placeholder {
            color: var(--ink-muted);
        }

        /* ── Submit bar ───────────────────────────────────────── */
        .submit-bar {
            display: flex;
            gap: .6rem;
            justify-content: flex-end;
            align-items: center;
            padding-top: .5rem;
        }

        /* ── Layout ─────────────────────────────── */
        .mandato-wrap {
            padding: 1.75rem 2rem;
            max-width: 1280px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* ── Header ──────────────────────────────── */
        .mandato-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .mandato-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.4rem;
            color: var(--ink);
            margin: 0;
        }

        .mandato-header p {
            font-size: .82rem;
            color: var(--ink-muted);
            margin: .2rem 0 0;
        }

        .mandato-header-actions {
            display: flex;
            gap: .6rem;
            align-items: center;
        }

        /* ── KPI Cards ───────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        @media(max-width:900px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width:560px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
        }

        .kpi-label {
            font-size: .72rem;
            color: var(--ink-muted);
            margin-bottom: .4rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: .2rem;
        }

        .kpi-value.green {
            color: #1e7e48;
        }

        .kpi-value.dark {
            color: var(--ink);
        }

        .kpi-value.amber {
            color: #b8902a;
        }

        .kpi-value.muted {
            color: var(--ink-muted);
        }

        .kpi-sub {
            font-size: .75rem;
            color: var(--ink-muted);
        }

        /* ── Eixos Grid ──────────────────────────── */
        .eixos-section h2 {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--ink-muted);
            margin-bottom: .9rem;
        }

        .eixos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media(max-width:900px) {
            .eixos-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width:560px) {
            .eixos-grid {
                grid-template-columns: 1fr;
            }
        }

        .eixo-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
            cursor: pointer;
            transition: box-shadow .15s, border-color .15s;
            text-decoration: none;
            display: block;
        }

        .eixo-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, .08);
            border-color: var(--gold);
        }

        .eixo-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .6rem;
        }

        .eixo-name {
            font-weight: 600;
            font-size: .9rem;
            color: var(--ink);
        }

        .eixo-pct {
            font-size: 1rem;
            font-weight: 700;
        }

        .eixo-pct.green {
            color: #1e7e48;
        }

        .eixo-pct.amber {
            color: #b8902a;
        }

        .eixo-pct.red {
            color: #b52b2b;
        }

        .eixo-bar-track {
            height: 6px;
            background: var(--border);
            border-radius: 999px;
            margin-bottom: .7rem;
            overflow: hidden;
        }

        .eixo-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width .4s ease;
        }

        .eixo-counts {
            display: flex;
            gap: 1rem;
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .eixo-counts span {
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot.green {
            background: #1e7e48;
        }

        .dot.amber {
            background: #b8902a;
        }

        .dot.gray {
            background: #aaa;
        }

        /* ── Ações Recentes ──────────────────────── */
        .acoes-section h2 {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--ink-muted);
            margin-bottom: .9rem;
        }

        .acoes-list {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .acao-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            align-items: center;
            gap: .75rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .82rem;
        }

        .acao-badge {
            padding: .2rem .55rem;
            border-radius: 4px;
            font-size: .68rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .acao-title {
            font-weight: 500;
            color: var(--ink);
        }

        .acao-meta {
            font-size: .72rem;
            color: var(--ink-muted);
            margin-top: .1rem;
        }

        .acao-date {
            font-size: .72rem;
            color: var(--ink-muted);
            white-space: nowrap;
        }

        /* ── Empty state ─────────────────────────── */
        .empty-mandato {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .empty-mandato h3 {
            font-size: 1.1rem;
            color: var(--ink);
            margin-bottom: .5rem;
        }

        .empty-mandato p {
            font-size: .84rem;
            color: var(--ink-muted);
            margin-bottom: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="mandato-wrap">

        {{-- Header --}}
        <div class="mandato-header">
            <div>
                <h1>Painel de Mandato</h1>
                <p>{{ $municipality->name }} · Gestão {{ date('Y') }}–{{ date('Y') + 4 }}</p>
            </div>
            <div class="mandato-header-actions">
                <a href="{{ route('mayor.mandato.eixos') }}" class="btn-secondary" style="font-size:.8rem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        style="width:14px;height:14px">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Configurar eixos
                </a>
                <a href="{{ route('mayor.mandato.acao.create') }}" class="btn-primary" style="font-size:.8rem">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        style="width:14px;height:14px">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nova ação
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if ($axes->isEmpty())
            {{-- Empty state --}}
            <div class="empty-mandato">
                <h3>Configure seu mandato</h3>
                <p>Cadastre os eixos temáticos e compromisso(s) do seu Plano de Governo para começar a acompanhar o
                    atendimento.</p>
                <a href="{{ route('mayor.mandato.eixos') }}" class="btn-primary">Configurar eixos e compromissos</a>
            </div>
        @else
            {{-- KPIs --}}
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Atendimento global</div>
                    <div class="kpi-value {{ $globalScore >= 50 ? 'green' : ($globalScore >= 25 ? 'amber' : 'muted') }}">
                        {{ $globalScore }}%</div>
                    <div class="kpi-sub">dos compromissos</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Ações cadastradas</div>
                    <div class="kpi-value dark">{{ $totalActions }}</div>
                    <div class="kpi-sub">{{ $concludedActions }} concluídas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Compromisso(s) plenos</div>
                    <div class="kpi-value green">{{ $plenas }}</div>
                    <div class="kpi-sub">de {{ $totalPromises }} no total</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Pendentes</div>
                    <div class="kpi-value muted">{{ $pendentes }}</div>
                    <div class="kpi-sub">sem ação vinculada</div>
                </div>
            </div>

            {{-- Eixos --}}
            <div class="eixos-section">
                <h2>Atendimento por eixo temático — clique em um eixo para ver o detalhamento dos compromissos</h2>
                <div class="eixos-grid">
                    @foreach ($axes as $axis)
                        @php
                            $score = $axis->score;
                            $counts = $axis->promise_counts;
                            $pctClass = $score >= 50 ? 'green' : ($score >= 25 ? 'amber' : 'red');
                            $barColor = $score >= 50 ? '#1e7e48' : ($score >= 25 ? '#b8902a' : '#b52b2b');
                        @endphp
                        <a href="{{ route('mayor.mandato.eixo', $axis->id) }}" class="eixo-card">
                            <div class="eixo-top">
                                <div class="eixo-name">
                                    @if ($axis->icon)
                                        {{ $axis->icon }}
                                    @endif
                                    {{ $axis->name }}
                                </div>
                                <div class="eixo-pct {{ $pctClass }}">{{ $score }}%</div>
                            </div>
                            <div class="eixo-bar-track">
                                <div class="eixo-bar-fill"
                                    style="width:{{ $score }}%;background:{{ $barColor }}"></div>
                            </div>
                            <div class="eixo-counts">
                                <span><span class="dot green"></span>{{ $counts['plenas'] }} plenas</span>
                                <span><span class="dot amber"></span>{{ $counts['parciais'] }} parciais</span>
                                <span><span class="dot gray"></span>{{ $counts['pendentes'] }} pendentes</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Ações recentes --}}
            @if ($recentActions->isNotEmpty())
                <div class="acoes-section">
                    <h2>Ações recentes</h2>
                    <div class="acoes-list">
                        @foreach ($recentActions as $action)
                            @php
                                $colors = [
                                    'concluido' => ['bg' => '#dcfce7', 'text' => '#1e7e48'],
                                    'em_andamento' => ['bg' => '#fef3c7', 'text' => '#b8902a'],
                                    'planejado' => ['bg' => '#dbeafe', 'text' => '#1e3a5f'],
                                    'suspenso' => ['bg' => '#fee2e2', 'text' => '#b52b2b'],
                                ];
                                $c = $colors[$action->status] ?? $colors['em_andamento'];
                            @endphp
                            <div class="acao-item">
                                <span class="acao-badge" style="background:{{ $c['bg'] }};color:{{ $c['text'] }}">
                                    {{ $action->status_label }}
                                </span>
                                <div>
                                    <div class="acao-title">{{ $action->title }}</div>
                                    <div class="acao-meta">
                                        {{ $action->axis?->name }}
                                        @if ($action->promises->isNotEmpty())
                                            · {{ $action->promises->pluck('text')->take(1)->implode('') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="acao-date">{{ $action->created_at->format('d/m/Y') }}</div>
                                <a href="{{ route('mayor.mandato.acao.edit', $action->id) }}"
                                    style="color:var(--ink-muted);font-size:.75rem">editar</a>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:.75rem;text-align:right">
                        <a href="{{ route('mayor.mandato.acoes') }}" style="font-size:.8rem;color:var(--gold)">Ver todas as
                            ações →</a>
                    </div>
                </div>
            @endif

        @endif

    </div>
@endsection
