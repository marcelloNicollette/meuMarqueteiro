@extends('layouts.mayor')

@section('title', 'Ações de Governo')
@section('topbar-title', 'Mandato · Ações de Governo')

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

        .acoes-wrap {
            padding: 1.75rem 2rem;
            max-width: 1080px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .acoes-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .acoes-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.35rem;
            color: var(--ink);
            margin: 0;
        }

        /* Filtros */
        .filter-bar {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar select,
        .filter-bar input {
            font-size: .82rem;
        }

        /* Tabela */
        .acoes-table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: .65rem 1rem;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-muted);
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        tbody td {
            padding: .75rem 1rem;
            font-size: .83rem;
            color: var(--ink);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background: var(--bg);
        }

        .status-badge {
            padding: .2rem .55rem;
            border-radius: 4px;
            font-size: .7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .progress-bar-mini {
            height: 5px;
            background: var(--border);
            border-radius: 999px;
            overflow: hidden;
            width: 80px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 999px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--ink-muted);
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="acoes-wrap">

        <div class="acoes-header">
            <div>
                <h1>Ações de Governo</h1>
                <p style="font-size:.82rem;color:var(--ink-muted);margin:.2rem 0 0">
                    {{ $actions->total() }} ações cadastradas
                </p>
            </div>
            <div style="display:flex;gap:.6rem">
                <a href="{{ route('mayor.mandato.painel') }}" class="btn-secondary" style="font-size:.8rem">← Painel</a>
                <a href="{{ route('mayor.mandato.acao.create') }}" class="btn-primary" style="font-size:.8rem">+ Nova ação</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        {{-- Filtro por eixo --}}
        <form method="GET" class="filter-bar">
            <select name="axis" onchange="this.form.submit()">
                <option value="">Todos os eixos</option>
                @foreach ($axes as $axis)
                    <option value="{{ $axis->id }}" {{ request('axis') == $axis->id ? 'selected' : '' }}>
                        {{ $axis->icon ?? '' }} {{ $axis->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">Todos os status</option>
                <option value="planejado" {{ request('status') == 'planejado' ? 'selected' : '' }}>Planejado</option>
                <option value="em_andamento" {{ request('status') == 'em_andamento' ? 'selected' : '' }}>Em andamento
                </option>
                <option value="concluido" {{ request('status') == 'concluido' ? 'selected' : '' }}>Concluído</option>
                <option value="suspenso" {{ request('status') == 'suspenso' ? 'selected' : '' }}>Suspenso</option>
            </select>
        </form>

        @if ($actions->isEmpty())
            <div class="acoes-table-wrap">
                <div class="empty-state">
                    Nenhuma ação cadastrada ainda.
                    <br><br>
                    <a href="{{ route('mayor.mandato.acao.create') }}" class="btn-primary"
                        style="font-size:.82rem">Cadastrar primeira ação</a>
                </div>
            </div>
        @else
            <div class="acoes-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ação</th>
                            <th>Eixo</th>
                            <th>Status</th>
                            <th>Execução</th>
                            <th>Compromissos</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($actions as $action)
                            @php
                                $colors = [
                                    'concluido' => ['bg' => '#dcfce7', 'text' => '#1e7e48'],
                                    'em_andamento' => ['bg' => '#fef3c7', 'text' => '#b8902a'],
                                    'planejado' => ['bg' => '#dbeafe', 'text' => '#1e3a5f'],
                                    'suspenso' => ['bg' => '#fee2e2', 'text' => '#b52b2b'],
                                ][$action->status] ?? ['bg' => '#f3f4f6', 'text' => '#666'];
                                $barColor =
                                    $action->physical_progress >= 75
                                        ? '#1e7e48'
                                        : ($action->physical_progress >= 25
                                            ? '#b8902a'
                                            : '#b52b2b');
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:500">{{ $action->title }}</div>
                                    @if ($action->secretaria)
                                        <div style="font-size:.73rem;color:var(--ink-muted)">{{ $action->secretaria }}
                                        </div>
                                    @endif
                                </td>
                                <td style="color:var(--ink-soft);font-size:.8rem">{{ $action->axis?->icon }}
                                    {{ $action->axis?->name }}</td>
                                <td>
                                    <span class="status-badge"
                                        style="background:{{ $colors['bg'] }};color:{{ $colors['text'] }}">
                                        {{ $action->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.4rem">
                                        <div class="progress-bar-mini">
                                            <div class="progress-fill"
                                                style="width:{{ $action->physical_progress }}%;background:{{ $barColor }}">
                                            </div>
                                        </div>
                                        <span
                                            style="font-size:.75rem;color:var(--ink-muted)">{{ $action->physical_progress }}%</span>
                                    </div>
                                </td>
                                <td style="font-size:.78rem;color:var(--ink-muted)">
                                    {{ $action->promises->count() }} compromisso(s)
                                </td>
                                <td>
                                    <a href="{{ route('mayor.mandato.acao.edit', $action->id) }}"
                                        style="font-size:.78rem;color:var(--gold)">editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            @if ($actions->hasPages())
                <div style="display:flex;justify-content:center">
                    {{ $actions->links() }}
                </div>
            @endif
        @endif

    </div>
@endsection
