@extends('layouts.mayor')

@section('title', 'Monitoramento de Menções')
@section('topbar-title', 'Redes & Notícias')

@push('styles')
    <style>
        /* ── Botões ────────────────────────────────────────────────── */
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

        .alert-success {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid #c3e6d0;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: .75rem 1rem;
            font-size: .84rem;
            margin-bottom: 1rem;
        }

        /* ── Layout ────────────────────────────────────────────────── */
        .mentions-wrap {
            padding: 1.75rem 2rem;
            max-width: 1080px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* ── Header ────────────────────────────────────────────────── */
        .mentions-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .mentions-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.35rem;
            color: var(--ink);
            margin: 0;
        }

        .mentions-header p {
            font-size: .82rem;
            color: var(--ink-muted);
            margin: .2rem 0 0;
        }

        /* ── KPIs ──────────────────────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: .75rem;
        }

        @media(max-width:800px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: .85rem 1rem;
        }

        .kpi-val {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
        }

        .kpi-label {
            font-size: .72rem;
            color: var(--ink-muted);
            margin-top: .2rem;
        }

        /* ── Filtros ───────────────────────────────────────────────── */
        .filter-bar {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: .4rem .85rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 500;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--ink-muted);
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .filter-btn.negative.active {
            background: var(--red);
            border-color: var(--red);
        }

        .filter-btn.positive.active {
            background: var(--green);
            border-color: var(--green);
        }

        /* ── Mention cards ─────────────────────────────────────────── */
        .mention-list {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        .mention-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .9rem 1.1rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: .85rem;
            align-items: start;
            transition: border-color .15s;
        }

        .mention-card:hover {
            border-color: var(--gold);
        }

        .mention-card.unread {
            border-left: 3px solid var(--gold);
        }

        .mention-platform {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--bg);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .mention-title {
            font-weight: 600;
            font-size: .88rem;
            color: var(--ink);
            margin-bottom: .2rem;
        }

        .mention-snippet {
            font-size: .82rem;
            color: var(--ink-soft);
            line-height: 1.5;
            margin-bottom: .35rem;
        }

        .mention-meta {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            align-items: center;
            font-size: .72rem;
            color: var(--ink-muted);
        }

        .mention-badge {
            padding: .15rem .5rem;
            border-radius: 4px;
            font-size: .7rem;
            font-weight: 600;
        }

        .mention-actions {
            display: flex;
            flex-direction: column;
            gap: .3rem;
            align-items: flex-end;
            flex-shrink: 0;
        }

        /* ── Gráfico ───────────────────────────────────────────────── */
        .chart-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
        }

        .chart-title {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--ink-muted);
            margin-bottom: 1rem;
        }

        .chart-bars {
            display: flex;
            gap: .4rem;
            align-items: flex-end;
            height: 80px;
        }

        .chart-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }

        .chart-bar {
            width: 100%;
            border-radius: 3px 3px 0 0;
            min-height: 2px;
        }

        .chart-label {
            font-size: .65rem;
            color: var(--ink-muted);
            margin-top: .2rem;
            white-space: nowrap;
        }

        /* ── Empty state ───────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .empty-state p {
            font-size: .85rem;
            color: var(--ink-muted);
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="mentions-wrap">

        {{-- Header --}}
        <div class="mentions-header">
            <div>
                <h1>Redes & Notícias</h1>
                <p>Monitoramento de menções sobre {{ $municipality->name }} — atualizado automaticamente</p>
                <p style="font-size:.78rem;color:var(--ink-muted);margin:.35rem 0 0;max-width:62ch">
                    A busca consulta fontes públicas (Google News e Twitter/X via RSS) usando suas palavras‑chave, salva as
                    ocorrências no sistema e
                    aplica análise de sentimento automaticamente.
                </p>

            </div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap">
                <a href="{{ route('mayor.mentions.config') }}" class="btn-secondary" style="font-size:.8rem">
                    ⚙️ Palavras-chave ({{ $keywords->count() }})
                </a>
                <form method="POST" action="{{ route('mayor.mentions.refresh') }}">
                    @csrf
                    <button type="submit" class="btn-primary" style="font-size:.8rem">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
                            <path
                                d="M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                        </svg>
                        Buscar agora
                    </button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        @if ($keywords->isEmpty())
            <div class="empty-state">
                <div style="font-size:2rem;margin-bottom:.5rem">📡</div>
                <h3 style="font-size:1.1rem;color:var(--ink);margin-bottom:.5rem">Configure o monitoramento</h3>
                <p>Adicione palavras-chave para começar a monitorar menções sobre o município.</p>
                <a href="{{ route('mayor.mentions.config') }}" class="btn-primary">Configurar palavras-chave</a>
            </div>
        @else
            {{-- KPIs --}}
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-val" style="color:var(--ink)">{{ $stats['total'] }}</div>
                    <div class="kpi-label">Total ({{ $days }}d)</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-val" style="color:var(--green)">{{ $stats['positive'] }}</div>
                    <div class="kpi-label">Positivas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-val" style="color:var(--red)">{{ $stats['negative'] }}</div>
                    <div class="kpi-label">Negativas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-val" style="color:var(--ink-muted)">{{ $stats['neutral'] }}</div>
                    <div class="kpi-label">Neutras</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-val" style="color:{{ $stats['unread'] > 0 ? 'var(--gold)' : 'var(--ink-muted)' }}">
                        {{ $stats['unread'] }}</div>
                    <div class="kpi-label">Não lidas</div>
                </div>
            </div>

            {{-- Gráfico simples --}}
            @if (!empty($chartData))
                <div class="chart-wrap">
                    <div class="chart-title">Menções por dia</div>
                    <div style="display:flex;gap:.75rem;margin-bottom:.5rem;font-size:.72rem;align-items:center">
                        <span style="display:flex;align-items:center;gap:.3rem"><span
                                style="width:10px;height:10px;border-radius:2px;background:var(--green);display:inline-block"></span>Positivas</span>
                        <span style="display:flex;align-items:center;gap:.3rem"><span
                                style="width:10px;height:10px;border-radius:2px;background:var(--red);display:inline-block"></span>Negativas</span>
                        <span style="display:flex;align-items:center;gap:.3rem"><span
                                style="width:10px;height:10px;border-radius:2px;background:#ccc;display:inline-block"></span>Neutras</span>
                    </div>
                    @php
                        $maxVal =
                            max(array_map(fn($d) => $d['positive'] + $d['negative'] + $d['neutral'], $chartData)) ?: 1;
                    @endphp
                    <div class="chart-bars">
                        @foreach ($chartData as $day)
                            @php
                                $total = $day['positive'] + $day['negative'] + $day['neutral'];
                                $posH = $total > 0 ? round(($day['positive'] / $maxVal) * 76) : 0;
                                $negH = $total > 0 ? round(($day['negative'] / $maxVal) * 76) : 0;
                                $neuH = $total > 0 ? round(($day['neutral'] / $maxVal) * 76) : 0;
                            @endphp
                            <div class="chart-col">
                                @if ($neuH > 0)
                                    <div class="chart-bar" style="height:{{ $neuH }}px;background:#ccc"></div>
                                @endif
                                @if ($negH > 0)
                                    <div class="chart-bar" style="height:{{ $negH }}px;background:var(--red)">
                                    </div>
                                @endif
                                @if ($posH > 0)
                                    <div class="chart-bar" style="height:{{ $posH }}px;background:var(--green)">
                                    </div>
                                @endif
                                @if ($total === 0)
                                    <div class="chart-bar" style="height:4px;background:var(--border)"></div>
                                @endif
                                <span class="chart-label">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Filtros --}}
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem">
                <div class="filter-bar">
                    <a href="{{ route('mayor.mentions.index', array_merge(request()->query(), ['filter' => 'all'])) }}"
                        class="filter-btn {{ $filter === 'all' ? 'active' : '' }}">Todas</a>
                    <a href="{{ route('mayor.mentions.index', array_merge(request()->query(), ['filter' => 'unread'])) }}"
                        class="filter-btn {{ $filter === 'unread' ? 'active' : '' }}">
                        Não lidas @if ($stats['unread'] > 0)
                            <span
                                style="background:var(--gold);color:#fff;border-radius:999px;padding:.1rem .35rem;font-size:.65rem;margin-left:.2rem">{{ $stats['unread'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('mayor.mentions.index', array_merge(request()->query(), ['filter' => 'negative'])) }}"
                        class="filter-btn negative {{ $filter === 'negative' ? 'active' : '' }}">⚠️ Negativas</a>
                    <a href="{{ route('mayor.mentions.index', array_merge(request()->query(), ['filter' => 'positive'])) }}"
                        class="filter-btn positive {{ $filter === 'positive' ? 'active' : '' }}">✅ Positivas</a>
                    <a href="{{ route('mayor.mentions.index', array_merge(request()->query(), ['filter' => 'neutral'])) }}"
                        class="filter-btn {{ $filter === 'neutral' ? 'active' : '' }}">Neutras</a>
                </div>
                <div style="display:flex;gap:.5rem;align-items:center">
                    <select onchange="window.location=this.value"
                        style="font-size:.78rem;padding:.35rem .6rem;border:1px solid var(--border);border-radius:6px;background:var(--white)">
                        @foreach ([3 => '3 dias', 7 => '7 dias', 14 => '14 dias', 30 => '30 dias'] as $d => $label)
                            <option
                                value="{{ route('mayor.mentions.index', array_merge(request()->query(), ['days' => $d])) }}"
                                {{ $days == $d ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($stats['unread'] > 0)
                        <form method="POST" action="{{ route('mayor.mentions.read') }}">
                            @csrf
                            <button type="submit" class="btn-secondary" style="font-size:.75rem;padding:.4rem .8rem">
                                Marcar todas como lidas
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Lista de menções --}}
            @if ($mentions->isEmpty())
                <div class="empty-state">
                    <p>Nenhuma menção encontrada para os filtros selecionados.</p>
                </div>
            @else
                <div class="mention-list">
                    @foreach ($mentions as $mention)
                        @php $sc = $mention->sentiment_color; @endphp
                        <div class="mention-card {{ !$mention->is_read ? 'unread' : '' }}">
                            {{-- Ícone da plataforma --}}
                            <div class="mention-platform">{{ $mention->platform_icon }}</div>

                            {{-- Conteúdo --}}
                            <div>
                                @if ($mention->title)
                                    <div class="mention-title">{{ $mention->title }}</div>
                                @endif
                                @if ($mention->content)
                                    <div class="mention-snippet">{{ Str::limit($mention->content, 200) }}</div>
                                @endif
                                <div class="mention-meta">
                                    <span class="mention-badge"
                                        style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">
                                        {{ $mention->sentiment_label }}
                                        @if ($mention->sentiment_score)
                                            ({{ $mention->sentiment_score > 0 ? '+' : '' }}{{ $mention->sentiment_score }})
                                        @endif
                                    </span>
                                    <span>{{ $mention->source_label }}</span>
                                    @if ($mention->author)
                                        <span>{{ $mention->author }}</span>
                                    @endif
                                    @if ($mention->keyword)
                                        <span style="color:var(--gold)">#{{ $mention->keyword }}</span>
                                    @endif
                                    <span>{{ $mention->time_ago }}</span>
                                    @if ($mention->sentiment_reason)
                                        <span style="color:var(--ink-soft);font-style:italic">—
                                            {{ $mention->sentiment_reason }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Ações --}}
                            <div class="mention-actions">
                                @if ($mention->url)
                                    <a href="{{ $mention->url }}" target="_blank" rel="noopener" class="btn-secondary"
                                        style="font-size:.72rem;padding:.3rem .6rem">
                                        Abrir →
                                    </a>
                                @endif
                                @if (!$mention->is_read)
                                    <form method="POST" action="{{ route('mayor.mentions.read') }}">
                                        @csrf
                                        <input type="hidden" name="mention_id" value="{{ $mention->id }}">
                                        <button type="submit"
                                            style="font-size:.7rem;color:var(--ink-muted);background:none;border:none;cursor:pointer">
                                            ✓ lida
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Paginação --}}
                @if ($mentions->hasPages())
                    <div style="display:flex;justify-content:center">{{ $mentions->links() }}</div>
                @endif
            @endif
        @endif
    </div>
@endsection
