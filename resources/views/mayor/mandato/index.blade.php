@extends('layouts.mayor')

@section('title', 'Gerenciador de Mandato')
@section('topbar-title', 'Gerenciador de Mandato')

@push('styles')
    <style>
        .mandato-layout {
            padding: 1.75rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            max-width: 1280px;
        }

        .mandato-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .mandato-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .mandato-header p {
            font-size: .84rem;
            color: var(--ink-muted);
        }

        /* ── Hero ─── */
        .mandate-hero {
            background: var(--ink);
            border-radius: 16px;
            padding: 1.75rem 2rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .mandate-hero::after {
            content: '';
            position: absolute;
            right: -40px;
            top: -40px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(184, 144, 42, .15), transparent 70%);
            pointer-events: none;
        }

        .mh-label {
            font-size: .68rem;
            font-weight: 500;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .35);
            margin-bottom: .5rem;
        }

        .mh-headline {
            font-family: 'Lora', serif;
            font-size: 1.3rem;
            color: #fff;
            margin-bottom: 1.1rem;
            line-height: 1.3;
        }

        .mh-headline .risk-flag {
            font-size: .82rem;
            color: #f59e0b;
            font-family: "Open Sans", sans-serif;
            font-weight: 500;
            margin-left: .5rem;
        }

        .mh-bar-bg {
            height: 6px;
            border-radius: 4px;
            background: rgba(255, 255, 255, .1);
            overflow: hidden;
            margin-bottom: .9rem;
        }

        .mh-bar {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--gold), var(--gold-lt));
            transition: width .8s cubic-bezier(.16, 1, .3, 1);
        }

        .mh-counters {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .mh-counter {
            font-size: .75rem;
            color: rgba(255, 255, 255, .4);
        }

        .mh-counter strong {
            color: rgba(255, 255, 255, .8);
            font-weight: 500;
        }

        .mh-pct {
            font-family: 'Lora', serif;
            font-size: 3rem;
            color: var(--gold-lt);
            line-height: 1;
            text-align: right;
        }

        .mh-pct-lbl {
            font-size: .72rem;
            color: rgba(255, 255, 255, .3);
            margin-top: .2rem;
            text-align: right;
        }

        /* ── Stats ─── */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .85rem;
        }

        .qs-card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            display: flex;
            align-items: center;
            gap: .85rem;
            transition: box-shadow .2s, border-color .2s, background .2s;
            cursor: pointer;
            user-select: none;
        }

        .qs-card:hover {
            box-shadow: 0 3px 12px rgba(0, 0, 0, .06);
        }

        .qs-card.active {
            border-color: var(--ink);
            background: #f0ede8;
        }

        .qs-card.active.blue {
            border-color: #1a5fa8;
            background: #e8f0fb;
        }

        .qs-card.active.green {
            border-color: var(--green);
            background: var(--green-bg);
        }

        .qs-card.active.amber {
            border-color: #e65100;
            background: #fff8e1;
        }

        .qs-card.active.muted {
            border-color: var(--ink);
            background: #f0ede8;
        }

        .qs-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .qs-icon svg {
            width: 18px;
            height: 18px;
        }

        .qs-icon.blue {
            background: #e8f0fb;
            color: #1a5fa8;
        }

        .qs-icon.green {
            background: var(--green-bg);
            color: var(--green);
        }

        .qs-icon.amber {
            background: #fff8e1;
            color: #e65100;
        }

        .qs-icon.muted {
            background: var(--surface);
            color: var(--ink-muted);
        }

        .qs-val {
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: var(--ink);
            line-height: 1;
        }

        .qs-lbl {
            font-size: .73rem;
            color: var(--ink-muted);
            margin-top: .1rem;
        }

        /* ── Toolbar ─── */
        .section-toolbar {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1.1rem;
            flex-wrap: wrap;
        }

        .section-toolbar h2 {
            font-family: 'Lora', serif;
            font-size: 1rem;
            color: var(--ink);
            flex: 1;
        }

        .filter-area-select {
            padding: .38rem .8rem;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            background: var(--white);
            font-family: "Open Sans", sans-serif;
            font-size: .78rem;
            color: var(--ink);
            cursor: pointer;
            outline: none;
        }

        .filter-area-select:focus {
            border-color: var(--ink);
        }

        .filter-row {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: .36rem .85rem;
            border-radius: 20px;
            border: 1.5px solid var(--border);
            background: none;
            cursor: pointer;
            font-family: "Open Sans", sans-serif;
            font-size: .77rem;
            font-weight: 500;
            color: var(--ink-muted);
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .filter-btn.active {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }

        .filter-btn.active.risk {
            border-color: #e65100;
            background: #e65100;
        }

        .filter-btn.active.green {
            border-color: var(--green);
            background: var(--green);
        }

        .filter-btn.active.blue {
            border-color: #1a5fa8;
            background: #1a5fa8;
        }

        .filter-btn .count {
            font-size: .65rem;
            padding: .1rem .35rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, .2);
            color: inherit;
        }

        .filter-btn:not(.active) .count {
            background: var(--surface);
            color: var(--ink-muted);
        }

        /* ── Cards ─── */
        .commitments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 1rem;
        }

        .c-card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 13px;
            padding: 1.2rem 1.3rem 1rem;
            transition: box-shadow .2s, border-color .2s, transform .15s;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .c-card:hover {
            box-shadow: 0 5px 18px rgba(0, 0, 0, .08);
            border-color: #d4cfc8;
            transform: translateY(-1px);
        }

        .c-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 3px;
            border-radius: 13px 0 0 13px;
        }

        .c-card.em_andamento::before {
            background: #1a5fa8;
        }

        .c-card.entregue::before {
            background: var(--green);
        }

        .c-card.em_risco::before {
            background: #f59e0b;
        }

        .c-card.prometido::before {
            background: #c8c3bb;
        }

        .c-card.cancelado::before {
            background: var(--red);
        }

        .c-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .7rem;
        }

        .c-area-badge {
            font-size: .64rem;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--ink-muted);
            padding: .18rem .5rem;
            background: var(--surface);
            border-radius: 5px;
        }

        .c-priority-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .c-priority-dot.alta {
            background: var(--red);
        }

        .c-priority-dot.media {
            background: #f59e0b;
        }

        .c-priority-dot.baixa {
            background: var(--green);
        }

        .c-title {
            font-family: 'Lora', serif;
            font-size: .95rem;
            font-weight: 500;
            color: var(--ink);
            line-height: 1.45;
            margin-bottom: .4rem;
        }

        .c-secretary {
            font-size: .74rem;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            gap: .3rem;
            margin-bottom: .9rem;
        }

        .c-secretary svg {
            width: 11px;
            height: 11px;
            flex-shrink: 0;
        }

        .c-budget {
            font-size: .72rem;
            color: var(--ink-muted);
            margin-bottom: .85rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .c-budget svg {
            width: 11px;
            height: 11px;
        }

        .c-budget strong {
            color: var(--ink-soft);
        }

        .c-prog-row {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin-bottom: .9rem;
        }

        .c-prog-bg {
            flex: 1;
            height: 5px;
            background: var(--surface);
            border-radius: 3px;
            overflow: hidden;
        }

        .c-prog-fill {
            height: 100%;
            border-radius: 3px;
            transition: width .6s ease;
        }

        .c-prog-fill.em_andamento {
            background: #1a5fa8;
        }

        .c-prog-fill.entregue {
            background: var(--green);
        }

        .c-prog-fill.em_risco {
            background: #f59e0b;
        }

        .c-prog-fill.prometido {
            background: #c8c3bb;
        }

        .c-prog-fill.cancelado {
            background: #c8c3bb;
        }

        .c-prog-pct {
            font-size: .76rem;
            font-weight: 500;
            color: var(--ink-soft);
            min-width: 30px;
            text-align: right;
        }

        .c-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-top: auto;
        }

        .c-status-tag {
            font-size: .71rem;
            font-weight: 500;
            padding: .2rem .6rem;
            border-radius: 10px;
        }

        .c-status-tag.em_andamento {
            background: #e8f0fb;
            color: #1a5fa8;
        }

        .c-status-tag.entregue {
            background: var(--green-bg);
            color: var(--green);
        }

        .c-status-tag.em_risco {
            background: #fff8e1;
            color: #e65100;
        }

        .c-status-tag.prometido {
            background: var(--surface);
            color: var(--ink-muted);
        }

        .c-status-tag.cancelado {
            background: var(--red-bg);
            color: var(--red);
        }

        .c-deadline {
            font-size: .71rem;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            gap: .25rem;
        }

        .c-deadline.overdue {
            color: var(--red);
            font-weight: 500;
        }

        .c-deadline svg {
            width: 11px;
            height: 11px;
        }

        .c-actions {
            position: absolute;
            top: .8rem;
            right: .8rem;
            display: flex;
            gap: .3rem;
            opacity: 0;
            transition: opacity .15s;
        }

        .c-card:hover .c-actions {
            opacity: 1;
        }

        .c-action-btn {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--ink-muted);
            transition: all .12s;
            text-decoration: none;
        }

        .c-action-btn:hover {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .c-action-btn svg {
            width: 13px;
            height: 13px;
        }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 3rem 1rem;
            color: var(--ink-muted);
        }

        .empty-state-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .empty-state-icon svg {
            width: 24px;
            height: 24px;
            color: var(--ink-muted);
        }

        .empty-state h3 {
            font-size: .95rem;
            color: var(--ink);
            margin-bottom: .35rem;
        }

        .empty-state p {
            font-size: .81rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-family: "Open Sans", sans-serif;
            font-size: .83rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            border: 1.5px solid transparent;
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        .btn-dark {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .btn-dark:hover {
            background: var(--ink-soft);
        }

        @media(max-width:900px) {
            .mandato-layout {
                padding: 1rem
            }

            .mandate-hero {
                grid-template-columns: 1fr
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        @media(max-width:600px) {
            .commitments-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
@endpush

@section('content')
    <div class="mandato-layout">

        {{-- Cabeçalho --}}
        <div class="mandato-header">
            <div>
                <h1>Programa de Governo</h1>
                <p>Acompanhamento de compromissos e execução do mandato</p>
            </div>
            <a href="{{ route('mayor.mandato.commitments.create') }}" class="btn btn-dark">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                </svg>
                Novo compromisso
            </a>
        </div>

        {{-- Hero de progresso --}}
        @php
            $total = $commitments->count();
            $delivered = $commitments->where('status', 'entregue')->count();
            $running = $commitments->where('status', 'em_andamento')->count();
            $atRisk = $commitments->where('status', 'em_risco')->count();
            $progress = $total > 0 ? round(($delivered / $total) * 100) : 0;
        @endphp
        <div class="mandate-hero">
            <div>
                <div class="mh-label">Execução do mandato</div>
                <div class="mh-headline">
                    {{ $delivered }} de {{ $total }} compromissos entregues
                    @if ($atRisk > 0)
                        <span class="risk-flag">⚠ {{ $atRisk }} em risco</span>
                    @endif
                </div>
                <div class="mh-bar-bg">
                    <div class="mh-bar" style="width:{{ $progress }}%"></div>
                </div>
                <div class="mh-counters">
                    <div class="mh-counter">Entregues: <strong>{{ $delivered }}</strong></div>
                    <div class="mh-counter">Em andamento: <strong>{{ $running }}</strong></div>
                    <div class="mh-counter">Em risco: <strong>{{ $atRisk }}</strong></div>
                    <div class="mh-counter">Prometidos:
                        <strong>{{ $commitments->where('status', 'prometido')->count() }}</strong>
                    </div>
                </div>
            </div>
            <div>
                <div class="mh-pct">{{ $progress }}%</div>
                <div class="mh-pct-lbl">concluído</div>
            </div>
        </div>

        {{-- Stats rápidas (clicáveis como filtro) --}}
        <div class="quick-stats">
            <div class="qs-card blue" data-filter="em_andamento" onclick="setQsFilter(this,'em_andamento')">
                <div class="qs-icon blue"><svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg></div>
                <div>
                    <div class="qs-val">{{ $running }}</div>
                    <div class="qs-lbl">Em andamento</div>
                </div>
            </div>
            <div class="qs-card green" data-filter="entregue" onclick="setQsFilter(this,'entregue')">
                <div class="qs-icon green"><svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                    </svg></div>
                <div>
                    <div class="qs-val">{{ $delivered }}</div>
                    <div class="qs-lbl">Entregues</div>
                </div>
            </div>
            <div class="qs-card amber" data-filter="em_risco" onclick="setQsFilter(this,'em_risco')">
                <div class="qs-icon amber"><svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                    </svg></div>
                <div>
                    <div class="qs-val">{{ $atRisk }}</div>
                    <div class="qs-lbl">Em risco</div>
                </div>
            </div>
            <div class="qs-card muted" data-filter="todos" onclick="setQsFilter(this,'todos')">
                <div class="qs-icon muted"><svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z" />
                    </svg></div>
                <div>
                    <div class="qs-val">{{ $total }}</div>
                    <div class="qs-lbl">Total</div>
                </div>
            </div>
        </div>

        {{-- Lista de compromissos --}}
        <div>
            <div class="section-toolbar">
                <h2>Compromissos</h2>
                <select class="filter-area-select" id="areaFilter" onchange="applyFilters()">
                    <option value="">Todas as áreas</option>
                    <option value="saude">Saúde</option>
                    <option value="educacao">Educação</option>
                    <option value="infraestrutura">Infraestrutura</option>
                    <option value="social">Social</option>
                    <option value="seguranca">Segurança</option>
                    <option value="meio_ambiente">Meio Ambiente</option>
                    <option value="economia">Economia</option>
                    <option value="cultura">Cultura</option>
                    <option value="outros">Outros</option>
                </select>
                <div class="filter-row">
                    <button class="filter-btn active" data-filter="todos" onclick="setFilter(this,'todos')">Todos <span
                            class="count">{{ $total }}</span></button>
                    <button class="filter-btn risk" data-filter="em_risco" onclick="setFilter(this,'em_risco')">Em risco
                        <span class="count">{{ $atRisk }}</span></button>
                    <button class="filter-btn blue" data-filter="em_andamento" onclick="setFilter(this,'em_andamento')">Em
                        andamento <span class="count">{{ $running }}</span></button>
                    <button class="filter-btn green" data-filter="entregue" onclick="setFilter(this,'entregue')">Entregues
                        <span class="count">{{ $delivered }}</span></button>
                    <button class="filter-btn" data-filter="prometido" onclick="setFilter(this,'prometido')">Prometidos
                        <span class="count">{{ $commitments->where('status', 'prometido')->count() }}</span></button>
                </div>
            </div>

            <div class="commitments-grid" id="commitmentsGrid">
                @forelse($commitments as $commitment)
                    <div class="c-card {{ $commitment->status }}" data-status="{{ $commitment->status }}"
                        data-area="{{ $commitment->area }}">
                        <div class="c-actions">
                            <a href="{{ route('mayor.mandato.commitments.edit', $commitment) }}" class="c-action-btn"
                                title="Editar">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                                </svg>
                            </a>
                            <button class="c-action-btn" onclick="askAbout('{{ addslashes($commitment->title) }}')"
                                title="Perguntar ao assistente">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>
                        </div>
                        <div class="c-card-top">
                            <span class="c-area-badge">{{ ucfirst(str_replace('_', ' ', $commitment->area)) }}</span>
                            <span class="c-priority-dot {{ $commitment->priority }}"
                                title="Prioridade {{ $commitment->priority }}"></span>
                        </div>
                        <div class="c-title">{{ $commitment->title }}</div>
                        @if ($commitment->responsible_secretary)
                            <div class="c-secretary">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                                {{ $commitment->responsible_secretary }}
                            </div>
                        @endif
                        @if ($commitment->budget_allocated)
                            <div class="c-budget">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                                </svg>
                                R$ <strong>{{ number_format($commitment->budget_allocated, 0, ',', '.') }}</strong>
                                @if ($commitment->budget_spent)
                                    · Gasto: R$ {{ number_format($commitment->budget_spent, 0, ',', '.') }}
                                @endif
                            </div>
                        @endif
                        <div class="c-prog-row">
                            <div class="c-prog-bg">
                                <div class="c-prog-fill {{ $commitment->status }}"
                                    style="width:{{ $commitment->progress_percent }}%"></div>
                            </div>
                            <span class="c-prog-pct">{{ $commitment->progress_percent }}%</span>
                        </div>
                        <div class="c-card-footer">
                            <span
                                class="c-status-tag {{ $commitment->status }}">{{ match ($commitment->status) {'em_andamento' => 'Em andamento','entregue' => 'Entregue ✓','em_risco' => '⚠ Em risco','prometido' => 'Prometido','cancelado' => 'Cancelado',default => $commitment->status} }}</span>
                            @if ($commitment->deadline)
                                @php $isOverdue = $commitment->deadline->isPast() && $commitment->status !== 'entregue'; @endphp
                                <div class="c-deadline {{ $isOverdue ? 'overdue' : '' }}">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z" />
                                    </svg>
                                    {{ $commitment->deadline->format('d/m/Y') }}@if ($isOverdue)
                                        · atrasado
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @empty
                        <div class="empty-state">
                            <div class="empty-state-icon"><svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11zm-7-3h2v-2h-2zm0-4h2v-4h-2z" />
                                </svg></div>
                            <h3>Nenhum compromisso cadastrado</h3>
                            <p>Adicione o primeiro compromisso do programa de governo.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    @endsection

    @push('scripts')
        <script>
            let currentFilter = 'todos';

            function setActiveQsCard(filter) {
                document.querySelectorAll('.qs-card').forEach(c => c.classList.remove('active'));
                const qsCard = document.querySelector(`.qs-card[data-filter="${filter}"]`);
                if (qsCard) qsCard.classList.add('active');
            }

            function setActiveFilterBtn(filter) {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                const btn = document.querySelector(`.filter-btn[data-filter="${filter}"]`);
                if (btn) btn.classList.add('active');
            }

            // Chamado pelos qs-cards
            function setQsFilter(card, status) {
                // Toggle: clicar no mesmo card ativo volta para "todos"
                if (currentFilter === status && status !== 'todos') status = 'todos';
                currentFilter = status;
                setActiveQsCard(status);
                setActiveFilterBtn(status);
                applyFilters();
                // Scroll suave até os cards
                document.getElementById('commitmentsGrid').scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }

            // Chamado pelos filter-btns
            function setFilter(btn, status) {
                currentFilter = status;
                setActiveFilterBtn(status);
                setActiveQsCard(status);
                applyFilters();
            }

            function applyFilters() {
                const area = document.getElementById('areaFilter').value;
                document.querySelectorAll('.c-card').forEach(card => {
                    const okStatus = currentFilter === 'todos' || card.dataset.status === currentFilter;
                    const okArea = !area || card.dataset.area === area;
                    card.style.display = (okStatus && okArea) ? 'flex' : 'none';
                });
            }

            // Init — "Total" começa ativo
            document.addEventListener('DOMContentLoaded', () => setActiveQsCard('todos'));

            function askAbout(title) {
                sessionStorage.setItem('chatPrefill',
                    `Qual é a situação atual do compromisso: "${title}"? Quais são os riscos e o que pode ser feito para acelerar a entrega?`
                );
                window.location.href = '{{ route('mayor.chat.index') }}';
            }
        </script>
    @endpush
