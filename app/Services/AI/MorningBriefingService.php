<?php

namespace App\Services\AI;

use App\Enums\ResourceOpportunityStatus;
use App\Models\Demand;
use App\Models\FederalProgramAlert;
use App\Models\MandateAction;
use App\Models\MandatePromise;
use App\Models\MorningBriefing;
use App\Models\Municipality;
use App\Models\Project;
use App\Models\ProjectThesis;
use App\Models\SocialMention;
use App\Models\User;
use App\Services\Mandato\MandateProjectionService;
use App\Services\Projects\ProjectBankLibraryService;
use App\Services\Projects\ProjectFundingMatchService;
use App\Services\Radar\HybridRadarReadService;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MorningBriefingService
{
    public function __construct(
        private readonly AIProviderService $ai,
        private readonly MandateProjectionService $mandateProjection,
        private readonly HybridRadarReadService $radarRead,
        private readonly ProjectFundingMatchService $projectFundingMatch,
        private readonly WebPushService $webPush,
    ) {}

    public function generate(Municipality $municipality): MorningBriefing
    {
        $user = $municipality->mayor;

        if (!$user instanceof User) {
            throw new \RuntimeException("Municipio {$municipality->name} sem prefeito vinculado para gerar o Pra Hoje.");
        }

        return $this->generateForUser($user);
    }

    public function generateForUser(User $user, bool $force = false): MorningBriefing
    {
        $municipality = $user->municipality;
        if (!$municipality instanceof Municipality) {
            throw new \RuntimeException('Usuario sem município associado.');
        }

        $existing = MorningBriefing::query()
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->latest('id')
            ->first();

        if ($existing instanceof MorningBriefing && !$force) {
            return $existing;
        }

        $cards = $this->buildCardsForUser($user);
        $opening = $this->generateOpening($user, $cards);
        $sections = $this->buildSectionsPayload($cards);
        $content = $this->buildReadableContent($opening, $cards);

        $aiProvider = 'rules';
        $aiModel = 'deterministic';
        $tokensUsed = 0;

        MorningBriefing::query()
            ->where('user_id', $user->id)
            ->whereNull('superseded_at')
            ->whereDate('date', '<', today())
            ->update(['superseded_at' => now()]);

        $payload = [
            'municipality_id' => $municipality->id,
            'user_id' => $user->id,
            'date' => today(),
            'scope_profile' => $user->role->value,
            'content' => $content,
            'opening_text' => $opening,
            'sections' => $sections,
            'cards' => $cards->values()->all(),
            'delivery_channel' => $this->resolveDeliveryChannel($user),
            'delivered_at' => now(),
            'read_at' => null,
            'superseded_at' => null,
            'ai_provider' => $aiProvider,
            'ai_model' => $aiModel,
            'tokens_used' => $tokensUsed,
            'rag_sources_count' => 0,
        ];

        if ($existing instanceof MorningBriefing) {
            $existing->fill($payload);
            $existing->save();
            $briefing = $existing->fresh();
        } else {
            $briefing = MorningBriefing::query()->create($payload);
        }

        $this->sendPush($user, $briefing, $cards);

        return $briefing;
    }

    public function shouldGenerateForUser(User $user, ?Carbon $reference = null): bool
    {
        if (!$user->municipality || !$user->municipality->subscription_active || $user->municipality->onboarding_status !== 'completed') {
            return false;
        }

        $reference ??= now('America/Sao_Paulo');
        $preferences = $this->resolvePraHojePreferences($user);
        if (($preferences['enabled'] ?? true) !== true) {
            return false;
        }

        $existingToday = MorningBriefing::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $reference->toDateString())
            ->exists();

        if ($existingToday) {
            return false;
        }

        [$hour, $minute] = array_pad(explode(':', (string) ($preferences['delivery_time'] ?? '07:30')), 2, '00');
        $scheduledAt = $reference->copy()
            ->setHour((int) $hour)
            ->setMinute((int) $minute)
            ->setSecond(0);

        return $reference->betweenIncluded($scheduledAt, $scheduledAt->copy()->addMinutes(14));
    }

    private function buildCardsForUser(User $user): Collection
    {
        $cards = collect()
            ->merge($this->buildResolveAiCards($user))
            ->merge($this->buildMandateCards($user))
            ->merge($this->buildProjectCards($user))
            ->merge($this->buildProjectBankCards($user))
            ->merge($this->buildCommunicationCards($user))
            ->merge($this->buildMentionCards($user))
            ->merge($this->buildRadarCards($user));

        $cards = $cards
            ->sortBy([
                ['priority_rank', 'asc'],
                ['score', 'desc'],
            ])
            ->values();

        return $this->applySuppression($user, $cards)
            ->take(6)
            ->values();
    }

    private function buildResolveAiCards(User $user): Collection
    {
        try {
            $query = Demand::query()
                ->where('municipality_id', $user->municipality_id)
                ->whereIn('status', ['registered', 'pending', 'in_progress', 'reopened', 'overdue', 'awaiting_confirmation']);

            if (($user->isSecretary() || $user->isAdvisor()) && $user->contact_area_id) {
                $query->where('contact_area_id', $user->contact_area_id);
            }

            $demands = $query
                ->orderByRaw("
                    CASE status
                        WHEN 'overdue' THEN 1
                        WHEN 'reopened' THEN 2
                        WHEN 'awaiting_confirmation' THEN 3
                        ELSE 4
                    END
                ")
                ->orderByRaw("
                    CASE priority
                        WHEN 'alta' THEN 1
                        WHEN 'media' THEN 2
                        ELSE 3
                    END
                ")
                ->orderByRaw('COALESCE(due_at, CAST(due_date AS timestamp), created_at) ASC')
                ->limit(4)
                ->get();

            return $demands->map(function (Demand $demand) use ($user) {
                $isOverdue = $demand->status === 'overdue' || ($demand->due_at && $demand->due_at->isPast());
                $isReopened = $demand->status === 'reopened';

                $score = match (true) {
                    $isOverdue && $demand->priority === 'alta' => 100,
                    $isOverdue => 94,
                    $isReopened => 88,
                    $demand->priority === 'alta' => 82,
                    default => 74,
                };

                $routeName = $user->isMayor() ? 'mayor.mandato.demands.show' : 'resolve-ai.demands.show';
                $situationParts = [];
                $situationParts[] = $demand->title ?: Str::limit($demand->raw_input ?? 'Demanda sem titulo', 60);
                if ($demand->area) {
                    $situationParts[] = $demand->area;
                }
                if ($demand->locality) {
                    $situationParts[] = $demand->locality;
                }
                if ($demand->due_at) {
                    $situationParts[] = 'prazo ' . $demand->due_at->format('d/m H:i');
                }

                return [
                    'stable_key' => 'resolve_ai:demand:' . $demand->id . ':' . ($isOverdue ? 'overdue' : $demand->status),
                    'module_key' => 'resolve_ai',
                    'module_label' => 'Resolve ai',
                    'title' => $isOverdue
                        ? 'Demanda com prazo estourado'
                        : ($isReopened ? 'Demanda reaberta e sem fechamento' : 'Demanda aberta pedindo encaminhamento'),
                    'situation' => implode(' · ', array_filter($situationParts)),
                    'suggestion' => $isOverdue
                        ? 'Abrir a demanda e destravar a resposta ainda hoje.'
                        : ($isReopened ? 'Revisar a reabertura e definir o proximo responsável.' : 'Validar prazo, pasta e proximo passo em uma linha.'),
                    'priority_bucket' => 'urgências_com_prazo',
                    'priority_rank' => 1,
                    'score' => $score,
                    'severity_score' => $score,
                    'deep_link_url' => route($routeName, $demand),
                    'deep_link_label' => 'Abrir no Resolve ai',
                    'conversation_prompt' => $this->buildResolveAiConversationPrompt($demand),
                ];
            })->values();
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: falha ao montar cards do Resolve ai.', ['exception' => $e]);
            return collect();
        }
    }

    private function buildMandateCards(User $user): Collection
    {
        if (!$user->isMayor()) {
            return collect();
        }

        try {
            $municipality = $user->municipality;
            $projection = $this->mandateProjection->calculate($municipality);
            $cards = collect();

            $topAxisAlert = collect($projection['axis_alerts'] ?? [])->first();
            if (is_array($topAxisAlert) && ($topAxisAlert['gap'] ?? 0) > 0) {
                $gap = (int) ($topAxisAlert['gap'] ?? 0);
                $score = 92 + min($gap, 5);
                $cards->push([
                    'stable_key' => 'mandato:axis-gap:' . ($topAxisAlert['axis_id'] ?? '0'),
                    'module_key' => 'mandato',
                    'module_label' => 'Mandato',
                    'title' => 'Eixo com risco de não fechar no ritmo atual',
                    'situation' => ($topAxisAlert['axis_name'] ?? 'Eixo prioritario') . " · lacuna projetada de {$gap} compromisso(s)",
                    'suggestion' => 'Abrir o eixo e decidir quais entregas entram no foco da semana.',
                    'priority_bucket' => 'riscos_de_mandato',
                    'priority_rank' => 2,
                    'score' => $score,
                    'severity_score' => $score,
                    'deep_link_url' => route('mayor.mandato.painel', [
                        'area' => 'actions',
                        'action_axis' => $topAxisAlert['axis_id'] ?? null,
                    ]),
                    'deep_link_label' => 'Abrir no Mandato',
                    'conversation_prompt' => $this->buildMandateAxisConversationPrompt($topAxisAlert, $projection),
                ]);
            }

            $pendingPromise = MandatePromise::query()
                ->where('municipality_id', $municipality->id)
                ->where('is_active', true)
                ->where('status', 'pending')
                ->withCount('actions')
                ->with('axis:id,name')
                ->orderBy('order')
                ->get(['id', 'mandate_axis_id', 'text', 'status'])
                ->first(fn (MandatePromise $promise) => (int) ($promise->actions_count ?? 0) === 0);

            if ($pendingPromise instanceof MandatePromise) {
                $cards->push([
                    'stable_key' => 'mandato:pending-promise:' . $pendingPromise->id,
                    'module_key' => 'mandato',
                    'module_label' => 'Mandato',
                    'title' => 'Compromisso aberto ainda sem acao vinculada',
                    'situation' => ($pendingPromise->axis?->name ?: 'Mandato') . ' · ' . Str::limit($pendingPromise->text, 110),
                    'suggestion' => 'Abrir o compromisso e decidir se vira acao de governo agora.',
                    'priority_bucket' => 'riscos_de_mandato',
                    'priority_rank' => 2,
                    'score' => 86,
                    'severity_score' => 86,
                    'deep_link_url' => route('mayor.mandato.painel', [
                        'area' => 'commitments',
                        'promise_review' => $pendingPromise->id,
                    ]),
                    'deep_link_label' => 'Abrir no Mandato',
                    'conversation_prompt' => $this->buildMandatePromiseConversationPrompt($pendingPromise),
                ]);
            }

            $suspendedAction = MandateAction::query()
                ->where('municipality_id', $municipality->id)
                ->where('status', 'suspenso')
                ->with('axis:id,name')
                ->orderByDesc('updated_at')
                ->first(['id', 'mandate_axis_id', 'title', 'updated_at']);

            if ($suspendedAction instanceof MandateAction) {
                $cards->push([
                    'stable_key' => 'mandato:suspended-action:' . $suspendedAction->id,
                    'module_key' => 'mandato',
                    'module_label' => 'Mandato',
                    'title' => 'Acao suspensa pedindo decisão executiva',
                    'situation' => ($suspendedAction->axis?->name ?: 'Mandato') . ' · ' . $suspendedAction->title,
                    'suggestion' => 'Revisar o bloqueio e decidir se retoma, replaneja ou encerra.',
                    'priority_bucket' => 'alertas_operacionais',
                    'priority_rank' => 3,
                    'score' => 78,
                    'severity_score' => 78,
                    'deep_link_url' => route('mayor.mandato.acao.edit', $suspendedAction),
                    'deep_link_label' => 'Abrir no Mandato',
                    'conversation_prompt' => $this->buildMandateActionConversationPrompt($suspendedAction),
                ]);
            }

            return $cards->values();
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: falha ao montar cards do Mandato.', ['exception' => $e]);
            return collect();
        }
    }

    private function buildRadarCards(User $user): Collection
    {
        if (!$user->isMayor()) {
            return collect();
        }

        try {
            $programs = $this->radarRead->topMunicipalityPrograms(
                municipality: $user->municipality,
                limit: 3,
                minMatchScore: 0.75,
                statuses: [
                    ResourceOpportunityStatus::Published->value,
                    ResourceOpportunityStatus::ClosingSoon->value,
                    ResourceOpportunityStatus::Reopened->value,
                ],
                visibleOnly: false,
            );

            return collect($programs)->map(function ($program) {
                $daysLeft = $program->deadline?->diffInDays(now(), false);
                $score = match (true) {
                    $daysLeft !== null && $daysLeft <= 3 => 90,
                    $daysLeft !== null && $daysLeft <= 7 => 84,
                    default => 72,
                };

                return [
                    'stable_key' => 'radar:program:' . ($program->id ?: ($program->canonical_cycle_id ?? $program->program_name)),
                    'module_key' => 'radar_recursos',
                    'module_label' => 'Radar de Recursos',
                    'title' => 'Oportunidade com aderência alta ao município',
                    'situation' => trim($program->program_name . ' · ' . round(((float) $program->match_score) * 100) . '% compativel' . ($program->deadline ? ' · prazo ' . $program->deadline->format('d/m') : '')),
                    'suggestion' => $daysLeft !== null && $daysLeft <= 7
                        ? 'Abrir o edital e decidir hoje se entra na corrida.'
                        : 'Abrir a oportunidade e avaliar encaixe com projeto ou acao.',
                    'priority_bucket' => $daysLeft !== null && $daysLeft <= 7 ? 'urgências_com_prazo' : 'oportunidades',
                    'priority_rank' => $daysLeft !== null && $daysLeft <= 7 ? 1 : 4,
                    'score' => $score,
                    'severity_score' => $score,
                    'deep_link_url' => route('mayor.mandato.federal-programs', array_filter([
                        'highlight_program_id' => $program->id ?: null,
                        'highlight_canonical_cycle_id' => $program->canonical_cycle_id ?? null,
                        'highlight_canonical_opportunity_id' => $program->canonical_opportunity_id ?? null,
                    ])),
                    'deep_link_label' => 'Abrir no Radar de Recursos',
                    'conversation_prompt' => $this->buildRadarConversationPrompt($program),
                ];
            })->values();
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: falha ao montar cards do Radar.', ['exception' => $e]);
            return collect();
        }
    }

    private function buildProjectCards(User $user): Collection
    {
        if (!$user->isMayor()) {
            return collect();
        }

        try {
            $projects = Project::query()
                ->where('municipality_id', $user->municipality_id)
                ->orderByDesc('updated_at')
                ->limit(12)
                ->get();

            $cards = collect();

            $overdueProject = $projects
                ->filter(function (Project $project) {
                    if ($project->status === 'concluido') {
                        return false;
                    }

                    return $this->resolveProjectExpectedEndDate($project)?->isPast() === true;
                })
                ->sortBy(fn (Project $project) => $this->resolveProjectExpectedEndDate($project)?->getTimestamp() ?? PHP_INT_MAX)
                ->first();

            if ($overdueProject instanceof Project) {
                $expectedEnd = $this->resolveProjectExpectedEndDate($overdueProject);

                $cards->push([
                    'stable_key' => 'projects:deadline-overdue:' . $overdueProject->id . ':' . ($expectedEnd?->format('Y-m-d') ?? 'none'),
                    'module_key' => 'projetos',
                    'module_label' => 'Projetos',
                    'title' => 'Projeto com prazo de etapa vencido',
                    'situation' => trim($overdueProject->title . ' · ' . $this->projectPhaseLabel($overdueProject->current_phase) . ($expectedEnd ? ' · previsão ' . $expectedEnd->format('d/m') : '')),
                    'suggestion' => 'Abrir o projeto e decidir hoje se replaneja a fase atual ou destrava a execução.',
                    'priority_bucket' => 'alertas_operacionais',
                    'priority_rank' => 3,
                    'score' => 83,
                    'severity_score' => 83,
                    'deep_link_url' => route('mayor.projects.show', $overdueProject),
                    'deep_link_label' => 'Abrir em Projetos',
                    'conversation_prompt' => $this->buildProjectDeadlineConversationPrompt($overdueProject, $expectedEnd),
                ]);
            }

            $fundingProject = $projects
                ->where('status', 'captacao_em_andamento')
                ->map(function (Project $project) {
                    $analysis = data_get($project->metadata, 'funding_analysis');

                    if (!is_array($analysis) || empty($analysis['matches'])) {
                        $analysis = $this->projectFundingMatch->analyze($project);
                    }

                    $bestFederalMatch = collect($analysis['matches'] ?? [])
                        ->filter(fn ($match) => is_array($match) && ($match['source_type'] ?? null) === 'federal')
                        ->sortByDesc('score')
                        ->first();

                    return [
                        'project' => $project,
                        'match' => $bestFederalMatch,
                    ];
                })
                ->filter(fn (array $entry) => is_array($entry['match'] ?? null) && (int) ($entry['match']['score'] ?? 0) >= 55)
                ->sortByDesc(fn (array $entry) => (int) ($entry['match']['score'] ?? 0))
                ->first();

            if (is_array($fundingProject)) {
                /** @var Project $project */
                $project = $fundingProject['project'];
                $match = $fundingProject['match'];
                $score = max(74, min(90, (int) ($match['score'] ?? 74)));

                $cards->push([
                    'stable_key' => 'projects:funding-match:' . $project->id . ':' . md5((string) ($match['title'] ?? 'match')),
                    'module_key' => 'projetos',
                    'module_label' => 'Projetos',
                    'title' => 'Projeto em captação com edital compatível identificado',
                    'situation' => trim($project->title . ' · ' . ($match['title'] ?? 'Edital compativel') . ' · ' . ((int) ($match['score'] ?? 0)) . '/100 de aderência'),
                    'suggestion' => 'Abrir a analise de financiamento e decidir se essa captação entra no foco de hoje.',
                    'priority_bucket' => 'oportunidades',
                    'priority_rank' => 4,
                    'score' => $score,
                    'severity_score' => $score,
                    'deep_link_url' => route('mayor.projects.show', $project) . '#project-funding',
                    'deep_link_label' => 'Abrir em Projetos',
                    'conversation_prompt' => $this->buildProjectFundingConversationPrompt($project, $match),
                ]);
            }

            return $cards->values();
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: falha ao montar cards de Projetos.', ['exception' => $e]);
            return collect();
        }
    }

    private function buildProjectBankCards(User $user): Collection
    {
        if (!$user->isMayor()) {
            return collect();
        }

        try {
            if (ProjectThesis::query()->where('municipality_id', $user->municipality_id)->count() === 0) {
                app(ProjectBankLibraryService::class)->ensureLibraryForMunicipality($user->municipality, force: false);
            }

            $thesis = ProjectThesis::query()
                ->where('municipality_id', $user->municipality_id)
                ->where('urgency', 'alta')
                ->where(function ($builder) use ($user) {
                    $builder
                        ->whereDoesntHave('userStates', fn ($states) => $states->where('user_id', $user->id))
                        ->orWhereHas('userStates', fn ($states) => $states
                            ->where('user_id', $user->id)
                            ->where(function ($stateQuery) {
                                $stateQuery
                                    ->whereNull('last_action_at')
                                    ->orWhere('last_action_at', '<', now()->subDays(7));
                            }));
                })
                ->orderBy('resource_deadline')
                ->orderByDesc('updated_at')
                ->first();

            if (!$thesis instanceof ProjectThesis) {
                return collect();
            }

            $score = $thesis->resource_deadline && $thesis->resource_deadline->diffInDays(now(), false) <= 60 ? 79 : 71;

            return collect([[
                'stable_key' => 'project_bank:thesis:' . $thesis->id . ':' . ($thesis->updated_at?->format('YmdHis') ?? '0'),
                'module_key' => 'banco_projetos',
                'module_label' => 'Banco de Projetos',
                'title' => 'Tese com urgência alta ainda sem acao tomada',
                'situation' => trim($thesis->title . ' · ' . ucfirst($thesis->category) . ($thesis->resource_deadline ? ' · prazo ' . $thesis->resource_deadline->format('d/m') : '')),
                'suggestion' => 'Abrir a tese e decidir hoje se salva, compartilha ou ja vira projeto.',
                'priority_bucket' => 'oportunidades',
                'priority_rank' => 4,
                'score' => $score,
                'severity_score' => $score,
                'deep_link_url' => route('mayor.project-bank.show', $thesis),
                'deep_link_label' => 'Abrir no Banco de Projetos',
                'conversation_prompt' => $this->buildProjectBankConversationPrompt($thesis),
            ]]);
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: falha ao montar cards do Banco de Projetos.', ['exception' => $e]);
            return collect();
        }
    }

    private function buildCommunicationCards(User $user): Collection
    {
        if (!$user->isMayor()) {
            return collect();
        }

        try {
            $municipalityId = (int) $user->municipality_id;
            $openStatuses = ['registered', 'pending', 'in_progress', 'reopened', 'overdue', 'awaiting_confirmation'];
            $cards = collect();

            $overdueDemand = Demand::query()
                ->where('municipality_id', $municipalityId)
                ->whereIn('status', $openStatuses)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->orderBy('due_at')
                ->get()
                ->first(fn (Demand $demand) => $this->isCommunicationOperationDemand($demand));

            if ($overdueDemand instanceof Demand) {
                $type = $this->resolveCommunicationDemandType($overdueDemand);
                $cards->push([
                    'stable_key' => 'communication:overdue-demand:' . $overdueDemand->id,
                    'module_key' => 'comunicação',
                    'module_label' => 'Comunicação',
                    'title' => 'Pauta com prazo vencido no Núcleo de Operação',
                    'situation' => ($type['label'] ?? 'Operacao') . ' · ' . ($overdueDemand->title ?: Str::limit((string) $overdueDemand->raw_input, 72)) . ' · prazo ' . $overdueDemand->due_at?->format('d/m H:i'),
                    'suggestion' => 'Abrir a pauta, redefinir dono e confirmar o proximo passo hoje.',
                    'priority_bucket' => 'urgências_com_prazo',
                    'priority_rank' => 1,
                    'score' => 93,
                    'severity_score' => 93,
                    'deep_link_url' => route('mayor.content.index', [
                        'area' => 'operations',
                        'operation_type' => $type['key'] ?? 'all',
                        'highlight_demand' => $overdueDemand->id,
                    ]),
                    'deep_link_label' => 'Abrir na Comunicação',
                    'conversation_prompt' => $this->buildCommunicationDemandConversationPrompt($overdueDemand, 'pauta_vencida'),
                ]);
            }

            $todayEventDemand = Demand::query()
                ->where('municipality_id', $municipalityId)
                ->whereIn('status', ['registered', 'pending', 'in_progress', 'reopened', 'overdue'])
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])
                ->orderBy('due_at')
                ->get()
                ->first(fn (Demand $demand) => $this->resolveCommunicationDemandType($demand)['key'] === 'event_coverage');

            if ($todayEventDemand instanceof Demand) {
                $cards->push([
                    'stable_key' => 'communication:event-no-coverage:' . $todayEventDemand->id,
                    'module_key' => 'comunicação',
                    'module_label' => 'Comunicação',
                    'title' => 'Evento do dia ainda sem cobertura confirmada',
                    'situation' => ($todayEventDemand->title ?: Str::limit((string) $todayEventDemand->raw_input, 72)) . ' · hoje às ' . $todayEventDemand->due_at?->format('H:i'),
                    'suggestion' => 'Abrir a pauta e checar equipe, deslocamento e cobertura antes do horario.',
                    'priority_bucket' => 'urgências_com_prazo',
                    'priority_rank' => 1,
                    'score' => 89,
                    'severity_score' => 89,
                    'deep_link_url' => route('mayor.content.index', [
                        'area' => 'operations',
                        'operation_type' => 'event_coverage',
                        'highlight_demand' => $todayEventDemand->id,
                    ]),
                    'deep_link_label' => 'Abrir na Comunicação',
                    'conversation_prompt' => $this->buildCommunicationDemandConversationPrompt($todayEventDemand, 'evento_sem_cobertura'),
                ]);
            }

            return $cards->values();
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: falha ao montar cards de Comunicação.', ['exception' => $e]);
            return collect();
        }
    }

    private function buildMentionCards(User $user): Collection
    {
        if (!$user->isMayor()) {
            return collect();
        }

        try {
            $query = SocialMention::query()
                ->where('municipality_id', $user->municipality_id)
                ->where('published_at', '>=', now()->subHours(12))
                ->orderByDesc('published_at');

            $cards = collect();

            $urgentMention = (clone $query)
                ->where('sentiment', 'urgent')
                ->first();

            if ($urgentMention instanceof SocialMention) {
                $cards->push([
                    'stable_key' => 'mentions:urgent:' . $urgentMention->id . ':' . ($urgentMention->is_read ? 'read' : 'unread'),
                    'module_key' => 'mencoes',
                    'module_label' => 'Menções',
                    'title' => 'Menção urgente detectada nas últimas 12 horas',
                    'situation' => $this->summarizeMention($urgentMention),
                    'suggestion' => 'Abrir a menção e alinhar resposta ou monitoramento antes de ela escalar.',
                    'priority_bucket' => 'urgências_com_prazo',
                    'priority_rank' => 1,
                    'score' => 96,
                    'severity_score' => 96,
                    'deep_link_url' => route('mayor.content.index', [
                        'area' => 'mentions',
                        'mention_filter' => 'urgent',
                        'highlight_mention' => $urgentMention->id,
                    ]),
                    'deep_link_label' => 'Abrir em Menções',
                    'conversation_prompt' => $this->buildMentionConversationPrompt($urgentMention),
                ]);
            }

            $negativeMention = (clone $query)
                ->where('sentiment', 'negative')
                ->where('is_read', false)
                ->first();

            if ($negativeMention instanceof SocialMention) {
                $cards->push([
                    'stable_key' => 'mentions:negative:' . $negativeMention->id . ':' . ($negativeMention->is_read ? 'read' : 'unread'),
                    'module_key' => 'mencoes',
                    'module_label' => 'Menções',
                    'title' => 'Menção negativa recente ainda sem triagem final',
                    'situation' => $this->summarizeMention($negativeMention),
                    'suggestion' => 'Abrir o contexto da menção e decidir se vira resposta, nota ou monitoramento.',
                    'priority_bucket' => 'alertas_operacionais',
                    'priority_rank' => 3,
                    'score' => 82,
                    'severity_score' => 82,
                    'deep_link_url' => route('mayor.content.index', [
                        'area' => 'mentions',
                        'mention_filter' => 'negative',
                        'highlight_mention' => $negativeMention->id,
                    ]),
                    'deep_link_label' => 'Abrir em Menções',
                    'conversation_prompt' => $this->buildMentionConversationPrompt($negativeMention),
                ]);
            }

            return $cards->values();
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: falha ao montar cards de Menções.', ['exception' => $e]);
            return collect();
        }
    }

    private function applySuppression(User $user, Collection $cards): Collection
    {
        $previousCards = MorningBriefing::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [today()->subDays(3), today()->subDay()])
            ->get(['cards'])
            ->pluck('cards')
            ->filter()
            ->flatten(1);

        $previousSeverity = [];
        foreach ($previousCards as $card) {
            if (!is_array($card) || empty($card['stable_key'])) {
                continue;
            }

            $stableKey = (string) $card['stable_key'];
            $severity = (int) ($card['severity_score'] ?? $card['score'] ?? 0);
            $previousSeverity[$stableKey] = max($previousSeverity[$stableKey] ?? 0, $severity);
        }

        return $cards->filter(function (array $card) use ($previousSeverity) {
            $stableKey = (string) ($card['stable_key'] ?? '');
            if ($stableKey === '' || !array_key_exists($stableKey, $previousSeverity)) {
                return true;
            }

            return (int) ($card['severity_score'] ?? $card['score'] ?? 0) > (int) $previousSeverity[$stableKey];
        })->values();
    }

    private function generateOpening(User $user, Collection $cards): string
    {
        $today = now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM');
        $topCards = $cards->take(3)->map(function (array $card, int $index) {
            $position = $index + 1;
            return "{$position}. {$card['module_label']}: {$card['title']} | {$card['situation']} | {$card['suggestion']}";
        })->implode("\n");

        $fallback = "Bom dia, {$user->name}. Hoje e {$today}. "
            . ($cards->isEmpty()
                ? 'Seu Pra Hoje amanheceu sem urgências novas; vale revisar oportunidades e manter o ritmo do que ja esta em curso.'
                : 'Seu Pra Hoje separou os pontos que mais pedem decisão agora, em ordem de urgência e impacto.');

        try {
            $response = $this->ai->chat([
                [
                    'role' => 'system',
                    'content' => 'Voce escreve a abertura do módulo Pra Hoje. Responda em portugues do Brasil, tom coloquial, no maximo 4 linhas, sem markdown e sem listas.',
                ],
                [
                    'role' => 'user',
                    'content' => "Usuario: {$user->name}\nPerfil: {$user->role->label()}\nData: {$today}\nItens priorizados:\n{$topCards}\n\nEscreva uma abertura pessoal curta, acolhedora e objetiva, preparando o usuario para os cards do dia.",
                ],
            ], [
                'temperature' => 0.6,
                'max_tokens' => 180,
                'timeout' => 20,
            ]);

            $opening = trim($response->content);

            return $opening !== '' ? Str::limit($opening, 420, '') : $fallback;
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: IA indisponível para abertura, usando fallback.', ['exception' => $e]);
            return $fallback;
        }
    }

    private function buildSectionsPayload(Collection $cards): array
    {
        return [
            'generated_at' => now()->toISOString(),
            'cards_total' => $cards->count(),
            'priority_counts' => [
                'urgências_com_prazo' => $cards->where('priority_bucket', 'urgências_com_prazo')->count(),
                'riscos_de_mandato' => $cards->where('priority_bucket', 'riscos_de_mandato')->count(),
                'alertas_operacionais' => $cards->where('priority_bucket', 'alertas_operacionais')->count(),
                'oportunidades' => $cards->where('priority_bucket', 'oportunidades')->count(),
            ],
            'modules' => $cards->pluck('module_label')->countBy()->all(),
            'suppression_window_days' => 3,
        ];
    }

    private function buildReadableContent(string $opening, Collection $cards): string
    {
        $lines = [trim($opening), '', 'Itens priorizados para hoje:'];

        foreach ($cards as $card) {
            $lines[] = '- ' . $card['module_label'] . ': ' . $card['title'];
            $lines[] = '  Situacao: ' . $card['situation'];
            $lines[] = '  Proximo passo: ' . $card['suggestion'];
        }

        if ($cards->isEmpty()) {
            $lines[] = '- Nenhum item critico novo apareceu nas fontes monitoradas hoje.';
        }

        return implode("\n", $lines);
    }

    private function resolveDeliveryChannel(User $user): string
    {
        $preferences = $this->resolvePraHojePreferences($user);

        return ($preferences['email_enabled'] ?? false) ? 'app_email' : 'app';
    }

    private function resolvePraHojePreferences(User $user): array
    {
        $preferences = data_get($user->preferences, 'pra_hoje', []);

        return [
            'enabled' => (bool) data_get($preferences, 'enabled', true),
            'delivery_time' => (string) data_get($preferences, 'delivery_time', '07:30'),
            'email_enabled' => (bool) data_get($preferences, 'email_enabled', false),
        ];
    }

    private function sendPush(User $user, MorningBriefing $briefing, Collection $cards): void
    {
        try {
            $topCard = $cards->first();
            $body = $topCard
                ? Str::limit(($topCard['module_label'] ?? 'Pra Hoje') . ': ' . ($topCard['title'] ?? ''), 120)
                : 'Seu resumo do dia ja esta pronto.';

            $this->webPush->sendToUser($user, [
                'title' => 'Pra Hoje pronto',
                'body' => $body,
                'icon' => '/images/mascote-robo.jpg',
                'url' => route('pra-hoje.show', $briefing),
                'tag' => 'pra-hoje-' . today()->format('Y-m-d') . '-user-' . $user->id,
                'requireInteraction' => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: push falhou.', ['exception' => $e]);
        }

        $this->sendEmailIfEnabled($user, $briefing);
    }

    private function sendEmailIfEnabled(User $user, MorningBriefing $briefing): void
    {
        if (($this->resolvePraHojePreferences($user)['email_enabled'] ?? false) !== true) {
            return;
        }

        try {
            Mail::raw(
                trim(($briefing->opening_text ?: 'Seu Pra Hoje ja esta pronto.') . "\n\nAbra agora: " . route('pra-hoje.show', $briefing)),
                function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                        ->subject('Pra Hoje pronto');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Pra Hoje: envio opcional por e-mail falhou.', ['exception' => $e]);
        }
    }

    private function isCommunicationOperationDemand(Demand $demand): bool
    {
        $type = $this->resolveCommunicationDemandType($demand)['key'];

        return in_array($type, ['event_coverage', 'press_service', 'crisis_monitoring', 'mandate_delivery', 'resolve_story'], true);
    }

    private function resolveCommunicationDemandType(Demand $demand): array
    {
        if ((string) $demand->input_type === 'mandato_action_completed') {
            return ['key' => 'mandate_delivery', 'label' => 'Mandato em conteúdo'];
        }

        $text = Str::of(trim(implode(' ', array_filter([
            $demand->title,
            $demand->description,
            $demand->raw_input,
        ]))))->lower()->value();

        if (
            str_contains($text, 'evento') ||
            str_contains($text, 'inauguracao') ||
            str_contains($text, 'visita tecnica') ||
            str_contains($text, 'audiencia publica') ||
            str_contains($text, 'agenda') ||
            str_contains($text, 'cobertura')
        ) {
            return ['key' => 'event_coverage', 'label' => 'Cobertura de evento'];
        }

        if (
            str_contains($text, 'imprensa') ||
            str_contains($text, 'release') ||
            str_contains($text, 'entrevista') ||
            str_contains($text, 'coletiva')
        ) {
            return ['key' => 'press_service', 'label' => 'Atendimento à imprensa'];
        }

        if (
            str_contains($text, 'crise') ||
            str_contains($text, 'nota oficial') ||
            str_contains($text, 'mencao')
        ) {
            return ['key' => 'crisis_monitoring', 'label' => 'Monitoramento de crise'];
        }

        if (in_array((string) $demand->status, ['awaiting_confirmation', 'completed', 'resolved'], true)) {
            return ['key' => 'resolve_story', 'label' => 'Demanda convertida em conteúdo'];
        }

        return ['key' => 'content_production', 'label' => 'Produção de conteúdo'];
    }

    private function summarizeMention(SocialMention $mention): string
    {
        $parts = [];
        $parts[] = $mention->title ?: Str::limit((string) $mention->content, 72);
        if ($mention->source_label) {
            $parts[] = $mention->source_label;
        }
        if ($mention->published_at) {
            $parts[] = $mention->published_at->diffForHumans();
        }

        return implode(' · ', array_filter($parts));
    }

    private function resolveProjectExpectedEndDate(Project $project): ?Carbon
    {
        $raw = data_get($project->metadata, 'expected_end_date');

        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function projectPhaseLabel(?string $phase): string
    {
        return match ((string) $phase) {
            'estrutura_inicial' => 'Estrutura inicial',
            'questionario_em_andamento' => 'Questionario em andamento',
            'documento_em_revisão' => 'Documento em revisão',
            'analise_de_sobreposição' => 'Analise de sobreposição',
            'captacao_em_planejamento' => 'Captação em planejamento',
            'pronto_para_submissao' => 'Pronto para submissao',
            default => 'Fase do projeto',
        };
    }

    private function buildResolveAiConversationPrompt(Demand $demand): string
    {
        $lines = [
            'Quero analisar esta demanda priorizada no Pra Hoje e sair com um plano de acao objetivo.',
            '',
            'DADOS DA DEMANDA:',
            '- Título: ' . ($demand->title ?: 'Demanda sem titulo'),
            '- Status: ' . $demand->status,
        ];

        if ($demand->priority) {
            $lines[] = '- Prioridade: ' . $demand->priority;
        }
        if ($demand->area) {
            $lines[] = '- Pasta: ' . $demand->area;
        }
        if ($demand->locality) {
            $lines[] = '- Localidade: ' . $demand->locality;
        }
        if ($demand->due_at) {
            $lines[] = '- Prazo: ' . $demand->due_at->format('d/m/Y H:i');
        }
        if ($demand->raw_input) {
            $lines[] = '- Contexto original: ' . Str::limit(trim($demand->raw_input), 700);
        }

        $lines[] = '';
        $lines[] = 'Me entregue: resumo do risco, proxima acao recomendada e mensagem curta para cobrar andamento.';

        return implode("\n", $lines);
    }

    private function buildMandateAxisConversationPrompt(array $axisAlert, array $projection): string
    {
        return implode("\n", [
            'Quero analisar este risco de mandato priorizado no Pra Hoje e sair com foco claro de execução.',
            '',
            'DADOS DO EIXO:',
            '- Eixo: ' . ($axisAlert['axis_name'] ?? 'Nao informado'),
            '- Lacuna projetada: ' . ($axisAlert['gap'] ?? 0) . ' compromisso(s)',
            '- Promessas totais: ' . ($axisAlert['total_promises'] ?? 0),
            '- Promessas projetadas como entregues: ' . ($axisAlert['projected_fulfilled'] ?? 0),
            '- Alerta geral: ' . ($projection['alert_message'] ?? 'Sem mensagem'),
            '',
            'Me entregue: leitura executiva, 3 acoes concretas para esta semana e argumento politico para comunicar prioridade.',
        ]);
    }

    private function buildMandatePromiseConversationPrompt(MandatePromise $promise): string
    {
        return implode("\n", [
            'Quero transformar este compromisso aberto do Mandato em plano de execução.',
            '',
            'DADOS DO COMPROMISSO:',
            '- Eixo: ' . ($promise->axis?->name ?: 'Nao informado'),
            '- Texto: ' . trim($promise->text),
            '- Status: ' . $promise->status,
            '- Acoes vinculadas: ' . (int) ($promise->actions_count ?? 0),
            '',
            'Me entregue: diagnostico rapido, melhor proxima acao e criterios para saber se a entrega deve entrar no foco do governo agora.',
        ]);
    }

    private function buildMandateActionConversationPrompt(MandateAction $action): string
    {
        return implode("\n", [
            'Quero destravar esta acao suspensa do Mandato a partir do card do Pra Hoje.',
            '',
            'DADOS DA ACAO:',
            '- Título: ' . $action->title,
            '- Eixo: ' . ($action->axis?->name ?: 'Nao informado'),
            '- Status: ' . $action->status,
            '',
            'Me entregue: possiveis causas do bloqueio, 3 caminhos de decisão e mensagem de alinhamento para a equipe.',
        ]);
    }

    private function buildRadarConversationPrompt(FederalProgramAlert $program): string
    {
        $lines = [
            'Quero transformar esta oportunidade priorizada no Pra Hoje em plano de acao executavel.',
            '',
            'DADOS DA OPORTUNIDADE:',
            '- Nome: ' . $program->program_name,
            '- Status: ' . $program->statusLabel(),
        ];

        if ($program->area) {
            $lines[] = '- Area: ' . $program->area;
        }
        if ($program->match_score) {
            $lines[] = '- Compatibilidade: ' . round(((float) $program->match_score) * 100) . '%';
        }
        if ($program->deadline) {
            $lines[] = '- Prazo: ' . $program->deadline->format('d/m/Y');
        }
        if ($program->source_url) {
            $lines[] = '- Edital: ' . $program->source_url;
        }

        $lines[] = '';
        $lines[] = 'Me entregue: viabilidade em 5 linhas, checklist imediato e próximas 3 acoes para hoje.';

        return implode("\n", $lines);
    }

    private function buildProjectDeadlineConversationPrompt(Project $project, ?Carbon $expectedEnd): string
    {
        $lines = [
            'Quero analisar este projeto priorizado no Pra Hoje e sair com uma decisão objetiva de destravamento.',
            '',
            'DADOS DO PROJETO:',
            '- Título: ' . $project->title,
            '- Status: ' . $project->status_label,
            '- Fase atual: ' . $this->projectPhaseLabel($project->current_phase),
        ];

        if ($expectedEnd) {
            $lines[] = '- Prazo previsto: ' . $expectedEnd->format('d/m/Y');
        }
        if ($project->responsible_secretariat) {
            $lines[] = '- Secretaria responsável: ' . $project->responsible_secretariat;
        }

        $lines[] = '';
        $lines[] = 'Me entregue: leitura do risco, proximo passo recomendado e como cobrar avancos hoje sem perder contexto.';

        return implode("\n", $lines);
    }

    private function buildProjectFundingConversationPrompt(Project $project, array $match): string
    {
        $lines = [
            'Quero transformar este projeto em captação em uma frente objetiva de submissao.',
            '',
            'DADOS DO PROJETO:',
            '- Título: ' . $project->title,
            '- Status: ' . $project->status_label,
            '- Fase atual: ' . $this->projectPhaseLabel($project->current_phase),
            '- Edital compativel: ' . ($match['title'] ?? 'Nao informado'),
            '- Aderência: ' . ((int) ($match['score'] ?? 0)) . '/100',
        ];

        if (!empty($match['subtitle'])) {
            $lines[] = '- Origem: ' . $match['subtitle'];
        }
        if (!empty($match['funding_type'])) {
            $lines[] = '- Modalidade: ' . $match['funding_type'];
        }

        $lines[] = '';
        $lines[] = 'Me entregue: viabilidade em poucas linhas, checklist de captura e as 3 acoes que precisam acontecer hoje.';

        return implode("\n", $lines);
    }

    private function buildProjectBankConversationPrompt(ProjectThesis $thesis): string
    {
        $lines = [
            'Quero avaliar esta tese do Banco de Projetos priorizada no Pra Hoje e decidir se ela vira projeto agora.',
            '',
            'DADOS DA TESE:',
            '- Título: ' . $thesis->title,
            '- Categoria: ' . $thesis->category,
            '- Urgência: ' . ucfirst($thesis->urgency),
            '- Porte: ' . ucfirst($thesis->estimated_size),
            '- Complexidade: ' . ucfirst($thesis->execution_complexity),
            '- Justificativa: ' . Str::limit(trim($thesis->justification), 600),
            '- Potencial de impacto: ' . Str::limit(trim($thesis->potential_impact), 400),
            '- Fonte de recurso: ' . Str::limit(trim($thesis->funding_source), 400),
        ];

        if ($thesis->government_alignment) {
            $lines[] = '- Alinhamento com programa de governo: ' . Str::limit(trim($thesis->government_alignment), 300);
        }
        if ($thesis->resource_deadline) {
            $lines[] = '- Prazo do recurso: ' . $thesis->resource_deadline->format('d/m/Y');
        }

        $lines[] = '';
        $lines[] = 'Me entregue: leitura executiva, criterio de decisão e as 3 acoes para hoje caso a tese deva avancar.';

        return implode("\n", $lines);
    }

    private function buildCommunicationDemandConversationPrompt(Demand $demand, string $context): string
    {
        $contextLabel = match ($context) {
            'evento_sem_cobertura' => 'evento do dia sem cobertura confirmada',
            default => 'pauta vencida no Núcleo de Operação',
        };

        $lines = [
            'Quero agir sobre esta pauta priorizada no Pra Hoje.',
            '',
            'CONTEXTO:',
            '- Tipo: ' . $contextLabel,
            '- Título: ' . ($demand->title ?: 'Pauta sem titulo'),
            '- Status: ' . $demand->status,
        ];

        if ($demand->due_at) {
            $lines[] = '- Prazo: ' . $demand->due_at->format('d/m/Y H:i');
        }
        if ($demand->area) {
            $lines[] = '- Pasta: ' . $demand->area;
        }
        if ($demand->raw_input) {
            $lines[] = '- Contexto operacional: ' . Str::limit(trim($demand->raw_input), 700);
        }

        $lines[] = '';
        $lines[] = 'Me entregue: leitura de risco, decisão imediata e mensagem curta para alinhar a equipe.';

        return implode("\n", $lines);
    }

    private function buildMentionConversationPrompt(SocialMention $mention): string
    {
        $lines = [
            'Quero analisar esta menção priorizada no Pra Hoje e decidir a melhor resposta.',
            '',
            'DADOS DA MENCAO:',
            '- Classificacao: ' . ($mention->sentiment_label ?? $mention->sentiment),
            '- Título: ' . ($mention->title ?: 'Sem titulo'),
        ];

        if ($mention->source_label) {
            $lines[] = '- Fonte: ' . $mention->source_label;
        }
        if ($mention->author) {
            $lines[] = '- Autor: ' . $mention->author;
        }
        if ($mention->published_at) {
            $lines[] = '- Publicada em: ' . $mention->published_at->format('d/m/Y H:i');
        }
        if ($mention->content) {
            $lines[] = '- Conteudo: ' . Str::limit(trim($mention->content), 700);
        }
        if ($mention->url) {
            $lines[] = '- Link: ' . $mention->url;
        }

        $lines[] = '';
        $lines[] = 'Me entregue: gravidade, linha de resposta recomendada e proximo passo operacional.';

        return implode("\n", $lines);
    }
}
