@extends('layouts.mayor')

@section('title', 'Novo Projeto')
@section('topbar-title', 'Projetos')

@push('styles')
    <style>
        .project-create-layout {
            padding: 1.6rem 2rem 2.2rem;
            display: grid;
            grid-template-columns: minmax(0, 720px) 320px;
            gap: 1rem;
            align-items: start;
        }

        .project-create-card,
        .project-create-side {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
        }

        .project-create-card {
            padding: 1.35rem 1.4rem 1.5rem;
        }

        .project-create-side {
            padding: 1.1rem 1rem;
            position: sticky;
            top: calc(var(--nav-h) + 1rem);
        }

        .project-create-header {
            margin-bottom: 1.2rem;
        }

        .project-create-header h1 {
            font-family: "Outfit", sans-serif;
            font-size: 1.45rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .project-create-header p {
            font-size: .84rem;
            color: var(--ink-muted);
            line-height: 1.65;
            max-width: 720px;
        }

        .project-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
        }

        .field {
            margin-bottom: 1rem;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin-bottom: .4rem;
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            color: var(--ink);
            font-size: .88rem;
            padding: .75rem .85rem;
            outline: none;
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
            background: var(--white);
        }

        .field textarea {
            min-height: 170px;
            resize: vertical;
        }

        .field-help {
            margin-top: .35rem;
            font-size: .74rem;
            line-height: 1.55;
            color: var(--ink-muted);
        }

        .project-form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .8rem;
            margin-top: 1rem;
        }

        .project-form-note {
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.6;
            max-width: 420px;
        }

        .project-side-title {
            font-size: .74rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .3rem;
        }

        .project-side-head {
            font-family: "Outfit", sans-serif;
            font-size: 1.02rem;
            color: var(--ink);
            margin-bottom: .85rem;
        }

        .project-side-help {
            font-size: .78rem;
            color: var(--ink-soft);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .project-section-list {
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }

        .project-section-item {
            padding: .65rem .7rem;
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: var(--surface);
        }

        .project-section-item strong {
            display: block;
            font-size: .78rem;
            color: var(--ink);
            margin-bottom: .2rem;
        }

        .project-section-item span {
            display: block;
            font-size: .72rem;
            color: var(--ink-muted);
            line-height: 1.45;
        }

        .validation-errors {
            margin-bottom: 1rem;
            padding: .9rem 1rem;
            background: rgba(181, 43, 43, .08);
            border: 1px solid rgba(181, 43, 43, .15);
            border-radius: 12px;
            color: #8b1e1e;
            font-size: .82rem;
        }

        @media (max-width: 1080px) {
            .project-create-layout {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .project-create-side {
                position: static;
            }
        }

        @media (max-width: 640px) {
            .project-form-grid {
                grid-template-columns: 1fr;
            }

            .project-form-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush

@section('content')
    <div class="project-create-layout">
        <section class="project-create-card">
            <div class="project-create-header">
                <h1>{{ $sourceThesis ? 'Gerar projeto a partir de uma tese do banco' : 'Novo projeto municipal' }}</h1>
                <p>
                    @if ($sourceThesis)
                        A tese ja entrou como contexto inicial. Revise os campos abaixo e siga para o fluxo normal do módulo
                        Projetos sem precisar reescrever a idéia do zero.
                    @else
                        Esta primeira etapa cria a base do projeto na plataforma, reserva as 15 seções obrigatórias e
                        prepara o
                        fluxo para as próximas fases de perguntas dinâmicas, verificação de sobreposição e geração
                        assistida.
                    @endif
                </p>
            </div>

            @if ($sourceThesis)
                <div class="project-create-note" style="margin-bottom:1rem">
                    <strong>Tese de origem:</strong> {{ $sourceThesis->title }}<br>
                    <span>{{ $sourceThesis->category }} · urgência {{ ucfirst($sourceThesis->urgency) }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="validation-errors">
                    <strong>Revise os campos abaixo:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('mayor.projects.store') }}">
                @csrf
                <input type="hidden" name="source_thesis_id" value="{{ $sourceThesis?->id }}">

                <div class="project-form-grid">
                    <div class="field full">
                        <label for="title">Nome do projeto</label>
                        <input id="title" name="title" type="text" maxlength="160"
                            value="{{ old('title', $sourceThesis?->title) }}"
                            placeholder="Ex.: Revitalizacao da Praca do Jardim America">
                        <div class="field-help">Use um nome claro e administrativo. Ele sera a base da identificação do
                            documento.</div>
                    </div>

                    <div class="field">
                        <label for="project_type">Tipo de projeto</label>
                        <select id="project_type" name="project_type">
                            <option value="">A definir</option>
                            @foreach ($projectTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('project_type', \Illuminate\Support\Str::lower($sourceThesis?->category ?? '')) === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="status">Status inicial</label>
                        <select id="status" name="status">
                            @foreach ($projectStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'em_elaboração') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field full">
                        <label for="responsible_secretariat">Secretaria responsável</label>
                        <input id="responsible_secretariat" name="responsible_secretariat" type="text" maxlength="120"
                            value="{{ old('responsible_secretariat') }}" placeholder="Ex.: Secretaria de Infraestrutura">
                    </div>

                    <div class="field full">
                        <label for="initial_idea">Idéia inicial</label>
                        <textarea id="initial_idea" name="initial_idea"
                            placeholder="Descreva a idéia em linguagem livre. Ex.: Precisamos construir uma praca com area de lazer, iluminacao e acessibilidade no bairro...">{{ old(
                                'initial_idea',
                                trim(
                                    implode(
                                        "\n\n",
                                        array_filter([
                                            $sourceThesis?->justification,
                                            $sourceThesis?->potential_impact,
                                            $sourceThesis?->funding_source,
                                            $sourceThesis?->government_alignment
                                                ? 'Alinhamento com programa de governo: ' . $sourceThesis->government_alignment
                                                : null,
                                        ]),
                                    ),
                                ),
                            ) }}</textarea>
                        <div class="field-help">
                            Nas próximas fases, esta idéia alimentará o assistente para identificar o tipo do projeto, gerar
                            perguntas objetivas e montar o documento final com 15 seções.
                        </div>
                    </div>
                </div>

                <div class="project-form-actions">
                    <div class="project-form-note">
                        Ao criar o projeto agora, a plataforma ja salva a base estrutural, registra o criador e deixa pronta
                        a organização para histórico de edições, colaboradores e geração futura.
                    </div>
                    <button type="submit" class="btn btn-dark">Criar estrutura do projeto</button>
                </div>
            </form>
        </section>

        <aside class="project-create-side">
            <div class="project-side-title">Estrutura obrigatória</div>
            <div class="project-side-head">15 seções reservadas desde o início</div>
            <div class="project-side-help">
                Cada projeto nasce com a arquitetura documental exigida no escopo do módulo. O preenchimento inteligente
                entra nas próximas fases.
            </div>

            <div class="project-section-list">
                @foreach ($sectionDefinitions as $section)
                    <div class="project-section-item">
                        <strong>{{ $section['title'] }}</strong>
                        <span>{{ $section['description'] }}</span>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
@endsection
