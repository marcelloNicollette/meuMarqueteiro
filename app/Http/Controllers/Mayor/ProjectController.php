<?php

namespace App\Http\Controllers\Mayor;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\MandateAction;
use App\Models\Project;
use App\Models\ProjectCollaborator;
use App\Models\ProjectDocumentRevision;
use App\Models\ProjectSection;
use App\Models\ProjectThesis;
use App\Models\User;
use App\Services\Projects\ProjectDocxExportService;
use App\Services\Projects\ProjectDocumentGenerationService;
use App\Services\Projects\ProjectExportService;
use App\Services\Projects\ProjectFundingMatchService;
use App\Services\Projects\ProjectQuestionAutofillService;
use App\Services\Projects\ProjectOverlapAnalysisService;
use App\Services\Projects\ProjectQuestionFlowService;
use App\Services\Projects\ProjectRevisionService;
use App\Services\Projects\ProjectStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectStructureService $structure,
        private ProjectQuestionFlowService $questionFlow,
        private ProjectDocumentGenerationService $documentGeneration,
        private ProjectDocxExportService $docxExport,
        private ProjectExportService $exportService,
        private ProjectRevisionService $revisionService,
        private ProjectOverlapAnalysisService $overlapAnalysis,
        private ProjectFundingMatchService $fundingMatch,
        private ProjectQuestionAutofillService $questionAutofill,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $projects = $this->accessibleProjectsQuery($user)
            ->with(['owner:id,name', 'lastEditedBy:id,name'])
            ->withCount([
                'sections',
                'collaborators as collaborators_count' => fn($query) => $query->whereNotNull('accepted_at'),
                'collaborators as pending_collaborators_count' => fn($query) => $query->whereNull('accepted_at'),
            ])
            ->orderByDesc('updated_at')
            ->get();

        $projects->each(function (Project $project) use ($user) {
            $role = $this->projectAccessRole($project, $user);
            $project->setAttribute('current_user_project_role', $role);
            $project->setAttribute('current_user_can_edit', $this->canEditWorkingCopy($project, $user));
        });

        $allProjects = $projects;
        $activeFilters = [
            'role' => (string) $request->string('role', 'all'),
            'collaboration' => (string) $request->string('collaboration', 'all'),
        ];

        $projects = $this->applyProjectListFilters($projects, $user, $activeFilters)->values();

        $pendingInvites = ProjectCollaborator::query()
            ->where('user_id', $user->id)
            ->whereNull('accepted_at')
            ->with([
                'project:id,municipality_id,owner_user_id,title,status,updated_at',
                'project.owner:id,name,email',
                'invitedBy:id,name',
            ])
            ->orderByDesc('invited_at')
            ->get();

        $statusCounts = [
            'total' => $allProjects->count(),
            'em_elaboração' => $allProjects->where('status', 'em_elaboração')->count(),
            'concluido' => $allProjects->where('status', 'concluido')->count(),
            'em_execução' => $allProjects->where('status', 'em_execução')->count(),
            'captacao_em_andamento' => $allProjects->where('status', 'captacao_em_andamento')->count(),
        ];

        return view('mayor.projects.index', [
            'projects' => $projects,
            'pendingInvites' => $pendingInvites,
            'statusCounts' => $statusCounts,
            'projectTypes' => $this->projectTypes(),
            'listRoleFilters' => $this->projectListRoleFilters(),
            'listCollaborationFilters' => $this->projectListCollaborationFilters(),
            'activeFilters' => $activeFilters,
            'filteredCount' => $projects->count(),
            'availableCount' => $allProjects->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $sourceThesis = $this->resolveSourceThesis($request);

        return view('mayor.projects.create', [
            'projectTypes' => $this->projectTypes(),
            'projectStatuses' => $this->projectStatuses(),
            'sectionDefinitions' => $this->structure->definitions(),
            'sourceThesis' => $sourceThesis,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'source_thesis_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'initial_idea' => ['required', 'string', 'max:5000'],
            'project_type' => ['nullable', 'in:' . implode(',', array_keys($this->projectTypes()))],
            'responsible_secretariat' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:' . implode(',', array_keys($this->projectStatuses()))],
        ]);

        $sourceThesis = $this->resolveSourceThesisFromId($data['source_thesis_id'] ?? null);

        $project = DB::transaction(function () use ($user, $data, $sourceThesis) {
            $metadata = [
                'generation_status' => 'pending',
                'overlap_check_status' => 'pending',
                'funding_match_status' => 'pending',
            ];

            if ($sourceThesis instanceof ProjectThesis) {
                $metadata['source_thesis_snapshot'] = [
                    'id' => $sourceThesis->id,
                    'title' => $sourceThesis->title,
                    'category' => $sourceThesis->category,
                    'justification' => $sourceThesis->justification,
                    'potential_impact' => $sourceThesis->potential_impact,
                    'funding_source' => $sourceThesis->funding_source,
                    'government_alignment' => $sourceThesis->government_alignment,
                    'reference_municipalities' => $sourceThesis->reference_municipalities,
                    'urgency' => $sourceThesis->urgency,
                    'estimated_size' => $sourceThesis->estimated_size,
                    'execution_complexity' => $sourceThesis->execution_complexity,
                    'resource_deadline' => optional($sourceThesis->resource_deadline)->toDateString(),
                    'metadata' => is_array($sourceThesis->metadata) ? $sourceThesis->metadata : [],
                ];
            }

            $project = Project::create([
                'municipality_id' => $user->municipality_id,
                'owner_user_id' => $user->id,
                'last_edited_by_user_id' => $user->id,
                'source_thesis_id' => $sourceThesis?->id,
                'title' => $data['title'],
                'initial_idea' => $data['initial_idea'],
                'project_type' => $data['project_type'] ?: null,
                'status' => $data['status'],
                'responsible_secretariat' => $data['responsible_secretariat'] ?: null,
                'current_phase' => 'estrutura_inicial',
                'generated_document_version' => 1,
                'last_edited_at' => now(),
                'metadata' => $metadata,
            ]);

            $project->sections()->createMany($this->structure->buildInitialSections());

            $project->editHistory()->create([
                'user_id' => $user->id,
                'action' => 'project_created',
                'field_name' => 'project',
                'new_content' => $data['initial_idea'],
                'metadata' => [
                    'title' => $project->title,
                    'status' => $project->status,
                    'project_type' => $project->project_type,
                    'source_thesis_id' => $sourceThesis?->id,
                ],
            ]);

            return $project;
        });

        $project->loadMissing('municipality');
        $this->questionFlow->ensureGenerated($project, $user);

        return redirect()
            ->route('mayor.projects.show', $project)
            ->with('success', 'Projeto criado com a estrutura inicial e o questionario dinamico da fase 2.');
    }

    private function resolveSourceThesis(Request $request): ?ProjectThesis
    {
        return $this->resolveSourceThesisFromId($request->integer('source_thesis') ?: null);
    }

    private function resolveSourceThesisFromId(?int $thesisId): ?ProjectThesis
    {
        if (!$thesisId) {
            return null;
        }

        $user = Auth::user();

        return ProjectThesis::query()
            ->where('municipality_id', $user->municipality_id)
            ->find($thesisId);
    }

    public function show(Request $request, Project $project): View
    {
        $this->authorizeProjectAccess($project);

        $currentUser = Auth::user();
        $canEditByRole = $this->canEditProject($project, $currentUser);
        $canEditProject = $this->canEditWorkingCopy($project, $currentUser);
        $project->loadMissing('municipality');
        $this->revisionService->repairLegacyWorkingRevision($project);
        if ($canEditProject) {
            $this->questionFlow->ensureGenerated($project);
        }

        $project->load([
            'owner:id,name,email',
            'lastEditedBy:id,name',
            'sections',
            'intakeQuestions',
            'collaborators.user:id,name,email',
            'collaborators.invitedBy:id,name',
            'editHistory.user:id,name',
            'editHistory.section:id,title',
        ]);

        $completedSections = $project->sections->where('needs_review', false)->count();
        $filledSections = $project->sections->filter(fn($section) => filled($section->content))->count();
        $answeredQuestions = $project->intakeQuestions->filter(fn($question) => filled($question->answer))->count();
        $existingCollaboratorIds = $project->collaborators->pluck('user_id')->all();
        $eligibleUsers = User::query()
            ->where('municipality_id', $project->municipality_id)
            ->where('is_active', true)
            ->where('id', '!=', $project->owner_user_id)
            ->whereNotIn('id', $existingCollaboratorIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $activeCollaborators = $project->collaborators
            ->whereNotNull('accepted_at')
            ->values();

        $pendingCollaborators = $project->collaborators
            ->whereNull('accepted_at')
            ->values();

        $editHistory = $project->editHistory->take(24)->values();
        $documentRevisions = $project->documentRevisions()
            ->with([
                'user:id,name',
                'approvedBy:id,name',
                'publishedBy:id,name',
                'previousRevision:id,project_id,revision_number',
                'restoredFromRevision:id,project_id,revision_number',
            ])
            ->orderByDesc('revision_number')
            ->limit(12)
            ->get();
        $documentRevisions->each(fn(ProjectDocumentRevision $revision) => $this->hydrateRevisionApprovalData($revision));
        $publishedRevision = $this->activePublishedRevision($project);
        $currentDraftRevision = $this->activeWorkingDraftRevision($project);
        if ($publishedRevision) {
            $this->hydrateRevisionApprovalData($publishedRevision);
        }
        if ($currentDraftRevision) {
            $this->hydrateRevisionApprovalData($currentDraftRevision);
        }
        $documentRevisions = $this->visibleDocumentRevisions($documentRevisions, null, $currentDraftRevision);
        $selectedRevision = $this->resolveSelectedRevision($documentRevisions, $request, $currentUser);
        $documentRevisions = $this->visibleDocumentRevisions($documentRevisions, $selectedRevision, $currentDraftRevision);
        $latestRevision = $currentDraftRevision ?? $publishedRevision ?? $documentRevisions->first();
        $draftPublishedComparison = ($publishedRevision && $currentDraftRevision && $publishedRevision->id !== $currentDraftRevision->id)
            ? $this->revisionService->compareSnapshots($publishedRevision->snapshot, $currentDraftRevision->snapshot)
            : null;
        $isEditingLocked = $this->canEditProject($project, $currentUser)
            && $publishedRevision
            && !$currentDraftRevision;
        $canManageCollaborators = $this->canManageCollaborators($project, $currentUser);
        $canManageRevisions = $this->canManageRevisions($project, $currentUser);
        $canDeleteProject = $this->canDeleteProject($project, $currentUser);
        $currentUserRole = $this->projectAccessRole($project, $currentUser);
        $canEditCoreMetadata = $this->canEditCoreMetadata($project, $currentUser);
        $projectEditNotice = $canEditProject ? null : $this->projectEditBlockMessage($project, $currentUser);
        $projectMetadata = $this->normalizedProjectMetadata($project);
        $approvalEligibleUsers = $this->approvalEligibleUsers($project);
        $approvalEligibleUsersByStep = $selectedRevision
            ? $this->revisionService->eligibleUsersByApprovalStep($project, $selectedRevision->approval_steps, $approvalEligibleUsers)
            : [];
        $canApproveSelectedRevisionSteps = $selectedRevision
            ? $this->canApproveRevisionSteps($selectedRevision, $currentUser)
            : [];
        $mandateSyncSuggestion = $this->buildMandateStatusSyncSuggestion($project);

        return view('mayor.projects.show', [
            'project' => $project,
            'completedSections' => $completedSections,
            'filledSections' => $filledSections,
            'answeredQuestions' => $answeredQuestions,
            'activeCollaborators' => $activeCollaborators,
            'pendingCollaborators' => $pendingCollaborators,
            'editHistory' => $editHistory,
            'documentRevisions' => $documentRevisions,
            'selectedRevision' => $selectedRevision,
            'latestRevision' => $latestRevision,
            'publishedRevision' => $publishedRevision,
            'currentDraftRevision' => $currentDraftRevision,
            'draftPublishedComparison' => $draftPublishedComparison,
            'isEditingLocked' => $isEditingLocked,
            'canEditProject' => $canEditProject,
            'canEditByRole' => $canEditByRole,
            'canEditCoreMetadata' => $canEditCoreMetadata,
            'canManageCollaborators' => $canManageCollaborators,
            'canManageRevisions' => $canManageRevisions,
            'canDeleteProject' => $canDeleteProject,
            'currentUserProjectRole' => $currentUserRole,
            'projectEditNotice' => $projectEditNotice,
            'projectMetadata' => $projectMetadata,
            'overlapAnalysis' => data_get($project->metadata, 'overlap_analysis'),
            'fundingAnalysis' => data_get($project->metadata, 'funding_analysis'),
            'projectStatuses' => $this->projectStatuses(),
            'projectPhaseOptions' => $this->projectPhaseOptions(),
            'projectPriorityOptions' => $this->projectPriorityOptions(),
            'projectTypes' => $this->projectTypes(),
            'eligibleUsers' => $eligibleUsers,
            'approvalEligibleUsers' => $approvalEligibleUsers,
            'approvalEligibleUsersByStep' => $approvalEligibleUsersByStep,
            'canApproveSelectedRevisionSteps' => $canApproveSelectedRevisionSteps,
            'mandateSyncSuggestion' => $mandateSyncSuggestion,
        ]);
    }

    public function inviteCollaborator(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeCollaboratorManagement($project, 'invite_collaborator');

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'permission' => ['required', 'in:viewer,editor'],
        ]);

        $invitee = User::query()
            ->where('municipality_id', $project->municipality_id)
            ->where('is_active', true)
            ->findOrFail($data['user_id']);

        if ($invitee->id === $project->owner_user_id) {
            return $this->redirectToProjectShow($project)
                ->with('warning', 'O criador do projeto ja possui acesso total e não precisa de convite.');
        }

        $exists = $project->collaborators()
            ->where('user_id', $invitee->id)
            ->exists();

        if ($exists) {
            return $this->redirectToProjectShow($project)
                ->with('warning', 'Este usuario ja possui um convite ou colaboracao registrada neste projeto.');
        }

        $inviter = Auth::user();

        $project->collaborators()->create([
            'user_id' => $invitee->id,
            'invited_by_user_id' => $inviter->id,
            'permission' => $data['permission'],
            'invited_at' => now(),
            'accepted_at' => null,
        ]);

        $this->logProjectHistory($project, [
            'user_id' => $inviter->id,
            'action' => 'project_collaborator_invited',
            'field_name' => 'collaborator',
            'new_content' => $invitee->email,
            'metadata' => [
                'invitee_name' => $invitee->name,
                'invitee_email' => $invitee->email,
                'permission' => $data['permission'],
            ],
        ]);

        return $this->redirectToProjectShow($project)
            ->with('success', "Convite enviado para {$invitee->name}.");
    }

    public function acceptCollaboratorInvite(Project $project): RedirectResponse
    {
        $user = Auth::user();

        if ($project->municipality_id !== $user->municipality_id) {
            abort(403);
        }

        $collaborator = $project->collaborators()
            ->where('user_id', $user->id)
            ->whereNull('accepted_at')
            ->first();

        if (!$collaborator) {
            return redirect()
                ->route('mayor.projects.index')
                ->with('warning', 'Nao existe convite pendente para este projeto.');
        }

        $collaborator->forceFill([
            'accepted_at' => now(),
        ])->save();

        $this->logProjectHistory($project, [
            'user_id' => $user->id,
            'action' => 'project_collaborator_accepted',
            'field_name' => 'collaborator',
            'new_content' => $user->email,
            'metadata' => [
                'permission' => $collaborator->permission,
            ],
        ]);

        return redirect()
            ->route('mayor.projects.show', $project)
            ->with('success', 'Convite aceito. Voce agora participa deste projeto.');
    }

    public function removeCollaborator(Project $project, ProjectCollaborator $collaborator): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $this->authorizeCollaboratorManagement($project, 'remove_collaborator');

        $this->ensureCollaboratorBelongsToProject($project, $collaborator);

        $targetUser = $collaborator->user;
        $wasAccepted = filled($collaborator->accepted_at);
        $permission = $collaborator->permission;

        $collaborator->delete();

        $this->logProjectHistory($project, [
            'user_id' => Auth::id(),
            'action' => 'project_collaborator_removed',
            'field_name' => 'collaborator',
            'previous_content' => $targetUser?->email,
            'metadata' => [
                'collaborator_name' => $targetUser?->name,
                'collaborator_email' => $targetUser?->email,
                'permission' => $permission,
                'previous_status' => $wasAccepted ? 'active' : 'pending',
            ],
        ]);

        return $this->redirectToProjectShow($project)->with(
            'success',
            $wasAccepted
                ? 'Colaborador removido do projeto.'
                : 'Convite pendente cancelado com sucesso.'
        );
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $user = Auth::user();
        if (!$this->canDeleteProject($project, $user)) {
            $this->logPermissionBlocked($project, $user, 'delete_project', 'owner');
            abort(403, 'Somente o proprietario do projeto ou um administrador pode excluir este registro.');
        }

        $password = trim((string) $request->input('delete_password', ''));
        if ($password === '') {
            return $this->redirectToProjectShow($project)
                ->withErrors(['delete_password' => 'Informe sua senha para confirmar a exclusao do projeto.']);
        }

        if (!Hash::check($password, (string) $user->password)) {
            return $this->redirectToProjectShow($project)
                ->withErrors(['delete_password' => 'Senha invalida. A exclusao do projeto foi cancelada.']);
        }

        $project->loadMissing([
            'owner:id,name,email',
            'collaborators.user:id,name,email',
            'sections:id,project_id,section_key,title',
        ]);

        $deletionSnapshot = [
            'title' => $project->title,
            'status' => $project->status,
            'project_type' => $project->project_type,
            'current_phase' => $project->current_phase,
            'owner_user_id' => $project->owner_user_id,
            'owner_name' => $project->owner?->name,
            'sections_count' => $project->sections->count(),
            'collaborators_count' => $project->collaborators->count(),
            'deleted_by_user_id' => $user->id,
            'deleted_by_name' => $user->name,
        ];

        DB::transaction(function () use ($project, $user, $deletionSnapshot) {
            $this->logProjectHistory($project, [
                'user_id' => $user->id,
                'action' => 'project_deleted',
                'field_name' => 'project',
                'previous_content' => $project->title,
                'metadata' => $deletionSnapshot,
            ]);

            Log::warning('projects.deleted', [
                'project_id' => $project->id,
                'municipality_id' => $project->municipality_id,
                'deleted_at' => now()->toIso8601String(),
                'snapshot' => $deletionSnapshot,
            ]);

            $project->delete();
        });

        return redirect()
            ->route('mayor.projects.index')
            ->with('success', 'Projeto excluido com sucesso.');
    }

    public function analyzeOverlap(Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeProjectEdit($project, 'analyze_overlap');

        $user = Auth::user();
        $project->loadMissing(['sections', 'intakeQuestions']);
        $analysis = $this->overlapAnalysis->analyze($project, $user);

        $message = match ($analysis['status'] ?? 'clear') {
            'review_required' => 'A verificação encontrou forte sobreposição com projetos existentes. Revise antes de seguir.',
            'attention' => 'A verificação encontrou proximidade parcial com outros projetos. Vale revisar os itens apontados.',
            default => 'Verificação de sobreposição concluida sem conflitos relevantes.',
        };

        return $this->redirectToProjectShow($project)
            ->with('success', $message);
    }

    public function generateDocument(Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeProjectEdit($project, 'generate_document');

        $user = Auth::user();
        $project->loadMissing(['municipality', 'sections', 'intakeQuestions']);
        $analysis = $this->overlapAnalysis->analyze($project, $user);
        $funding = $this->fundingMatch->analyze($project, $user);
        $this->documentGeneration->generate($project, $user);
        $project->refresh()->loadMissing('sections');
        $this->revisionService->createRevision($project, $user, 'document_generated');

        $redirect = $this->redirectToProjectShow($project);

        if (($analysis['status'] ?? 'clear') !== 'clear') {
            $redirect->with('warning', 'O documento foi gerado, mas a verificação encontrou possivel sobreposição com outros projetos da prefeitura.');
        }

        if (($funding['status'] ?? 'none') === 'none') {
            $redirect->with('warning', 'O documento foi gerado, mas ainda não foram encontrados programas compatíveis relevantes para financiamento.');
        }

        return $redirect->with('success', 'Documento do projeto gerado com IA usando o questionario respondido e preenchendo as 15 seções obrigatórias.');
    }

    public function analyzeFunding(Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeProjectEdit($project, 'analyze_funding');

        $user = Auth::user();
        $project->loadMissing(['municipality', 'sections', 'intakeQuestions']);
        $analysis = $this->fundingMatch->analyze($project, $user);

        $message = match ($analysis['status'] ?? 'none') {
            'strong' => 'Foram encontrados programas com forte aderência ao projeto.',
            'moderate' => 'Foram encontrados programas com compatibilidade parcial a boa.',
            'initial' => 'Ha algumas sugestoes iniciais de financiamento para revisar.',
            default => 'Nenhum programa compativel relevante foi encontrado nesta verificação.',
        };

        return $this->redirectToProjectShow($project)
            ->with('success', $message);
    }

    public function regenerateQuestionnaire(Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeProjectEdit($project, 'regenerate_questionnaire');

        $user = Auth::user();
        $project->loadMissing('municipality');
        $this->questionFlow->ensureGenerated($project, $user, true);

        return $this->redirectToProjectShow($project)
            ->with('success', 'Perguntas dinâmicas regeneradas com base no contexto atual do projeto.');
    }

    public function autoAnswerQuestionnaire(Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeProjectEdit($project, 'save_questionnaire_answers');

        $user = Auth::user();
        $project->loadMissing(['municipality', 'intakeQuestions']);

        $result = $this->questionAutofill->generateAnswers($project, $user);
        $changes = $this->persistQuestionnaireAnswers(
            $project,
            $result['answers'] ?? [],
            $user,
            $result['source'] ?? 'ai',
            $result['context_summary'] ?? null
        );

        $project->refresh();
        $this->questionFlow->syncAnsweredCount($project);
        $this->updateQuestionnaireAutomationMetadata(
            $project,
            $result['source'] ?? 'ai',
            $result['context_summary'] ?? null
        );

        if ($changes === 0) {
            return $this->redirectToProjectShow($project)
                ->with('warning', 'A IA analisou o contexto do municipio, mas nao encontrou novas respostas para atualizar.');
        }

        return $this->redirectToProjectShow($project)
            ->with('success', 'Questionario preenchido com IA usando o contexto do municipio, base de conteudo e dados internos do sistema.');
    }

    public function saveQuestionnaireAnswers(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeProjectEdit($project, 'save_questionnaire_answers');

        $questionIds = $project->intakeQuestions()->pluck('id')->all();
        $rules = [];
        foreach ($questionIds as $questionId) {
            $rules["answers.{$questionId}"] = ['nullable', 'string', 'max:5000'];
        }

        $data = $request->validate($rules);
        $answers = $data['answers'] ?? [];
        $user = Auth::user();
        $this->persistQuestionnaireAnswers($project, $answers, $user, 'manual');

        $project->refresh();
        $this->questionFlow->syncAnsweredCount($project);

        return $this->redirectToProjectShow($project)
            ->with('success', 'Respostas do questionario salvas com sucesso.');
    }

    public function updateSection(Request $request, Project $project, ProjectSection $section): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeProjectEdit($project, 'update_section');
        $this->ensureSectionBelongsToProject($project, $section);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
            'needs_review' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        $previousContent = trim((string) ($section->content ?? ''));
        $newContent = trim((string) $data['content']);
        $needsReview = (bool) ($data['needs_review'] ?? false);

        $section->forceFill([
            'content' => $newContent,
            'needs_review' => $needsReview,
            'metadata' => array_merge(is_array($section->metadata) ? $section->metadata : [], [
                'updated_manually_at' => now()->toIso8601String(),
                'updated_manually_by' => $user->id,
            ]),
        ])->save();

        $project->forceFill([
            'last_edited_by_user_id' => $user->id,
            'last_edited_at' => now(),
            'current_phase' => 'documento_em_revisão',
        ])->save();

        $this->logProjectHistory($project, [
            'user_id' => $user->id,
            'project_section_id' => $section->id,
            'action' => 'project_section_updated',
            'field_name' => $section->section_key,
            'previous_content' => $previousContent !== '' ? $previousContent : null,
            'new_content' => $newContent,
            'metadata' => [
                'section_title' => $section->title,
                'needs_review' => $needsReview,
            ],
        ]);

        $project->refresh()->loadMissing('sections');
        $this->revisionService->createRevision($project, $user, 'section_updated', [
            'section_title' => $section->title,
        ]);

        return $this->redirectToProjectShow($project)
            ->with('success', "Secao {$section->section_order} atualizada com sucesso.");
    }

    public function updateMetadata(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        $user = Auth::user();
        $canEditProject = $this->canEditWorkingCopy($project, $user);
        $canEditCoreMetadata = $this->canEditCoreMetadata($project, $user);

        if (!$canEditProject) {
            $this->logPermissionBlocked($project, $user, 'update_project_metadata', 'editor');
            abort(403, $this->projectEditBlockMessage($project, $user, 'update_project_metadata'));
        }

        $coreFields = ['title', 'initial_idea', 'project_type', 'status', 'responsible_secretariat', 'current_phase'];
        if ($request->hasAny($coreFields) && !$canEditCoreMetadata) {
            $this->logPermissionBlocked($project, $user, 'update_core_project_metadata', 'owner');
            abort(403, 'Somente o proprietario do projeto pode editar os metadados principais.');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'initial_idea' => ['sometimes', 'required', 'string', 'max:5000'],
            'project_type' => ['sometimes', 'nullable', 'in:' . implode(',', array_keys($this->projectTypes()))],
            'status' => ['sometimes', 'required', 'in:' . implode(',', array_keys($this->projectStatuses()))],
            'responsible_secretariat' => ['sometimes', 'nullable', 'string', 'max:120'],
            'current_phase' => ['sometimes', 'required', 'in:' . implode(',', array_keys($this->projectPhaseOptions()))],
            'metadata.executive_summary' => ['nullable', 'string', 'max:2000'],
            'metadata.primary_goal' => ['nullable', 'string', 'max:400'],
            'metadata.target_audience' => ['nullable', 'string', 'max:400'],
            'metadata.territorial_scope' => ['nullable', 'string', 'max:250'],
            'metadata.funding_strategy' => ['nullable', 'string', 'max:500'],
            'metadata.implementation_notes' => ['nullable', 'string', 'max:3000'],
            'metadata.risk_notes' => ['nullable', 'string', 'max:3000'],
            'metadata.priority' => ['nullable', 'in:' . implode(',', array_keys($this->projectPriorityOptions()))],
            'metadata.expected_beneficiaries' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'metadata.estimated_budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'metadata.expected_start_date' => ['nullable', 'date'],
            'metadata.expected_end_date' => ['nullable', 'date', 'after_or_equal:metadata.expected_start_date'],
        ]);

        $structuredFields = $this->projectStructuredMetadataFields();
        $changes = [];

        DB::transaction(function () use (
            $project,
            $user,
            $validated,
            $canEditCoreMetadata,
            $structuredFields,
            &$changes
        ) {
            $project->refresh();
            $project->loadMissing('editHistory');

            $coreFields = [
                'title' => 'Título',
                'initial_idea' => 'Idéia inicial',
                'project_type' => 'Tipo do projeto',
                'status' => 'Status',
                'responsible_secretariat' => 'Secretaria responsável',
                'current_phase' => 'Fase atual',
            ];

            if ($canEditCoreMetadata) {
                foreach ($coreFields as $field => $label) {
                    if (!array_key_exists($field, $validated)) {
                        continue;
                    }

                    $newValue = $validated[$field];
                    $oldValue = $project->{$field};

                    if ((string) ($oldValue ?? '') === (string) ($newValue ?? '')) {
                        continue;
                    }

                    $project->{$field} = $newValue;
                    $changes[] = [
                        'field' => $field,
                        'label' => $label,
                        'old' => $oldValue,
                        'new' => $newValue,
                        'scope' => 'core',
                    ];
                }
            }

            $metadata = is_array($project->metadata) ? $project->metadata : [];
            $incomingMetadata = $validated['metadata'] ?? [];

            foreach ($structuredFields as $field => $config) {
                if (!array_key_exists($field, $incomingMetadata)) {
                    continue;
                }

                $newValue = $incomingMetadata[$field];
                $oldValue = data_get($metadata, $field);

                if ((string) ($oldValue ?? '') === (string) ($newValue ?? '')) {
                    continue;
                }

                data_set($metadata, $field, $newValue);
                $changes[] = [
                    'field' => $field,
                    'label' => $config['label'],
                    'old' => $oldValue,
                    'new' => $newValue,
                    'scope' => 'structured',
                ];
            }

            if (empty($changes)) {
                return;
            }

            $project->metadata = $metadata;
            $project->last_edited_by_user_id = $user->id;
            $project->last_edited_at = now();
            $project->save();

            foreach ($changes as $change) {
                $this->logProjectHistory($project, [
                    'user_id' => $user->id,
                    'action' => 'project_metadata_updated',
                    'field_name' => $change['field'],
                    'previous_content' => $change['old'] !== null ? (string) $change['old'] : null,
                    'new_content' => $change['new'] !== null ? (string) $change['new'] : null,
                    'metadata' => [
                        'label' => $change['label'],
                        'scope' => $change['scope'],
                    ],
                ]);
            }
        });

        if (empty($changes)) {
            return $this->redirectToProjectShow($project)
                ->with('warning', 'Nenhuma alteracao de metadados foi identificada para salvar.');
        }

        $project->refresh()->loadMissing('sections');
        $this->revisionService->createRevision($project, $user, 'metadata_updated', [
            'summary' => count($changes) . ' metadados atualizados na ficha do projeto',
        ]);

        $redirect = $this->redirectToProjectShow($project);
        $statusMovedToCompleted = collect($changes)->contains(
            fn(array $change) => $change['field'] === 'status' && $change['new'] === 'concluido'
        );

        if ($statusMovedToCompleted && $this->buildMandateStatusSyncSuggestion($project)) {
            $redirect->with(
                'warning',
                'Projeto concluido. Revise as acoes vinculadas no Mandato para atualizar o status correspondente.'
            );
        }

        return $redirect->with('success', 'Metadados do projeto atualizados com sucesso.');
    }

    public function exportWord(Project $project): Response|RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        if ($this->activePublishedRevision($project)) {
            return $this->redirectToProjectShow($project, ['fragment' => 'project-document'])
                ->with('warning', 'A exportacao do rascunho foi bloqueada porque ja existe uma versão final publicada. Use a exportacao final publicada.');
        }

        $project->loadMissing([
            'municipality',
            'owner',
            'lastEditedBy',
            'sections',
            'collaborators.user',
        ]);

        $this->logProjectHistory($project, [
            'user_id' => Auth::id(),
            'action' => 'project_exported_docx',
            'field_name' => 'export',
            'metadata' => [
                'format' => 'docx',
            ],
        ]);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'project-docx-');
        $this->docxExport->save($project, $temporaryFile);

        return response()->download(
            $temporaryFile,
            $this->exportService->filename($project, 'docx'),
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]
        )->deleteFileAfterSend(true);
    }

    public function exportPublishedWord(Project $project): Response
    {
        $this->authorizeProjectAccess($project);

        $project->loadMissing([
            'municipality',
            'owner',
            'lastEditedBy',
            'sections',
            'collaborators.user',
        ]);

        $publishedRevision = $this->activePublishedRevision($project);
        if (!$publishedRevision) {
            abort(404, 'Nao existe revisão publicada para exportacao final.');
        }

        $this->logProjectHistory($project, [
            'user_id' => Auth::id(),
            'action' => 'project_exported_published_docx',
            'field_name' => 'export',
            'metadata' => [
                'format' => 'docx',
                'revision_number' => $publishedRevision->revision_number,
                'scope' => 'published',
            ],
        ]);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'project-published-docx-');
        $this->docxExport->save($project, $temporaryFile, $publishedRevision);

        return response()->download(
            $temporaryFile,
            $this->exportService->filename($project, 'docx', true),
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]
        )->deleteFileAfterSend(true);
    }

    public function exportPdf(Project $project): Response|RedirectResponse
    {
        $this->authorizeProjectAccess($project);

        if ($this->activePublishedRevision($project)) {
            return $this->redirectToProjectShow($project, ['fragment' => 'project-document'])
                ->with('warning', 'A exportacao do rascunho foi bloqueada porque ja existe uma versão final publicada. Use a exportacao final publicada.');
        }

        $project->loadMissing([
            'municipality',
            'owner',
            'lastEditedBy',
            'sections',
            'collaborators.user',
        ]);

        $this->logProjectHistory($project, [
            'user_id' => Auth::id(),
            'action' => 'project_exported_pdf',
            'field_name' => 'export',
            'metadata' => [
                'format' => 'pdf',
            ],
        ]);

        return Pdf::loadView('mayor.projects.exports.pdf', $this->exportService->buildViewData($project))
            ->setPaper('a4')
            ->download($this->exportService->filename($project, 'pdf'));
    }

    public function exportPublishedPdf(Project $project): Response
    {
        $this->authorizeProjectAccess($project);

        $project->loadMissing([
            'municipality',
            'owner',
            'lastEditedBy',
            'sections',
            'collaborators.user',
        ]);

        $publishedRevision = $this->activePublishedRevision($project);
        if (!$publishedRevision) {
            abort(404, 'Nao existe revisão publicada para exportacao final.');
        }

        $this->logProjectHistory($project, [
            'user_id' => Auth::id(),
            'action' => 'project_exported_published_pdf',
            'field_name' => 'export',
            'metadata' => [
                'format' => 'pdf',
                'revision_number' => $publishedRevision->revision_number,
                'scope' => 'published',
            ],
        ]);

        return Pdf::loadView(
            'mayor.projects.exports.pdf',
            $this->exportService->buildRevisionViewData($project, $publishedRevision)
        )
            ->setPaper('a4')
            ->download($this->exportService->filename($project, 'pdf', true));
    }

    public function openWorkingDraft(Project $project): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeRevisionManagement($project, 'open_working_draft');

        $publishedRevision = $this->activePublishedRevision($project);
        if (!$publishedRevision) {
            return $this->redirectToProjectShow($project, ['fragment' => 'project-revisions'])
                ->with('warning', 'Ainda não existe revisão publicada para abrir um novo rascunho.');
        }

        $latestRevision = $this->activeWorkingDraftRevision($project);
        if ($latestRevision) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $latestRevision->id,
                'fragment' => 'project-revisions',
            ])
                ->with('warning', 'Ja existe uma revisão de trabalho em aberto para continuar a edição.');
        }

        $draftRevision = $this->revisionService->openWorkingDraftFromPublished($project, $publishedRevision, Auth::user());

        return $this->redirectToProjectShow($project, [
            'compare_revision' => $draftRevision->id,
            'fragment' => 'project-revisions',
        ])
            ->with('success', "Novo rascunho aberto a partir da revisão publicada {$publishedRevision->revision_number}.");
    }

    public function approveRevision(Request $request, Project $project, ProjectDocumentRevision $revision): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeRevisionManagement($project, 'approve_revision');
        $this->ensureRevisionBelongsToProject($project, $revision);
        $guardRedirect = $this->guardActiveWorkflowRevision($project, $revision, 'approve_revision');
        if ($guardRedirect) {
            return $guardRedirect;
        }

        $validator = validator($request->all(), [
            'approval_reason' => ['required', 'string', 'min:10', 'max:4000'],
        ]);
        if ($validator->fails()) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->withErrors($validator)->withInput();
        }
        $data = $validator->validated();

        if ($revision->status === 'published') {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Esta revisão ja esta publicada.');
        }

        if (!$this->revisionService->allApprovalStepsCompleted($revision)) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Conclua todas as etapas formais de aprovacao antes de aprovar a revisão.');
        }

        $this->revisionService->approveRevision($revision, Auth::user(), $data['approval_reason']);

        return $this->redirectToProjectShow($project, [
            'compare_revision' => $revision->id,
            'fragment' => 'project-revisions',
        ])
            ->with('success', "Revisão {$revision->revision_number} aprovada com sucesso.");
    }

    public function assignRevisionStepResponsible(
        Request $request,
        Project $project,
        ProjectDocumentRevision $revision,
        string $stepKey
    ): RedirectResponse {
        $this->authorizeProjectAccess($project);
        $this->authorizeRevisionManagement($project, 'assign_revision_step_responsible');
        $this->ensureRevisionBelongsToProject($project, $revision);
        $guardRedirect = $this->guardActiveWorkflowRevision($project, $revision, 'assign_revision_step_responsible');
        if ($guardRedirect) {
            return $guardRedirect;
        }

        $data = $request->validate([
            'responsible_user_id' => ['required', 'integer'],
        ]);

        $normalizedSteps = $this->revisionService->normalizeApprovalSteps($revision->approval_steps);
        $step = collect($normalizedSteps)->firstWhere('key', $stepKey);

        if (!$step) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Etapa de aprovacao não encontrada.');
        }

        $responsibleUser = $this->revisionService
            ->eligibleUsersForApprovalStep($project, $step, $this->approvalEligibleUsers($project))
            ->firstWhere('id', (int) $data['responsible_user_id']);

        if (!$responsibleUser) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'O usuario selecionado não esta elegivel para assumir esta etapa.');
        }

        try {
            $updatedRevision = $this->revisionService->assignApprovalStepResponsible(
                $revision,
                $stepKey,
                $responsibleUser,
                Auth::user()
            );
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', $exception->getMessage());
        }

        $step = collect($updatedRevision->approval_steps)->firstWhere('key', $stepKey);
        $label = $step['label'] ?? 'Etapa';

        return $this->redirectToProjectShow($project, [
            'compare_revision' => $revision->id,
            'fragment' => 'project-revisions',
        ])
            ->with('success', "Responsável definido para {$label}.");
    }

    public function approveRevisionStep(Project $project, ProjectDocumentRevision $revision, string $stepKey): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->ensureRevisionBelongsToProject($project, $revision);
        $guardRedirect = $this->guardActiveWorkflowRevision($project, $revision, 'approve_revision_step');
        if ($guardRedirect) {
            return $guardRedirect;
        }
        $currentUser = Auth::user();

        if ($revision->status === 'published') {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Nao e possivel alterar etapas de aprovacao de uma revisão ja publicada.');
        }

        $step = collect($this->revisionService->normalizeApprovalSteps($revision->approval_steps))->firstWhere('key', $stepKey);
        if (!$step) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Etapa de aprovacao não encontrada.');
        }

        $responsibleId = (int) ($step['responsible_user_id'] ?? 0);
        if ($responsibleId <= 0) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Defina um responsável para esta etapa antes de marca-la como concluida.');
        }

        if ($responsibleId > 0 && $responsibleId !== $currentUser->id) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Esta etapa possui outro responsável designado.');
        }

        if (!$this->revisionService->canUserCompleteApprovalStep($revision, $step, $currentUser)) {
            $this->logPermissionBlocked($project, $currentUser, 'approve_revision_step', $step['required_profile_label'] ?? 'responsável da etapa');

            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'O usuario responsável desta etapa não atende ao perfil exigido para conclui-la.');
        }

        try {
            $updatedRevision = $this->revisionService->completeApprovalStep($revision, $stepKey, $currentUser);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', $exception->getMessage());
        }

        $step = collect($updatedRevision->approval_steps)->firstWhere('key', $stepKey);
        $label = $step['label'] ?? 'Etapa';

        return $this->redirectToProjectShow($project, [
            'compare_revision' => $revision->id,
            'fragment' => 'project-revisions',
        ])
            ->with('success', "{$label} aprovada com sucesso.");
    }

    public function publishRevision(Request $request, Project $project, ProjectDocumentRevision $revision): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeRevisionManagement($project, 'publish_revision');
        $this->ensureRevisionBelongsToProject($project, $revision);
        $guardRedirect = $this->guardActiveWorkflowRevision($project, $revision, 'publish_revision');
        if ($guardRedirect) {
            return $guardRedirect;
        }

        $validator = validator($request->all(), [
            'publication_reason' => ['required', 'string', 'min:10', 'max:4000'],
        ]);
        if ($validator->fails()) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->withErrors($validator)->withInput();
        }
        $data = $validator->validated();

        if (!$this->revisionService->allApprovalStepsCompleted($revision)) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'Conclua todas as etapas formais de aprovacao antes de publicar a versão final.');
        }

        if ($revision->status !== 'approved') {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'A revisão precisa estar aprovada antes da publicacao final.');
        }

        $this->revisionService->publishRevision($revision, Auth::user(), $data['publication_reason']);

        return $this->redirectToProjectShow($project, [
            'compare_revision' => $revision->id,
            'fragment' => 'project-revisions',
        ])
            ->with('success', "Revisão {$revision->revision_number} publicada como versão final.");
    }

    public function restoreRevision(Project $project, ProjectDocumentRevision $revision): RedirectResponse
    {
        $this->authorizeProjectAccess($project);
        $this->authorizeRevisionManagement($project, 'restore_revision');
        $this->ensureRevisionBelongsToProject($project, $revision);

        if ($this->revisionService->isCurrentWorkingRevision($revision) || $this->revisionService->isCurrentPublishedRevision($revision)) {
            return $this->redirectToProjectShow($project, [
                'compare_revision' => $revision->id,
                'fragment' => 'project-revisions',
            ])->with('warning', 'A revisão selecionada ja representa a base atual do projeto. Use revisoes historicas apenas para restauracao.');
        }

        $restoredRevision = $this->revisionService->restoreRevision($project, $revision, Auth::user());

        return $this->redirectToProjectShow($project, [
            'compare_revision' => $restoredRevision->id,
            'fragment' => 'project-revisions',
        ])
            ->with('success', "Conteudo restaurado a partir da revisão {$revision->revision_number}. Uma nova revisão em rascunho foi criada.");
    }

    private function accessibleProjectsQuery(User $user)
    {
        $query = Project::query()
            ->where('municipality_id', $user->municipality_id);

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($builder) use ($user) {
            $builder
                ->where('owner_user_id', $user->id)
                ->orWhereHas('collaborators', fn($collaborators) => $collaborators
                    ->where('user_id', $user->id)
                    ->whereNotNull('accepted_at'));
        });
    }

    private function authorizeProjectAccess(Project $project): void
    {
        $user = Auth::user();

        if ($project->municipality_id !== $user->municipality_id) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return;
        }

        $collaborator = $this->collaboratorMembership($project, $user);
        $isCollaborator = $collaborator && filled($collaborator->accepted_at);

        if ($project->owner_user_id !== $user->id && !$isCollaborator) {
            abort(403);
        }
    }

    private function authorizeProjectEdit(Project $project, ?string $attemptedAction = null): void
    {
        $user = Auth::user();

        if (!$this->canEditWorkingCopy($project, $user)) {
            $this->logPermissionBlocked($project, $user, $attemptedAction ?? 'edit_project', 'editor');
            abort(403, $this->projectEditBlockMessage($project, $user, $attemptedAction));
        }
    }

    private function authorizeCollaboratorManagement(Project $project, ?string $attemptedAction = null): void
    {
        $user = Auth::user();

        if (!$this->canManageCollaborators($project, $user)) {
            $this->logPermissionBlocked($project, $user, $attemptedAction ?? 'manage_collaborators', 'owner');
            abort(403, 'Somente o proprietario do projeto pode gerenciar colaboradores.');
        }
    }

    private function canEditProject(Project $project, User $user): bool
    {
        if ($user->isAdmin() || $project->owner_user_id === $user->id) {
            return true;
        }

        $collaborator = $this->collaboratorMembership($project, $user);

        return (bool) $collaborator
            && filled($collaborator->accepted_at)
            && $collaborator->permission === 'editor';
    }

    private function canEditWorkingCopy(Project $project, User $user): bool
    {
        if (!$this->canEditProject($project, $user)) {
            return false;
        }

        $latestRevision = $project->documentRevisions()->latest('revision_number')->first();
        $hasPublishedRevision = $project->documentRevisions()
            ->where('status', 'published')
            ->exists();

        if (!$hasPublishedRevision) {
            return true;
        }

        return $latestRevision !== null && $latestRevision->status !== 'published';
    }

    private function canEditCoreMetadata(Project $project, User $user): bool
    {
        return $user->isAdmin() || $project->owner_user_id === $user->id;
    }

    private function canManageCollaborators(Project $project, User $user): bool
    {
        return $user->isAdmin() || $project->owner_user_id === $user->id;
    }

    private function canManageRevisions(Project $project, User $user): bool
    {
        return $user->isAdmin() || $project->owner_user_id === $user->id;
    }

    private function canDeleteProject(Project $project, User $user): bool
    {
        return $user->isAdmin() || $project->owner_user_id === $user->id;
    }

    private function activePublishedRevision(Project $project): ?ProjectDocumentRevision
    {
        return $this->revisionService->currentPublishedRevision($project);
    }

    private function activeWorkingDraftRevision(Project $project): ?ProjectDocumentRevision
    {
        return $this->revisionService->currentWorkingRevision($project);
    }

    private function collaboratorMembership(Project $project, User $user): ?ProjectCollaborator
    {
        return $project->collaborators()
            ->where('user_id', $user->id)
            ->first();
    }

    private function projectAccessRole(Project $project, User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin';
        }

        if ($project->owner_user_id === $user->id) {
            return 'owner';
        }

        $collaborator = $this->collaboratorMembership($project, $user);

        if (!$collaborator || blank($collaborator->accepted_at)) {
            return 'guest';
        }

        return $collaborator->permission === 'editor' ? 'editor' : 'viewer';
    }

    private function ensureCollaboratorBelongsToProject(Project $project, ProjectCollaborator $collaborator): void
    {
        if ($collaborator->project_id !== $project->id) {
            abort(404);
        }
    }

    private function ensureSectionBelongsToProject(Project $project, ProjectSection $section): void
    {
        if ($section->project_id !== $project->id) {
            abort(404);
        }
    }

    private function ensureRevisionBelongsToProject(Project $project, ProjectDocumentRevision $revision): void
    {
        if ($revision->project_id !== $project->id) {
            abort(404);
        }
    }

    private function logProjectHistory(Project $project, array $payload): void
    {
        $project->editHistory()->create($payload);
    }

    private function logPermissionBlocked(Project $project, User $user, string $attemptedAction, string $requiredRole): void
    {
        $this->logProjectHistory($project, [
            'user_id' => $user->id,
            'action' => 'project_permission_blocked',
            'field_name' => 'permission',
            'metadata' => [
                'attempted_action' => $attemptedAction,
                'required_role' => $requiredRole,
                'actual_role' => $this->projectAccessRole($project, $user),
            ],
        ]);
    }

    private function persistQuestionnaireAnswers(
        Project $project,
        array $answers,
        User $user,
        string $answerSource = 'manual',
        ?string $contextSummary = null
    ): int {
        $changes = 0;

        DB::transaction(function () use ($project, $answers, $user, $answerSource, $contextSummary, &$changes) {
            $project->loadMissing('intakeQuestions');

            foreach ($project->intakeQuestions as $question) {
                $newAnswer = trim((string) ($answers[$question->id] ?? ''));
                $previousAnswer = trim((string) ($question->answer ?? ''));

                if ($newAnswer === $previousAnswer) {
                    continue;
                }

                $questionMetadata = is_array($question->metadata) ? $question->metadata : [];
                $questionMetadata['answer_source'] = $answerSource;
                $questionMetadata['last_answered_by_user_id'] = $user->id;
                $questionMetadata['last_answered_at'] = now()->toIso8601String();

                if ($contextSummary) {
                    $questionMetadata['answer_context_summary'] = $contextSummary;
                }

                $question->forceFill([
                    'answer' => $newAnswer !== '' ? $newAnswer : null,
                    'answered_at' => $newAnswer !== '' ? now() : null,
                    'metadata' => $questionMetadata,
                ])->save();

                $project->editHistory()->create([
                    'user_id' => $user->id,
                    'project_section_id' => null,
                    'action' => 'project_question_answered',
                    'field_name' => $question->question_key,
                    'previous_content' => $previousAnswer !== '' ? $previousAnswer : null,
                    'new_content' => $newAnswer !== '' ? $newAnswer : null,
                    'metadata' => [
                        'question_text' => $question->question_text,
                        'answer_source' => $answerSource,
                        'context_summary' => $contextSummary,
                    ],
                ]);

                $changes++;
            }

            if ($changes === 0) {
                return;
            }

            $project->forceFill([
                'last_edited_by_user_id' => $user->id,
                'last_edited_at' => now(),
            ])->save();
        });

        return $changes;
    }

    private function updateQuestionnaireAutomationMetadata(
        Project $project,
        string $answerSource,
        ?string $contextSummary = null
    ): void {
        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $questionnaire = is_array($metadata['questionnaire'] ?? null) ? $metadata['questionnaire'] : [];
        $questionnaire['last_answer_source'] = $answerSource;
        $questionnaire['auto_answered_at'] = now()->toIso8601String();

        if ($contextSummary) {
            $questionnaire['last_answer_context_summary'] = $contextSummary;
        }

        $metadata['questionnaire'] = $questionnaire;

        $project->forceFill([
            'metadata' => $metadata,
        ])->save();
    }

    private function authorizeRevisionManagement(Project $project, ?string $attemptedAction = null): void
    {
        $user = Auth::user();

        if (!$this->canManageRevisions($project, $user)) {
            $this->logPermissionBlocked($project, $user, $attemptedAction ?? 'manage_revisions', 'owner');
            abort(403, 'Somente o proprietario do projeto pode aprovar, publicar ou restaurar revisoes.');
        }
    }

    private function applyProjectListFilters($projects, User $user, array $activeFilters)
    {
        $role = $activeFilters['role'] ?? 'all';
        $collaboration = $activeFilters['collaboration'] ?? 'all';

        if ($role !== 'all') {
            $projects = $projects->filter(fn(Project $project) => ($project->current_user_project_role ?? 'guest') === $role);
        }

        return $projects->filter(function (Project $project) use ($collaboration, $user) {
            return match ($collaboration) {
                'owned' => $project->owner_user_id === $user->id,
                'shared' => in_array($project->current_user_project_role ?? 'guest', ['editor', 'viewer'], true),
                'can_edit' => (bool) ($project->current_user_can_edit ?? false),
                'read_only' => !($project->current_user_can_edit ?? false),
                default => true,
            };
        });
    }

    private function normalizedProjectMetadata(Project $project): array
    {
        $metadata = is_array($project->metadata) ? $project->metadata : [];

        $defaults = [];
        foreach ($this->projectStructuredMetadataFields() as $field => $config) {
            $defaults[$field] = data_get($metadata, $field, '');
        }

        return $defaults;
    }

    private function projectStructuredMetadataFields(): array
    {
        return [
            'executive_summary' => ['label' => 'Resumo executivo'],
            'primary_goal' => ['label' => 'Objetivo principal'],
            'target_audience' => ['label' => 'Público beneficiado'],
            'territorial_scope' => ['label' => 'Abrangencia territorial'],
            'funding_strategy' => ['label' => 'Estrategia de financiamento'],
            'implementation_notes' => ['label' => 'Notas de implementacao'],
            'risk_notes' => ['label' => 'Riscos e cuidados'],
            'priority' => ['label' => 'Prioridade'],
            'expected_beneficiaries' => ['label' => 'Beneficiários estimados'],
            'estimated_budget' => ['label' => 'Orcamento estimado'],
            'expected_start_date' => ['label' => 'Previsão de início'],
            'expected_end_date' => ['label' => 'Previsão de conclusao'],
        ];
    }

    private function projectListRoleFilters(): array
    {
        return [
            'all' => 'Todos os papeis',
            'owner' => 'Proprietario',
            'editor' => 'Editor',
            'viewer' => 'Viewer',
            'admin' => 'Administrador',
        ];
    }

    private function projectListCollaborationFilters(): array
    {
        return [
            'all' => 'Todas as colaboracoes',
            'owned' => 'Projetos meus',
            'shared' => 'Compartilhados comigo',
            'can_edit' => 'Com edição',
            'read_only' => 'Somente leitura',
        ];
    }

    private function resolveSelectedRevision($documentRevisions, Request $request, User $user): ?ProjectDocumentRevision
    {
        $selectedRevisionId = $request->integer('compare_revision');

        if ($selectedRevisionId > 0) {
            $selected = $documentRevisions->firstWhere('id', $selectedRevisionId);
            if ($selected) {
                return $selected;
            }
        }

        $actionableRevision = $documentRevisions->first(
            fn(ProjectDocumentRevision $revision) => $this->revisionHasActionableStepForUser($revision, $user)
        );
        if ($actionableRevision) {
            return $actionableRevision;
        }

        $approvedRevision = $documentRevisions->first(
            fn(ProjectDocumentRevision $revision) => $revision->status === 'approved'
        );
        if ($approvedRevision) {
            return $approvedRevision;
        }

        $workflowRevision = $documentRevisions->first(
            fn(ProjectDocumentRevision $revision) => $revision->status === 'draft' && $this->revisionHasWorkflowProgress($revision)
        );
        if ($workflowRevision) {
            return $workflowRevision;
        }

        $draftRevision = $documentRevisions->first(
            fn(ProjectDocumentRevision $revision) => $revision->status === 'draft'
        );
        if ($draftRevision) {
            return $draftRevision;
        }

        return $documentRevisions->first();
    }

    private function hydrateRevisionApprovalData(ProjectDocumentRevision $revision): void
    {
        $steps = $this->revisionService->normalizeApprovalSteps($revision->approval_steps);
        $completedCount = collect($steps)->where('approved', true)->count();

        $revision->setAttribute('approval_steps', $steps);
        $revision->setAttribute('approval_steps_completed_count', $completedCount);
        $revision->setAttribute('approval_steps_total_count', count($steps));
    }

    private function canApproveRevisionSteps(ProjectDocumentRevision $revision, User $user): array
    {
        return collect($this->revisionService->normalizeApprovalSteps($revision->approval_steps))
            ->mapWithKeys(fn(array $step) => [
                $step['key'] => $this->revisionService->canUserCompleteApprovalStep($revision, $step, $user),
            ])
            ->all();
    }

    private function revisionHasActionableStepForUser(ProjectDocumentRevision $revision, User $user): bool
    {
        return collect($this->revisionService->normalizeApprovalSteps($revision->approval_steps))
            ->contains(fn(array $step) => $this->revisionService->canUserCompleteApprovalStep($revision, $step, $user));
    }

    private function revisionHasWorkflowProgress(ProjectDocumentRevision $revision): bool
    {
        if (in_array($revision->status, ['approved', 'published'], true)) {
            return true;
        }

        return collect($this->revisionService->normalizeApprovalSteps($revision->approval_steps))
            ->contains(function (array $step) {
                return (int) ($step['responsible_user_id'] ?? 0) > 0
                    || !empty($step['approved'])
                    || !empty($step['approved_at'])
                    || !empty($step['completed_by_user_id']);
            });
    }

    private function visibleDocumentRevisions($documentRevisions, ?ProjectDocumentRevision $selectedRevision, ?ProjectDocumentRevision $currentWorkingRevision)
    {
        return $documentRevisions
            ->filter(function (ProjectDocumentRevision $revision) use ($selectedRevision, $currentWorkingRevision) {
                if ($selectedRevision && $revision->id === $selectedRevision->id) {
                    return true;
                }

                if ($currentWorkingRevision && $revision->id === $currentWorkingRevision->id) {
                    return true;
                }

                return $revision->status === 'published';
            })
            ->unique('id')
            ->sortByDesc('revision_number')
            ->values();
    }

    private function guardActiveWorkflowRevision(
        Project $project,
        ProjectDocumentRevision $revision,
        string $attemptedAction
    ): ?RedirectResponse {
        if ($this->revisionService->isCurrentWorkingRevision($revision)) {
            return null;
        }

        $currentUser = Auth::user();
        $this->logPermissionBlocked($project, $currentUser, $attemptedAction, 'revisão_ativa');

        return $this->redirectToProjectShow($project, [
            'compare_revision' => $revision->id,
            'fragment' => 'project-revisions',
        ])->with('warning', $this->revisionWorkflowBlockMessage($attemptedAction));
    }

    private function projectEditBlockMessage(Project $project, User $user, ?string $attemptedAction = null): string
    {
        $actionLabel = $this->projectEditActionLabel($attemptedAction);

        if ($this->canEditProject($project, $user)) {
            return "Existe uma versão final publicada como documento oficial. Abra um novo rascunho para {$actionLabel}.";
        }

        return "Este projeto esta aberto para você em modo de visualizacao. Apenas proprietario e colaboradores com permissao de edição podem {$actionLabel}.";
    }

    private function projectEditActionLabel(?string $attemptedAction = null): string
    {
        return match ($attemptedAction) {
            'save_questionnaire_answers' => 'salvar respostas do questionario',
            'regenerate_questionnaire' => 'regenerar perguntas dinâmicas',
            'analyze_overlap' => 'rodar a analise de sobreposição',
            'analyze_funding' => 'rodar a analise de financiamento',
            'generate_document' => 'atualizar o documento',
            'update_section' => 'editar seções do documento',
            'update_project_metadata' => 'atualizar os metadados do projeto',
            'edit_project', null => 'alterar o projeto',
            default => 'executar esta acao',
        };
    }

    private function revisionWorkflowBlockMessage(string $attemptedAction): string
    {
        $actionLabel = match ($attemptedAction) {
            'assign_revision_step_responsible' => 'definir responsaveis das etapas',
            'approve_revision_step' => 'concluir etapas de aprovacao',
            'approve_revision' => 'aprovar a revisão',
            'publish_revision' => 'publicar a versão final',
            default => 'executar esta acao na revisão',
        };

        return "Somente a revisão ativa de trabalho pode {$actionLabel}.";
    }

    private function redirectToProjectShow(Project $project, array $overrides = []): RedirectResponse
    {
        $fragment = $overrides['fragment'] ?? request()->input('return_fragment');
        unset($overrides['fragment']);

        $query = [];
        $returnCompareRevision = request()->input('return_compare_revision');
        if (filled($returnCompareRevision)) {
            $query['compare_revision'] = (int) $returnCompareRevision;
        }

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
                continue;
            }

            $query[$key] = $value;
        }

        $url = route('mayor.projects.show', array_merge(['project' => $project], $query));

        if (filled($fragment)) {
            $url .= '#' . ltrim((string) $fragment, '#');
        }

        return redirect()->to($url);
    }

    private function approvalEligibleUsers(Project $project)
    {
        $acceptedCollaboratorIds = $project->collaborators()
            ->whereNotNull('accepted_at')
            ->pluck('user_id');

        return User::query()
            ->where('municipality_id', $project->municipality_id)
            ->where('is_active', true)
            ->where(function ($query) use ($project, $acceptedCollaboratorIds) {
                $query
                    ->where('id', $project->owner_user_id)
                    ->orWhereIn('id', $acceptedCollaboratorIds);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function buildMandateStatusSyncSuggestion(Project $project): ?array
    {
        if ((string) $project->status !== 'concluido') {
            return null;
        }

        $linkedActions = MandateAction::query()
            ->where('municipality_id', $project->municipality_id)
            ->where('project_id', $project->id)
            ->with('axis:id,name')
            ->orderByDesc('updated_at')
            ->get([
                'id',
                'municipality_id',
                'mandate_axis_id',
                'project_id',
                'title',
                'status',
                'physical_progress',
                'updated_at',
            ]);

        if ($linkedActions->isEmpty()) {
            return null;
        }

        $actionsToReview = $linkedActions
            ->filter(fn(MandateAction $action) => (string) $action->status !== 'concluido')
            ->values();

        if ($actionsToReview->isEmpty()) {
            return null;
        }

        return [
            'linked_actions_count' => $linkedActions->count(),
            'actions_to_review_count' => $actionsToReview->count(),
            'actions' => $actionsToReview->map(fn(MandateAction $action) => [
                'id' => $action->id,
                'title' => $action->title,
                'axis_name' => $action->axis?->name,
                'status_label' => $action->status_label,
                'physical_progress' => (int) ($action->physical_progress ?? 0),
                'edit_url' => route('mayor.mandato.acao.edit', $action->id),
            ])->all(),
        ];
    }

    private function projectTypes(): array
    {
        return [
            'infraestrutura' => 'Infraestrutura',
            'social' => 'Social',
            'ambiental' => 'Ambiental',
            'economico' => 'Economico',
            'institucional' => 'Institucional',
        ];
    }

    private function projectStatuses(): array
    {
        return [
            'em_elaboração' => 'Em elaboração',
            'concluido' => 'Concluido',
            'em_execução' => 'Em execução',
            'captacao_em_andamento' => 'Captação em andamento',
        ];
    }

    private function projectPhaseOptions(): array
    {
        return [
            'estrutura_inicial' => 'Estrutura inicial',
            'questionario_em_andamento' => 'Questionario em andamento',
            'documento_em_revisão' => 'Documento em revisão',
            'analise_de_sobreposição' => 'Analise de sobreposição',
            'captacao_em_planejamento' => 'Captação em planejamento',
            'pronto_para_submissao' => 'Pronto para submissao',
        ];
    }

    private function projectPriorityOptions(): array
    {
        return [
            'baixa' => 'Baixa',
            'media' => 'Media',
            'alta' => 'Alta',
            'urgente' => 'Urgente',
        ];
    }
}
