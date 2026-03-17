@extends('layouts.mayor')

@section('title', 'Programas Federais')
@section('topbar-title', 'Radar de Programas Federais')

@push('styles')
    <style>
        .programs-layout {
            padding: 1.75rem 2rem;
            max-width: 1100px;
        }

        /* ── Header ─── */
        .programs-header {
            margin-bottom: 1.75rem;
        }

        .programs-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.5rem;
            color: var(--ink);
            margin-bottom: .3rem;
        }

        .programs-header p {
            font-size: .85rem;
            color: var(--ink-muted);
        }

        /* ── Banner de destaque ─── */
        .highlight-banner {
            background: linear-gradient(135deg, #0f1117 60%, #1a2a1a);
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
            position: relative;
            overflow: hidden;
        }

        .highlight-banner::before {
            content: '';
            position: absolute;
            right: -60px;
            top: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 1px solid rgba(184, 144, 42, .15);
        }

        .highlight-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(184, 144, 42, .15);
            border: 1px solid rgba(184, 144, 42, .25);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .highlight-icon svg {
            width: 22px;
            height: 22px;
            fill: var(--gold-lt);
        }

        .highlight-text h3 {
            font-family: 'Lora', serif;
            font-size: 1rem;
            color: #fff;
            margin-bottom: .25rem;
        }

        .highlight-text p {
            font-size: .82rem;
            color: rgba(255, 255, 255, .45);
            line-height: 1.6;
        }

        .highlight-cta {
            margin-left: auto;
            flex-shrink: 0;
            padding: .6rem 1.1rem;
            border-radius: 8px;
            background: var(--gold);
            color: var(--ink);
            font-family: "Open Sans", sans-serif;
            font-size: .83rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }

        .highlight-cta:hover {
            background: var(--gold-lt);
        }

        /* ── Tabs por área ─── */
        .area-tabs {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .area-tab {
            padding: .45rem .9rem;
            border-radius: 20px;
            border: 1.5px solid var(--border);
            background: none;
            cursor: pointer;
            font-family: "Open Sans", sans-serif;
            font-size: .8rem;
            font-weight: 500;
            color: var(--ink-muted);
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .area-tab.active {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }

        .area-tab-count {
            background: rgba(255, 255, 255, .15);
            color: inherit;
            font-size: .65rem;
            padding: .1rem .35rem;
            border-radius: 8px;
        }

        .area-tab:not(.active) .area-tab-count {
            background: var(--surface);
        }

        /* ── Grid de programas ─── */
        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 1rem;
        }

        .program-card {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: box-shadow .2s, transform .15s;
        }

        .program-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
            transform: translateY(-1px);
        }

        .program-card.high-match {
            border-color: var(--gold);
        }

        .program-card-head {
            padding: 1rem 1.2rem .85rem;
        }

        .prog-head-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .75rem;
        }

        .prog-area-tag {
            font-size: .66rem;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: .22rem .6rem;
            border-radius: 5px;
            flex-shrink: 0;
        }

        .area-saude {
            background: #fce4ec;
            color: #c62828;
        }

        .area-educacao {
            background: #e3f2fd;
            color: #1565c0;
        }

        .area-infraestrutura {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .area-saneamento {
            background: #e0f2f1;
            color: #00695c;
        }

        .area-habitacao {
            background: #fff8e1;
            color: #e65100;
        }

        .area-social {
            background: var(--green-bg);
            color: var(--green);
        }

        .area-outros {
            background: var(--surface);
            color: var(--ink-muted);
        }

        .match-badge {
            display: flex;
            align-items: center;
            gap: .3rem;
            font-size: .72rem;
            font-weight: 500;
            color: var(--gold);
            white-space: nowrap;
        }

        .match-badge svg {
            width: 13px;
            height: 13px;
        }

        .prog-title {
            font-family: 'Lora', serif;
            font-size: .97rem;
            color: var(--ink);
            line-height: 1.35;
            margin-bottom: .4rem;
        }

        .prog-ministry {
            font-size: .76rem;
            color: var(--ink-muted);
            margin-bottom: .85rem;
        }

        .prog-info-row {
            display: flex;
            gap: 1.2rem;
        }

        .prog-info-item {
            font-size: .75rem;
        }

        .prog-info-label {
            color: var(--ink-muted);
            margin-bottom: .1rem;
            font-size: .68rem;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .prog-info-value {
            color: var(--ink);
            font-weight: 500;
        }

        .prog-info-value.urgent {
            color: var(--red);
        }

        .prog-info-value.soon {
            color: #e65100;
        }

        .program-card-footer {
            padding: .75rem 1.2rem;
            border-top: 1px solid var(--border-lt);
            background: var(--surface);
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .prog-status {
            font-size: .72rem;
            font-weight: 500;
            padding: .22rem .65rem;
            border-radius: 10px;
        }

        .prog-status.open {
            background: var(--green-bg);
            color: var(--green);
        }

        .prog-status.closing {
            background: #fff8e1;
            color: #e65100;
        }

        .prog-status.applied {
            background: var(--blue-bg);
            color: var(--blue);
        }

        .prog-status.closed {
            background: var(--surface);
            color: var(--ink-muted);
        }

        .prog-ask-btn {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .8rem;
            border-radius: 7px;
            font-family: "Open Sans", sans-serif;
            font-size: .78rem;
            font-weight: 500;
            background: var(--ink);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background .15s;
            text-decoration: none;
        }

        .prog-ask-btn:hover {
            background: #1e2230;
        }

        .prog-ask-btn svg {
            width: 13px;
            height: 13px;
        }

        .prog-link {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .78rem;
            color: var(--ink-muted);
            text-decoration: none;
            transition: color .15s;
        }

        .prog-link:hover {
            color: var(--ink);
        }

        .prog-link svg {
            width: 12px;
            height: 12px;
        }

        /* ── Empty state ─── */
        .programs-empty {
            grid-column: 1/-1;
            text-align: center;
            padding: 3rem;
            color: var(--ink-muted);
        }

        .programs-empty svg {
            width: 36px;
            height: 36px;
            margin-bottom: 1rem;
            opacity: .3;
        }

        .programs-empty p {
            font-size: .87rem;
        }

        /* ── Toast ─── */
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: var(--ink);
            color: #fff;
            padding: .75rem 1.2rem;
            border-radius: 9px;
            font-size: .84rem;
            transform: translateY(60px);
            opacity: 0;
            transition: all .3s ease;
            z-index: 999;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        @media (max-width: 768px) {
            .programs-layout {
                padding: 1rem;
            }

            .highlight-banner {
                flex-wrap: wrap;
            }

            .highlight-cta {
                width: 100%;
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')

    <div class="programs-layout">

        <div class="programs-header">
            <h1>Radar de Programas Federais</h1>
            <p>
                Oportunidades identificadas pelo assistente com base no perfil de
                <strong>{{ auth()->user()->municipality->name }}</strong>.
                Atualizado em {{ auth()->user()->municipality->data_last_synced_at?->format('d/m/Y') ?? 'breve' }}.
            </p>
        </div>

        {{-- ── Banner ────────────────────────────────────────────── --}}
        <div class="highlight-banner">
            <div class="highlight-icon">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z" />
                </svg>
            </div>
            <div class="highlight-text">
                <h3>{{ $programs->where('status', 'open')->count() }} programas abertos identificados</h3>
                <p>O assistente monitorou editais e selecionou os que se encaixam no perfil do seu município.</p>
            </div>
            <button class="highlight-cta" onclick="askAssistantGeneral()">
                Analisar com o assistente
            </button>
        </div>

        {{-- ── Tabs por área ────────────────────────────────────────── --}}
        @php
            $areas = $programs->groupBy('area');
            $areaLabels = [
                'saude' => 'Saúde',
                'educacao' => 'Educação',
                'infraestrutura' => 'Infraestrutura',
                'saneamento' => 'Saneamento',
                'habitacao' => 'Habitação',
                'social' => 'Social',
                'outros' => 'Outros',
            ];
        @endphp

        <div class="area-tabs">
            <button class="area-tab active" data-area="todos" onclick="filterArea(this)">
                Todos <span class="area-tab-count">{{ $programs->count() }}</span>
            </button>
            @foreach ($areas as $area => $areaPrograms)
                <button class="area-tab" data-area="{{ $area }}" onclick="filterArea(this)">
                    {{ $areaLabels[$area] ?? ucfirst($area) }}
                    <span class="area-tab-count">{{ $areaPrograms->count() }}</span>
                </button>
            @endforeach
        </div>

        {{-- ── Grid de programas ──────────────────────────────────── --}}
        <div class="programs-grid" id="programsGrid">

            @forelse($programs->sortByDesc('match_score') as $program)
                @php
                    $isHighMatch = ($program->match_score ?? 0) >= 0.85;
                    $deadline = $program->deadline;
                    $daysLeft = $deadline ? now()->diffInDays($deadline, false) : null;
                    $deadlineClass =
                        $daysLeft !== null ? ($daysLeft <= 7 ? 'urgent' : ($daysLeft <= 30 ? 'soon' : '')) : '';

                    $statusLabels = [
                        'open' => 'Aberto',
                        'closing' => 'Encerrando',
                        'applied' => 'Candidatado',
                        'closed' => 'Encerrado',
                    ];
                @endphp

                <div class="program-card {{ $isHighMatch ? 'high-match' : '' }}" data-area="{{ $program->area }}">
                    <div class="program-card-head">
                        <div class="prog-head-row">
                            <span class="prog-area-tag area-{{ $program->area }}">
                                {{ $areaLabels[$program->area] ?? ucfirst($program->area) }}
                            </span>
                            @if ($program->match_score)
                                <div class="match-badge">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                    </svg>
                                    {{ round($program->match_score * 100) }}% compatível
                                </div>
                            @endif
                        </div>

                        <div class="prog-title">{{ $program->program_name }}</div>

                        @if ($program->ministry)
                            <div class="prog-ministry">{{ $program->ministry }}</div>
                        @endif

                        <div class="prog-info-row">
                            @if ($program->max_value)
                                <div class="prog-info-item">
                                    <div class="prog-info-label">Valor máximo</div>
                                    <div class="prog-info-value">
                                        R$ {{ number_format($program->max_value, 0, ',', '.') }}
                                    </div>
                                </div>
                            @endif

                            @if ($deadline)
                                <div class="prog-info-item">
                                    <div class="prog-info-label">Prazo</div>
                                    <div class="prog-info-value {{ $deadlineClass }}">
                                        @if ($daysLeft !== null && $daysLeft <= 0)
                                            Encerrado
                                        @elseif($daysLeft !== null && $daysLeft <= 7)
                                            {{ $daysLeft }}d restantes ⚠️
                                        @elseif($daysLeft !== null && $daysLeft <= 30)
                                            {{ $daysLeft }} dias
                                        @else
                                            {{ $deadline->format('d/m/Y') }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="program-card-footer">
                        <span class="prog-status {{ $program->status }}">
                            {{ $statusLabels[$program->status] ?? $program->status }}
                        </span>

                        @if ($program->source_url)
                            <a href="{{ $program->source_url }}" target="_blank" class="prog-link">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z" />
                                </svg>
                                Edital
                            </a>
                        @endif

                        <button class="prog-ask-btn" onclick="askAboutProgram('{{ addslashes($program->program_name) }}')">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                            </svg>
                            Perguntar ao assistente
                        </button>
                    </div>
                </div>
            @empty
                <div class="programs-empty">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                    </svg>
                    <p>Nenhum programa identificado ainda.<br>Os dados serão sincronizados automaticamente.</p>
                </div>
            @endforelse

        </div>
    </div>

    {{-- Toast --}}
    <div class="toast" id="toast"></div>

@endsection

@push('scripts')
    <script>
        // ── Filtro por área ───────────────────────────────────────
        function filterArea(btn) {
            document.querySelectorAll('.area-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const area = btn.dataset.area;
            document.querySelectorAll('.program-card').forEach(card => {
                card.style.display = (area === 'todos' || card.dataset.area === area) ? 'block' : 'none';
            });
        }

        // ── Perguntar sobre programa específico ───────────────────
        function askAboutProgram(programName) {
            const question =
                `Quero entender melhor o programa "${programName}". O meu município se enquadra nos critérios de elegibilidade? Quais são os passos para se candidatar e quais documentos são necessários?`;
            sessionStorage.setItem('chatPrefill', question);
            window.location.href = '{{ route('mayor.chat.index') }}';
        }

        // ── Perguntar sobre programas em geral ────────────────────
        function askAssistantGeneral() {
            const question =
                'Quais são os programas federais mais importantes que se encaixam no nosso município agora? Priorize pelos prazos mais curtos e pelos valores mais altos.';
            sessionStorage.setItem('chatPrefill', question);
            window.location.href = '{{ route('mayor.chat.index') }}';
        }

        // ── Toast ─────────────────────────────────────────────────
        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // Preencher chat com prefill se veio de outro módulo
        document.addEventListener('DOMContentLoaded', () => {
            const prefill = sessionStorage.getItem('chatPrefill');
            if (prefill) sessionStorage.removeItem('chatPrefill');
        });
    </script>
@endpush
