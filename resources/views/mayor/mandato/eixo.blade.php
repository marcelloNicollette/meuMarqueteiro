@extends('layouts.mayor')

@section('title', $axis->name . ' — Eixo do Mandato')
@section('topbar-title', 'Mandato · ' . $axis->name)

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

        .eixo-wrap {
            padding: 1.75rem 2rem;
            max-width: 1080px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Header */
        .eixo-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .eixo-header-back {
            color: var(--ink-muted);
            font-size: .8rem;
            display: flex;
            align-items: center;
            gap: .3rem;
            text-decoration: none;
        }

        .eixo-header-back:hover {
            color: var(--gold);
        }

        .eixo-title {
            font-family: 'Lora', serif;
            font-size: 1.4rem;
            color: var(--ink);
            margin: 0;
        }

        .eixo-score-badge {
            margin-left: auto;
            font-size: 1.5rem;
            font-weight: 700;
            padding: .3rem .9rem;
            border-radius: 8px;
        }

        /* Score bar */
        .eixo-score-bar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            font-size: .8rem;
            color: var(--ink-muted);
            margin-bottom: .5rem;
        }

        .bar-track {
            height: 10px;
            background: var(--border);
            border-radius: 999px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width .5s ease;
        }

        .bar-counts {
            display: flex;
            gap: 1.5rem;
            margin-top: .75rem;
            font-size: .78rem;
        }

        .bar-counts span {
            display: flex;
            align-items: center;
            gap: .35rem;
            color: var(--ink-muted);
        }

        .dot {
            width: 9px;
            height: 9px;
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

        /* Promessas */
        .promises-section h2 {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--ink-muted);
            margin-bottom: .75rem;
        }

        .promise-list {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .promise-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .85rem 1rem;
        }

        .promise-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .promise-text {
            font-size: .88rem;
            color: var(--ink);
            flex: 1;
            line-height: 1.5;
        }

        .promise-status {
            font-size: .7rem;
            font-weight: 600;
            padding: .2rem .55rem;
            border-radius: 4px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .promise-progress {
            margin-top: .6rem;
        }

        .promise-bar-track {
            height: 5px;
            background: var(--border);
            border-radius: 999px;
            overflow: hidden;
        }

        .promise-bar-fill {
            height: 100%;
            border-radius: 999px;
        }

        /* Ações vinculadas */
        .promise-actions {
            margin-top: .6rem;
            padding-top: .6rem;
            border-top: 1px solid var(--border);
        }

        .promise-actions-title {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            margin-bottom: .4rem;
        }

        .linked-action {
            font-size: .78rem;
            color: var(--ink-soft);
            display: flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .25rem;
        }

        .linked-level {
            font-size: .68rem;
            font-weight: 600;
            padding: .1rem .4rem;
            border-radius: 3px;
        }

        /* Empty promise */
        .promise-empty {
            font-size: .78rem;
            color: var(--ink-muted);
            padding: .4rem 0;
            font-style: italic;
        }

        /* Add promise form */
        .add-promise-form {
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: 8px;
            padding: .85rem 1rem;
            display: flex;
            gap: .6rem;
            align-items: flex-start;
        }

        .add-promise-form textarea {
            flex: 1;
            font-size: .85rem;
            resize: vertical;
            min-height: 60px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .8rem;
        }

        .summary-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .95rem 1rem;
        }

        .summary-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            margin-bottom: .4rem;
        }

        .summary-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--ink);
        }

        .summary-meta {
            font-size: .76rem;
            color: var(--ink-muted);
            margin-top: .35rem;
        }

        .action-detail-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .action-detail-card {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            padding: .95rem 1rem;
        }

        .action-detail-top {
            display: flex;
            justify-content: space-between;
            gap: .85rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .action-detail-title {
            font-size: .92rem;
            font-weight: 700;
            color: var(--ink);
        }

        .action-detail-desc {
            font-size: .8rem;
            color: var(--ink-soft);
            line-height: 1.5;
            margin-top: .35rem;
        }

        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .65rem;
        }

        .mini-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            color: var(--ink-soft);
            background: #fff;
            border: 1px solid var(--border);
        }

        .promise-actions-grid {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            margin-top: .6rem;
        }

        @media (max-width: 900px) {
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@section('content')
    <div class="eixo-wrap">
        @php
            $axisSummary = $axisBoard['summary'] ?? [];
            $axisPromises = $axisBoard['promises'] ?? [];
            $axisActions = $axisBoard['actions'] ?? [];
            $axisBreakdown = $axisBoard['promise_breakdown'] ?? [];
        @endphp

        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('mayor.mandato.painel') }}" class="eixo-header-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                    <path d="M19 12H5M12 5l-7 7 7 7" />
                </svg>
                Painel de Mandato
            </a>
        </div>

        {{-- Header do eixo --}}
        <div class="eixo-header">
            <div>
                <h1 class="eixo-title">
                    @if ($axis->icon)
                        {{ $axis->icon }}
                    @endif
                    {{ $axis->name }}
                </h1>
                @if ($axis->description)
                    <p style="font-size:.82rem;color:var(--ink-muted);margin:.25rem 0 0">{{ $axis->description }}</p>
                @endif
            </div>
            @php
                $score = $axis->score;
                $scoreColor = $score >= 50 ? '#1e7e48' : ($score >= 25 ? '#b8902a' : '#b52b2b');
                $scoreBg = $score >= 50 ? '#dcfce7' : ($score >= 25 ? '#fef3c7' : '#fee2e2');
            @endphp
            <div class="eixo-score-badge" style="background:{{ $scoreBg }};color:{{ $scoreColor }}">
                {{ $score }}%
            </div>
        </div>

        {{-- Barra de score do eixo --}}
        @php $counts = $axisSummary['counts'] ?? $axis->promise_counts; @endphp
        <div class="eixo-score-bar">
            <div class="bar-label">
                <span>Atendimento do eixo</span>
                <span>{{ $counts['total'] }} compromissos</span>
            </div>
            <div class="bar-track">
                <div class="bar-fill" style="width:{{ $score }}%;background:{{ $scoreColor }}"></div>
            </div>
            <div class="bar-counts">
                <span><span class="dot green"></span>{{ $counts['plenas'] }} plenas (100%)</span>
                <span><span class="dot amber"></span>{{ $counts['parciais'] }} parciais</span>
                <span><span class="dot gray"></span>{{ $counts['pendentes'] }} pendentes</span>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Compromissos plenos</div>
                <div class="summary-value">{{ $axisBreakdown['fulfilled'] ?? 0 }}</div>
                <div class="summary-meta">Itens com score integral neste eixo.</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Compromissos parciais</div>
                <div class="summary-value">{{ $axisBreakdown['partial'] ?? 0 }}</div>
                <div class="summary-meta">Promessas com alguma entrega, mas ainda incompletas.</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Compromissos pendentes</div>
                <div class="summary-value">{{ $axisBreakdown['pending'] ?? 0 }}</div>
                <div class="summary-meta">Itens que ainda demandam atenção executiva.</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Ações do eixo</div>
                <div class="summary-value">{{ $axisSummary['actions_total'] ?? 0 }}</div>
                <div class="summary-meta">
                    {{ $axisSummary['actions_in_progress'] ?? 0 }} em andamento ·
                    {{ $axisSummary['pending_without_actions'] ?? 0 }} compromisso(s) sem ação.
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        {{-- Lista de promessas --}}
        <div class="promises-section">
            <h2>Compromissos deste eixo</h2>

            @if (empty($axisPromises))
                <div
                    style="text-align:center;padding:2rem;background:var(--surface);border:1px solid var(--border);border-radius:10px">
                    <p style="color:var(--ink-muted);font-size:.85rem">Nenhum compromisso neste eixo ainda.</p>
                </div>
            @else
                <div class="promise-list">
                    @foreach ($axisPromises as $promise)
                        @php
                            $sc = $promise['score'] ?? 0;
                            $pColor = $sc >= 100 ? '#1e7e48' : ($sc >= 50 ? '#b8902a' : ($sc > 0 ? '#b8902a' : '#aaa'));
                            $pBg = $sc >= 100 ? '#dcfce7' : ($sc >= 50 ? '#fef3c7' : ($sc > 0 ? '#fef3c7' : '#f3f4f6'));
                        @endphp
                        <div class="promise-item">
                            <div class="promise-top">
                                <div class="promise-text">{{ $promise['text'] }}</div>
                                <span class="promise-status"
                                    style="background:{{ $pBg }};color:{{ $pColor }}">
                                    {{ $promise['status_label'] }}
                                </span>
                            </div>
                            @if ($sc > 0)
                                <div class="promise-progress">
                                    <div class="promise-bar-track">
                                        <div class="promise-bar-fill"
                                            style="width:{{ $sc }}%;background:{{ $pColor }}"></div>
                                    </div>
                                </div>
                            @endif

                            <div class="chip-row">
                                <span class="mini-chip">Score {{ $promise['score'] ?? 0 }}</span>
                                <span class="mini-chip">{{ $promise['actions_count'] ?? 0 }} ação(ões)</span>
                                @if (!empty($promise['specificity']))
                                    <span class="mini-chip">{{ ucfirst($promise['specificity']) }}</span>
                                @endif
                                @foreach (array_slice($promise['keywords'] ?? [], 0, 3) as $keyword)
                                    <span class="mini-chip">{{ $keyword }}</span>
                                @endforeach
                            </div>

                            {{-- Ações vinculadas --}}
                            @if (!empty($promise['linked_actions']))
                                <div class="promise-actions">
                                    <div class="promise-actions-title">Ações vinculadas</div>
                                    <div class="promise-actions-grid">
                                    @foreach ($promise['linked_actions'] as $action)
                                        @php
                                            $lvl = $action['promises'][0]['level'] ?? 0;
                                            $lc =
                                                $lvl >= 75
                                                    ? ['bg' => '#dcfce7', 'text' => '#1e7e48']
                                                    : ($lvl >= 25
                                                        ? ['bg' => '#fef3c7', 'text' => '#b8902a']
                                                        : ['bg' => '#f3f4f6', 'text' => '#666']);
                                        @endphp
                                        <div class="action-detail-card" style="padding:.7rem .8rem">
                                            <div class="action-detail-top">
                                                <div style="flex:1">
                                                    <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
                                                        <span class="linked-level"
                                                            style="background:{{ $lc['bg'] }};color:{{ $lc['text'] }}">{{ $lvl }}%</span>
                                                        <a href="{{ route('mayor.mandato.acao.edit', $action['id']) }}"
                                                            style="color:var(--ink);text-decoration:none;font-size:.82rem;font-weight:700">
                                                            {{ $action['title'] }}
                                                        </a>
                                                        <span class="promise-status"
                                                            style="background:{{ $action['status_color'] }}15;color:{{ $action['status_color'] }}">
                                                            {{ $action['status_label'] }}
                                                        </span>
                                                    </div>
                                                    @if (!empty($action['description']))
                                                        <div class="action-detail-desc">{{ $action['description'] }}</div>
                                                    @endif
                                                </div>
                                                <a href="{{ route('mayor.mandato.acao.edit', $action['id']) }}"
                                                    style="font-size:.75rem;color:var(--gold);text-decoration:none">editar</a>
                                            </div>
                                            <div class="chip-row">
                                                <span class="mini-chip">{{ $action['physical_progress'] ?? 0 }}% execução</span>
                                                @if (!empty($action['secretaria']))
                                                    <span class="mini-chip">{{ $action['secretaria'] }}</span>
                                                @endif
                                                @if (!empty($action['start_date']))
                                                    <span class="mini-chip">Início {{ $action['start_date'] }}</span>
                                                @endif
                                                @if (!empty($action['end_date']))
                                                    <span class="mini-chip">Fim {{ $action['end_date'] }}</span>
                                                @endif
                                                @if (($action['milestones_total'] ?? 0) > 0)
                                                    <span class="mini-chip">
                                                        {{ $action['milestones_completed'] ?? 0 }}/{{ $action['milestones_total'] }} marcos
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="promise-empty">Nenhuma ação vinculada a este compromisso ainda.</div>
                            @endif

                            {{-- Remover promessa --}}
                            <form method="POST" action="{{ route('mayor.mandato.promise.destroy', $promise['id']) }}"
                                style="margin-top:.4rem" onsubmit="return confirm('Remover este compromisso?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    style="font-size:.7rem;color:var(--ink-muted);background:none;border:none;cursor:pointer;padding:0">
                                    remover compromisso
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Adicionar nova promessa --}}
            <div style="margin-top:.75rem">
                <form method="POST" action="{{ route('mayor.mandato.promise.store') }}" class="add-promise-form">
                    @csrf
                    <input type="hidden" name="mandate_axis_id" value="{{ $axis->id }}">
                    <textarea name="text" placeholder="Adicionar novo compromisso neste eixo..." required></textarea>
                    <button type="submit" class="btn-primary" style="font-size:.8rem;white-space:nowrap">Adicionar</button>
                </form>
            </div>
        </div>

        {{-- Ações do eixo --}}
        @if (!empty($axisActions))
            <div class="promises-section">
                <h2>Todas as ações deste eixo ({{ count($axisActions) }})</h2>
                <div class="action-detail-list">
                    @foreach ($axisActions as $action)
                        @php
                            $ac = [
                                'concluido' => ['bg' => '#dcfce7', 'text' => '#1e7e48'],
                                'em_andamento' => ['bg' => '#fef3c7', 'text' => '#b8902a'],
                                'planejado' => ['bg' => '#dbeafe', 'text' => '#1e3a5f'],
                                'suspenso' => ['bg' => '#fee2e2', 'text' => '#b52b2b'],
                            ][$action['status']] ?? ['bg' => '#f3f4f6', 'text' => '#666'];
                        @endphp
                        <div class="action-detail-card">
                            <div class="action-detail-top">
                                <div style="flex:1">
                                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;flex-wrap:wrap">
                                    <span class="promise-status"
                                        style="background:{{ $ac['bg'] }};color:{{ $ac['text'] }}">{{ $action['status_label'] }}</span>
                                    <span
                                        class="action-detail-title">{{ $action['title'] }}</span>
                                    </div>
                                    @if (!empty($action['description']))
                                        <div class="action-detail-desc">{{ $action['description'] }}</div>
                                    @endif
                                </div>
                                <a href="{{ route('mayor.mandato.acao.edit', $action['id']) }}"
                                    style="font-size:.75rem;color:var(--gold);text-decoration:none">editar</a>
                            </div>
                            <div class="chip-row">
                                <span class="mini-chip">{{ $action['physical_progress'] ?? 0 }}% execução</span>
                                @if (!empty($action['secretaria']))
                                    <span class="mini-chip">{{ $action['secretaria'] }}</span>
                                @endif
                                @if (!empty($action['start_date']))
                                    <span class="mini-chip">Início {{ $action['start_date'] }}</span>
                                @endif
                                @if (!empty($action['end_date']))
                                    <span class="mini-chip">Fim {{ $action['end_date'] }}</span>
                                @endif
                                @if (!empty($action['region']))
                                    <span class="mini-chip">{{ $action['region'] }}</span>
                                @endif
                                @if (($action['beneficiaries'] ?? 0) > 0)
                                    <span class="mini-chip">{{ $action['beneficiaries'] }} beneficiários</span>
                                @endif
                                @if (($action['milestones_total'] ?? 0) > 0)
                                    <span class="mini-chip">
                                        {{ $action['milestones_completed'] ?? 0 }}/{{ $action['milestones_total'] }} marcos
                                    </span>
                                @endif
                            </div>
                            @if (!empty($action['promises']))
                                <div style="font-size:.75rem;color:var(--ink-muted);margin-top:.55rem">
                                    Compromissos vinculados:
                                    {{ collect($action['promises'])->map(fn ($promise) => $promise['text'] . ' (' . $promise['level'] . '%)')->implode(' · ') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Botão nova ação neste eixo --}}
        <div>
            <a href="{{ route('mayor.mandato.acao.create') }}?axis={{ $axis->id }}" class="btn-primary"
                style="font-size:.85rem">
                + Nova ação para {{ $axis->name }}
            </a>
        </div>

    </div>
@endsection
