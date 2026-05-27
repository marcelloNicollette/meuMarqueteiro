@extends('layouts.mayor')

@section('title', 'Tese do Banco de Projetos')

@push('styles')
    <style>
        .project-thesis-page {
            padding: 1.5rem;
            display: grid;
            gap: 1rem;
        }

        .project-thesis-card,
        .project-thesis-side {
            background: #fff;
            border: 1px solid #e5e1da;
            border-radius: 18px;
            padding: 1.25rem;
        }

        .project-thesis-layout {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .project-thesis-head h1 {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            margin: .6rem 0;
        }

        .project-thesis-chips,
        .project-thesis-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .project-thesis-chip {
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

        .project-thesis-section {
            margin-top: 1rem;
        }

        .project-thesis-section h2 {
            font-size: 1rem;
            margin-bottom: .45rem;
        }

        .project-thesis-section p {
            color: #4a4f5d;
            line-height: 1.65;
            white-space: pre-line;
        }

        .project-thesis-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
            margin-top: 1rem;
        }

        .project-thesis-meta article {
            background: #faf8f4;
            border: 1px solid #eee7dc;
            border-radius: 14px;
            padding: .9rem;
        }

        .project-thesis-meta strong {
            display: block;
            font-size: .8rem;
            color: #7b808f;
            margin-bottom: .25rem;
            text-transform: uppercase;
        }

        .project-thesis-side h3 {
            margin-bottom: .75rem;
        }

        .project-thesis-share {
            display: grid;
            gap: .75rem;
        }

        .project-thesis-share select {
            min-height: 150px;
            border: 1px solid #d8d3ca;
            border-radius: 10px;
            padding: .6rem;
        }

        .project-thesis-status {
            color: #5b6170;
            font-size: .92rem;
            margin-top: .55rem;
        }

        .project-thesis-banner {
            border-radius: 16px;
            padding: 1rem 1.1rem;
            border: 1px solid #e7d7a6;
            background: #fff8e7;
            color: #6b5a1f;
        }

        .project-thesis-banner strong {
            display: block;
            margin-bottom: .35rem;
        }

        .project-thesis-banner.is-share {
            border-color: #d6dff5;
            background: #f5f8ff;
            color: #21406d;
        }

        @media (max-width: 980px) {
            .project-thesis-layout {
                grid-template-columns: 1fr;
            }

            .project-thesis-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="project-thesis-page">
        <div>
            <a href="{{ route('mayor.project-bank.index') }}" class="btn">Voltar ao Banco</a>
        </div>

        @if ($incomingNotification)
            <section class="project-thesis-banner {{ $incomingNotification->event_type === 'share_received' ? 'is-share' : '' }}">
                <strong>{{ $incomingNotification->title }}</strong>
                <span>{{ $incomingNotification->message }}</span>
            </section>
        @endif

        @if ($thesis->urgency === 'alta' && $daysUntilDeadline !== null && $daysUntilDeadline <= 60)
            <section class="project-thesis-banner">
                <strong>Alerta proativo de prazo</strong>
                <span>
                    @if ($daysUntilDeadline < 0)
                        O prazo de recurso desta tese ja venceu e precisa de decisao imediata.
                    @elseif ($daysUntilDeadline === 0)
                        O prazo de recurso desta tese vence hoje.
                    @elseif ($daysUntilDeadline === 1)
                        O prazo de recurso desta tese vence em 1 dia.
                    @else
                        O prazo de recurso desta tese vence em {{ $daysUntilDeadline }} dias.
                    @endif
                </span>
            </section>
        @endif

        <div class="project-thesis-layout">
            <section class="project-thesis-card">
                <div class="project-thesis-head">
                    <div class="project-thesis-chips">
                        <span class="project-thesis-chip">{{ ucfirst($thesis->urgency) }}</span>
                        <span class="project-thesis-chip">{{ $thesis->category }}</span>
                        <span class="project-thesis-chip">{{ ucfirst($thesis->estimated_size) }}</span>
                        <span class="project-thesis-chip">{{ ucfirst($thesis->execution_complexity) }}</span>
                    </div>
                    <h1>{{ $thesis->title }}</h1>
                    <p class="project-thesis-status">Status atual da tese: {{ str_replace('_', ' ', $trackingStatus) }}</p>
                </div>

                <div class="project-thesis-actions" style="margin-top:1rem">
                    <form method="POST" action="{{ route('mayor.project-bank.save', $thesis) }}">
                        @csrf
                        <button type="submit"
                            class="btn">{{ $userState?->is_saved ? 'Remover dos salvos' : 'Salvar para depois' }}</button>
                    </form>
                    @if ($canGenerateProject)
                        <form method="POST" action="{{ route('mayor.project-bank.generate-project', $thesis) }}">
                            @csrf
                            <button type="submit" class="btn btn-dark">Gerar Projeto</button>
                        </form>
                    @endif
                </div>

                <div class="project-thesis-section">
                    <h2>Justificativa</h2>
                    <p>{{ $thesis->justification }}</p>
                </div>

                <div class="project-thesis-section">
                    <h2>Potencial de impacto</h2>
                    <p>{{ $thesis->potential_impact }}</p>
                </div>

                <div class="project-thesis-section">
                    <h2>Fonte de recurso sugerida</h2>
                    <p>{{ $thesis->funding_source }}</p>
                </div>

                @if ($thesis->reference_municipalities)
                    <div class="project-thesis-section">
                        <h2>Municipios de referencia</h2>
                        <p>{{ $thesis->reference_municipalities }}</p>
                    </div>
                @endif

                @if ($thesis->government_alignment)
                    <div class="project-thesis-section">
                        <h2>Alinhamento com o programa de governo</h2>
                        <p>{{ $thesis->government_alignment }}</p>
                    </div>
                @endif

                <div class="project-thesis-meta">
                    <article>
                        <strong>Porte estimado</strong>
                        <span>{{ ucfirst($thesis->estimated_size) }}</span>
                    </article>
                    <article>
                        <strong>Complexidade</strong>
                        <span>{{ ucfirst($thesis->execution_complexity) }}</span>
                    </article>
                    <article>
                        <strong>Urgência</strong>
                        <span>{{ ucfirst($thesis->urgency) }}</span>
                    </article>
                    <article>
                        <strong>Prazo do recurso</strong>
                        <span>{{ $thesis->resource_deadline?->format('d/m/Y') ?? 'Nao informado' }}</span>
                    </article>
                </div>
            </section>

            <aside class="project-thesis-side">
                <h3>Compartilhar tese</h3>
                @if ($receivedShare)
                    <p class="project-thesis-status" style="margin-top:0">
                        {{ $receivedShare->sharedBy?->name ?? 'Sua equipe' }} compartilhou esta tese com voce.
                    </p>
                @endif
                <form method="POST" action="{{ route('mayor.project-bank.share', $thesis) }}"
                    class="project-thesis-share">
                    @csrf
                    <select name="recipients[]" multiple>
                        @foreach ($eligibleUsers as $eligibleUser)
                            <option value="{{ $eligibleUser->id }}">
                                {{ $eligibleUser->name }}{{ $eligibleUser->email ? ' · ' . $eligibleUser->email : '' }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn">Compartilhar com a equipe</button>
                </form>

                @if ($thesis->sourceProjects->isNotEmpty())
                    <div class="project-thesis-section">
                        <h2>Projetos gerados a partir desta tese</h2>
                        @foreach ($thesis->sourceProjects as $project)
                            <p>
                                <a href="{{ route('mayor.projects.show', $project) }}">{{ $project->title }}</a><br>
                                <small>{{ $project->owner?->name ?? 'Sem responsável' }} ·
                                    {{ $project->status_label }}</small>
                            </p>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection
