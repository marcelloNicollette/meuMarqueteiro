@extends('layouts.mayor')

@section('title', 'Radar de Recursos')
@section('topbar-title', 'Radar de Recursos')

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
            font-family: "Outfit", sans-serif;
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
            font-family: "Outfit", sans-serif;
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
            font-family: "Inter", sans-serif;
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

        .scope-tabs {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-bottom: .85rem;
        }

        .scope-tab {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem .95rem;
            border-radius: 999px;
            border: 1.5px solid var(--border);
            background: #fff;
            color: var(--ink-muted);
            cursor: pointer;
            font-family: "Inter", sans-serif;
            font-size: .79rem;
            font-weight: 600;
            transition: all .15s;
        }

        .scope-tab.active {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }

        .scope-tab-count {
            font-size: .68rem;
            padding: .1rem .38rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, .15);
        }

        .scope-tab:not(.active) .scope-tab-count {
            background: var(--surface);
        }

        .saved-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #fffdf5;
            border: 1px solid #f0e2b6;
            border-radius: 12px;
            padding: .95rem 1.1rem;
            margin-bottom: 1rem;
        }

        .summary-stack {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .saved-summary strong {
            display: block;
            color: var(--ink);
            font-size: .9rem;
            margin-bottom: .15rem;
        }

        .saved-summary span {
            font-size: .8rem;
            color: var(--ink-muted);
        }

        .saved-summary button {
            flex-shrink: 0;
        }

        .area-tab {
            padding: .45rem .9rem;
            border-radius: 20px;
            border: 1.5px solid var(--border);
            background: none;
            cursor: pointer;
            font-family: "Inter", sans-serif;
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
            display: flex;
            flex-direction: column;
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
            flex: 1;
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
            font-family: "Outfit", sans-serif;
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
            margin-top: auto;
        }

        .prog-status {
            font-size: .72rem;
            font-weight: 500;
            padding: .22rem .65rem;
            border-radius: 10px;
        }

        .prog-status.published,
        .prog-status.reopened {
            background: var(--green-bg);
            color: var(--green);
        }

        .prog-status.closing_soon {
            background: #fff8e1;
            color: #e65100;
        }

        .prog-status.monitoring {
            background: var(--blue-bg);
            color: var(--blue);
        }

        .prog-status.closed_recently,
        .prog-status.archived {
            background: var(--surface);
            color: var(--ink-muted);
        }

        .prog-status.pending_review {
            background: #fffbeb;
            color: #b45309;
        }

        .prog-status.rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        .prog-ask-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .8rem;
            border-radius: 7px;
            font-family: "Inter", sans-serif;
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

        .prog-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .prog-action-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .85rem;
            border-radius: 7px;
            font-family: "Inter", sans-serif;
            font-size: .78rem;
            font-weight: 600;
            background: var(--gold);
            color: var(--ink);
            border: none;
            cursor: pointer;
            transition: background .15s, transform .15s;
            text-decoration: none;
        }

        .prog-action-btn:hover {
            background: var(--gold-lt);
            transform: translateY(-1px);
        }

        .prog-action-btn svg {
            width: 13px;
            height: 13px;
        }

        .prog-meta-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .38rem .75rem;
            border-radius: 7px;
            font-family: "Inter", sans-serif;
            font-size: .74rem;
            font-weight: 600;
            background: #fff;
            color: var(--ink-muted);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all .15s;
        }

        .prog-meta-btn:hover {
            color: var(--ink);
            border-color: var(--ink-muted);
        }

        .prog-meta-btn.is-active {
            background: #eefbf3;
            color: #0f8a4b;
            border-color: #b7e4c7;
        }

        .prog-meta-btn.is-notify {
            background: #fffaf0;
            color: #9a6700;
            border-color: #f2d38b;
        }

        .prog-meta-btn svg {
            width: 12px;
            height: 12px;
        }

        .prog-detail-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .38rem .75rem;
            border-radius: 7px;
            font-family: "Inter", sans-serif;
            font-size: .74rem;
            font-weight: 600;
            background: #f8fafc;
            color: var(--ink);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all .15s;
        }

        .prog-detail-btn:hover {
            background: #eef2f7;
            border-color: var(--ink-muted);
        }

        .prog-detail-btn svg {
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

        .programs-empty.hidden {
            display: none;
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

        .detail-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 17, 23, .55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 1200;
        }

        .detail-modal.open {
            display: flex;
        }

        .detail-panel {
            width: min(980px, 100%);
            max-height: 88vh;
            overflow: auto;
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 30px 80px rgba(15, 17, 23, .22);
        }

        .detail-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.35rem 1rem;
            border-bottom: 1px solid var(--border-lt);
        }

        .detail-head h3 {
            font-family: "Outfit", sans-serif;
            font-size: 1.18rem;
            color: var(--ink);
            margin-bottom: .35rem;
        }

        .detail-head p {
            font-size: .82rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .detail-close {
            border: 0;
            background: transparent;
            color: var(--ink-muted);
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }

        .detail-body {
            padding: 1.1rem 1.35rem 1.35rem;
            display: grid;
            gap: 1rem;
        }

        .detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            align-items: center;
        }

        .detail-actions form {
            margin: 0;
        }

        .detail-actions .prog-action-btn,
        .detail-actions .prog-meta-btn,
        .detail-actions .prog-ask-btn,
        .detail-actions .prog-link {
            margin: 0;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .75rem;
        }

        .detail-stat {
            background: var(--surface);
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            padding: .85rem .95rem;
        }

        .detail-stat strong {
            display: block;
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .35rem;
        }

        .detail-stat span {
            display: block;
            color: var(--ink);
            font-size: .92rem;
            line-height: 1.45;
        }

        .detail-section {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            padding: 1rem 1.05rem;
        }

        .detail-section h4 {
            font-size: .86rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: .6rem;
        }

        .detail-section p {
            font-size: .84rem;
            color: var(--ink-muted);
            line-height: 1.65;
        }

        .detail-list {
            margin: 0;
            padding-left: 1rem;
            color: var(--ink-muted);
            font-size: .83rem;
            line-height: 1.7;
        }

        .detail-tags {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap;
        }

        .detail-tag {
            display: inline-flex;
            align-items: center;
            padding: .28rem .62rem;
            border-radius: 999px;
            background: var(--surface);
            border: 1px solid var(--border-lt);
            color: var(--ink-muted);
            font-size: .73rem;
            font-weight: 600;
        }

        .detail-loading {
            padding: 2rem 1.25rem;
            text-align: center;
            color: var(--ink-muted);
            font-size: .84rem;
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
            <h1>Radar de Recursos</h1>
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
                <h3>{{ $programs->whereIn('status', \App\Enums\ResourceOpportunityStatus::activeForRadar())->count() }}
                    oportunidades ativas identificadas</h3>
                <p>O assistente monitorou fontes de captação e destacou as oportunidades mais aderentes ao perfil do seu
                    município.</p>
            </div>
            <button class="highlight-cta" onclick="askAssistantGeneral()">
                Analisar com o assistente
            </button>
        </div>

        <div class="summary-stack">
            <div class="saved-summary">
                <div>
                    <strong>{{ $savedTotal }} oportunidade(s) salva(s)</strong>
                    <span>Use a visão de salvos para reencontrar rápidamente o que você marcou para acompanhar.</span>
                </div>
                <button class="highlight-cta" type="button" onclick="setScopeFilter('saved')">
                    Ver salvas
                </button>
            </div>
            <div class="saved-summary">
                <div>
                    <strong>{{ $reopenActiveTotal }} reabertura(s) ativa(s)</strong>
                    <span>Veja as oportunidades encerradas que estão com monitoramento de reabertura ligado.</span>
                </div>
                <button class="highlight-cta" type="button" onclick="setScopeFilter('reopen')">
                    Ver reaberturas
                </button>
            </div>
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

        <div class="scope-tabs">
            <button class="scope-tab active" data-scope="all" onclick="setScopeFilter('all', this)">
                Todas
                <span class="scope-tab-count">{{ $programs->count() }}</span>
            </button>
            <button class="scope-tab" data-scope="saved" onclick="setScopeFilter('saved', this)">
                Salvas
                <span class="scope-tab-count">{{ $savedTotal }}</span>
            </button>
            <button class="scope-tab" data-scope="reopen" onclick="setScopeFilter('reopen', this)">
                Reabertura ativa
                <span class="scope-tab-count">{{ $reopenActiveTotal }}</span>
            </button>
        </div>

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

                    $normalizedStatus = \App\Enums\ResourceOpportunityStatus::normalize($program->status, $deadline);
                    $statusLabel = \App\Enums\ResourceOpportunityStatus::labelFor($program->status, $deadline);
                @endphp

                <div class="program-card {{ $isHighMatch ? 'high-match' : '' }}" data-area="{{ $program->area }}"
                    data-saved="{{ $program->is_saved ? '1' : '0' }}"
                    data-reopen="{{ $program->is_reopen_notifying ? '1' : '0' }}">
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
                            <span class="prog-status {{ $normalizedStatus }}">{{ $statusLabel }}</span>
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

                        @if ($program->source_url)
                            <a href="{{ $program->source_url }}" target="_blank" class="prog-link">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z" />
                                </svg>
                                Edital
                            </a>
                        @endif

                        <div class="prog-actions">
                            <form method="POST" action="{{ route('mayor.mandato.federal-programs.save') }}">
                                @csrf
                                @if ($program->exists)
                                    <input type="hidden" name="program_id" value="{{ $program->id }}">
                                @endif
                                @if (filled($program->canonical_cycle_id))
                                    <input type="hidden" name="canonical_cycle_id"
                                        value="{{ $program->canonical_cycle_id }}">
                                @endif
                                @if (filled($program->canonical_opportunity_id))
                                    <input type="hidden" name="canonical_opportunity_id"
                                        value="{{ $program->canonical_opportunity_id }}">
                                @endif
                                <button class="prog-meta-btn {{ $program->is_saved ? 'is-active' : '' }}" type="submit">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17 3H5a2 2 0 0 0-2 2v16l8-3.5L19 21V5a2 2 0 0 0-2-2z" />
                                    </svg>
                                    {{ $program->is_saved ? 'Salvo' : 'Salvar' }}
                                </button>
                            </form>
                            <button class="prog-detail-btn" type="button"
                                data-program-id="{{ $program->exists ? $program->id : '' }}"
                                data-canonical-cycle-id="{{ $program->canonical_cycle_id ?? '' }}"
                                data-canonical-opportunity-id="{{ $program->canonical_opportunity_id ?? '' }}"
                                onclick="openProgramDetail(this)">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M11 9h2V7h-2m1 13C7 20 2.73 16.11 2 11.5 2.73 6.89 7 3 12 3s9.27 3.89 10 8.5C21.27 16.11 17 20 12 20m0-11a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z" />
                                </svg>
                                Ver detalhes
                            </button>
                            @if ($program->can_subscribe_reopen)
                                <form method="POST"
                                    action="{{ route('mayor.mandato.federal-programs.reopen-notification') }}">
                                    @csrf
                                    @if ($program->exists)
                                        <input type="hidden" name="program_id" value="{{ $program->id }}">
                                    @endif
                                    @if (filled($program->canonical_cycle_id))
                                        <input type="hidden" name="canonical_cycle_id"
                                            value="{{ $program->canonical_cycle_id }}">
                                    @endif
                                    @if (filled($program->canonical_opportunity_id))
                                        <input type="hidden" name="canonical_opportunity_id"
                                            value="{{ $program->canonical_opportunity_id }}">
                                    @endif
                                    <button
                                        class="prog-meta-btn {{ $program->is_reopen_notifying ? 'is-notify is-active' : 'is-notify' }}"
                                        type="submit">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M12 6V3L8 7l4 4V8c2.76 0 5 2.24 5 5a5 5 0 0 1-8.66 3.46l-1.42 1.42A7 7 0 1 0 12 6z" />
                                        </svg>
                                        {{ $program->is_reopen_notifying ? 'Reabertura ativa' : 'Notificar reabertura' }}
                                    </button>
                                </form>
                            @endif
                            <a class="prog-action-btn"
                                href="{{ route('mayor.mandato.acao.create', [
                                    'title' => 'Ação a partir do programa: ' . $program->program_name,
                                    'description' => trim(
                                        ($program->description ? $program->description . "\n\n" : '') . 'Origem: ' . ($program->source_url ?? ''),
                                    ),
                                    'funding_source' => $program->funding_type ? ucfirst($program->funding_type) : 'Federal',
                                    'end_date' => optional($program->deadline)->format('Y-m-d'),
                                    'program_area' => $program->area,
                                ]) }}">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
                                </svg>
                                Criar ação
                            </a>
                            <form method="POST" action="{{ route('mayor.mandato.federal-programs.ask.payload') }}">
                                @csrf
                                @if ($program->exists)
                                    <input type="hidden" name="program_id" value="{{ $program->id }}">
                                @endif
                                @if (filled($program->canonical_cycle_id))
                                    <input type="hidden" name="canonical_cycle_id"
                                        value="{{ $program->canonical_cycle_id }}">
                                @endif
                                @if (filled($program->canonical_opportunity_id))
                                    <input type="hidden" name="canonical_opportunity_id"
                                        value="{{ $program->canonical_opportunity_id }}">
                                @endif
                                <button class="prog-ask-btn" type="submit">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                                    </svg>
                                    Perguntar ao assistente
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="programs-empty">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                    </svg>
                    <p>Nenhuma oportunidade identificada ainda.<br>Os dados serão sincronizados automaticamente.</p>
                </div>
            @endforelse

        </div>
        @if ($programs->isNotEmpty())
            <div class="programs-empty hidden" id="savedEmptyState">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 3H5a2 2 0 0 0-2 2v16l8-3.5L19 21V5a2 2 0 0 0-2-2z" />
                </svg>
                <p>Nenhuma oportunidade salva neste filtro.<br>Use o botão <strong>Salvar</strong> nos cards para montar sua
                    shortlist.</p>
            </div>
            <div class="programs-empty hidden" id="reopenEmptyState">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 6V3L8 7l4 4V8c2.76 0 5 2.24 5 5a5 5 0 0 1-8.66 3.46l-1.42 1.42A7 7 0 1 0 12 6z" />
                </svg>
                <p>Nenhuma reabertura ativa neste filtro.<br>Ative <strong>Notificar reabertura</strong> em oportunidades
                    encerradas para acompanhar novas janelas.</p>
            </div>
        @endif
    </div>

    {{-- Toast --}}
    <div class="toast" id="toast"></div>

    <div class="detail-modal" id="programDetailModal" onclick="closeProgramDetail(event)">
        <div class="detail-panel" role="dialog" aria-modal="true" aria-labelledby="programDetailTitle">
            <div class="detail-head">
                <div>
                    <h3 id="programDetailTitle">Detalhes da oportunidade</h3>
                    <p id="programDetailSubtitle">Carregando dados canônicos da oportunidade.</p>
                </div>
                <button class="detail-close" type="button" onclick="closeProgramDetail()">&times;</button>
            </div>
            <div class="detail-body" id="programDetailBody">
                <div class="detail-loading">Carregando detalhe expandido...</div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        let currentAreaFilter = 'todos';
        let currentScopeFilter = 'all';
        const detailRoute = '{{ route('mayor.mandato.federal-programs.detail') }}';
        const saveRoute = '{{ route('mayor.mandato.federal-programs.save') }}';
        const reopenRoute = '{{ route('mayor.mandato.federal-programs.reopen-notification') }}';
        const askRoute = '{{ route('mayor.mandato.federal-programs.ask.payload') }}';
        const actionCreateRoute = '{{ route('mayor.mandato.acao.create') }}';
        const csrfToken = @json(csrf_token());
        const highlightProgram = @json($highlightProgram ?? null);

        // ── Filtro por área ───────────────────────────────────────
        function filterArea(btn) {
            document.querySelectorAll('.area-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentAreaFilter = btn.dataset.area;
            applyProgramFilters();
        }

        function setScopeFilter(scope, btn = null) {
            currentScopeFilter = scope;
            document.querySelectorAll('.scope-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.scope === scope);
            });

            if (btn) {
                btn.classList.add('active');
            }

            applyProgramFilters();
        }

        function applyProgramFilters() {
            let visibleCount = 0;
            document.querySelectorAll('.program-card').forEach(card => {
                const matchesArea = currentAreaFilter === 'todos' || card.dataset.area === currentAreaFilter;
                const matchesScope =
                    currentScopeFilter === 'all' ||
                    (currentScopeFilter === 'saved' && card.dataset.saved === '1') ||
                    (currentScopeFilter === 'reopen' && card.dataset.reopen === '1');
                const visible = matchesArea && matchesScope;

                card.style.display = visible ? 'flex' : 'none';
                if (visible) visibleCount += 1;
            });

            const savedEmptyState = document.getElementById('savedEmptyState');
            const reopenEmptyState = document.getElementById('reopenEmptyState');
            if (savedEmptyState) {
                savedEmptyState.classList.toggle('hidden', visibleCount > 0 || currentScopeFilter !== 'saved');
            }
            if (reopenEmptyState) {
                reopenEmptyState.classList.toggle('hidden', visibleCount > 0 || currentScopeFilter !== 'reopen');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (!highlightProgram) {
                return;
            }

            const trigger = document.querySelector(
                `.prog-detail-btn[data-program-id="${highlightProgram.program_id ?? ''}"][data-canonical-cycle-id="${highlightProgram.canonical_cycle_id ?? ''}"][data-canonical-opportunity-id="${highlightProgram.canonical_opportunity_id ?? ''}"]`
            ) || document.querySelector(
                `.prog-detail-btn[data-program-id="${highlightProgram.program_id ?? ''}"]`
            ) || document.querySelector(
                `.prog-detail-btn[data-canonical-cycle-id="${highlightProgram.canonical_cycle_id ?? ''}"][data-canonical-opportunity-id="${highlightProgram.canonical_opportunity_id ?? ''}"]`
            );

            if (trigger) {
                openProgramDetail(trigger);
                trigger.closest('.program-card')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });

        // ── Perguntar sobre programa específico ───────────────────
        function askAboutProgram(programName) {
            const question =
                `Quero entender melhor a oportunidade "${programName}" do Radar de Recursos. O meu município se enquadra nos critérios de elegibilidade? Quais são os passos para avançar e quais documentos são necessários?`;
            sessionStorage.setItem('chatPrefill', question);
            window.location.href = '{{ route('mayor.chat.index') }}';
        }

        // ── Perguntar sobre programas em geral ────────────────────
        function askAssistantGeneral() {
            const question =
                'Quais são as oportunidades mais importantes do Radar de Recursos para o nosso município agora? Priorize pelos prazos mais curtos, pela viabilidade e pelos maiores valores.';
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

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderDetailList(items) {
            if (!Array.isArray(items) || items.length === 0) {
                return '<p>Nenhuma informação registrada.</p>';
            }

            const rows = items.map(item => {
                if (typeof item === 'string') {
                    return `<li>${escapeHtml(item)}</li>`;
                }

                return `<li>${escapeHtml(JSON.stringify(item))}</li>`;
            }).join('');

            return `<ul class="detail-list">${rows}</ul>`;
        }

        function renderDetailTags(items) {
            if (!Array.isArray(items) || items.length === 0) {
                return '<p>Nenhuma tag registrada.</p>';
            }

            return `<div class="detail-tags">${items.map(tag => `<span class="detail-tag">${escapeHtml(tag)}</span>`).join('')}</div>`;
        }

        function formatMoney(value) {
            if (value === null || value === undefined || value === '') return 'Nao informado';
            const amount = Number(value);
            if (Number.isNaN(amount)) return escapeHtml(value);
            return amount.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        function formatDate(value) {
            if (!value) return 'Nao informado';
            const date = new Date(`${value}T00:00:00`);
            if (Number.isNaN(date.getTime())) return escapeHtml(value);
            return date.toLocaleDateString('pt-BR');
        }

        function renderHiddenIdentifiers(detail) {
            return `
                ${detail.legacy_program_id ? `<input type="hidden" name="program_id" value="${escapeHtml(detail.legacy_program_id)}">` : ''}
                ${detail.canonical_cycle_id ? `<input type="hidden" name="canonical_cycle_id" value="${escapeHtml(detail.canonical_cycle_id)}">` : ''}
                ${detail.canonical_opportunity_id ? `<input type="hidden" name="canonical_opportunity_id" value="${escapeHtml(detail.canonical_opportunity_id)}">` : ''}
            `;
        }

        function buildCreateActionUrl(detail) {
            const params = new URLSearchParams({
                title: `Ação a partir do programa: ${detail.title || 'Oportunidade do Radar de Recursos'}`,
                description: `${detail.description || ''}${detail.source_url ? `\n\nOrigem: ${detail.source_url}` : ''}`
                    .trim(),
                funding_source: detail.funding_type ? String(detail.funding_type).charAt(0).toUpperCase() + String(
                    detail.funding_type).slice(1) : 'Federal',
                end_date: detail.deadline_at || '',
                program_area: detail.area || '',
            });

            return `${actionCreateRoute}?${params.toString()}`;
        }

        function renderDetailActions(detail) {
            const hiddenIdentifiers = renderHiddenIdentifiers(detail);
            const supportsCanonicalActions = detail.supports_canonical_actions !== false;
            const editalLink = detail.source_url ? `
                <a href="${escapeHtml(detail.source_url)}" target="_blank" class="prog-link">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z" />
                    </svg>
                    Edital
                </a>
            ` : '';

            const applicationLink = detail.application_url ? `
                <a href="${escapeHtml(detail.application_url)}" target="_blank" class="prog-link">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 3v2h3.59L7.76 14.83l1.41 1.41L19 6.41V10h2V3zM5 5h6V3H5a2 2 0 0 0-2 2v6h2zM19 19h-6v2h6a2 2 0 0 0 2-2v-6h-2zM5 13H3v6a2 2 0 0 0 2 2h6v-2H5z" />
                    </svg>
                    Inscrição
                </a>
            ` : '';

            return `
                <div class="detail-actions">
                    ${supportsCanonicalActions ? `
                                                <form method="POST" action="${saveRoute}">
                                                    <input type="hidden" name="_token" value="${csrfToken}">
                                                    ${hiddenIdentifiers}
                                                    <button class="prog-meta-btn ${detail.is_saved ? 'is-active' : ''}" type="submit">
                                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M17 3H5a2 2 0 0 0-2 2v16l8-3.5L19 21V5a2 2 0 0 0-2-2z" />
                                                        </svg>
                                                        ${detail.is_saved ? 'Salvo' : 'Salvar'}
                                                    </button>
                                                </form>
                                            ` : ''}
                    ${supportsCanonicalActions && detail.can_subscribe_reopen ? `
                                                            <form method="POST" action="${reopenRoute}">
                                                                <input type="hidden" name="_token" value="${csrfToken}">
                                                                ${hiddenIdentifiers}
                                                                <button class="prog-meta-btn ${detail.is_reopen_notifying ? 'is-notify is-active' : 'is-notify'}" type="submit">
                                                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                                                        <path d="M12 6V3L8 7l4 4V8c2.76 0 5 2.24 5 5a5 5 0 0 1-8.66 3.46l-1.42 1.42A7 7 0 1 0 12 6z" />
                                                                    </svg>
                                                                    ${detail.is_reopen_notifying ? 'Reabertura ativa' : 'Notificar reabertura'}
                                                                </button>
                                                            </form>
                                                        ` : ''}
                    <a class="prog-action-btn" href="${escapeHtml(buildCreateActionUrl(detail))}">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
                        </svg>
                        Criar ação
                    </a>
                    <form method="POST" action="${askRoute}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        ${hiddenIdentifiers}
                        <button class="prog-ask-btn" type="submit">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                            </svg>
                            Perguntar ao assistente
                        </button>
                    </form>
                    ${editalLink}
                    ${applicationLink}
                </div>
            `;
        }

        function openProgramDetail(trigger) {
            const modal = document.getElementById('programDetailModal');
            const body = document.getElementById('programDetailBody');
            const title = document.getElementById('programDetailTitle');
            const subtitle = document.getElementById('programDetailSubtitle');

            modal.classList.add('open');
            body.innerHTML = '<div class="detail-loading">Carregando detalhe expandido...</div>';
            title.textContent = 'Detalhes da oportunidade';
            subtitle.textContent = 'Carregando dados canônicos da oportunidade.';

            const payload = {
                program_id: trigger.dataset.programId || null,
                canonical_cycle_id: trigger.dataset.canonicalCycleId || null,
                canonical_opportunity_id: trigger.dataset.canonicalOpportunityId || null,
            };

            fetch(detailRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(async response => {
                    if (!response.ok) {
                        throw new Error('Nao foi possivel carregar o detalhe da oportunidade.');
                    }
                    return response.json();
                })
                .then(detail => {
                    title.textContent = detail.title || 'Detalhes da oportunidade';
                    subtitle.textContent =
                        `${detail.status_label || 'Status não informado'} • ${detail.source_name || 'Fonte não informada'} • leitura ${detail.read_mode || 'hibrida'}`;

                    body.innerHTML = `
                        ${renderDetailActions(detail)}
                        <div class="detail-grid">
                            <div class="detail-stat"><strong>Status</strong><span>${escapeHtml(detail.status_label || 'Nao informado')}</span></div>
                            <div class="detail-stat"><strong>Compatibilidade</strong><span>${detail.match_percentage !== null ? `${escapeHtml(detail.match_percentage)}%` : 'Nao informado'}</span></div>
                            <div class="detail-stat"><strong>Viabilidade</strong><span>${escapeHtml(detail.viability_level || 'Nao informado')}</span></div>
                            <div class="detail-stat"><strong>Valor total</strong><span>${formatMoney(detail.total_value)}</span></div>
                            <div class="detail-stat"><strong>Prazo</strong><span>${formatDate(detail.deadline_at)}</span></div>
                            <div class="detail-stat"><strong>Contrapartida</strong><span>${detail.counterpart_percentage !== null ? `${escapeHtml(detail.counterpart_percentage)}%` : 'Nao informado'}</span></div>
                        </div>
                        <div class="detail-section">
                            <h4>Resumo executivo</h4>
                            <p>${escapeHtml(detail.summary || detail.description || 'Sem resumo detalhado para esta oportunidade.')}</p>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-section">
                                <h4>Compatibilidade explicada</h4>
                                <p>${escapeHtml(detail.match_reason || 'Sem justificativa detalhada registrada.')}</p>
                                ${renderDetailList(detail.compatibility_factors)}
                            </div>
                            <div class="detail-section">
                                <h4>Viabilidade de captação</h4>
                                <p>${escapeHtml(detail.viability_reason || 'Sem justificativa detalhada registrada.')}</p>
                                ${renderDetailList(detail.viability_factors)}
                            </div>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-section">
                                <h4>Elegibilidade</h4>
                                ${renderDetailList(detail.eligibility_rules)}
                            </div>
                            <div class="detail-section">
                                <h4>Documentação exigida</h4>
                                ${renderDetailList(detail.documentation_requirements)}
                            </div>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-section">
                                <h4>Dados do ciclo</h4>
                                <ul class="detail-list">
                                    <li><strong>Publicação:</strong> ${formatDate(detail.published_at)}</li>
                                    <li><strong>Abertura:</strong> ${formatDate(detail.opens_at)}</li>
                                    <li><strong>Encerramento:</strong> ${formatDate(detail.closed_at)}</li>
                                    <li><strong>Visível até:</strong> ${formatDate(detail.closed_visibility_until)}</li>
                                    <li><strong>Referência:</strong> ${escapeHtml(detail.publication_reference || 'Nao informada')}</li>
                                </ul>
                            </div>
                            <div class="detail-section">
                                <h4>Origem e indexação</h4>
                                <ul class="detail-list">
                                    <li><strong>Fonte:</strong> ${escapeHtml(detail.source_name || 'Nao informada')}</li>
                                    <li><strong>Chave:</strong> ${escapeHtml(detail.source_key || 'Nao informada')}</li>
                                    <li><strong>Captura:</strong> ${escapeHtml(detail.capture_method || 'Nao informada')}</li>
                                    <li><strong>Frequência:</strong> ${escapeHtml(detail.refresh_frequency || 'Nao informada')}</li>
                                    <li><strong>Escopo:</strong> ${escapeHtml(detail.resource_scope || 'Nao informado')}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h4>Tags temáticas</h4>
                            ${renderDetailTags(detail.thematic_tags)}
                        </div>
                    `;
                })
                .catch(error => {
                    body.innerHTML =
                        `<div class="detail-loading">${escapeHtml(error.message || 'Falha ao carregar detalhes.')}</div>`;
                });
        }

        function closeProgramDetail(event = null) {
            const modal = document.getElementById('programDetailModal');
            if (event && event.target && event.target !== modal) {
                return;
            }
            modal.classList.remove('open');
        }

        // Preencher chat com prefill se veio de outro módulo
        document.addEventListener('DOMContentLoaded', () => {
            const prefill = sessionStorage.getItem('chatPrefill');
            if (prefill) sessionStorage.removeItem('chatPrefill');
            applyProgramFilters();

            @if (session('success'))
                showToast(@json(session('success')));
            @elseif (session('warning'))
                showToast(@json(session('warning')));
            @endif
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeProgramDetail();
            }
        });
    </script>
@endpush
