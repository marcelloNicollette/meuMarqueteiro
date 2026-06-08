@extends('layouts.mayor')

@section('title', 'Projetos')
@section('topbar-title', 'Projetos')

@push('styles')
    <style>
        .projects-page {
            padding: 1.75rem 2rem 2.25rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .projects-hero {
            background: linear-gradient(135deg, #0f1117 0%, #183153 100%);
            border-radius: 18px;
            padding: 1.5rem 1.7rem;
            color: #fff;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.2rem;
        }

        .projects-hero h1 {
            font-family: "Outfit", sans-serif;
            font-size: 1.55rem;
            margin-bottom: .35rem;
        }

        .projects-hero p {
            max-width: 780px;
            color: rgba(255, 255, 255, .72);
            font-size: .88rem;
            line-height: 1.65;
        }

        .projects-hero .btn {
            flex-shrink: 0;
            background: var(--gold);
            color: var(--ink);
        }

        .projects-grid-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .9rem;
        }

        .project-stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem 1.1rem;
        }

        .project-stat-label {
            font-size: .72rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .35rem;
        }

        .project-stat-value {
            font-family: "Outfit", sans-serif;
            font-size: 1.5rem;
            color: var(--ink);
        }

        .project-stat-help {
            margin-top: .25rem;
            font-size: .76rem;
            color: var(--ink-soft);
        }

        .projects-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .projects-toolbar h2 {
            font-family: "Outfit", sans-serif;
            font-size: 1.08rem;
            color: var(--ink);
        }

        .projects-toolbar p {
            font-size: .8rem;
            color: var(--ink-muted);
            margin-top: .2rem;
        }

        .projects-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
        }

        .projects-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .8rem;
            align-items: end;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .projects-filter-field {
            min-width: 220px;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .projects-filter-field label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ink-muted);
        }

        .projects-filter-field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            color: var(--ink);
            font-size: .84rem;
            padding: .72rem .8rem;
            outline: none;
        }

        .projects-filter-field select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
            background: var(--white);
        }

        .projects-filter-actions {
            display: flex;
            gap: .55rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .projects-filter-summary {
            font-size: .78rem;
            color: var(--ink-muted);
            margin-left: auto;
        }

        .projects-invite-panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .projects-invite-panel h3 {
            font-family: "Outfit", sans-serif;
            font-size: 1rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .projects-invite-panel p {
            font-size: .78rem;
            color: var(--ink-muted);
            line-height: 1.55;
            margin-bottom: .85rem;
        }

        .projects-invite-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: .85rem;
        }

        .projects-invite-card {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .9rem;
        }

        .projects-invite-card strong {
            display: block;
            font-size: .88rem;
            color: var(--ink);
            margin-bottom: .18rem;
        }

        .projects-invite-card span,
        .projects-invite-card small {
            display: block;
            font-size: .75rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .projects-invite-card form {
            margin-top: .75rem;
        }

        .project-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.05rem 1.05rem 1rem;
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .project-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .7rem;
        }

        .project-card-title {
            font-family: "Outfit", sans-serif;
            font-size: 1rem;
            color: var(--ink);
            line-height: 1.35;
        }

        .project-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .22rem .58rem;
            border-radius: 999px;
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .04em;
            background: var(--surface);
            color: var(--ink-soft);
            border: 1px solid var(--border);
            white-space: nowrap;
        }

        .project-chip.status-em_elaboração {
            background: rgba(184, 144, 42, .12);
            color: var(--gold);
            border-color: rgba(184, 144, 42, .18);
        }

        .project-chip.status-concluido {
            background: rgba(30, 126, 72, .1);
            color: #1e7e48;
            border-color: rgba(30, 126, 72, .16);
        }

        .project-chip.status-em_execução {
            background: rgba(26, 95, 168, .1);
            color: #1a5fa8;
            border-color: rgba(26, 95, 168, .14);
        }

        .project-chip.status-captacao_em_andamento {
            background: rgba(124, 58, 237, .1);
            color: #7c3aed;
            border-color: rgba(124, 58, 237, .14);
        }

        .project-chip.role-owner {
            background: rgba(15, 23, 42, .08);
            color: var(--ink);
            border-color: rgba(15, 23, 42, .12);
        }

        .project-chip.role-editor {
            background: rgba(26, 95, 168, .1);
            color: #1a5fa8;
            border-color: rgba(26, 95, 168, .14);
        }

        .project-chip.role-viewer {
            background: rgba(107, 114, 128, .12);
            color: #4b5563;
            border-color: rgba(107, 114, 128, .18);
        }

        .project-card-text {
            font-size: .8rem;
            color: var(--ink-soft);
            line-height: 1.65;
        }

        .project-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }

        .project-meta-item {
            padding: .7rem .75rem;
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: var(--surface);
        }

        .project-meta-item strong {
            display: block;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ink-muted);
            margin-bottom: .2rem;
        }

        .project-meta-item span {
            font-size: .8rem;
            color: var(--ink);
        }

        .project-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .7rem;
            margin-top: auto;
            padding-top: .1rem;
        }

        .project-card-footer small {
            font-size: .75rem;
            color: var(--ink-muted);
        }

        .projects-empty {
            background: var(--white);
            border: 1px dashed var(--border);
            border-radius: 18px;
            padding: 2.4rem 1.5rem;
            text-align: center;
        }

        .projects-empty h3 {
            font-family: "Outfit", sans-serif;
            font-size: 1.15rem;
            color: var(--ink);
            margin-bottom: .4rem;
        }

        .projects-empty p {
            font-size: .84rem;
            color: var(--ink-muted);
            max-width: 620px;
            margin: 0 auto 1rem;
            line-height: 1.65;
        }

        @media (max-width: 980px) {
            .projects-grid-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .projects-hero {
                flex-direction: column;
            }
        }

        @media (max-width: 640px) {
            .projects-page {
                padding: 1rem;
            }

            .projects-grid-stats,
            .projects-list,
            .project-meta-grid {
                grid-template-columns: 1fr;
            }

            .projects-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="projects-page">
        <section class="projects-hero">
            <div>
                <h1>Projetos estruturados para execução e captação</h1>
                <p>
                    Transforme uma idéia em um projeto municipal completo, com base pronta para perguntas dinâmicas,
                    documento estruturado em 15 seções, verificação de sobreposição e integracao futura com o radar de
                    financiamento.
                </p>
            </div>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap">
                <a href="{{ route('mayor.project-bank.index') }}" class="btn">
                    Abrir Banco de Projetos
                </a>
                <a href="{{ route('mayor.projects.create') }}" class="btn">
                    Novo projeto
                </a>
            </div>
        </section>

        <section class="projects-grid-stats">
            <article class="project-stat-card">
                <div class="project-stat-label">Projetos</div>
                <div class="project-stat-value">{{ $statusCounts['total'] ?? 0 }}</div>
                <div class="project-stat-help">Base municipal salva na plataforma.</div>
            </article>
            <article class="project-stat-card">
                <div class="project-stat-label">Em elaboração</div>
                <div class="project-stat-value">{{ $statusCounts['em_elaboração'] ?? 0 }}</div>
                <div class="project-stat-help">Projetos ainda em montagem documental.</div>
            </article>
            <article class="project-stat-card">
                <div class="project-stat-label">Concluidos</div>
                <div class="project-stat-value">{{ $statusCounts['concluido'] ?? 0 }}</div>
                <div class="project-stat-help">Projetos prontos para uso ou apresentação.</div>
            </article>
            <article class="project-stat-card">
                <div class="project-stat-label">Captação ativa</div>
                <div class="project-stat-value">{{ $statusCounts['captacao_em_andamento'] ?? 0 }}</div>
                <div class="project-stat-help">Projetos com busca de financiamento em curso.</div>
            </article>
        </section>

        <section class="projects-toolbar">
            <div>
                <h2>Painel do módulo</h2>
                <p>Fase 6 evoluida: colaboracao por convite, filtros por papel, metadados estruturados e exportacao real.
                </p>
            </div>
            <a href="{{ route('mayor.projects.create') }}" class="btn btn-dark">Criar estrutura inicial</a>
        </section>

        <form method="GET" action="{{ route('mayor.projects.index') }}" class="projects-filters">
            <div class="projects-filter-field">
                <label for="role">Papel no projeto</label>
                <select id="role" name="role">
                    @foreach ($listRoleFilters as $value => $label)
                        <option value="{{ $value }}" @selected(($activeFilters['role'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="projects-filter-field">
                <label for="collaboration">Colaboracao</label>
                <select id="collaboration" name="collaboration">
                    @foreach ($listCollaborationFilters as $value => $label)
                        <option value="{{ $value }}" @selected(($activeFilters['collaboration'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="projects-filter-actions">
                <button type="submit" class="btn btn-dark">Aplicar filtros</button>
                <a href="{{ route('mayor.projects.index') }}" class="btn btn-secondary">Limpar</a>
            </div>

            <div class="projects-filter-summary">
                Exibindo {{ $filteredCount ?? $projects->count() }} de {{ $availableCount ?? $projects->count() }}
                projetos acessiveis.
            </div>
        </form>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (!empty($pendingInvites) && $pendingInvites->isNotEmpty())
            <section class="projects-invite-panel">
                <h3>Convites pendentes</h3>
                <p>Voce possui convites de colaboracao aguardando aceite neste município.</p>

                <div class="projects-invite-list">
                    @foreach ($pendingInvites as $invite)
                        <article class="projects-invite-card">
                            <strong>{{ $invite->project?->title ?? 'Projeto' }}</strong>
                            <span>Convidado por
                                {{ $invite->invitedBy?->name ?? ($invite->project?->owner?->name ?? 'Responsável do projeto') }}</span>
                            <small>
                                Permissao: {{ $invite->permission === 'viewer' ? 'Visualizacao' : 'Edição' }} ·
                                convite em {{ $invite->invited_at?->diffForHumans() ?? 'agora' }}
                            </small>

                            <form method="POST"
                                action="{{ route('mayor.projects.collaborators.accept', $invite->project) }}">
                                @csrf
                                <button type="submit" class="btn btn-dark">Aceitar convite</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($projects->isEmpty())
            <section class="projects-empty">
                <h3>Nenhum projeto cadastrado ainda</h3>
                <p>
                    A estrutura do módulo ja esta pronta para comecar. Crie o primeiro projeto da prefeitura e a plataforma
                    ja reservara as 15 seções obrigatórias para as próximas fases de perguntas dinâmicas e geração
                    assistida.
                </p>
                <a href="{{ route('mayor.projects.create') }}" class="btn btn-dark">Criar primeiro projeto</a>
            </section>
        @else
            <section class="projects-list">
                @foreach ($projects as $project)
                    <article class="project-card">
                        <div class="project-card-top">
                            <div>
                                <div class="project-card-title">{{ $project->title }}</div>
                                @php
                                    $role = $project->current_user_project_role ?? 'owner';
                                    $roleLabel = match ($role) {
                                        'owner' => 'Proprietario',
                                        'editor' => 'Colaborador editor',
                                        'viewer' => 'Colaborador viewer',
                                        'admin' => 'Administrador',
                                        default => 'Acesso ao projeto',
                                    };
                                @endphp
                                <div style="margin-top:.45rem; display:flex; gap:.4rem; flex-wrap:wrap;">
                                    <span class="project-chip role-{{ $role }}">{{ $roleLabel }}</span>
                                    @if (!($project->current_user_can_edit ?? true))
                                        <span class="project-chip">Somente leitura</span>
                                    @endif
                                </div>
                            </div>
                            <span class="project-chip status-{{ $project->status }}">{{ $project->status_label }}</span>
                        </div>

                        <div class="project-card-text">
                            {{ \Illuminate\Support\Str::limit($project->initial_idea, 200) }}
                        </div>

                        <div class="project-meta-grid">
                            <div class="project-meta-item">
                                <strong>Tipo</strong>
                                <span>{{ $project->type_label }}</span>
                            </div>
                            <div class="project-meta-item">
                                <strong>Secretaria</strong>
                                <span>{{ $project->responsible_secretariat ?: 'A definir' }}</span>
                            </div>
                            <div class="project-meta-item">
                                <strong>Seções</strong>
                                <span>{{ $project->sections_count ?? 0 }} blocos estruturais</span>
                            </div>
                            <div class="project-meta-item">
                                <strong>Colaboradores</strong>
                                <span>{{ $project->collaborators_count ?? 0 }} vinculados</span>
                            </div>
                            <div class="project-meta-item">
                                <strong>Convites pendentes</strong>
                                <span>{{ $project->pending_collaborators_count ?? 0 }} aguardando aceite</span>
                            </div>
                        </div>

                        <div class="project-card-footer">
                            <small>
                                Criado por {{ $project->owner?->name ?? 'Usuario' }} · atualizado
                                {{ $project->updated_at?->diffForHumans() ?? 'agora' }}
                            </small>
                            <a href="{{ route('mayor.projects.show', $project) }}" class="btn btn-dark">Abrir</a>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
@endsection
