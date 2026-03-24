@extends('layouts.mayor')

@section('title', $axis->name . ' — Eixo do Mandato')
@section('topbar-title', 'Mandato · ' . $axis->name)

@push('styles')
    <style>
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
    </style>
@endpush

@section('content')
    <div class="eixo-wrap">

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
        @php $counts = $axis->promise_counts; @endphp
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

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        {{-- Lista de compromisso --}}
        <div class="promises-section">
            <h2>Compromisso(s) deste eixo</h2>

            @if ($axis->promises->isEmpty())
                <div
                    style="text-align:center;padding:2rem;background:var(--surface);border:1px solid var(--border);border-radius:10px">
                    <p style="color:var(--ink-muted);font-size:.85rem">Nenhum compromisso cadastrado neste eixo ainda.</p>
                </div>
            @else
                <div class="promise-list">
                    @foreach ($axis->promises as $promise)
                        @php
                            $sc = $promise->score;
                            $pColor = $sc >= 100 ? '#1e7e48' : ($sc >= 50 ? '#b8902a' : ($sc > 0 ? '#b8902a' : '#aaa'));
                            $pBg = $sc >= 100 ? '#dcfce7' : ($sc >= 50 ? '#fef3c7' : ($sc > 0 ? '#fef3c7' : '#f3f4f6'));
                        @endphp
                        <div class="promise-item">
                            <div class="promise-top">
                                <div class="promise-text">{{ $promise->text }}</div>
                                <span class="promise-status"
                                    style="background:{{ $pBg }};color:{{ $pColor }}">
                                    {{ $promise->status_label }}
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

                            {{-- Ações vinculadas --}}
                            @if ($promise->actions->isNotEmpty())
                                <div class="promise-actions">
                                    <div class="promise-actions-title">Ações vinculadas</div>
                                    @foreach ($promise->actions as $action)
                                        @php
                                            $lvl = $action->pivot->fulfillment_level;
                                            $lc =
                                                $lvl >= 75
                                                    ? ['bg' => '#dcfce7', 'text' => '#1e7e48']
                                                    : ($lvl >= 25
                                                        ? ['bg' => '#fef3c7', 'text' => '#b8902a']
                                                        : ['bg' => '#f3f4f6', 'text' => '#666']);
                                        @endphp
                                        <div class="linked-action">
                                            <span class="linked-level"
                                                style="background:{{ $lc['bg'] }};color:{{ $lc['text'] }}">{{ $lvl }}%</span>
                                            <a href="{{ route('mayor.mandato.acao.edit', $action->id) }}"
                                                style="color:var(--ink);text-decoration:none">
                                                {{ $action->title }}
                                            </a>
                                            <span style="color:var(--ink-muted);font-size:.72rem">·
                                                {{ $action->status_label }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="promise-empty">Nenhuma ação vinculada a este compromisso ainda.</div>
                            @endif

                            {{-- Remover promessa --}}
                            <form method="POST" action="{{ route('mayor.mandato.promise.destroy', $promise->id) }}"
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

            {{-- Adicionar nova compromisso --}}
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
        @php
            $axisActions = $axis->actions()->with('promises')->orderByDesc('created_at')->get();
        @endphp
        @if ($axisActions->isNotEmpty())
            <div class="promises-section">
                <h2>Todas as ações deste eixo ({{ $axisActions->count() }})</h2>
                <div class="promise-list">
                    @foreach ($axisActions as $action)
                        @php
                            $ac = [
                                'concluido' => ['bg' => '#dcfce7', 'text' => '#1e7e48'],
                                'em_andamento' => ['bg' => '#fef3c7', 'text' => '#b8902a'],
                                'planejado' => ['bg' => '#dbeafe', 'text' => '#1e3a5f'],
                                'suspenso' => ['bg' => '#fee2e2', 'text' => '#b52b2b'],
                            ][$action->status] ?? ['bg' => '#f3f4f6', 'text' => '#666'];
                        @endphp
                        <div class="promise-item"
                            style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
                            <div style="flex:1">
                                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
                                    <span class="promise-status"
                                        style="background:{{ $ac['bg'] }};color:{{ $ac['text'] }}">{{ $action->status_label }}</span>
                                    <span
                                        style="font-weight:600;font-size:.88rem;color:var(--ink)">{{ $action->title }}</span>
                                </div>
                                @if ($action->secretaria)
                                    <div style="font-size:.75rem;color:var(--ink-muted)">{{ $action->secretaria }}</div>
                                @endif
                                @if ($action->promises->isNotEmpty())
                                    <div style="font-size:.75rem;color:var(--ink-muted);margin-top:.2rem">
                                        compromissos: {{ $action->promises->pluck('text')->implode(' · ') }}
                                    </div>
                                @endif
                            </div>
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.25rem;flex-shrink:0">
                                @if ($action->physical_progress)
                                    <span style="font-size:.72rem;color:var(--ink-muted)">{{ $action->physical_progress }}%
                                        executado</span>
                                @endif
                                <a href="{{ route('mayor.mandato.acao.edit', $action->id) }}"
                                    style="font-size:.75rem;color:var(--gold)">editar</a>
                            </div>
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
