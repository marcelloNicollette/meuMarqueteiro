@extends('layouts.mayor')

@section('title', 'Projetos')

@section('topbar-title', 'Projetos')
@push('styles')
    <style>
        .project-bank-page {
            padding: 1.5rem 0;
            display: grid;
            gap: 1.25rem;
        }

        .project-bank-hero,
        .project-bank-filters,
        .project-bank-notifications,
        .project-bank-section,
        .project-bank-card {
            background: #fff;
            border: 1px solid #e5e1da;
            border-radius: 18px;
            padding: 1.25rem;
        }

        .project-bank-hero h1 {
            font-family: "Outfit", sans-serif;
            font-size: 1.8rem;
            margin-bottom: .45rem;
        }

        .project-bank-hero p {
            color: #5b6170;
            max-width: 860px;
            line-height: 1.6;
        }

        .project-bank-meta {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
            color: #5b6170;
            font-size: .9rem;
        }

        .project-bank-filters form {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .85rem;
        }

        .project-bank-filters label {
            display: block;
            font-size: .82rem;
            margin-bottom: .35rem;
            color: #5b6170;
        }

        .project-bank-filters input,
        .project-bank-filters select {
            width: 100%;
            border: 1px solid #d8d3ca;
            border-radius: 10px;
            padding: .7rem .8rem;
        }

        .project-bank-list {
            display: grid;
            gap: 1rem;
        }

        .project-bank-section-head,
        .project-bank-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .project-bank-section-head h2,
        .project-bank-filters h2 {
            margin: 0;
            font-size: 1.08rem;
        }

        .project-bank-section-head p,
        .project-bank-filters p {
            margin: .35rem 0 0;
            color: #5b6170;
            line-height: 1.5;
        }

        .project-bank-toolbar {
            margin-top: 1rem;
        }

        .project-bank-counter {
            color: #5b6170;
            font-size: .92rem;
        }

        .project-bank-actions-inline {
            display: flex;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .project-bank-notifications-list {
            display: grid;
            gap: .85rem;
        }

        .project-bank-notification {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            padding: 1rem;
            border: 1px solid #eee7dc;
            border-radius: 14px;
            background: #faf8f4;
        }

        .project-bank-notification.unread {
            border-color: #d7b668;
            background: #fff8e7;
        }

        .project-bank-notification h3 {
            font-size: .98rem;
            margin-bottom: .35rem;
        }

        .project-bank-notification p {
            margin: 0;
            color: #4a4f5d;
            line-height: 1.5;
        }

        .project-bank-notification small {
            color: #6b7280;
        }

        .project-bank-card-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
        }

        .project-bank-card h2 {
            font-size: 1.1rem;
            margin-bottom: .5rem;
        }

        .project-bank-chips,
        .project-bank-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
        }

        .project-bank-chip {
            display: inline-flex;
            align-items: center;
            padding: .3rem .7rem;
            border-radius: 999px;
            background: #f4efe2;
            color: #7d6218;
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .project-bank-card p {
            color: #4a4f5d;
            line-height: 1.6;
        }

        .project-bank-status {
            font-size: .8rem;
            color: #5b6170;
        }

        .project-bank-empty {
            background: #fff;
            border: 1px dashed #d8d3ca;
            border-radius: 18px;
            padding: 2rem;
            color: #5b6170;
        }

        .project-bank-empty.is-inline {
            display: none;
            background: #faf8f4;
            border-style: solid;
            padding: 1.25rem;
        }

        .project-bank-empty.is-inline.is-visible {
            display: block;
        }

        .project-bank-card.is-hidden {
            display: none;
        }

        @media (max-width: 1100px) {
            .project-bank-filters form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .project-bank-filters form {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="project-bank-page">
        <!--<section class="project-bank-hero">
                                                                    <h1>Projetos</h1>

                                                                    <div class="project-bank-meta">
                                                                        <span>{{ $theses->count() }} tese(s) nesta visão</span>
                                                                        <span>{{ $savedCount }} salva(s) por voce</span>
                                                                        <span>{{ $urgentWindowCount }} com prazo em ate 60 dias</span>
                                                                        <span>{{ $unreadNotificationsCount }} notificacao(oes) nao lida(s)</span>
                                                                    </div>
                                                                </section>-->

        @if ($notifications->isNotEmpty())
            <section class="project-bank-notifications">
                <h2 style="margin-bottom:.85rem">Alertas e compartilhamentos</h2>
                <div class="project-bank-notifications-list">
                    @foreach ($notifications as $notification)
                        <article class="project-bank-notification {{ $notification->read_at ? '' : 'unread' }}">
                            <div>
                                <h3>{{ $notification->title }}</h3>
                                <p>{{ $notification->message }}</p>
                                <small>
                                    {{ $notification->thesis?->title ?? 'Tese indisponivel' }}
                                    @if ($notification->event_type === 'share_received' && $notification->share?->sharedBy)
                                        · compartilhada por {{ $notification->share->sharedBy->name }}
                                    @endif
                                </small>
                            </div>
                            <div>
                                <a href="{{ $notification->action_url ?: route('mayor.project-bank.show', $notification->thesis) }}"
                                    class="btn">
                                    Abrir
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="project-bank-filters">
            <div class="project-bank-section-head" style="margin-bottom:1rem">
                <div>
                    <h2>Filtros inteligentes</h2>
                    <p>Os filtros atuam sem reload e refinam a biblioteca em tempo real.</p>
                </div>
                <div class="project-bank-counter">
                    <strong id="project-bank-visible-count">{{ $theses->count() }}</strong> projeto(s) visiveis agora
                </div>
            </div>
            <form method="GET" action="{{ route('mayor.project-bank.index') }}">
                <div>
                    <label for="scope">Escopo</label>
                    <select id="scope" name="scope">
                        <option value="all" @selected(($activeFilters['scope'] ?? 'all') === 'all')>Todas</option>
                        <option value="saved" @selected(($activeFilters['scope'] ?? '') === 'saved')>Salvas</option>
                    </select>
                </div>
                <div>
                    <label for="category">Categoria</label>
                    <select id="category" name="category">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(($activeFilters['category'] ?? '') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="search">Busca</label>
                    <input id="search" name="search" type="text" value="{{ $activeFilters['search'] ?? '' }}"
                        placeholder="Título, justificativa ou categoria">
                </div>
                <div style="padding-top: 1.3rem;">
                    <button type="button" class="btn" id="project-bank-clear-filters">Limpar filtros</button>
                </div>
            </form>

        </section>

        <!--<section class="project-bank-section">
                    <div class="project-bank-section-head" style="margin-bottom:1rem">
                        <div>
                            <h2>Salvas por voce</h2>
                            <p>Espaco dedicado para retomar depois as teses que entraram no seu radar.</p>
                        </div>
                        <div class="project-bank-counter">
                            <strong id="project-bank-saved-visible-count">{{ $savedTheses->count() }}</strong> de
                            {{ $savedTheses->count() }}
                            salva(s) visiveis
                        </div>
                    </div>

                    @if ($savedTheses->isEmpty())
                        <section class="project-bank-empty">
                            Voce ainda nao salvou nenhuma tese. Quando clicar em `Salvar para depois`, ela aparece aqui em destaque.
                        </section>
@else
    <section class="project-bank-list" data-bank-list="saved">
                            @foreach ($savedTheses as $thesis)
    @include('mayor.project-bank.partials.thesis-card', [
        'thesis' => $thesis,
        'canGenerateProject' => $canGenerateProject,
    ])
    @endforeach
                        </section>
                        <section class="project-bank-empty is-inline" id="project-bank-saved-empty">
                            Nenhuma tese salva combina com os filtros atuais.
                        </section>
                    @endif
                </section>-->

        @if ($theses->isEmpty())
            <section class="project-bank-empty">
                Nenhuma projeto apareceu no banco deste município ainda. Quando a biblioteca estiver carregada, as projetos
                também ficam
                acessiveis aqui e no módulo `Projetos`.
            </section>
        @else
            <section class="project-bank-section">
                <div class="project-bank-section-head" style="margin-bottom:1rem">
                    <div>
                        <h2>Idéias de projetos para o seu município</h2>

                    </div>
                    <div class="project-bank-counter">
                        <strong id="project-bank-library-visible-count">{{ $theses->count() }}</strong> de
                        {{ $theses->count() }}
                        projeto(s) visiveis
                    </div>
                </div>
                <section class="project-bank-list" data-bank-list="library">
                    @foreach ($theses as $thesis)
                        @include('mayor.project-bank.partials.thesis-card', [
                            'thesis' => $thesis,
                            'canGenerateProject' => $canGenerateProject,
                        ])
                    @endforeach
                </section>
                <section class="project-bank-empty is-inline" id="project-bank-library-empty">
                    Nenhuma tese da biblioteca combina com os filtros atuais.
                </section>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.project-bank-filters form');
            if (!form) {
                return;
            }

            const controls = {
                scope: form.querySelector('[name="scope"]'),
                category: form.querySelector('[name="category"]'),
                urgency: form.querySelector('[name="urgency"]'),
                size: form.querySelector('[name="size"]'),
                complexity: form.querySelector('[name="complexity"]'),
                search: form.querySelector('[name="search"]'),
            };

            const clearButton = document.getElementById('project-bank-clear-filters');
            const allCards = Array.from(document.querySelectorAll('[data-card="thesis"]'));
            const savedCards = Array.from(document.querySelectorAll(
                '[data-bank-list="saved"] [data-card="thesis"]'));
            const libraryCards = Array.from(document.querySelectorAll(
                '[data-bank-list="library"] [data-card="thesis"]'));
            const visibleCounter = document.getElementById('project-bank-visible-count');
            const savedVisibleCounter = document.getElementById('project-bank-saved-visible-count');
            const libraryVisibleCounter = document.getElementById('project-bank-library-visible-count');
            const savedEmpty = document.getElementById('project-bank-saved-empty');
            const libraryEmpty = document.getElementById('project-bank-library-empty');

            const normalize = (value) => (value || '').toString().trim().toLowerCase();

            const currentFilters = () => ({
                scope: normalize(controls.scope?.value || 'all'),
                category: normalize(controls.category?.value),
                urgency: normalize(controls.urgency?.value),
                size: normalize(controls.size?.value),
                complexity: normalize(controls.complexity?.value),
                search: normalize(controls.search?.value),
            });

            const matchesBaseFilters = (card, filters) => {
                const data = card.dataset;
                const matchesCategory = !filters.category || data.category === filters.category;
                const matchesUrgency = !filters.urgency || data.urgency === filters.urgency;
                const matchesSize = !filters.size || data.size === filters.size;
                const matchesComplexity = !filters.complexity || data.complexity === filters.complexity;
                const matchesSearch = !filters.search || (data.search || '').includes(filters.search);

                return matchesCategory && matchesUrgency && matchesSize && matchesComplexity && matchesSearch;
            };

            const syncUrl = (filters) => {
                const params = new URLSearchParams();
                Object.entries(filters).forEach(([key, value]) => {
                    if (!value || value === 'all') {
                        return;
                    }

                    params.set(key, value);
                });

                const query = params.toString();
                const url = query ? `${window.location.pathname}?${query}` : window.location.pathname;
                window.history.replaceState({}, '', url);
            };

            const toggleEmptyState = (element, visibleCount) => {
                if (!element) {
                    return;
                }

                element.classList.toggle('is-visible', visibleCount === 0);
            };

            const applyFilters = () => {
                const filters = currentFilters();
                let visibleLibrary = 0;
                let visibleSaved = 0;

                libraryCards.forEach((card) => {
                    const matches = matchesBaseFilters(card, filters) &&
                        (filters.scope !== 'saved' || card.dataset.saved === '1');

                    card.classList.toggle('is-hidden', !matches);
                    if (matches) {
                        visibleLibrary++;
                    }
                });

                savedCards.forEach((card) => {
                    const matches = matchesBaseFilters(card, filters);
                    card.classList.toggle('is-hidden', !matches);
                    if (matches) {
                        visibleSaved++;
                    }
                });

                if (visibleCounter) {
                    visibleCounter.textContent = String(visibleLibrary);
                }

                if (savedVisibleCounter) {
                    savedVisibleCounter.textContent = String(visibleSaved);
                }

                if (libraryVisibleCounter) {
                    libraryVisibleCounter.textContent = String(visibleLibrary);
                }

                toggleEmptyState(savedEmpty, visibleSaved);
                toggleEmptyState(libraryEmpty, visibleLibrary);
                syncUrl(filters);
            };

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                applyFilters();
            });

            Object.values(controls).forEach((control) => {
                if (!control) {
                    return;
                }

                const eventName = control.tagName === 'INPUT' ? 'input' : 'change';
                control.addEventListener(eventName, applyFilters);
            });

            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    Object.entries(controls).forEach(([key, control]) => {
                        if (!control) {
                            return;
                        }

                        control.value = key === 'scope' ? 'all' : '';
                    });

                    applyFilters();
                });
            }

            if (allCards.length > 0) {
                applyFilters();
            }
        });
    </script>
@endpush
