@extends('layouts.mayor')
@section('title', 'Briefing do Dia')
@section('topbar-title', 'Briefing Matinal')

@push('styles')
    <style>
        .briefing-wrap {
            max-width: 780px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* Cabeçalho */
        .briefing-header {
            background: linear-gradient(135deg, var(--ink) 0%, #1e2740 100%);
            border-radius: 16px;
            padding: 1.75rem 2rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .briefing-header-left {
            flex: 1;
        }

        .briefing-date {
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: .5rem;
        }

        .briefing-title {
            font-family: 'Lora', serif;
            font-size: 1.4rem;
            color: #fff;
            line-height: 1.3;
            margin-bottom: .3rem;
        }

        .briefing-meta {
            font-size: .78rem;
            color: rgba(255, 255, 255, .45);
        }

        .briefing-badge {
            display: flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .8rem;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .briefing-badge.unread {
            background: var(--gold);
            color: var(--ink);
        }

        .briefing-badge.read {
            background: rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .5);
        }

        /* Stats do mandato no topo */
        .briefing-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .75rem;
            margin-bottom: 1.75rem;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem 1.1rem;
            text-align: center;
        }

        .stat-value {
            font-family: 'Lora', serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--ink);
            line-height: 1;
            margin-bottom: .25rem;
        }

        .stat-value.green {
            color: #1e7e48;
        }

        .stat-value.orange {
            color: #d97706;
        }

        .stat-value.blue {
            color: #1a5fa8;
        }

        .stat-label {
            font-size: .72rem;
            color: var(--ink-muted);
        }

        /* Conteúdo do briefing */
        .briefing-body {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .briefing-content {
            padding: 1.75rem 2rem;
            font-size: .92rem;
            line-height: 1.85;
            color: var(--ink-soft);
        }

        /* Estilizar o markdown renderizado */
        .briefing-content h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
            margin: 1.5rem 0 .6rem;
            padding-bottom: .4rem;
            border-bottom: 1.5px solid var(--border-lt);
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .briefing-content h2:first-child {
            margin-top: 0;
        }

        .briefing-content p {
            margin-bottom: .85rem;
        }

        .briefing-content ul,
        .briefing-content ol {
            margin: .5rem 0 .85rem 1.2rem;
        }

        .briefing-content li {
            margin-bottom: .3rem;
        }

        .briefing-content strong {
            color: var(--ink);
            font-weight: 600;
        }

        /* Footer do card */
        .briefing-footer {
            padding: .9rem 1.75rem;
            border-top: 1px solid var(--border-lt);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .76rem;
            color: var(--ink-muted);
        }

        .briefing-footer-actions {
            display: flex;
            gap: .5rem;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .45rem .9rem;
            border-radius: 8px;
            font-family: "Open Sans", sans-serif;
            font-size: .78rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--ink-soft);
            text-decoration: none;
            transition: all .15s;
        }

        .btn-action:hover {
            border-color: var(--ink);
            color: var(--ink);
        }

        .btn-action.gold {
            background: var(--gold);
            color: white;
            border-color: var(--gold);
        }

        .btn-action.gold:hover {
            background: var(--gold-lt);
        }

        .btn-action svg {
            width: 13px;
            height: 13px;
        }

        /* Navegação */
        .briefing-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
        }

        @media (max-width: 640px) {
            .briefing-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .briefing-header {
                flex-direction: column;
            }

            .briefing-content {
                padding: 1.25rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="briefing-wrap">

        {{-- Navegação de volta --}}
        <div style="margin-bottom:1.25rem">
            <a href="{{ route('mayor.mandato.briefings') }}"
                style="display:inline-flex;align-items:center;gap:.35rem;font-size:.82rem;color:var(--ink-muted);text-decoration:none">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
                Todos os briefings
            </a>
        </div>

        {{-- Cabeçalho --}}
        <div class="briefing-header">
            <div class="briefing-header-left">
                <div class="briefing-date">
                    {{ $briefing->date->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </div>
                <div class="briefing-title">☀️ Briefing Matinal</div>
                <div class="briefing-meta">
                    Gerado às {{ $briefing->created_at->format('H:i') }}
                    @if ($briefing->tokens_used)
                        · {{ number_format($briefing->tokens_used) }} tokens
                    @endif
                </div>
            </div>
            <div class="briefing-badge {{ $briefing->read_at ? 'read' : 'unread' }}">
                @if ($briefing->read_at)
                    ✓ Lido
                @else
                    NOVO
                @endif
            </div>
        </div>

        {{-- Stats do mandato (do sections JSON) --}}
        @if ($briefing->sections && isset($briefing->sections['compromissos_total']))
            @php
                $stats = $briefing->sections;
                $total = $stats['compromissos_total'] ?? 0;
                $ok = $stats['compromissos_ok'] ?? 0;
                $pct = $stats['pct_concluido'] ?? 0;
                $risco = $stats['em_risco'] ?? 0;
                $demandas = $stats['demandas_pendentes'] ?? 0;
                $programas = $stats['programas_abertos'] ?? 0;
            @endphp
            <div class="briefing-stats">
                <div class="stat-card">
                    <div class="stat-value green">{{ $pct }}%</div>
                    <div class="stat-label">Mandato cumprido</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $ok }}/{{ $total }}</div>
                    <div class="stat-label">Compromissos entregues</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value {{ $risco > 0 ? 'orange' : 'green' }}">{{ $risco }}</div>
                    <div class="stat-label">Em risco</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value blue">{{ $programas }}</div>
                    <div class="stat-label">Programas federais abertos</div>
                </div>
            </div>
        @endif

        {{-- Conteúdo do briefing --}}
        <div class="briefing-body">
            <div class="briefing-content">
                {!! nl2br(e($briefing->content)) !!}
            </div>
            <div class="briefing-footer">
                <span>{{ $briefing->date->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                <div class="briefing-footer-actions">
                    <button class="btn-action" onclick="copyBriefing()" title="Copiar texto">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
                        </svg>
                        Copiar
                    </button>
                    <a href="{{ route('mayor.chat.index') }}?context=briefing_{{ $briefing->id }}"
                        class="btn-action gold">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" />
                        </svg>
                        Perguntar ao assistente
                    </a>
                </div>
            </div>
        </div>

        {{-- Navegação entre briefings --}}
        <div class="briefing-nav">
            <span style="font-size:.8rem;color:var(--ink-muted)">
                <a href="{{ route('mayor.mandato.briefings') }}" style="color:var(--ink-muted);text-decoration:none">←
                    Todos os briefings</a>
            </span>
            <span style="font-size:.8rem;color:var(--ink-muted)">
                {{ $briefing->date->locale('pt_BR')->isoFormat('D [de] MMMM') }}
            </span>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function copyBriefing() {
            const content = document.querySelector('.briefing-content');
            if (content) {
                navigator.clipboard.writeText(content.innerText).then(() => {
                    const btn = event.currentTarget;
                    const orig = btn.innerHTML;
                    btn.innerHTML =
                        '<svg viewBox="0 0 24 24" fill="currentColor" style="width:13px;height:13px"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Copiado!';
                    setTimeout(() => btn.innerHTML = orig, 2000);
                });
            }
        }
    </script>
@endpush
