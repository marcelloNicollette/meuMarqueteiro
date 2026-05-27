<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ResourceOpportunityStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SyncFederalProgramsJob;
use App\Mail\RadarSyncSnapshotMail;
use App\Models\ApiSyncLog;
use App\Models\FederalProgramAlert;
use App\Models\Municipality;
use App\Models\ResourceCurationQueue;
use App\Models\ResourceOpportunity;
use App\Models\ResourceOpportunityCycle;
use App\Models\ResourceSource;
use App\Models\User;
use App\Services\FederalPrograms\DiaryMonitorRadarFetcher;
use App\Services\FederalPrograms\StructuredScrapingRadarFetcher;
use App\Services\Radar\HybridRadarReadService;
use App\Services\Radar\RadarSyncExportService;
use App\Services\Radar\RadarSyncSnapshotService;
use App\Services\Support\RuntimeMailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class FederalProgramsController extends Controller
{
    private const CURATION_AUDIT_LOG = 'radar_curation';

    public function __construct(
        private readonly HybridRadarReadService $radarRead,
        private readonly RadarSyncExportService $radarSyncExport,
        private readonly RadarSyncSnapshotService $radarSyncSnapshot,
        private readonly RuntimeMailConfigService $runtimeMail,
        private readonly DiaryMonitorRadarFetcher $diaryMonitorFetcher,
        private readonly StructuredScrapingRadarFetcher $structuredScrapingFetcher,
    ) {}

    // ── Painel de sincronismo ────────────────────────────────────────────
    public function index(Request $request)
    {
        $this->expireStaleExecutions();
        $this->syncResourceCurationQueue();

        $filters = $this->normalizedHistoryFilters($request);
        $curationFilters = $this->normalizedCurationFilters($request);
        $curationAuditFilters = $this->normalizedCurationAuditFilters($request);

        $municipalities = Municipality::where('subscription_active', true)
            ->withCount(['federalPrograms'])
            ->orderBy('name')
            ->get();
        $reviewers = User::query()
            ->admins()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = $this->radarRead->adminStats();
        $programStats = $this->radarRead->municipalityProgramStats();
        $historyQuery = $this->buildHistoryQuery($filters);
        $curationQueueQuery = $this->buildCurationQueueQuery($curationFilters);
        $curationAuditQuery = $this->buildCurationAuditQuery($curationAuditFilters);
        $sourceRunStats = $this->latestSourceRunStats();
        $sourceRunHistory = $this->latestSourceRunHistory();
        $sourceCatalog = $this->buildSourceCatalog($sourceRunStats);
        $sourceCatalogSummary = $this->buildSourceCatalogSummary($sourceCatalog);
        $sourceRunSummary = $this->buildSourceRunSummary($sourceRunStats, $sourceRunHistory);
        $groupBOperationalSummary = $this->buildPipelineOperationalSummary($sourceCatalog, 'group_b_scraping');
        $groupCOperationalSummary = $this->buildPipelineOperationalSummary($sourceCatalog, 'group_c_diary_monitor');

        $summaryQuery = clone $historyQuery;
        $curationSummaryQuery = clone $curationQueueQuery;

        $syncExecutions = ApiSyncLog::query()
            ->radarFederalPrograms()
            ->whereIn('municipality_id', $municipalities->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('municipality_id')
            ->mapWithKeys(fn (ApiSyncLog $log) => [
                $log->municipality_id => $this->serializeExecution($log),
            ]);
        $busyMunicipalityIds = $syncExecutions
            ->filter(fn (array $execution) => $execution['is_busy'])
            ->keys()
            ->values();
        $history = $historyQuery
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ApiSyncLog $log) => $this->historyRowPayload($log));
        $municipalitySummary = $this->buildMunicipalitySummary($summaryQuery->get());
        $curationQueue = $curationQueueQuery
            ->paginate(12, ['*'], 'curation_page')
            ->withQueryString()
            ->through(fn (ResourceCurationQueue $entry) => $this->serializeCurationQueueEntry($entry));
        $curationSummary = $this->buildCurationQueueSummary($curationSummaryQuery->get());
        $curationKpis = $this->buildCurationOperationalKpis($curationAuditFilters);
        $curationOperatorSummary = $this->buildCurationOperatorSummary($curationAuditFilters);
        $currentOperator = auth()->user();
        $currentOperatorCurationSummary = $this->buildCurrentOperatorCurationSummary($currentOperator);
        $curationLoadBalancing = $this->buildCurationLoadBalancingPayload($reviewers);
        $curationCapacityLimits = $this->buildCurationCapacityLimitsPayload($curationLoadBalancing);
        $curationOperatorComparison = $this->buildCurationOperatorComparisonPayload($reviewers, $curationLoadBalancing);
        $curationSuggestedAssignments = $this->buildSuggestedCurationAssignments($reviewers, $curationLoadBalancing);
        $curationOperatorGoals = $this->buildCurationOperatorGoalsPayload(
            $curationOperatorComparison,
            $curationLoadBalancing,
            $curationKpis
        );
        $curationDistributionPolicies = $this->buildCurationDistributionPoliciesPayload(
            $curationSummary,
            $curationKpis,
            $curationLoadBalancing,
            $curationCapacityLimits
        );
        $curationExecutiveTeam = $this->buildCurationExecutiveTeamPayload(
            $curationSummary,
            $curationKpis,
            $curationLoadBalancing,
            $curationCapacityLimits,
            $curationOperatorComparison
        );
        $curationExceptionsSummary = $this->buildCurationExceptionsSummary();
        $curationExceptionRows = $this->buildCurationExceptionRows();
        $curationAuditHistory = $curationAuditQuery
            ->limit(12)
            ->get()
            ->map(fn (Activity $activity) => $this->serializeCurationAuditActivity($activity));
        $queueHealth = $this->queueHealthPayload();
        $snapshotMailConfig = [
            'enabled' => $this->radarSyncSnapshot->snapshotEnabled(),
            'daily_enabled' => $this->radarSyncSnapshot->dailyEnabled(),
            'weekly_enabled' => $this->radarSyncSnapshot->weeklyEnabled(),
            'recipients' => $this->radarSyncSnapshot->recipientsFromSettings(),
            'mailer' => $this->runtimeMail->activeMailerName(),
            'smtp_runtime_enabled' => $this->runtimeMail->shouldUseRuntimeSmtp(),
        ];

        return view('admin.federal-programs.index', compact(
            'municipalities',
            'stats',
            'programStats',
            'syncExecutions',
            'busyMunicipalityIds',
            'history',
            'municipalitySummary',
            'curationQueue',
            'curationSummary',
            'curationFilters',
            'curationAuditFilters',
            'curationAuditHistory',
            'curationKpis',
            'curationOperatorSummary',
            'currentOperator',
            'currentOperatorCurationSummary',
            'curationLoadBalancing',
            'curationCapacityLimits',
            'curationOperatorComparison',
            'curationOperatorGoals',
            'curationSuggestedAssignments',
            'curationDistributionPolicies',
            'curationExecutiveTeam',
            'curationExceptionsSummary',
            'curationExceptionRows',
            'reviewers',
            'queueHealth',
            'filters',
            'snapshotMailConfig',
            'sourceCatalog',
            'sourceCatalogSummary',
            'sourceRunHistory',
            'sourceRunSummary',
            'groupBOperationalSummary',
            'groupCOperationalSummary',
        ));
    }

    public function assignCurationEntry(Request $request, ResourceCurationQueue $entry)
    {
        $validated = $request->validate([
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ]);

        $assignedUserId = $validated['assigned_to_user_id'] ?? null;

        if ($assignedUserId) {
            $reviewer = User::query()
                ->admins()
                ->active()
                ->whereKey($assignedUserId)
                ->first();

            if (!$reviewer) {
                return redirect()
                    ->back()
                    ->with('status', 'O responsável selecionado precisa ser um administrador ativo.');
            }
        }

        $before = $this->curationAuditSnapshot($entry);
        $entry->update([
            'assigned_to_user_id' => $assignedUserId,
            'priority' => $validated['priority'],
        ]);
        $this->recordCurationAudit(
            'assign',
            $request->user(),
            $entry,
            $before,
            $this->curationAuditSnapshot($entry)
        );

        return redirect()
            ->back()
            ->with('status', 'Fila de curadoria atualizada.');
    }

    public function transitionCurationEntry(Request $request, ResourceCurationQueue $entry)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['start_review', 'approve', 'publish', 'reject'])],
            'decision_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $action = (string) $validated['action'];
        $notes = trim((string) ($validated['decision_notes'] ?? ''));
        $message = $this->applyCurationTransition($entry, $action, $notes, auth()->user());

        return redirect()
            ->back()
            ->with('status', $message);
    }

    public function bulkUpdateCuration(Request $request)
    {
        $validated = $request->validate([
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer', 'exists:resource_curation_queue,id'],
            'bulk_action' => ['required', Rule::in(['assign', 'reprioritize', 'start_review', 'approve', 'publish', 'reject'])],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'decision_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $entryIds = collect($validated['entry_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $bulkAction = (string) $validated['bulk_action'];
        $assignedUserId = $validated['assigned_to_user_id'] ?? null;
        $priority = $validated['priority'] ?? null;
        $notes = trim((string) ($validated['decision_notes'] ?? ''));
        $user = auth()->user();

        if ($entryIds->isEmpty()) {
            return redirect()->back()->with('status', 'Selecione ao menos um item da fila para aplicar a ação em lote.');
        }

        if ($assignedUserId) {
            $reviewer = User::query()
                ->admins()
                ->active()
                ->whereKey($assignedUserId)
                ->first();

            if (!$reviewer) {
                return redirect()->back()->with('status', 'O responsável selecionado precisa ser um administrador ativo.');
            }
        }

        if ($bulkAction === 'reprioritize' && !$priority) {
            return redirect()->back()->with('status', 'Informe a prioridade desejada para a ação em lote.');
        }

        if ($bulkAction === 'assign' && !$assignedUserId && !$priority) {
            return redirect()->back()->with('status', 'Informe um responsável ou uma prioridade para a atribuição em lote.');
        }

        $entries = ResourceCurationQueue::query()
            ->with(['opportunity', 'cycle'])
            ->whereIn('id', $entryIds)
            ->get();

        $processed = 0;

        foreach ($entries as $entry) {
            if ($bulkAction === 'assign' || $bulkAction === 'reprioritize') {
                $before = $this->curationAuditSnapshot($entry);
                $updates = [];

                if ($bulkAction === 'assign') {
                    $updates['assigned_to_user_id'] = $assignedUserId;
                }

                if ($priority) {
                    $updates['priority'] = $priority;
                }

                if ($notes !== '') {
                    $updates['decision_notes'] = $notes;
                }

                if ($updates !== []) {
                    $entry->update($updates);
                    $this->recordCurationAudit(
                        $bulkAction,
                        $user,
                        $entry,
                        $before,
                        $this->curationAuditSnapshot($entry),
                        [
                            'bulk_operation' => true,
                            'selected_count' => $entryIds->count(),
                        ]
                    );
                    $processed++;
                }

                continue;
            }

            $this->applyCurationTransition($entry, $bulkAction, $notes, $user, [
                'bulk_operation' => true,
                'selected_count' => $entryIds->count(),
            ]);
            $processed++;
        }

        $actionLabel = match ($bulkAction) {
            'assign' => 'atribuição em lote',
            'reprioritize' => 'repriorização em lote',
            'start_review' => 'início de revisão em lote',
            'approve' => 'aprovação em lote',
            'publish' => 'publicação em lote',
            'reject' => 'rejeição em lote',
            default => 'ação em lote',
        };

        return redirect()
            ->back()
            ->with('status', "{$processed} item(ns) atualizados via {$actionLabel}.");
    }

    public function confirmSuggestedCurationAssignments(Request $request)
    {
        $validated = $request->validate([
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer', 'exists:resource_curation_queue,id'],
            'decision_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $entryIds = collect($validated['entry_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($entryIds->isEmpty()) {
            return redirect()
                ->back()
                ->with('status', 'Selecione ao menos uma sugestão para confirmar em lote.');
        }

        $reviewers = User::query()
            ->admins()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($reviewers->isEmpty()) {
            return redirect()
                ->back()
                ->with('status', 'Não há operadores ativos disponíveis para confirmar sugestões.');
        }

        $loadBalancing = $this->buildCurationLoadBalancingPayload($reviewers);
        $suggestionContext = $this->buildCurationSuggestionContext($reviewers, $loadBalancing);
        $notes = trim((string) ($validated['decision_notes'] ?? ''));
        $user = $request->user();
        $applied = 0;
        $skipped = 0;

        $entries = ResourceCurationQueue::query()
            ->with(['resourceSource', 'opportunity.resourceSource', 'cycle', 'municipality', 'assignedTo', 'reviewedBy'])
            ->whereIn('id', $entryIds)
            ->whereNull('assigned_to_user_id')
            ->whereIn('queue_status', ['pending', 'in_review', 'approved'])
            ->get();

        foreach ($entries as $entry) {
            $suggestion = $this->resolveSuggestedCurationAssignment($entry, $reviewers, $loadBalancing, $suggestionContext);
            $suggestedReviewerId = (int) ($suggestion['suggested_reviewer_id'] ?? 0);

            if ($suggestedReviewerId <= 0) {
                $skipped++;
                continue;
            }

            $before = $this->curationAuditSnapshot($entry);
            $updates = [
                'assigned_to_user_id' => $suggestedReviewerId,
            ];

            if ($notes !== '') {
                $updates['decision_notes'] = $notes;
            }

            $entry->update($updates);
            $this->recordCurationAudit(
                'apply_suggestion',
                $user,
                $entry,
                $before,
                $this->curationAuditSnapshot($entry),
                [
                    'bulk_operation' => true,
                    'selected_count' => $entryIds->count(),
                    'suggestion_score' => (int) ($suggestion['suggestion_score'] ?? 0),
                    'suggestion_reason' => (string) ($suggestion['suggestion_reason'] ?? ''),
                    'suggested_reviewer_id' => $suggestedReviewerId,
                    'suggested_reviewer_name' => (string) ($suggestion['suggested_reviewer_name'] ?? ''),
                ]
            );
            $applied++;
        }

        $skipped += max($entryIds->count() - $entries->count(), 0);
        $message = "{$applied} sugestão(ões) confirmadas em lote.";

        if ($skipped > 0) {
            $message .= " {$skipped} item(ns) foram ignorados por não  estarem mais elegíveis.";
        }

        return redirect()
            ->back()
            ->with('status', $message);
    }

    public function rebalanceCurationQueue(Request $request)
    {
        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'mode' => ['required', Rule::in(['critical_unassigned', 'high_score_unassigned', 'oldest_unassigned'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $targetUser = User::query()
            ->admins()
            ->active()
            ->whereKey((int) $validated['target_user_id'])
            ->first();

        if (!$targetUser) {
            return redirect()
                ->back()
                ->with('status', 'O operador selecionado para rebalanceamento precisa ser um administrador ativo.');
        }

        $limit = (int) ($validated['limit'] ?? 5);
        $mode = (string) $validated['mode'];
        $entries = $this->buildRebalanceQueueQuery($mode)
            ->limit($limit)
            ->get();

        if ($entries->isEmpty()) {
            return redirect()
                ->back()
                ->with('status', 'Nenhum item elegível foi encontrado para rebalanceamento neste critério.');
        }

        foreach ($entries as $entry) {
            $before = $this->curationAuditSnapshot($entry);
            $entry->update([
                'assigned_to_user_id' => $targetUser->id,
            ]);
            $this->recordCurationAudit(
                'assign',
                $request->user(),
                $entry,
                $before,
                $this->curationAuditSnapshot($entry),
                [
                    'bulk_operation' => true,
                    'rebalance_operation' => true,
                    'rebalance_mode' => $mode,
                    'selected_count' => $entries->count(),
                ]
            );
        }

        return redirect()
            ->back()
            ->with('status', "{$entries->count()} item(ns) sem responsável foram rebalanceados para {$targetUser->name}.");
    }

    public function overflowCurationQueue(Request $request)
    {
        $validated = $request->validate([
            'source_user_id' => ['required', 'integer', 'exists:users,id'],
            'target_user_id' => ['required', 'integer', 'exists:users,id', 'different:source_user_id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $sourceUser = User::query()
            ->admins()
            ->active()
            ->whereKey((int) $validated['source_user_id'])
            ->first();
        $targetUser = User::query()
            ->admins()
            ->active()
            ->whereKey((int) $validated['target_user_id'])
            ->first();

        if (!$sourceUser || !$targetUser) {
            return redirect()
                ->back()
                ->with('status', 'Os operadores do overflow precisam ser administradores ativos.');
        }

        $limit = (int) ($validated['limit'] ?? 3);
        $entries = ResourceCurationQueue::query()
            ->leftJoin('resource_opportunity_cycles as overflow_cycles', 'overflow_cycles.id', '=', 'resource_curation_queue.resource_opportunity_cycle_id')
            ->select('resource_curation_queue.*')
            ->with(['resourceSource', 'opportunity', 'cycle', 'municipality', 'assignedTo', 'reviewedBy'])
            ->where('resource_curation_queue.assigned_to_user_id', $sourceUser->id)
            ->whereIn('resource_curation_queue.queue_status', ['pending', 'approved'])
            ->orderByRaw("
                CASE
                    WHEN resource_curation_queue.sla_due_at IS NULL THEN 3
                    WHEN resource_curation_queue.sla_due_at < NOW() THEN 1
                    WHEN resource_curation_queue.sla_due_at <= NOW() + INTERVAL '24 hours' THEN 2
                    ELSE 3
                END
            ")
            ->orderByRaw("
                CASE resource_curation_queue.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 9
                END
            ")
            ->orderByRaw("COALESCE((overflow_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC")
            ->orderByRaw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at) ASC')
            ->limit($limit)
            ->get();

        if ($entries->isEmpty()) {
            return redirect()
                ->back()
                ->with('status', 'Nenhum item elegível foi encontrado para overflow operacional neste momento.');
        }

        foreach ($entries as $entry) {
            $before = $this->curationAuditSnapshot($entry);
            $entry->update([
                'assigned_to_user_id' => $targetUser->id,
            ]);
            $this->recordCurationAudit(
                'assign',
                $request->user(),
                $entry,
                $before,
                $this->curationAuditSnapshot($entry),
                [
                    'bulk_operation' => true,
                    'overflow_operation' => true,
                    'overflow_from_user_id' => $sourceUser->id,
                    'overflow_to_user_id' => $targetUser->id,
                    'selected_count' => $entries->count(),
                ]
            );
        }

        return redirect()
            ->back()
            ->with('status', "{$entries->count()} item(ns) foram movidos de {$sourceUser->name} para {$targetUser->name} por overflow operacional.");
    }

    public function exportHistoryCsv(Request $request)
    {
        $this->expireStaleExecutions();

        $filters = $this->normalizedHistoryFilters($request);
        $historyRows = $this->buildHistoryQuery($filters)
            ->get()
            ->map(fn (ApiSyncLog $log) => $this->historyRowPayload($log))
            ->values()
            ->all();

        return $this->radarSyncExport->downloadCsv(
            $this->exportFilename('histórico-sync-radar', 'csv'),
            $this->exportFilterRows($filters),
            [
                'ID',
                'Municipio',
                'Status',
                'Modo',
                'Origem Disparo',
                'Fila',
                'Operador',
                'Email Operador',
                'Motivo',
                'Novos',
                'Atualizados',
                'Descartados',
                'Capturados',
                'Salvos',
                'Início',
                'Fim',
                'Duracao (ms)',
                'Autoencerrado',
                'Possivel Travado',
                'Erro',
                'Timeline Consolidada',
            ],
            $this->historyExportRows($historyRows),
        );
    }

    public function exportHistoryXlsx(Request $request)
    {
        $this->expireStaleExecutions();

        $filters = $this->normalizedHistoryFilters($request);
        $historyRows = $this->buildHistoryQuery($filters)
            ->get()
            ->map(fn (ApiSyncLog $log) => $this->historyRowPayload($log))
            ->values()
            ->all();

        return $this->radarSyncExport->downloadXlsx(
            $this->exportFilename('histórico-sync-radar', 'xlsx'),
            [
                [
                    'name' => 'Filtros',
                    'rows' => $this->xlsxFilterSheetRows($filters),
                ],
                [
                    'name' => 'Histórico',
                    'rows' => array_merge([
                        [
                            'ID',
                            'Municipio',
                            'Status',
                            'Modo',
                            'Origem Disparo',
                            'Fila',
                            'Operador',
                            'Email Operador',
                            'Motivo',
                            'Novos',
                            'Atualizados',
                            'Descartados',
                            'Capturados',
                            'Salvos',
                            'Início',
                            'Fim',
                            'Duracao (ms)',
                            'Autoencerrado',
                            'Possivel Travado',
                            'Erro',
                            'Timeline Consolidada',
                        ],
                    ], $this->historyExportRows($historyRows)),
                ],
            ],
        );
    }

    public function exportSummaryCsv(Request $request)
    {
        $this->expireStaleExecutions();

        $filters = $this->normalizedHistoryFilters($request);
        $summaryRows = $this->buildMunicipalitySummary(
            $this->buildHistoryQuery($filters)->get()
        )->all();

        return $this->radarSyncExport->downloadCsv(
            $this->exportFilename('resumo-sync-radar', 'csv'),
            $this->exportFilterRows($filters),
            [
                'Municipio',
                'Execucoes',
                'Sucesso',
                'Falha',
                'Em Execução',
                'Na Fila',
                'Autoencerradas',
                'Reenfileiradas',
                'Ultimo Status',
                'Ultimo Operador',
                'Ultimo Motivo',
                'Ultima Atualizacao',
            ],
            $this->summaryExportRows($summaryRows),
        );
    }

    public function exportSummaryXlsx(Request $request)
    {
        $this->expireStaleExecutions();

        $filters = $this->normalizedHistoryFilters($request);
        $summaryRows = $this->buildMunicipalitySummary(
            $this->buildHistoryQuery($filters)->get()
        )->all();

        return $this->radarSyncExport->downloadXlsx(
            $this->exportFilename('resumo-sync-radar', 'xlsx'),
            [
                [
                    'name' => 'Filtros',
                    'rows' => $this->xlsxFilterSheetRows($filters),
                ],
                [
                    'name' => 'Resumo',
                    'rows' => array_merge([
                        [
                            'Municipio',
                            'Execucoes',
                            'Sucesso',
                            'Falha',
                            'Em Execução',
                            'Na Fila',
                            'Autoencerradas',
                            'Reenfileiradas',
                            'Ultimo Status',
                            'Ultimo Operador',
                            'Ultimo Motivo',
                            'Ultima Atualizacao',
                        ],
                    ], $this->summaryExportRows($summaryRows)),
                ],
            ],
        );
    }

    public function exportCurationAuditCsv(Request $request)
    {
        $filters = $this->normalizedCurationAuditFilters($request);
        $rows = $this->buildCurationAuditQuery($filters)
            ->get()
            ->map(fn (Activity $activity) => $this->serializeCurationAuditActivity($activity))
            ->values()
            ->all();

        return $this->radarSyncExport->downloadCsv(
            $this->exportFilename('auditoria-curadoria-radar', 'csv'),
            $this->curationAuditExportFilterRows($filters),
            [
                'ID',
                'Quando',
                'Operador',
                'Evento',
                'Descrição',
                'Item',
                'Fonte',
                'Municipio',
                'Score',
                'Fila Antes',
                'Fila Depois',
                'Prioridade Antes',
                'Prioridade Depois',
                'Responsável Antes',
                'Responsável Depois',
                'Em Lote',
                'Qtd Lote',
                'Campos Alterados',
                'Notas',
            ],
            $this->curationAuditExportRows($rows),
        );
    }

    public function exportCurationAuditXlsx(Request $request)
    {
        $filters = $this->normalizedCurationAuditFilters($request);
        $rows = $this->buildCurationAuditQuery($filters)
            ->get()
            ->map(fn (Activity $activity) => $this->serializeCurationAuditActivity($activity))
            ->values()
            ->all();

        return $this->radarSyncExport->downloadXlsx(
            $this->exportFilename('auditoria-curadoria-radar', 'xlsx'),
            [
                [
                    'name' => 'Filtros',
                    'rows' => $this->xlsxFilterSheetRows($filters),
                ],
                [
                    'name' => 'Auditoria Curadoria',
                    'rows' => array_merge([
                        [
                            'ID',
                            'Quando',
                            'Operador',
                            'Evento',
                            'Descrição',
                            'Item',
                            'Fonte',
                            'Municipio',
                            'Score',
                            'Fila Antes',
                            'Fila Depois',
                            'Prioridade Antes',
                            'Prioridade Depois',
                            'Responsável Antes',
                            'Responsável Depois',
                            'Em Lote',
                            'Qtd Lote',
                            'Campos Alterados',
                            'Notas',
                        ],
                    ], $this->curationAuditExportRows($rows)),
                ],
            ],
        );
    }

    public function exportCurationQueueCsv(Request $request)
    {
        $filters = $this->normalizedCurationFilters($request);
        $rows = $this->buildCurationQueueQuery($filters)
            ->get()
            ->map(fn (ResourceCurationQueue $entry) => $this->serializeCurationQueueEntry($entry))
            ->values()
            ->all();

        return $this->radarSyncExport->downloadCsv(
            $this->exportFilename('fila-curadoria-radar', 'csv'),
            $this->curationQueueExportFilterRows($filters),
            [
                'ID',
                'Título',
                'Fila',
                'Curadoria',
                'Status do ciclo',
                'Fonte',
                'Municipio',
                'UF',
                'Responsável',
                'Revisado por',
                'Prioridade',
                'SLA',
                'SLA vence em',
                'Score',
                'Razao do match',
                'Entrou na fila',
                'Revisão iniciada',
                'Revisado em',
                'Publicado em',
                'Atualizado em',
                'Notas',
                'URL',
            ],
            $this->curationQueueExportRows($rows),
        );
    }

    public function exportCurationQueueXlsx(Request $request)
    {
        $filters = $this->normalizedCurationFilters($request);
        $rows = $this->buildCurationQueueQuery($filters)
            ->get()
            ->map(fn (ResourceCurationQueue $entry) => $this->serializeCurationQueueEntry($entry))
            ->values()
            ->all();

        return $this->radarSyncExport->downloadXlsx(
            $this->exportFilename('fila-curadoria-radar', 'xlsx'),
            [
                [
                    'name' => 'Filtros',
                    'rows' => $this->xlsxFilterSheetRows($filters),
                ],
                [
                    'name' => 'Fila Curadoria',
                    'rows' => array_merge([
                        [
                            'ID',
                            'Título',
                            'Fila',
                            'Curadoria',
                            'Status do ciclo',
                            'Fonte',
                            'Municipio',
                            'UF',
                            'Responsável',
                            'Revisado por',
                            'Prioridade',
                            'SLA',
                            'SLA vence em',
                            'Score',
                            'Razao do match',
                            'Entrou na fila',
                            'Revisão iniciada',
                            'Revisado em',
                            'Publicado em',
                            'Atualizado em',
                            'Notas',
                            'URL',
                        ],
                    ], $this->curationQueueExportRows($rows)),
                ],
            ],
        );
    }

    public function sendSnapshotEmail(Request $request)
    {
        $period = strtolower((string) $request->input('period', 'daily'));

        if (!in_array($period, ['daily', 'weekly'], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'Periodo invalido para o snapshot do Radar.',
            ], 422);
        }

        $recipients = $this->radarSyncSnapshot->recipientsFromSettings();

        if ($recipients === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Nenhum destinatario do snapshot do Radar esta configurado.',
            ], 409);
        }

        $snapshot = $this->radarSyncSnapshot->buildSnapshot($period);

        try {
            $this->runtimeMail->send($recipients, new RadarSyncSnapshotMail($snapshot));

            return response()->json([
                'ok' => true,
                'message' => 'Snapshot operacional do Radar enviado para o time interno.',
                'period' => $period,
                'recipients' => $recipients,
                'summary' => $snapshot['summary'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Falha ao enviar o snapshot do Radar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Sync de um município via AJAX ────────────────────────────────────
    public function syncMunicipality(Request $request, Municipality $municipality)
    {
        $this->expireStaleExecutions($municipality);

        $force = $request->boolean('force', false);
        $connection = $this->resolveAsyncQueueConnection();

        if (!$connection) {
            return response()->json([
                'ok' => false,
                'message' => 'Nenhuma fila assíncrona está configurada. Ajuste o QUEUE_CONNECTION antes de iniciar o sync.',
            ], 409);
        }

        $activeExecution = $this->activeExecutionForMunicipality($municipality);

        if ($activeExecution) {
            return response()->json([
                'ok' => true,
                'message' => "Ja existe uma sincronizacao em andamento para {$municipality->name}.",
                'execution' => $this->serializeExecution($activeExecution),
            ]);
        }

        $syncLog = $this->createQueuedExecution(
            municipality: $municipality,
            force: $force,
            queuedVia: 'admin_panel',
        );

        try {
            $this->dispatchQueuedExecution($syncLog, $municipality, $force, $connection);
        } catch (\Throwable $e) {
            $syncLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => 'Falha ao enfileirar o sync: ' . $e->getMessage(),
            ]);
            $this->appendAuditEvent(
                $syncLog->fresh(),
                'queue_failed',
                'Falha ao enviar execução para a fila.',
                $this->currentOperatorPayload(),
                ['message' => $e->getMessage()],
            );

            return response()->json([
                'ok' => false,
                'message' => "Nao foi possivel enfileirar o sync de {$municipality->name}.",
                'execution' => $this->serializeExecution($syncLog->fresh()),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => "Sync enfileirado para {$municipality->name} na conexao {$connection}, fila {$this->radarQueueName()}.",
            'execution' => $this->serializeExecution($syncLog->fresh()),
        ]);
    }

    // ── Sync geral — todos os municípios via queue assíncrona ────────────
    public function syncAll(Request $request)
    {
        $this->expireStaleExecutions();

        $connection = $this->resolveAsyncQueueConnection();
        $force = $request->boolean('force', false);

        if (!$connection) {
            return response()->json([
                'ok' => false,
                'message' => 'Nenhuma fila assíncrona está configurada. Ajuste o QUEUE_CONNECTION antes de iniciar o sync.',
            ], 409);
        }

        $municipalities = Municipality::query()
            ->where('subscription_active', true)
            ->orderBy('name')
            ->get();

        $enqueuedIds = [];
        $skipped = 0;
        $failed = 0;

        foreach ($municipalities as $municipality) {
            if ($this->activeExecutionForMunicipality($municipality)) {
                $skipped++;
                continue;
            }

            $syncLog = $this->createQueuedExecution(
                municipality: $municipality,
                force: $force,
                queuedVia: 'admin_sync_all',
            );

            try {
                $this->dispatchQueuedExecution($syncLog, $municipality, $force, $connection);
                $enqueuedIds[] = $municipality->id;
            } catch (\Throwable $e) {
                $failed++;
                $syncLog->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => 'Falha ao enfileirar o sync: ' . $e->getMessage(),
                ]);
                $this->appendAuditEvent(
                    $syncLog->fresh(),
                    'queue_failed',
                    'Falha ao enviar execução para a fila.',
                    $this->currentOperatorPayload(),
                    ['message' => $e->getMessage()],
                );
            }
        }

        return response()->json([
            'ok' => true,
            'message' => "Sincronizacao enfileirada para " . count($enqueuedIds) . " município(s) na conexao {$connection}, fila {$this->radarQueueName()}." .
                ($skipped > 0 ? " {$skipped} município(s) ja tinham sync em andamento." : '') .
                ($failed > 0 ? " {$failed} município(s) falharam ao entrar na fila." : ''),
            'municipality_ids' => $enqueuedIds,
        ]);
    }

    public function retryExecution(Request $request, ApiSyncLog $execution)
    {
        if (!$this->isRadarExecution($execution)) {
            return response()->json([
                'ok' => false,
                'message' => 'Execução de sync não encontrada para o Radar de Recursos.',
            ], 404);
        }

        $execution = $execution->fresh(['municipality']);
        $municipality = $execution?->municipality;
        $connection = $this->resolveAsyncQueueConnection();

        if (!$municipality) {
            return response()->json([
                'ok' => false,
                'message' => 'Municipio da execução não encontrado.',
            ], 404);
        }

        if (!$connection) {
            return response()->json([
                'ok' => false,
                'message' => 'Nenhuma fila assíncrona está configurada. Ajuste o QUEUE_CONNECTION antes de reenfileirar o sync.',
            ], 409);
        }

        $this->expireStaleExecutions($municipality);
        $execution = $execution->fresh();

        if ($execution && $this->executionIsStale($execution)) {
            $execution = $this->closeExecutionAsStale($execution);
        }

        $activeExecution = $this->activeExecutionForMunicipality($municipality);

        if ($activeExecution && $activeExecution->id !== $execution?->id) {
            return response()->json([
                'ok' => true,
                'message' => "Ja existe uma sincronizacao em andamento para {$municipality->name}.",
                'execution' => $this->serializeExecution($activeExecution),
            ]);
        }

        if (!$execution || !$this->canRetryExecution($execution)) {
            return response()->json([
                'ok' => false,
                'message' => 'Apenas execucoes falhadas ou travadas podem ser reenfileiradas.',
            ], 409);
        }

        $reason = $this->validatedAuditReason($request, 'Informe o motivo do reenfileiramento.');
        $force = $request->has('force')
            ? $request->boolean('force')
            : (bool) data_get($execution->error_details, 'force', false);
        $operator = $this->currentOperatorPayload();

        $syncLog = $this->createQueuedExecution(
            municipality: $municipality,
            force: $force,
            queuedVia: 'admin_retry',
            extraDetails: [
                'retry_of_log_id' => $execution->id,
                'retry_of_status' => $execution->status,
                'operation_reason' => $reason,
                'triggered_by' => $operator,
            ],
        );
        $this->appendAuditEvent(
            $syncLog,
            'retry_requested',
            'Reprocessamento solicitado manualmente.',
            $operator,
            [
                'reason' => $reason,
                'retry_of_log_id' => $execution->id,
                'retry_of_status' => $execution->status,
            ],
        );

        try {
            $this->dispatchQueuedExecution($syncLog, $municipality, $force, $connection);
        } catch (\Throwable $e) {
            $syncLog->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => 'Falha ao reenfileirar o sync: ' . $e->getMessage(),
            ]);
            $this->appendAuditEvent(
                $syncLog->fresh(),
                'retry_queue_failed',
                'Falha ao reenfileirar a execução.',
                $operator,
                ['message' => $e->getMessage(), 'reason' => $reason],
            );

            return response()->json([
                'ok' => false,
                'message' => "Nao foi possivel reenfileirar o sync de {$municipality->name}.",
                'execution' => $this->serializeExecution($syncLog->fresh()),
            ], 500);
        }

        $this->appendAuditEvent(
            $execution,
            'retry_linked',
            'Execução anterior vinculada a um novo reenfileiramento.',
            $operator,
            [
                'reason' => $reason,
                'retried_to_log_id' => $syncLog->id,
            ],
            [
                'retried_to_log_id' => $syncLog->id,
                'retried_at' => now()->toIso8601String(),
                'last_operation_reason' => $reason,
                'last_operator' => $operator,
            ],
        );

        return response()->json([
            'ok' => true,
            'message' => "Reprocessamento enfileirado para {$municipality->name} na conexao {$connection}, fila {$this->radarQueueName()}.",
            'execution' => $this->serializeExecution($syncLog->fresh()),
            'previous_execution' => $this->serializeExecution($execution->fresh()),
        ]);
    }

    public function reconcileExecutions(Request $request)
    {
        $municipality = $this->resolveMunicipalityFilter($request);
        $reason = $this->validatedAuditReason($request, 'Informe o motivo da reconciliacao.');
        $operator = $this->currentOperatorPayload();
        $expired = $this->expireStaleExecutions($municipality, $operator, $reason, 'manual_reconcile');

        return response()->json([
            'ok' => true,
            'message' => $expired > 0
                ? "{$expired} execução(oes) stale foram encerradas automaticamente."
                : 'Nenhuma execução stale precisou ser encerrada agora.',
            'expired' => $expired,
            'queue_health' => $this->queueHealthPayload(),
        ]);
    }

    public function retryEligibleExecutions(Request $request)
    {
        $municipality = $this->resolveMunicipalityFilter($request);
        $reason = $this->validatedAuditReason($request, 'Informe o motivo do reenfileiramento em lote.');
        $operator = $this->currentOperatorPayload();
        $expired = $this->expireStaleExecutions($municipality, $operator, $reason, 'bulk_retry_reconcile');
        $connection = $this->resolveAsyncQueueConnection();

        if (!$connection) {
            return response()->json([
                'ok' => false,
                'message' => 'Nenhuma fila assíncrona está configurada. Ajuste o QUEUE_CONNECTION antes de reenfileirar em lote.',
            ], 409);
        }

        $executions = $this->latestRadarExecutionsByMunicipality($municipality);
        $enqueued = 0;
        $skippedBusy = 0;
        $skippedIneligible = 0;
        $failed = 0;
        $municipalityIds = [];

        foreach ($executions as $execution) {
            $execution = $execution->fresh(['municipality']);
            $executionMunicipality = $execution?->municipality;

            if (!$execution || !$executionMunicipality) {
                $skippedIneligible++;
                continue;
            }

            if ($this->activeExecutionForMunicipality($executionMunicipality)) {
                $skippedBusy++;
                continue;
            }

            if (!$this->canRetryExecution($execution)) {
                $skippedIneligible++;
                continue;
            }

            $force = (bool) data_get($execution->error_details, 'force', false);
            $syncLog = $this->createQueuedExecution(
                municipality: $executionMunicipality,
                force: $force,
                queuedVia: 'admin_retry_bulk',
                extraDetails: [
                    'retry_of_log_id' => $execution->id,
                    'retry_of_status' => $execution->status,
                    'operation_reason' => $reason,
                    'triggered_by' => $operator,
                ],
            );
            $this->appendAuditEvent(
                $syncLog,
                'bulk_retry_requested',
                'Execução reenfileirada pela acao em lote.',
                $operator,
                [
                    'reason' => $reason,
                    'retry_of_log_id' => $execution->id,
                    'retry_of_status' => $execution->status,
                ],
            );

            try {
                $this->dispatchQueuedExecution($syncLog, $executionMunicipality, $force, $connection);
                $this->appendAuditEvent(
                    $execution,
                    'bulk_retry_linked',
                    'Execução anterior vinculada ao reenfileiramento em lote.',
                    $operator,
                    [
                        'reason' => $reason,
                        'retried_to_log_id' => $syncLog->id,
                    ],
                    [
                        'retried_to_log_id' => $syncLog->id,
                        'retried_at' => now()->toIso8601String(),
                        'last_operation_reason' => $reason,
                        'last_operator' => $operator,
                    ],
                );
                $enqueued++;
                $municipalityIds[] = $executionMunicipality->id;
            } catch (\Throwable $e) {
                $failed++;
                $syncLog->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => 'Falha ao reenfileirar em lote: ' . $e->getMessage(),
                ]);
                $this->appendAuditEvent(
                    $syncLog->fresh(),
                    'bulk_retry_queue_failed',
                    'Falha ao reenfileirar a execução em lote.',
                    $operator,
                    ['message' => $e->getMessage(), 'reason' => $reason],
                );
            }
        }

        return response()->json([
            'ok' => true,
            'message' => "Reenfileiradas {$enqueued} execução(oes) elegiveis na fila {$this->radarQueueName()}." .
                ($expired > 0 ? " {$expired} stale encerrada(s)." : '') .
                ($skippedBusy > 0 ? " {$skippedBusy} município(s) ja tinham sync ativo." : '') .
                ($skippedIneligible > 0 ? " {$skippedIneligible} execução(oes) não eram elegiveis." : '') .
                ($failed > 0 ? " {$failed} falharam ao reenfileirar." : ''),
            'expired' => $expired,
            'enqueued' => $enqueued,
            'skipped_busy' => $skippedBusy,
            'skipped_ineligible' => $skippedIneligible,
            'failed' => $failed,
            'municipality_ids' => array_values(array_unique($municipalityIds)),
            'queue_health' => $this->queueHealthPayload(),
        ]);
    }

    public function backfillSources(Request $request)
    {
        $validated = $request->validate([
            'dry_run' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
        ]);

        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $force = (bool) ($validated['force'] ?? false);
        $limit = (int) ($validated['limit'] ?? 0);
        $municipalityId = $validated['municipality_id'] ?? null;

        $parameters = [
            '--dry-run' => $dryRun,
            '--force' => $force,
        ];

        if ($limit > 0) {
            $parameters['--limit'] = $limit;
        }

        if ($municipalityId !== null) {
            $parameters['--municipality'] = $municipalityId;
        }

        try {
            Artisan::call('marqueteiro:backfill-radar-sources', $parameters);
            $output = trim(Artisan::output());

            return response()->json([
                'ok' => true,
                'message' => $dryRun
                    ? 'Simulacao de backfill executada com sucesso.'
                    : 'Backfill de fontes executado com sucesso.',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Falha ao executar o backfill: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateSourceConfig(Request $request, ResourceSource $source)
    {
        $validated = $request->validate([
            'source_url' => ['nullable', 'url', 'max:500'],
            'access_guide' => ['nullable', 'string', 'max:2000'],
            'maintenance_notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'scraping_entrypoints' => ['nullable', 'string', 'max:4000'],
            'scraping_path_keywords' => ['nullable', 'string', 'max:2000'],
            'scraping_allowed_hosts' => ['nullable', 'string', 'max:2000'],
            'scraping_excluded_path_keywords' => ['nullable', 'string', 'max:2000'],
            'scraping_required_terms' => ['nullable', 'string', 'max:2000'],
            'scraping_title_terms' => ['nullable', 'string', 'max:2000'],
            'minimum_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'require_strong_signal' => ['nullable', 'boolean'],
            'diary_entrypoints' => ['nullable', 'string', 'max:4000'],
            'diary_allowed_hosts' => ['nullable', 'string', 'max:2000'],
            'diary_path_keywords' => ['nullable', 'string', 'max:2000'],
            'diary_required_terms' => ['nullable', 'string', 'max:2000'],
            'diary_title_terms' => ['nullable', 'string', 'max:2000'],
            'diary_ignore_terms' => ['nullable', 'string', 'max:2000'],
        ]);

        $metadata = is_array($source->source_metadata) ? $source->source_metadata : [];
        $sourceUrl = $validated['source_url'] ?? null;

        if ((string) $source->pipeline_group === 'group_c_diary_monitor') {
            $entrypoints = $this->parseTextareaLines($validated['diary_entrypoints'] ?? null);
            $allowedHosts = $this->parseTextareaLines($validated['diary_allowed_hosts'] ?? null);
            $pathKeywords = $this->parseTextareaLines($validated['diary_path_keywords'] ?? null);
            $requiredTerms = $this->parseTextareaLines($validated['diary_required_terms'] ?? null);
            $titleTerms = $this->parseTextareaLines($validated['diary_title_terms'] ?? null);
            $ignoreTerms = $this->parseTextareaLines($validated['diary_ignore_terms'] ?? null);

            $metadata['primary_entrypoint'] = $entrypoints[0] ?? $sourceUrl;
            $metadata['diary_entrypoints'] = $entrypoints;
            $metadata['diary_allowed_hosts'] = $allowedHosts;
            $metadata['diary_path_keywords'] = $pathKeywords;
            $metadata['diary_required_terms'] = $requiredTerms;
            $metadata['diary_title_terms'] = $titleTerms;
            $metadata['diary_ignore_terms'] = $ignoreTerms;
            $metadata['diary_minimum_score'] = (int) ($validated['minimum_score'] ?? 0);
            $metadata['diary_require_strong_signal'] = $request->boolean('require_strong_signal');
            $metadata['uses_custom_diary_profile'] = true;
        } else {
            $entrypoints = $this->parseTextareaLines($validated['scraping_entrypoints'] ?? null);
            $pathKeywords = $this->parseTextareaLines($validated['scraping_path_keywords'] ?? null);
            $allowedHosts = $this->parseTextareaLines($validated['scraping_allowed_hosts'] ?? null);
            $excludedPathKeywords = $this->parseTextareaLines($validated['scraping_excluded_path_keywords'] ?? null);
            $requiredTerms = $this->parseTextareaLines($validated['scraping_required_terms'] ?? null);
            $titleTerms = $this->parseTextareaLines($validated['scraping_title_terms'] ?? null);

            $metadata['primary_entrypoint'] = $entrypoints[0] ?? $sourceUrl;
            $metadata['scraping_entrypoints'] = $entrypoints;
            $metadata['scraping_path_keywords'] = $pathKeywords;
            $metadata['scraping_allowed_hosts'] = $allowedHosts;
            $metadata['scraping_excluded_path_keywords'] = $excludedPathKeywords;
            $metadata['scraping_required_terms'] = $requiredTerms;
            $metadata['scraping_title_terms'] = $titleTerms;
            $metadata['minimum_score'] = (int) ($validated['minimum_score'] ?? 0);
            $metadata['require_strong_signal'] = $request->boolean('require_strong_signal');
            $metadata['uses_custom_scraping_profile'] = true;
        }

        $source->update([
            'source_url' => $sourceUrl,
            'access_guide' => $validated['access_guide'] ?? null,
            'maintenance_notes' => $validated['maintenance_notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'source_metadata' => $metadata,
        ]);

        return redirect()
            ->route('admin.federal-programs.index')
            ->with('status', "Configuração da fonte {$source->name} atualizada.");
    }

    // ── Detalhes dos programas de um município ───────────────────────────
    public function municipalityPrograms(Municipality $municipality)
    {
        $programs = $this->radarRead->municipalityProgramsPayload($municipality);

        return response()->json([
            'municipality' => $municipality->name,
            'programs' => $programs,
        ]);
    }

    private function normalizedCurationFilters(Request $request): array
    {
        return [
            'queue_status' => (string) $request->query('curation_queue_status', 'all'),
            'curation_status' => (string) $request->query('curation_status', 'all'),
            'source_id' => $request->query('curation_source_id') ? (string) $request->query('curation_source_id') : '',
            'municipality_id' => $request->query('curation_municipality_id') ? (string) $request->query('curation_municipality_id') : '',
            'assigned_to_user_id' => $request->query('curation_assigned_to_user_id') ? (string) $request->query('curation_assigned_to_user_id') : '',
            'priority' => (string) $request->query('curation_priority', 'all'),
            'sla_bucket' => (string) $request->query('curation_sla_bucket', 'all'),
            'min_score' => trim((string) $request->query('curation_min_score', '')),
            'sort' => (string) $request->query('curation_sort', 'priority_score_recent'),
            'search' => trim((string) $request->query('curation_search', '')),
        ];
    }

    private function normalizedCurationAuditFilters(Request $request): array
    {
        return [
            'period' => (string) $request->query('curation_audit_period', '7d'),
            'event' => (string) $request->query('curation_audit_event', 'all'),
            'causer_id' => $request->query('curation_audit_causer_id') ? (string) $request->query('curation_audit_causer_id') : '',
            'source_id' => $request->query('curation_audit_source_id') ? (string) $request->query('curation_audit_source_id') : '',
            'municipality_id' => $request->query('curation_audit_municipality_id') ? (string) $request->query('curation_audit_municipality_id') : '',
        ];
    }

    private function buildCurationAuditQuery(array $filters)
    {
        $query = Activity::query()
            ->where('log_name', self::CURATION_AUDIT_LOG)
            ->with(['causer', 'subject']);

        if (($filters['event'] ?? 'all') !== 'all') {
            $query->where('event', $filters['event']);
        }

        if (filled($filters['causer_id'] ?? null)) {
            $query
                ->where('causer_type', User::class)
                ->where('causer_id', (int) $filters['causer_id']);
        }

        if (filled($filters['source_id'] ?? null)) {
            $source = ResourceSource::query()->find((int) $filters['source_id']);
            $sourceName = $source?->name;

            if ($sourceName) {
                $query->where(function ($builder) use ($sourceName) {
                    $builder
                        ->where('properties->after->source_name', $sourceName)
                        ->orWhere('properties->before->source_name', $sourceName);
                });
            }
        }

        if (filled($filters['municipality_id'] ?? null)) {
            $municipality = Municipality::query()->find((int) $filters['municipality_id']);
            $municipalityName = $municipality?->name;

            if ($municipalityName) {
                $query->where(function ($builder) use ($municipalityName) {
                    $builder
                        ->where('properties->after->municipality_name', $municipalityName)
                        ->orWhere('properties->before->municipality_name', $municipalityName);
                });
            }
        }

        if ($periodStart = $this->curationAuditPeriodStart((string) ($filters['period'] ?? '7d'))) {
            $query->where('created_at', '>=', $periodStart);
        }

        return $query->latest('id');
    }

    private function buildCurationQueueQuery(array $filters)
    {
        $query = ResourceCurationQueue::query()
            ->leftJoin('resource_opportunity_cycles as curation_cycles', 'curation_cycles.id', '=', 'resource_curation_queue.resource_opportunity_cycle_id')
            ->select('resource_curation_queue.*')
            ->with([
                'resourceSource:id,key,name,pipeline_group',
                'opportunity.resourceSource:id,key,name,pipeline_group',
                'cycle',
                'municipality:id,name,state_code',
                'assignedTo:id,name',
                'reviewedBy:id,name',
            ]);

        if (($filters['queue_status'] ?? 'all') !== 'all') {
            $query->where('queue_status', $filters['queue_status']);
        }

        if (($filters['curation_status'] ?? 'all') !== 'all') {
            $query->whereHas('opportunity', fn ($opportunityQuery) => $opportunityQuery->where('curation_status', $filters['curation_status']));
        }

        if (filled($filters['source_id'] ?? null)) {
            $query->where('resource_source_id', (int) $filters['source_id']);
        }

        if (filled($filters['municipality_id'] ?? null)) {
            $query->where('municipality_id', (int) $filters['municipality_id']);
        }

        if (filled($filters['assigned_to_user_id'] ?? null)) {
            if ($filters['assigned_to_user_id'] === 'unassigned') {
                $query->whereNull('assigned_to_user_id');
            } else {
                $query->where('assigned_to_user_id', (int) $filters['assigned_to_user_id']);
            }
        }

        if (($filters['priority'] ?? 'all') !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (($filters['min_score'] ?? '') !== '' && is_numeric($filters['min_score'])) {
            $query->whereRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) >= ?", [
                (float) $filters['min_score'],
            ]);
        }

        if (($filters['sla_bucket'] ?? 'all') !== 'all') {
            $openStatuses = ['pending', 'in_review', 'approved'];
            $now = now();
            $nextDay = now()->addDay();

            if ($filters['sla_bucket'] === 'overdue') {
                $query
                    ->whereIn('queue_status', $openStatuses)
                    ->whereNotNull('resource_curation_queue.sla_due_at')
                    ->where('resource_curation_queue.sla_due_at', '<', $now);
            }

            if ($filters['sla_bucket'] === 'due_soon') {
                $query
                    ->whereIn('queue_status', $openStatuses)
                    ->whereNotNull('resource_curation_queue.sla_due_at')
                    ->whereBetween('resource_curation_queue.sla_due_at', [$now, $nextDay]);
            }

            if ($filters['sla_bucket'] === 'on_track') {
                $query
                    ->whereIn('queue_status', $openStatuses)
                    ->whereNotNull('resource_curation_queue.sla_due_at')
                    ->where('resource_curation_queue.sla_due_at', '>', $nextDay);
            }

            if ($filters['sla_bucket'] === 'no_sla') {
                $query->whereNull('resource_curation_queue.sla_due_at');
            }
        }

        if ($search = ($filters['search'] ?? '')) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->whereHas('opportunity', fn ($opportunityQuery) => $opportunityQuery->where('title', 'like', '%' . $search . '%'))
                    ->orWhereHas('resourceSource', fn ($sourceQuery) => $sourceQuery->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('municipality', fn ($municipalityQuery) => $municipalityQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        return $query
            ->orderByRaw("
                CASE queue_status
                    WHEN 'pending' THEN 1
                    WHEN 'in_review' THEN 2
                    WHEN 'enriched' THEN 3
                    WHEN 'approved' THEN 4
                    WHEN 'published' THEN 5
                    WHEN 'rejected' THEN 6
                    ELSE 9
                END
            ")
            ->when(($filters['sort'] ?? 'priority_score_recent') === 'priority_score_recent', function ($builder) {
                $builder
                    ->orderByRaw("
                        CASE priority
                            WHEN 'urgent' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'normal' THEN 3
                            WHEN 'low' THEN 4
                            ELSE 9
                        END
                    ")
                    ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC")
                    ->orderByDesc(DB::raw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at)'));
            })
            ->when(($filters['sort'] ?? '') === 'match_score_desc', function ($builder) {
                $builder
                    ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC")
                    ->orderByRaw("
                        CASE priority
                            WHEN 'urgent' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'normal' THEN 3
                            WHEN 'low' THEN 4
                            ELSE 9
                        END
                    ")
                    ->orderByDesc(DB::raw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at)'));
            })
            ->when(($filters['sort'] ?? '') === 'recent_first', function ($builder) {
                $builder
                    ->orderByDesc(DB::raw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at)'))
                    ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC");
            })
            ->when(($filters['sort'] ?? '') === 'oldest_first', function ($builder) {
                $builder
                    ->orderBy(DB::raw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at)'))
                    ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC");
            })
            ->when(($filters['sort'] ?? '') === 'source_then_score', function ($builder) {
                $builder
                    ->orderBy('resource_curation_queue.resource_source_id')
                    ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC")
                    ->orderByDesc(DB::raw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at)'));
            })
            ->when(($filters['sort'] ?? '') === 'sla_then_score', function ($builder) {
                $builder
                    ->orderByRaw("
                        CASE
                            WHEN resource_curation_queue.sla_due_at IS NULL THEN 4
                            WHEN resource_curation_queue.sla_due_at < NOW() THEN 1
                            WHEN resource_curation_queue.sla_due_at <= NOW() + INTERVAL '24 hours' THEN 2
                            ELSE 3
                        END
                    ")
                    ->orderBy('resource_curation_queue.sla_due_at')
                    ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC");
            })
            ->orderByDesc('resource_curation_queue.updated_at');
    }

    private function buildCurationQueueSummary($entries): array
    {
        $rows = collect($entries);
        $openRows = $rows->whereIn('queue_status', ['pending', 'in_review', 'approved']);
        $now = now();
        $nextDay = now()->copy()->addDay();
        $pendingCount = $rows->where('queue_status', 'pending')->count();
        $inReviewCount = $rows->where('queue_status', 'in_review')->count();
        $approvedCount = $rows->where('queue_status', 'approved')->count();
        $publishedCount = $rows->where('queue_status', 'published')->count();
        $rejectedCount = $rows->where('queue_status', 'rejected')->count();
        $unassignedCount = $rows->whereNull('assigned_to_user_id')->count();
        $highPriorityCount = $rows->whereIn('priority', ['high', 'urgent'])->count();
        $overdueCount = $openRows
            ->filter(fn ($entry) => $entry->sla_due_at && $entry->sla_due_at->lt($now))
            ->count();
        $dueSoonCount = $openRows
            ->filter(fn ($entry) => $entry->sla_due_at && $entry->sla_due_at->gte($now) && $entry->sla_due_at->lte($nextDay))
            ->count();

        return [
            'total' => $rows->count(),
            'pending' => $pendingCount,
            'in_review' => $inReviewCount,
            'approved' => $approvedCount,
            'published' => $publishedCount,
            'rejected' => $rejectedCount,
            'unassigned' => $unassignedCount,
            'high_priority' => $highPriorityCount,
            'needs_attention' => $pendingCount + $inReviewCount,
            'overdue' => $overdueCount,
            'due_soon' => $dueSoonCount,
            'backlog_open' => $pendingCount + $inReviewCount + $approvedCount,
        ];
    }

    private function buildCurrentOperatorCurationSummary(?User $user): array
    {
        if (!$user) {
            return [
                'name' => 'Operador',
                'my_total' => 0,
                'my_pending' => 0,
                'my_in_review' => 0,
                'my_approved' => 0,
                'my_overdue' => 0,
                'my_due_soon' => 0,
                'recent_decisions' => 0,
            ];
        }

        $baseQuery = ResourceCurationQueue::query()->where('assigned_to_user_id', $user->id);
        $openQuery = (clone $baseQuery)->whereIn('queue_status', ['pending', 'in_review', 'approved']);
        $now = now();
        $nextDay = now()->copy()->addDay();

        return [
            'name' => $user->name,
            'my_total' => (clone $openQuery)->count(),
            'my_pending' => (clone $baseQuery)->where('queue_status', 'pending')->count(),
            'my_in_review' => (clone $baseQuery)->where('queue_status', 'in_review')->count(),
            'my_approved' => (clone $baseQuery)->where('queue_status', 'approved')->count(),
            'my_overdue' => (clone $openQuery)
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', $now)
                ->count(),
            'my_due_soon' => (clone $openQuery)
                ->whereNotNull('sla_due_at')
                ->whereBetween('sla_due_at', [$now, $nextDay])
                ->count(),
            'recent_decisions' => ResourceCurationQueue::query()
                ->where('reviewed_by_user_id', $user->id)
                ->whereNotNull('reviewed_at')
                ->where('reviewed_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }

    private function buildCurationLoadBalancingPayload($reviewers): array
    {
        $reviewerRows = collect($reviewers)->values();
        $reviewerCount = $reviewerRows->count();
        $openStatuses = ['pending', 'in_review', 'approved'];
        $now = now();
        $nextDay = now()->copy()->addDay();
        $openEntries = ResourceCurationQueue::query()
            ->whereIn('queue_status', $openStatuses)
            ->get(['id', 'assigned_to_user_id', 'queue_status', 'priority', 'sla_due_at']);
        $decisionsByReviewer = ResourceCurationQueue::query()
            ->whereNotNull('reviewed_by_user_id')
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', now()->subDays(7))
            ->selectRaw('reviewed_by_user_id, COUNT(*) as decisions_count')
            ->groupBy('reviewed_by_user_id')
            ->pluck('decisions_count', 'reviewed_by_user_id');
        $unassignedOpen = $openEntries->whereNull('assigned_to_user_id');
        $unassignedOpenCount = $unassignedOpen->count();
        $assignedOpenTotal = $openEntries->whereNotNull('assigned_to_user_id')->count();
        $targetLoad = $reviewerCount > 0 ? (int) ceil(($assignedOpenTotal + $unassignedOpenCount) / $reviewerCount) : 0;

        $rows = $reviewerRows
            ->map(function (User $reviewer) use ($openEntries, $decisionsByReviewer, $now, $nextDay, $targetLoad, $unassignedOpenCount) {
                $assignedEntries = $openEntries->where('assigned_to_user_id', $reviewer->id)->values();
                $openCount = $assignedEntries->count();
                $pendingCount = $assignedEntries->where('queue_status', 'pending')->count();
                $inReviewCount = $assignedEntries->where('queue_status', 'in_review')->count();
                $approvedCount = $assignedEntries->where('queue_status', 'approved')->count();
                $overdueCount = $assignedEntries->filter(fn ($entry) => $entry->sla_due_at && $entry->sla_due_at->lt($now))->count();
                $dueSoonCount = $assignedEntries->filter(fn ($entry) => $entry->sla_due_at && $entry->sla_due_at->between($now, $nextDay))->count();
                $highPriorityCount = $assignedEntries->whereIn('priority', ['high', 'urgent'])->count();
                $suggestedIntake = max($targetLoad - $openCount, 0);
                $loadState = $openCount > max($targetLoad + 2, 4) ? 'overloaded' : ($openCount < max($targetLoad - 1, 1) ? 'available' : 'balanced');

                return [
                    'id' => $reviewer->id,
                    'name' => $reviewer->name,
                    'open_count' => $openCount,
                    'pending_count' => $pendingCount,
                    'in_review_count' => $inReviewCount,
                    'approved_count' => $approvedCount,
                    'overdue_count' => $overdueCount,
                    'due_soon_count' => $dueSoonCount,
                    'high_priority_count' => $highPriorityCount,
                    'recent_decisions' => (int) ($decisionsByReviewer[$reviewer->id] ?? 0),
                    'suggested_intake' => min($suggestedIntake, $unassignedOpenCount),
                    'load_state' => $loadState,
                    'load_state_label' => match ($loadState) {
                        'available' => 'Pode receber mais itens',
                        'overloaded' => 'Carga alta',
                        default => 'Carga equilibrada',
                    },
                    'load_state_tone' => match ($loadState) {
                        'available' => '#047857',
                        'overloaded' => '#b91c1c',
                        default => '#1d4ed8',
                    },
                ];
            })
            ->sortBy([
                ['open_count', 'asc'],
                ['overdue_count', 'asc'],
                ['in_review_count', 'asc'],
            ])
            ->values();

        $suggestedTargetId = (int) ($rows->first()['id'] ?? 0);

        return [
            'reviewers' => $rows
                ->map(function (array $row) use ($suggestedTargetId) {
                    $row['is_suggested_target'] = $row['id'] === $suggestedTargetId;

                    return $row;
                })
                ->all(),
            'reviewers_count' => $reviewerCount,
            'open_total' => $openEntries->count(),
            'unassigned_open' => $unassignedOpenCount,
            'unassigned_overdue' => $unassignedOpen->filter(fn ($entry) => $entry->sla_due_at && $entry->sla_due_at->lt($now))->count(),
            'target_load' => $targetLoad,
            'suggested_target_id' => $suggestedTargetId,
            'suggested_target_name' => (string) ($rows->first()['name'] ?? ''),
        ];
    }

    private function buildRebalanceQueueQuery(string $mode)
    {
        $query = ResourceCurationQueue::query()
            ->leftJoin('resource_opportunity_cycles as curation_cycles', 'curation_cycles.id', '=', 'resource_curation_queue.resource_opportunity_cycle_id')
            ->select('resource_curation_queue.*')
            ->with(['resourceSource', 'opportunity', 'cycle', 'municipality', 'assignedTo', 'reviewedBy'])
            ->whereNull('resource_curation_queue.assigned_to_user_id')
            ->whereIn('resource_curation_queue.queue_status', ['pending', 'in_review', 'approved']);

        if ($mode === 'critical_unassigned') {
            return $query
                ->whereNotNull('resource_curation_queue.sla_due_at')
                ->where('resource_curation_queue.sla_due_at', '<', now())
                ->orderBy('resource_curation_queue.sla_due_at')
                ->orderByRaw("
                    CASE resource_curation_queue.priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'normal' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 9
                    END
                ")
                ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC")
                ->orderByRaw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at) ASC');
        }

        if ($mode === 'high_score_unassigned') {
            return $query
                ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC")
                ->orderByRaw("
                    CASE resource_curation_queue.priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'normal' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 9
                    END
                ")
                ->orderByRaw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at) ASC');
        }

        return $query
            ->orderByRaw('COALESCE(resource_curation_queue.entered_queue_at, resource_curation_queue.created_at) ASC')
            ->orderByRaw("COALESCE((curation_cycles.cycle_metadata->>'match_score')::numeric, 0) DESC");
    }

    private function buildCurationCapacityLimitsPayload(array $loadBalancing): array
    {
        $rows = collect($loadBalancing['reviewers'] ?? [])->values();
        $targetLoad = (int) ($loadBalancing['target_load'] ?? 0);
        $softLimit = max($targetLoad + 1, 3);
        $hardLimit = max($targetLoad + 3, 5);
        $availableTargets = $rows
            ->filter(fn (array $row) => (int) ($row['open_count'] ?? 0) < $softLimit)
            ->sortBy([
                ['open_count', 'asc'],
                ['overdue_count', 'asc'],
            ])
            ->values();

        $reviewers = $rows
            ->map(function (array $row) use ($softLimit, $hardLimit, $availableTargets) {
                $openCount = (int) ($row['open_count'] ?? 0);
                $overflowCount = max($openCount - $hardLimit, 0);
                $softExcess = max($openCount - $softLimit, 0);
                $recommendedReceive = max($softLimit - $openCount, 0);
                $limitState = $overflowCount > 0 ? 'overflow' : ($softExcess > 0 ? 'warning' : 'healthy');
                $suggestedOverflowTarget = $availableTargets
                    ->first(fn (array $target) => (int) ($target['id'] ?? 0) !== (int) ($row['id'] ?? 0));

                return array_merge($row, [
                    'soft_limit' => $softLimit,
                    'hard_limit' => $hardLimit,
                    'soft_excess' => $softExcess,
                    'overflow_count' => $overflowCount,
                    'recommended_receive' => $recommendedReceive,
                    'limit_state' => $limitState,
                    'limit_state_label' => match ($limitState) {
                        'overflow' => 'Overflow operacional',
                        'warning' => 'Acima do recomendado',
                        default => 'Dentro do limite',
                    },
                    'limit_state_tone' => match ($limitState) {
                        'overflow' => '#b91c1c',
                        'warning' => '#b45309',
                        default => '#047857',
                    },
                    'suggested_overflow_target_id' => (int) ($suggestedOverflowTarget['id'] ?? 0),
                    'suggested_overflow_target_name' => (string) ($suggestedOverflowTarget['name'] ?? ''),
                ]);
            })
            ->all();

        return [
            'soft_limit' => $softLimit,
            'hard_limit' => $hardLimit,
            'overflow_reviewers' => collect($reviewers)->where('limit_state', 'overflow')->count(),
            'warning_reviewers' => collect($reviewers)->where('limit_state', 'warning')->count(),
            'available_receivers' => $availableTargets->count(),
            'reviewers' => $reviewers,
        ];
    }

    private function buildCurationDistributionPoliciesPayload(
        array $curationSummary,
        array $curationKpis,
        array $loadBalancing,
        array $capacityLimits
    ): array {
        $policies = [
            [
                'key' => 'critical_unassigned',
                'title' => 'Distribuir SLA vencido sem responsável',
                'status' => ($loadBalancing['unassigned_overdue'] ?? 0) > 0 ? 'action_required' : 'healthy',
                'status_label' => ($loadBalancing['unassigned_overdue'] ?? 0) > 0 ? 'Ação imediata' : 'Sob controle',
                'tone' => ($loadBalancing['unassigned_overdue'] ?? 0) > 0 ? '#b91c1c' : '#047857',
                'metric' => (int) ($loadBalancing['unassigned_overdue'] ?? 0),
                'description' => 'Itens sem responsável e com SLA vencido devem ser redistribuídos antes da nova triagem.',
                'filters' => [
                    'curation_assigned_to_user_id' => 'unassigned',
                    'curation_sla_bucket' => 'overdue',
                    'curation_sort' => 'sla_then_score',
                    'curation_page' => 1,
                ],
            ],
            [
                'key' => 'soft_limit',
                'title' => 'Respeitar soft limit antes do overflow',
                'status' => ($capacityLimits['warning_reviewers'] ?? 0) > 0 ? 'warning' : 'healthy',
                'status_label' => ($capacityLimits['warning_reviewers'] ?? 0) > 0 ? 'Atenção' : 'Saudável',
                'tone' => ($capacityLimits['warning_reviewers'] ?? 0) > 0 ? '#b45309' : '#047857',
                'metric' => (int) ($capacityLimits['warning_reviewers'] ?? 0),
                'description' => 'Quando a fila ultrapassa o limite recomendado, o time deve redistribuir antes de acumular revisão.',
                'filters' => [
                    'curation_queue_status' => 'all',
                    'curation_sort' => 'priority_score_recent',
                    'curation_page' => 1,
                ],
            ],
            [
                'key' => 'overflow',
                'title' => 'Acionar overflow só em itens transferíveis',
                'status' => ($capacityLimits['overflow_reviewers'] ?? 0) > 0 ? 'action_required' : 'healthy',
                'status_label' => ($capacityLimits['overflow_reviewers'] ?? 0) > 0 ? 'Overflow ativo' : 'Sem overflow',
                'tone' => ($capacityLimits['overflow_reviewers'] ?? 0) > 0 ? '#b91c1c' : '#047857',
                'metric' => (int) ($capacityLimits['overflow_reviewers'] ?? 0),
                'description' => 'Overflow deve mover apenas itens pendentes ou aprovados; revisão ativa permanece com o operador atual.',
                'filters' => [
                    'curation_queue_status' => 'all',
                    'curation_sort' => 'oldest_first',
                    'curation_page' => 1,
                ],
            ],
            [
                'key' => 'high_score',
                'title' => 'Priorizar score alto ainda sem dono',
                'status' => ($loadBalancing['unassigned_open'] ?? 0) > 0 ? 'warning' : 'healthy',
                'status_label' => ($loadBalancing['unassigned_open'] ?? 0) > 0 ? 'Monitorar' : 'Sob controle',
                'tone' => ($loadBalancing['unassigned_open'] ?? 0) > 0 ? '#1d4ed8' : '#047857',
                'metric' => (int) ($loadBalancing['unassigned_open'] ?? 0),
                'description' => 'Itens sem responsável com bom score devem ir primeiro para operadores abaixo da carga-alvo.',
                'filters' => [
                    'curation_assigned_to_user_id' => 'unassigned',
                    'curation_min_score' => '0.70',
                    'curation_sort' => 'match_score_desc',
                    'curation_page' => 1,
                ],
            ],
            [
                'key' => 'assignment_coverage',
                'title' => 'Cobertura mínima de responsável',
                'status' => ((int) ($curationKpis['assignment_coverage'] ?? 0)) < 80 ? 'warning' : 'healthy',
                'status_label' => ((int) ($curationKpis['assignment_coverage'] ?? 0)) < 80 ? 'Abaixo da meta' : 'Dentro da meta',
                'tone' => ((int) ($curationKpis['assignment_coverage'] ?? 0)) < 80 ? '#b45309' : '#047857',
                'metric' => (int) ($curationKpis['assignment_coverage'] ?? 0),
                'description' => 'A cobertura de responsável da fila aberta deve permanecer acima de 80% para evitar gargalo de triagem.',
                'filters' => [
                    'curation_assigned_to_user_id' => 'unassigned',
                    'curation_queue_status' => 'all',
                    'curation_page' => 1,
                ],
            ],
        ];

        return [
            'items' => $policies,
            'action_required' => collect($policies)->where('status', 'action_required')->count(),
            'warnings' => collect($policies)->where('status', 'warning')->count(),
            'healthy' => collect($policies)->where('status', 'healthy')->count(),
            'policy_count' => count($policies),
            'open_backlog' => (int) ($curationSummary['backlog_open'] ?? 0),
        ];
    }

    private function buildCurationExecutiveTeamPayload(
        array $curationSummary,
        array $curationKpis,
        array $loadBalancing,
        array $capacityLimits,
        array $operatorComparison
    ): array {
        $teamSize = max((int) ($loadBalancing['reviewers_count'] ?? 0), 1);
        $openBacklog = (int) ($curationSummary['backlog_open'] ?? 0);
        $avgOpenPerReviewer = round($openBacklog / $teamSize, 1);
        $reviewers = collect($operatorComparison['reviewers'] ?? []);
        $topPublisher = (array) ($reviewers->sortByDesc('published_count')->first() ?? []);
        $fastestReviewer = (array) ($reviewers
            ->filter(fn (array $row) => (float) ($row['avg_decision_hours'] ?? 0) > 0)
            ->sortBy('avg_decision_hours')
            ->first() ?? []);
        $teamPublishRate = $reviewers->sum('reviewed_count') > 0
            ? (int) round(($reviewers->sum('published_count') / max($reviewers->sum('reviewed_count'), 1)) * 100)
            : 0;
        $executiveState = match (true) {
            ((int) ($capacityLimits['overflow_reviewers'] ?? 0)) > 0 || ((int) ($loadBalancing['unassigned_overdue'] ?? 0)) > 0 => 'critical',
            ((int) ($capacityLimits['warning_reviewers'] ?? 0)) > 0 || ((int) ($curationKpis['overdue_open'] ?? 0)) > 0 => 'attention',
            default => 'healthy',
        };

        return [
            'state' => $executiveState,
            'state_label' => match ($executiveState) {
                'critical' => 'Pressão crítica',
                'attention' => 'Atenção operacional',
                default => 'Operação estável',
            },
            'state_tone' => match ($executiveState) {
                'critical' => '#b91c1c',
                'attention' => '#b45309',
                default => '#047857',
            },
            'team_size' => (int) ($loadBalancing['reviewers_count'] ?? 0),
            'open_backlog' => $openBacklog,
            'avg_open_per_reviewer' => $avgOpenPerReviewer,
            'assignment_coverage' => (int) ($curationKpis['assignment_coverage'] ?? 0),
            'reviewed_in_period' => (int) ($curationKpis['reviewed_in_period'] ?? 0),
            'published_in_period' => (int) ($curationKpis['published_in_period'] ?? 0),
            'rejected_in_period' => (int) ($curationKpis['rejected_in_period'] ?? 0),
            'overdue_open' => (int) ($curationKpis['overdue_open'] ?? 0),
            'unassigned_open' => (int) ($loadBalancing['unassigned_open'] ?? 0),
            'unassigned_overdue' => (int) ($loadBalancing['unassigned_overdue'] ?? 0),
            'overflow_reviewers' => (int) ($capacityLimits['overflow_reviewers'] ?? 0),
            'warning_reviewers' => (int) ($capacityLimits['warning_reviewers'] ?? 0),
            'available_receivers' => (int) ($capacityLimits['available_receivers'] ?? 0),
            'team_publish_rate' => $teamPublishRate,
            'avg_decision_hours' => (float) ($curationKpis['avg_decision_hours'] ?? 0),
            'avg_publish_hours' => (float) ($curationKpis['avg_publish_hours'] ?? 0),
            'top_publisher_name' => (string) ($topPublisher['name'] ?? ''),
            'top_publisher_count' => (int) ($topPublisher['published_count'] ?? 0),
            'fastest_reviewer_name' => (string) ($fastestReviewer['name'] ?? ''),
            'fastest_reviewer_hours' => (float) ($fastestReviewer['avg_decision_hours'] ?? 0),
        ];
    }

    private function buildCurationSuggestionContext($reviewers, array $loadBalancing): array
    {
        $reviewerRows = collect($reviewers)->values();
        $reviewerIds = $reviewerRows->pluck('id')->all();
        $historicalRows = ResourceCurationQueue::query()
            ->whereIn('reviewed_by_user_id', $reviewerIds)
            ->whereNotNull('reviewed_by_user_id')
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', now()->subDays(90))
            ->get(['reviewed_by_user_id', 'resource_source_id', 'municipality_id']);

        return [
            'reviewers' => $reviewerRows,
            'load_by_reviewer_id' => collect($loadBalancing['reviewers'] ?? [])->keyBy('id'),
            'source_affinity' => $historicalRows
                ->filter(fn ($row) => (int) $row->resource_source_id > 0)
                ->groupBy(fn ($row) => $row->reviewed_by_user_id . ':' . $row->resource_source_id)
                ->map->count(),
            'municipality_affinity' => $historicalRows
                ->filter(fn ($row) => (int) $row->municipality_id > 0)
                ->groupBy(fn ($row) => $row->reviewed_by_user_id . ':' . $row->municipality_id)
                ->map->count(),
        ];
    }

    private function resolveSuggestedCurationAssignment(
        ResourceCurationQueue $entry,
        $reviewers,
        array $loadBalancing,
        ?array $context = null
    ): array {
        $context ??= $this->buildCurationSuggestionContext($reviewers, $loadBalancing);
        $reviewerRows = collect($context['reviewers'] ?? $reviewers)->values();

        if ($reviewerRows->isEmpty()) {
            return [
                'suggested_reviewer_id' => 0,
                'suggested_reviewer_name' => '',
                'suggestion_reason' => '',
                'suggestion_score' => 0,
                'suggestion_candidates' => [],
            ];
        }

        $loadByReviewerId = $context['load_by_reviewer_id'] ?? collect($loadBalancing['reviewers'] ?? [])->keyBy('id');
        $sourceAffinity = $context['source_affinity'] ?? collect();
        $municipalityAffinity = $context['municipality_affinity'] ?? collect();

        $candidates = $reviewerRows
            ->map(function (User $reviewer) use ($entry, $loadByReviewerId, $sourceAffinity, $municipalityAffinity) {
                $load = (array) ($loadByReviewerId->get($reviewer->id) ?? []);
                $loadState = (string) ($load['load_state'] ?? 'balanced');
                $openCount = (int) ($load['open_count'] ?? 0);
                $sourceKey = $reviewer->id . ':' . (int) $entry->resource_source_id;
                $municipalityKey = $reviewer->id . ':' . (int) $entry->municipality_id;
                $sourceHits = (int) ($sourceAffinity[$sourceKey] ?? 0);
                $municipalityHits = (int) ($municipalityAffinity[$municipalityKey] ?? 0);
                $score = 0;

                $score += match ($loadState) {
                    'available' => 8,
                    'balanced' => 4,
                    'overloaded' => -6,
                    default => 0,
                };
                $score += max(((int) ($load['suggested_intake'] ?? 0)) * 2, 0);
                $score += min($sourceHits, 4) * 3;
                $score += min($municipalityHits, 3) * 2;
                $score -= min($openCount, 12);

                $reasons = [];

                if ($loadState === 'available') {
                    $reasons[] = 'menor carga atual';
                } elseif ($loadState === 'balanced') {
                    $reasons[] = 'carga equilibrada';
                }

                if ($sourceHits > 0) {
                    $reasons[] = "histórico na fonte ({$sourceHits})";
                }

                if ($municipalityHits > 0) {
                    $reasons[] = "histórico no município ({$municipalityHits})";
                }

                if ($reasons === []) {
                    $reasons[] = 'disponibilidade operacional';
                }

                return [
                    'reviewer_id' => $reviewer->id,
                    'reviewer_name' => $reviewer->name,
                    'score' => $score,
                    'reason' => implode(' + ', $reasons),
                    'load_state' => $loadState,
                    'open_count' => $openCount,
                ];
            })
            ->sortByDesc('score')
            ->values();

        $best = (array) ($candidates->first() ?? []);

        return [
            'suggested_reviewer_id' => (int) ($best['reviewer_id'] ?? 0),
            'suggested_reviewer_name' => (string) ($best['reviewer_name'] ?? ''),
            'suggestion_reason' => (string) ($best['reason'] ?? ''),
            'suggestion_score' => (int) ($best['score'] ?? 0),
            'suggestion_candidates' => $candidates->take(3)->all(),
        ];
    }

    private function buildSuggestedCurationAssignments($reviewers, array $loadBalancing): array
    {
        $reviewerRows = collect($reviewers)->values();

        if ($reviewerRows->isEmpty()) {
            return [
                'items' => [],
                'available_count' => 0,
                'suggested_target_name' => '',
            ];
        }

        $suggestionContext = $this->buildCurationSuggestionContext($reviewerRows, $loadBalancing);

        $items = $this->buildRebalanceQueueQuery('high_score_unassigned')
            ->limit(6)
            ->get()
            ->map(function (ResourceCurationQueue $entry) use ($reviewerRows, $loadBalancing, $suggestionContext) {
                $serialized = $this->serializeCurationQueueEntry($entry);
                $suggestion = $this->resolveSuggestedCurationAssignment($entry, $reviewerRows, $loadBalancing, $suggestionContext);

                return array_merge($serialized, $suggestion);
            })
            ->values()
            ->all();

        return [
            'items' => $items,
            'available_count' => count($items),
            'suggested_target_name' => (string) ($loadBalancing['suggested_target_name'] ?? ''),
        ];
    }

    private function buildCurationOperatorGoalsPayload(
        array $operatorComparison,
        array $loadBalancing,
        array $curationKpis
    ): array {
        $rows = collect($operatorComparison['reviewers'] ?? [])->values();
        $reviewerCount = max($rows->count(), 1);
        $targetLoad = max((int) ($loadBalancing['target_load'] ?? 0), 1);
        $decisionGoalBase = max((int) ceil(max((int) ($curationKpis['reviewed_in_period'] ?? 0), $targetLoad * $reviewerCount) / $reviewerCount), 3);
        $hardLimit = max($targetLoad + 3, 5);

        $reviewers = $rows
            ->map(function (array $row) use ($targetLoad, $decisionGoalBase, $hardLimit) {
                $recentDecisions = (int) ($row['recent_decisions'] ?? 0);
                $openCount = (int) ($row['open_count'] ?? 0);
                $overdueCount = (int) ($row['overdue_count'] ?? 0);
                $dueSoonCount = (int) ($row['due_soon_count'] ?? 0);
                $throughputGoal = max($decisionGoalBase, min($decisionGoalBase + $overdueCount + ($openCount > $targetLoad ? 1 : 0), $decisionGoalBase + 4));
                $throughputProgress = $throughputGoal > 0
                    ? min((int) round(($recentDecisions / $throughputGoal) * 100), 999)
                    : 0;
                $backlogProgress = $openCount <= $targetLoad
                    ? 100
                    : max((int) round(($targetLoad / max($openCount, 1)) * 100), 0);
                $goalState = match (true) {
                    $overdueCount > 0 || $openCount > $hardLimit => 'critical',
                    $dueSoonCount > 0 || $throughputProgress < 80 || $openCount > $targetLoad => 'attention',
                    default => 'on_track',
                };
                $goalRank = match ($goalState) {
                    'critical' => 1,
                    'attention' => 2,
                    default => 3,
                };
                $focusLabel = match (true) {
                    $overdueCount > 0 => 'Zerar SLA vencido',
                    $openCount > $targetLoad => 'Reduzir backlog aberto',
                    $throughputProgress < 100 => 'Ganhar cadência de decisão',
                    default => 'Manter ritmo e cobertura',
                };

                return array_merge($row, [
                    'throughput_goal' => $throughputGoal,
                    'throughput_progress' => $throughputProgress,
                    'backlog_goal' => $targetLoad,
                    'backlog_progress' => $backlogProgress,
                    'overdue_goal' => 0,
                    'throughput_gap' => max($throughputGoal - $recentDecisions, 0),
                    'goal_rank' => $goalRank,
                    'goal_state' => $goalState,
                    'goal_state_label' => match ($goalState) {
                        'critical' => 'Meta em risco',
                        'attention' => 'Pede ajuste',
                        default => 'No alvo',
                    },
                    'goal_state_tone' => match ($goalState) {
                        'critical' => '#b91c1c',
                        'attention' => '#b45309',
                        default => '#047857',
                    },
                    'focus_label' => $focusLabel,
                ]);
            })
            ->sortBy([
                ['goal_rank', 'asc'],
                ['throughput_gap', 'desc'],
                ['open_count', 'desc'],
            ])
            ->values()
            ->all();

        return [
            'decision_goal_base' => $decisionGoalBase,
            'backlog_goal' => $targetLoad,
            'on_track' => collect($reviewers)->where('goal_state', 'on_track')->count(),
            'attention' => collect($reviewers)->where('goal_state', 'attention')->count(),
            'critical' => collect($reviewers)->where('goal_state', 'critical')->count(),
            'throughput_gap_total' => collect($reviewers)->sum('throughput_gap'),
            'reviewers' => $reviewers,
        ];
    }

    private function buildCurationOperatorComparisonPayload($reviewers, array $loadBalancing): array
    {
        $reviewerRows = collect($reviewers)->values();

        if ($reviewerRows->isEmpty()) {
            return [
                'reviewers' => [],
                'top_source_names' => [],
                'top_municipality_names' => [],
            ];
        }

        $reviewerIds = $reviewerRows->pluck('id')->all();
        $loadByReviewerId = collect($loadBalancing['reviewers'] ?? [])->keyBy('id');
        $recentReviewed = ResourceCurationQueue::query()
            ->leftJoin('resource_opportunity_cycles as comparison_cycles', 'comparison_cycles.id', '=', 'resource_curation_queue.resource_opportunity_cycle_id')
            ->selectRaw("
                resource_curation_queue.reviewed_by_user_id,
                resource_curation_queue.resource_source_id,
                resource_curation_queue.municipality_id,
                resource_curation_queue.queue_status,
                resource_curation_queue.reviewed_at,
                resource_curation_queue.entered_queue_at,
                COALESCE((comparison_cycles.cycle_metadata->>'match_score')::numeric, 0) as match_score
            ")
            ->whereIn('resource_curation_queue.reviewed_by_user_id', $reviewerIds)
            ->whereNotNull('resource_curation_queue.reviewed_at')
            ->where('resource_curation_queue.reviewed_at', '>=', now()->subDays(90))
            ->get();

        $sourceNames = ResourceSource::query()
            ->whereIn('id', $recentReviewed->pluck('resource_source_id')->filter()->unique()->values())
            ->pluck('name', 'id');
        $municipalityNames = Municipality::query()
            ->whereIn('id', $recentReviewed->pluck('municipality_id')->filter()->unique()->values())
            ->get(['id', 'name', 'state_code'])
            ->mapWithKeys(fn (Municipality $municipality) => [
                $municipality->id => trim($municipality->name . ' / ' . $municipality->state_code),
            ]);

        $rows = $reviewerRows
            ->map(function (User $reviewer) use ($recentReviewed, $loadByReviewerId, $sourceNames, $municipalityNames) {
                $history = $recentReviewed
                    ->where('reviewed_by_user_id', $reviewer->id)
                    ->values();
                $load = (array) ($loadByReviewerId->get($reviewer->id) ?? []);
                $topSources = $history
                    ->pluck('resource_source_id')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->take(3)
                    ->map(fn ($count, $sourceId) => ($sourceNames[$sourceId] ?? 'Fonte desconhecida') . ' (' . $count . ')')
                    ->values()
                    ->all();
                $topMunicipalities = $history
                    ->pluck('municipality_id')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->take(3)
                    ->map(fn ($count, $municipalityId) => ($municipalityNames[$municipalityId] ?? 'Município desconhecido') . ' (' . $count . ')')
                    ->values()
                    ->all();
                $reviewedCount = $history->count();
                $publishedCount = $history->where('queue_status', 'published')->count();
                $rejectedCount = $history->where('queue_status', 'rejected')->count();
                $avgMatchScore = round((float) $history->avg('match_score'), 2);
                $avgDecisionHours = round((float) $history
                    ->filter(fn ($row) => $row->entered_queue_at && $row->reviewed_at)
                    ->avg(fn ($row) => \Carbon\Carbon::parse($row->reviewed_at)->diffInMinutes(\Carbon\Carbon::parse($row->entered_queue_at)) / 60), 1);
                $bestFit = trim(implode(' · ', array_filter([
                    $topSources[0] ?? '',
                    $topMunicipalities[0] ?? '',
                ])));

                return [
                    'id' => $reviewer->id,
                    'name' => $reviewer->name,
                    'open_count' => (int) ($load['open_count'] ?? 0),
                    'pending_count' => (int) ($load['pending_count'] ?? 0),
                    'in_review_count' => (int) ($load['in_review_count'] ?? 0),
                    'approved_count' => (int) ($load['approved_count'] ?? 0),
                    'overdue_count' => (int) ($load['overdue_count'] ?? 0),
                    'due_soon_count' => (int) ($load['due_soon_count'] ?? 0),
                    'recent_decisions' => (int) ($load['recent_decisions'] ?? 0),
                    'load_state_label' => (string) ($load['load_state_label'] ?? 'Carga equilibrada'),
                    'load_state_tone' => (string) ($load['load_state_tone'] ?? '#1d4ed8'),
                    'reviewed_count' => $reviewedCount,
                    'published_count' => $publishedCount,
                    'rejected_count' => $rejectedCount,
                    'publish_rate' => $reviewedCount > 0 ? (int) round(($publishedCount / $reviewedCount) * 100) : 0,
                    'avg_match_score' => $avgMatchScore,
                    'avg_decision_hours' => $avgDecisionHours,
                    'top_sources' => $topSources,
                    'top_municipalities' => $topMunicipalities,
                    'best_fit_label' => $bestFit !== '' ? $bestFit : 'Afinidade ainda em formação',
                ];
            })
            ->sortBy([
                ['open_count', 'asc'],
                ['reviewed_count', 'desc'],
            ])
            ->values()
            ->all();

        return [
            'reviewers' => $rows,
            'top_source_names' => collect($rows)
                ->flatMap(fn (array $row) => array_map(fn ($item) => preg_replace('/\s+\(\d+\)$/', '', $item), $row['top_sources']))
                ->filter()
                ->unique()
                ->take(6)
                ->values()
                ->all(),
            'top_municipality_names' => collect($rows)
                ->flatMap(fn (array $row) => array_map(fn ($item) => preg_replace('/\s+\(\d+\)$/', '', $item), $row['top_municipalities']))
                ->filter()
                ->unique()
                ->take(6)
                ->values()
                ->all(),
        ];
    }

    private function buildCurationOperationalKpis(array $filters): array
    {
        $periodStart = $this->curationAuditPeriodStart((string) ($filters['period'] ?? '7d')) ?? now()->subDays(7);
        $openStatuses = ['pending', 'in_review', 'approved'];
        $openQuery = ResourceCurationQueue::query()->whereIn('queue_status', $openStatuses);
        $openCount = (clone $openQuery)->count();
        $assignedOpenCount = (clone $openQuery)->whereNotNull('assigned_to_user_id')->count();
        $reviewedInPeriod = ResourceCurationQueue::query()
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', $periodStart)
            ->count();
        $publishedInPeriod = ResourceCurationQueue::query()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $periodStart)
            ->count();
        $rejectedInPeriod = ResourceCurationQueue::query()
            ->where('queue_status', 'rejected')
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', $periodStart)
            ->count();
        $overdueOpenCount = (clone $openQuery)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->count();

        $avgDecisionHours = round((float) ResourceCurationQueue::query()
            ->whereNotNull('entered_queue_at')
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', $periodStart)
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (reviewed_at - entered_queue_at)) / 3600) as avg_hours')
            ->value('avg_hours'), 1);

        $avgPublishHours = round((float) ResourceCurationQueue::query()
            ->whereNotNull('entered_queue_at')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $periodStart)
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (published_at - entered_queue_at)) / 3600) as avg_hours')
            ->value('avg_hours'), 1);

        return [
            'period_label' => $this->curationAuditPeriodLabel((string) ($filters['period'] ?? '7d')),
            'open_backlog' => $openCount,
            'assignment_coverage' => $openCount > 0 ? (int) round(($assignedOpenCount / $openCount) * 100) : 0,
            'reviewed_in_period' => $reviewedInPeriod,
            'published_in_period' => $publishedInPeriod,
            'rejected_in_period' => $rejectedInPeriod,
            'overdue_open' => $overdueOpenCount,
            'avg_decision_hours' => $avgDecisionHours,
            'avg_publish_hours' => $avgPublishHours,
        ];
    }

    private function buildCurationExceptionsSummary(): array
    {
        $openStatuses = ['pending', 'in_review', 'approved'];
        $scoreExpression = "COALESCE((resource_opportunity_cycles.cycle_metadata->>'match_score')::numeric, 0)";

        $baseQuery = ResourceCurationQueue::query()
            ->leftJoin('resource_opportunity_cycles', 'resource_opportunity_cycles.id', '=', 'resource_curation_queue.resource_opportunity_cycle_id')
            ->whereIn('resource_curation_queue.queue_status', $openStatuses);

        return [
            'overdue_unassigned' => (clone $baseQuery)
                ->whereNull('resource_curation_queue.assigned_to_user_id')
                ->whereNotNull('resource_curation_queue.sla_due_at')
                ->where('resource_curation_queue.sla_due_at', '<', now())
                ->count(),
            'no_sla_open' => (clone $baseQuery)
                ->whereNull('resource_curation_queue.sla_due_at')
                ->count(),
            'stale_in_review' => (clone $baseQuery)
                ->where('resource_curation_queue.queue_status', 'in_review')
                ->whereNotNull('resource_curation_queue.review_started_at')
                ->where('resource_curation_queue.review_started_at', '<', now()->subDays(2))
                ->count(),
            'approved_waiting_publish' => (clone $baseQuery)
                ->where('resource_curation_queue.queue_status', 'approved')
                ->whereNotNull('resource_curation_queue.reviewed_at')
                ->where('resource_curation_queue.reviewed_at', '<', now()->subDay())
                ->count(),
            'high_priority_low_score' => (clone $baseQuery)
                ->whereIn('resource_curation_queue.priority', ['high', 'urgent'])
                ->whereRaw("{$scoreExpression} < ?", [0.55])
                ->count(),
        ];
    }

    private function buildCurationExceptionRows(): array
    {
        $openStatuses = ['pending', 'in_review', 'approved'];
        $entries = ResourceCurationQueue::query()
            ->with(['resourceSource:id,key,name,pipeline_group', 'opportunity.resourceSource:id,key,name,pipeline_group', 'cycle', 'municipality:id,name,state_code', 'assignedTo:id,name'])
            ->whereIn('queue_status', $openStatuses)
            ->get()
            ->map(fn (ResourceCurationQueue $entry) => $this->serializeCurationQueueEntry($entry))
            ->map(function (array $entry) {
                $exceptions = [];

                if (($entry['assigned_to_user_id'] ?? null) === null && ($entry['sla_state'] ?? '') === 'overdue') {
                    $exceptions[] = ['code' => 'overdue_unassigned', 'label' => 'SLA vencido sem responsável', 'tone' => 'danger'];
                }

                if (($entry['sla_state'] ?? '') === 'no_sla') {
                    $exceptions[] = ['code' => 'no_sla_open', 'label' => 'Item aberto sem SLA', 'tone' => 'warning'];
                }

                if (($entry['queue_status'] ?? '') === 'in_review' && $this->looksStaleReview($entry)) {
                    $exceptions[] = ['code' => 'stale_in_review', 'label' => 'Revisão parada há mais de 48h', 'tone' => 'warning'];
                }

                if (($entry['queue_status'] ?? '') === 'approved' && $this->looksApprovedWaitingPublish($entry)) {
                    $exceptions[] = ['code' => 'approved_waiting_publish', 'label' => 'Aprovado aguardando publicação', 'tone' => 'info'];
                }

                if (in_array($entry['priority'] ?? '', ['high', 'urgent'], true) && (float) ($entry['match_score'] ?? 0) < 0.55) {
                    $exceptions[] = ['code' => 'high_priority_low_score', 'label' => 'Alta prioridade com score baixo', 'tone' => 'danger'];
                }

                $entry['exceptions'] = $exceptions;
                $entry['exception_weight'] = collect($exceptions)->reduce(function (int $carry, array $exception) {
                    return $carry + match ($exception['tone'] ?? 'warning') {
                        'danger' => 3,
                        'warning' => 2,
                        'info' => 1,
                        default => 1,
                    };
                }, 0);

                return $entry;
            })
            ->filter(fn (array $entry) => !empty($entry['exceptions']))
            ->sortByDesc(fn (array $entry) => [$entry['exception_weight'], $entry['match_score'] ?? 0])
            ->take(8)
            ->values()
            ->all();

        return $entries;
    }

    private function buildCurationOperatorSummary(array $filters): array
    {
        $periodStart = $this->curationAuditPeriodStart((string) ($filters['period'] ?? '7d')) ?? now()->subDays(7);

        return ResourceCurationQueue::query()
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'resource_curation_queue.reviewed_by_user_id')
            ->whereNotNull('resource_curation_queue.reviewed_by_user_id')
            ->where('resource_curation_queue.reviewed_at', '>=', $periodStart)
            ->groupBy('resource_curation_queue.reviewed_by_user_id', 'reviewers.name')
            ->selectRaw('
                resource_curation_queue.reviewed_by_user_id,
                COALESCE(reviewers.name, ?) as reviewer_name,
                COUNT(*) as decisions_count,
                SUM(CASE WHEN resource_curation_queue.queue_status = ? THEN 1 ELSE 0 END) as published_count,
                SUM(CASE WHEN resource_curation_queue.queue_status = ? THEN 1 ELSE 0 END) as rejected_count,
                AVG(EXTRACT(EPOCH FROM (resource_curation_queue.reviewed_at - resource_curation_queue.entered_queue_at)) / 3600) as avg_decision_hours
            ', ['sistema', 'published', 'rejected'])
            ->orderByDesc('decisions_count')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'reviewer_name' => (string) $row->reviewer_name,
                'decisions_count' => (int) $row->decisions_count,
                'published_count' => (int) $row->published_count,
                'rejected_count' => (int) $row->rejected_count,
                'avg_decision_hours' => round((float) $row->avg_decision_hours, 1),
            ])
            ->all();
    }

    private function serializeCurationAuditActivity(Activity $activity): array
    {
        $before = (array) $activity->getExtraProperty('before', []);
        $after = (array) $activity->getExtraProperty('after', []);

        return [
            'id' => $activity->id,
            'event' => (string) $activity->event,
            'event_label' => $this->curationAuditEventLabel((string) $activity->event),
            'description' => (string) $activity->description,
            'created_at_human' => $activity->created_at?->diffForHumans(),
            'created_at_iso' => $activity->created_at?->toIso8601String(),
            'causer_name' => $activity->causer?->name ?? 'sistema',
            'bulk_operation' => (bool) $activity->getExtraProperty('bulk_operation', false),
            'selected_count' => (int) $activity->getExtraProperty('selected_count', 0),
            'changed_fields' => (array) $activity->getExtraProperty('changed_fields', []),
            'title' => (string) ($after['title'] ?? $before['title'] ?? 'Item sem titulo'),
            'source_name' => (string) ($after['source_name'] ?? $before['source_name'] ?? 'Fonte não identificada'),
            'municipality_name' => (string) ($after['municipality_name'] ?? $before['municipality_name'] ?? 'Sem município'),
            'before_queue_status' => (string) ($before['queue_status'] ?? ''),
            'after_queue_status' => (string) ($after['queue_status'] ?? ''),
            'before_priority' => (string) ($before['priority'] ?? ''),
            'after_priority' => (string) ($after['priority'] ?? ''),
            'before_assigned_to_name' => (string) ($before['assigned_to_name'] ?? ''),
            'after_assigned_to_name' => (string) ($after['assigned_to_name'] ?? ''),
            'match_score' => (float) ($after['match_score'] ?? $before['match_score'] ?? 0),
            'decision_notes' => (string) ($after['decision_notes'] ?? $before['decision_notes'] ?? ''),
        ];
    }

    private function curationAuditExportFilterRows(array $filters): array
    {
        return [
            ['Período', $this->curationAuditPeriodLabel((string) ($filters['period'] ?? '7d'))],
            ['Evento', ($filters['event'] ?? 'all') !== 'all' ? $this->curationAuditEventLabel((string) $filters['event']) : 'Todos'],
            ['Operador', filled($filters['causer_id'] ?? null)
                ? (User::query()->find((int) $filters['causer_id'])?->name ?? 'Nao encontrado')
                : 'Todos'],
            ['Fonte', filled($filters['source_id'] ?? null)
                ? (ResourceSource::query()->find((int) $filters['source_id'])?->name ?? 'Nao encontrada')
                : 'Todas'],
            ['Município', filled($filters['municipality_id'] ?? null)
                ? (Municipality::query()->find((int) $filters['municipality_id'])?->name ?? 'Nao encontrado')
                : 'Todos'],
        ];
    }

    private function curationAuditExportRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row) => [
                $row['id'],
                $row['created_at_iso'] ?: $row['created_at_human'],
                $row['causer_name'],
                $row['event_label'],
                $row['description'],
                $row['title'],
                $row['source_name'],
                $row['municipality_name'],
                number_format((float) $row['match_score'], 2, '.', ''),
                $row['before_queue_status'],
                $row['after_queue_status'],
                $row['before_priority'],
                $row['after_priority'],
                $row['before_assigned_to_name'],
                $row['after_assigned_to_name'],
                $row['bulk_operation'] ? 'Sim' : 'Nao',
                $row['selected_count'],
                implode(', ', $row['changed_fields'] ?? []),
                $row['decision_notes'],
            ])
            ->all();
    }

    private function curationQueueExportFilterRows(array $filters): array
    {
        return [
            ['Busca', $filters['search'] !== '' ? $filters['search'] : 'Sem filtro'],
            ['Fila', ($filters['queue_status'] ?? 'all') !== 'all' ? $this->curationQueueStatusLabel((string) $filters['queue_status']) : 'Todas'],
            ['Curadoria', ($filters['curation_status'] ?? 'all') !== 'all' ? $this->curationStatusLabel((string) $filters['curation_status']) : 'Todas'],
            ['Fonte', filled($filters['source_id'] ?? null)
                ? (ResourceSource::query()->find((int) $filters['source_id'])?->name ?? 'Nao encontrada')
                : 'Todas'],
            ['Município', filled($filters['municipality_id'] ?? null)
                ? (Municipality::query()->find((int) $filters['municipality_id'])?->name ?? 'Nao encontrado')
                : 'Todos'],
            ['Responsável', match (true) {
                ($filters['assigned_to_user_id'] ?? '') === 'unassigned' => 'Sem responsável',
                filled($filters['assigned_to_user_id'] ?? null) => (User::query()->find((int) $filters['assigned_to_user_id'])?->name ?? 'Nao encontrado'),
                default => 'Todos',
            }],
            ['Prioridade', ($filters['priority'] ?? 'all') !== 'all' ? $this->curationPriorityLabel((string) $filters['priority']) : 'Todas'],
            ['SLA', ($filters['sla_bucket'] ?? 'all') !== 'all' ? (string) $filters['sla_bucket'] : 'Todos'],
            ['Score mínimo', $filters['min_score'] !== '' ? (string) $filters['min_score'] : 'Sem filtro'],
            ['Ordenação', (string) ($filters['sort'] ?? 'priority_score_recent')],
        ];
    }

    private function curationQueueExportRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row) => [
                $row['id'],
                $row['title'],
                $row['queue_status_label'],
                $row['curation_status_label'],
                $row['status_label'],
                $row['source_name'],
                $row['municipality_name'],
                $row['municipality_uf'],
                $row['assigned_to_name'] ?: 'Sem responsável',
                $row['reviewed_by_name'] ?: '',
                $row['priority_label'],
                $row['sla_label'],
                $row['sla_due_at_iso'] ?: $row['sla_due_at_human'],
                number_format((float) $row['match_score'], 2, '.', ''),
                $row['match_reason'],
                $row['entered_queue_at_iso'] ?: $row['entered_queue_at_human'],
                $row['review_started_at_iso'] ?: $row['review_started_at_human'],
                $row['reviewed_at_iso'] ?: $row['reviewed_at_human'],
                $row['published_at_iso'] ?: $row['published_at_human'],
                $row['updated_at_iso'] ?: $row['updated_at_human'],
                $row['decision_notes'],
                $row['source_url'],
            ])
            ->all();
    }

    private function looksStaleReview(array $entry): bool
    {
        $reviewStartedAt = $entry['review_started_at_iso'] ?? null;

        if (is_string($reviewStartedAt) && trim($reviewStartedAt) !== '') {
            return \Carbon\Carbon::parse($reviewStartedAt)->lt(now()->subDays(2));
        }

        $enteredAt = $entry['entered_queue_at_iso'] ?? null;

        if (!is_string($enteredAt) || trim($enteredAt) === '') {
            return false;
        }

        return \Carbon\Carbon::parse($enteredAt)->lt(now()->subDays(2));
    }

    private function looksApprovedWaitingPublish(array $entry): bool
    {
        $reviewedAt = $entry['reviewed_at_iso'] ?? null;

        if (!is_string($reviewedAt) || trim($reviewedAt) === '') {
            return false;
        }

        return \Carbon\Carbon::parse($reviewedAt)->lt(now()->subDay()) && empty($entry['published_at_iso']);
    }

    private function serializeCurationQueueEntry(ResourceCurationQueue $entry): array
    {
        $opportunity = $entry->opportunity;
        $cycle = $entry->cycle;
        $source = $entry->resourceSource ?: $opportunity?->resourceSource;
        $metadata = is_array($cycle?->cycle_metadata) ? $cycle->cycle_metadata : [];
        $slaState = $this->curationSlaState($entry);

        return [
            'id' => $entry->id,
            'queue_status' => $entry->queue_status,
            'queue_status_label' => $this->curationQueueStatusLabel((string) $entry->queue_status),
            'queue_status_tone' => $this->curationQueueStatusTone((string) $entry->queue_status),
            'priority' => $entry->priority,
            'priority_label' => $this->curationPriorityLabel((string) $entry->priority),
            'priority_tone' => $this->curationPriorityTone((string) $entry->priority),
            'curation_status' => (string) ($opportunity?->curation_status ?? 'pending_review'),
            'curation_status_label' => $this->curationStatusLabel((string) ($opportunity?->curation_status ?? 'pending_review')),
            'curation_status_tone' => $this->curationStatusTone((string) ($opportunity?->curation_status ?? 'pending_review')),
            'title' => (string) ($opportunity?->title ?? data_get($entry->source_payload_snapshot, 'title', 'Oportunidade sem titulo')),
            'summary' => (string) ($opportunity?->summary ?? data_get($entry->source_payload_snapshot, 'summary', '')),
            'match_score' => (float) data_get($metadata, 'match_score', data_get($entry->source_payload_snapshot, 'match_score', 0)),
            'match_reason' => (string) data_get($metadata, 'match_reason', data_get($entry->source_payload_snapshot, 'match_reason', '')),
            'status' => (string) ($cycle?->status ?? ''),
            'status_label' => $this->resourceStatusLabel((string) ($cycle?->status ?? '')),
            'source_name' => (string) ($source?->name ?? data_get($entry->source_payload_snapshot, 'source_name', 'Fonte não identificada')),
            'source_key' => (string) ($source?->key ?? data_get($entry->source_payload_snapshot, 'source_key', '')),
            'pipeline_group_label' => $this->pipelineGroupLabel((string) ($source?->pipeline_group ?? 'group_d_human_curation')),
            'municipality_name' => (string) ($entry->municipality?->name ?? 'Sem município'),
            'municipality_uf' => (string) ($entry->municipality?->state_code ?? ''),
            'assigned_to_user_id' => $entry->assigned_to_user_id,
            'assigned_to_name' => $entry->assignedTo?->name,
            'reviewed_by_name' => $entry->reviewedBy?->name,
            'source_url' => (string) ($cycle?->notice_url ?: $opportunity?->source_url ?: data_get($entry->source_payload_snapshot, 'source_url', '')),
            'decision_notes' => (string) ($entry->decision_notes ?? ''),
            'entered_queue_at_human' => $entry->entered_queue_at?->diffForHumans(),
            'entered_queue_at_iso' => $entry->entered_queue_at?->toIso8601String(),
            'sla_due_at_human' => $entry->sla_due_at?->diffForHumans(),
            'sla_due_at_iso' => $entry->sla_due_at?->toIso8601String(),
            'sla_state' => $slaState['state'],
            'sla_label' => $slaState['label'],
            'sla_tone' => $slaState['tone'],
            'review_started_at_human' => $entry->review_started_at?->diffForHumans(),
            'review_started_at_iso' => $entry->review_started_at?->toIso8601String(),
            'reviewed_at_human' => $entry->reviewed_at?->diffForHumans(),
            'reviewed_at_iso' => $entry->reviewed_at?->toIso8601String(),
            'published_at_human' => $entry->published_at?->diffForHumans(),
            'published_at_iso' => $entry->published_at?->toIso8601String(),
            'updated_at_human' => $entry->updated_at?->diffForHumans(),
            'updated_at_iso' => $entry->updated_at?->toIso8601String(),
        ];
    }

    private function syncResourceCurationQueue(): void
    {
        if (!Schema::hasTable('resource_curation_queue')
            || !Schema::hasTable('resource_opportunities')
            || !Schema::hasTable('resource_opportunity_cycles')) {
            return;
        }

        ResourceOpportunityCycle::query()
            ->with(['opportunity.resourceSource'])
            ->where('is_current', true)
            ->get()
            ->filter(fn (ResourceOpportunityCycle $cycle) => (int) data_get($cycle->cycle_metadata, 'municipality_id', 0) > 0)
            ->each(function (ResourceOpportunityCycle $cycle) {
                $opportunity = $cycle->opportunity;

                if (!$opportunity instanceof ResourceOpportunity || !$opportunity->resource_source_id) {
                    return;
                }

                $municipalityId = (int) data_get($cycle->cycle_metadata, 'municipality_id', 0);
                $resourceSource = $opportunity->resourceSource;
                $entry = ResourceCurationQueue::query()->firstOrCreate(
                    [
                        'resource_opportunity_id' => $opportunity->id,
                        'resource_opportunity_cycle_id' => $cycle->id,
                        'municipality_id' => $municipalityId,
                    ],
                    [
                        'resource_source_id' => $opportunity->resource_source_id,
                        'queue_status' => $this->defaultQueueStatusForOpportunity($opportunity),
                        'priority' => $this->inferCurationPriority($opportunity, $cycle),
                        'entered_queue_at' => now(),
                        'sla_due_at' => $this->defaultCurationSla($opportunity, $cycle),
                        'source_payload_snapshot' => $this->curationSnapshotPayload($opportunity, $cycle),
                    ]
                );

                $entry->fill([
                    'resource_source_id' => $opportunity->resource_source_id,
                    'source_payload_snapshot' => $this->curationSnapshotPayload($opportunity, $cycle),
                ]);

                if (!$entry->entered_queue_at) {
                    $entry->entered_queue_at = now();
                }

                if (!$entry->sla_due_at) {
                    $entry->sla_due_at = $this->defaultCurationSla($opportunity, $cycle);
                }

                if (in_array($entry->queue_status, ['pending', 'in_review'], true)) {
                    $entry->priority = $this->inferCurationPriority($opportunity, $cycle);
                }

                if ($entry->isDirty()) {
                    $entry->save();
                }

                if ($opportunity->curation_status === 'rejected' && $entry->queue_status !== 'rejected') {
                    $entry->update([
                        'queue_status' => 'rejected',
                        'reviewed_at' => $entry->reviewed_at ?? now(),
                    ]);
                }
            });
    }

    private function defaultQueueStatusForOpportunity(ResourceOpportunity $opportunity): string
    {
        return match ((string) $opportunity->curation_status) {
            'rejected' => 'rejected',
            'curated' => 'approved',
            default => 'pending',
        };
    }

    private function inferCurationPriority(ResourceOpportunity $opportunity, ResourceOpportunityCycle $cycle): string
    {
        $matchScore = (float) data_get($cycle->cycle_metadata, 'match_score', 0);
        $pipelineGroup = (string) ($opportunity->resourceSource?->pipeline_group ?? '');
        $status = (string) $cycle->status;

        if (in_array($status, [ResourceOpportunityStatus::ClosingSoon->value, ResourceOpportunityStatus::Reopened->value], true) || $matchScore >= 0.9) {
            return 'urgent';
        }

        if ($pipelineGroup === 'group_c_diary_monitor' || $matchScore >= 0.7) {
            return 'high';
        }

        if ((bool) ($opportunity->resourceSource?->requires_human_curation)) {
            return 'high';
        }

        return 'normal';
    }

    private function defaultCurationSla(ResourceOpportunity $opportunity, ResourceOpportunityCycle $cycle)
    {
        return match ($this->inferCurationPriority($opportunity, $cycle)) {
            'urgent' => now()->addHours(8),
            'high' => now()->addDay(),
            'low' => now()->addDays(3),
            default => now()->addDays(2),
        };
    }

    private function curationSnapshotPayload(ResourceOpportunity $opportunity, ResourceOpportunityCycle $cycle): array
    {
        return [
            'title' => $opportunity->title,
            'summary' => $opportunity->summary,
            'source_url' => $cycle->notice_url ?: $opportunity->source_url,
            'source_name' => $opportunity->resourceSource?->name,
            'source_key' => $opportunity->resourceSource?->key,
            'status' => $cycle->status,
            'curation_status' => $opportunity->curation_status,
            'match_score' => data_get($cycle->cycle_metadata, 'match_score'),
            'match_reason' => data_get($cycle->cycle_metadata, 'match_reason'),
            'municipality_id' => data_get($cycle->cycle_metadata, 'municipality_id'),
        ];
    }

    private function syncLegacyAlertCurationState(?ResourceOpportunityCycle $cycle, string $curationStatus, ?string $status): void
    {
        $legacyAlertId = (int) data_get($cycle?->cycle_metadata, 'legacy_alert_id', 0);

        if ($legacyAlertId <= 0) {
            return;
        }

        $alert = FederalProgramAlert::query()->find($legacyAlertId);

        if (!$alert) {
            return;
        }

        $payload = ['curation_status' => $curationStatus];

        if ($status !== null) {
            $payload['status'] = $status;
        }

        $alert->update($payload);
    }

    private function applyCurationTransition(
        ResourceCurationQueue $entry,
        string $action,
        string $notes = '',
        ?User $user = null,
        array $extra = [],
    ): string {
        $entry->loadMissing(['opportunity', 'cycle']);
        $opportunity = $entry->opportunity;
        $cycle = $entry->cycle;

        if (!$opportunity instanceof ResourceOpportunity) {
            return 'Nao foi possivel localizar a oportunidade canonica da fila.';
        }

        $before = $this->curationAuditSnapshot($entry);
        $message = 'A fila de curadoria foi atualizada.';
        $entryUpdates = [
            'decision_notes' => $notes !== '' ? $notes : $entry->decision_notes,
        ];

        if ($action === 'start_review') {
            $entryUpdates['queue_status'] = 'in_review';
            $entryUpdates['review_started_at'] = $entry->review_started_at ?? now();
            $entryUpdates['assigned_to_user_id'] = $entry->assigned_to_user_id ?: ($user?->id);
            $message = 'Item movido para revisão.';
        }

        if ($action === 'approve') {
            $entryUpdates['queue_status'] = 'approved';
            $entryUpdates['review_started_at'] = $entry->review_started_at ?? now();
            $entryUpdates['reviewed_at'] = now();
            $entryUpdates['reviewed_by_user_id'] = $user?->id;
            $opportunity->update(['curation_status' => 'curated']);
            $this->syncLegacyAlertCurationState($cycle, 'curated', null);
            $message = 'Item aprovado para curadoria.';
        }

        if ($action === 'publish') {
            $entryUpdates['queue_status'] = 'published';
            $entryUpdates['review_started_at'] = $entry->review_started_at ?? now();
            $entryUpdates['reviewed_at'] = now();
            $entryUpdates['reviewed_by_user_id'] = $user?->id;
            $entryUpdates['published_at'] = now();
            $opportunity->update([
                'curation_status' => 'curated',
                'last_published_at' => $opportunity->last_published_at ?? now(),
            ]);

            if ($cycle instanceof ResourceOpportunityCycle) {
                $cycleStatus = $cycle->status;

                if (in_array($cycleStatus, [null, '', ResourceOpportunityStatus::Monitoring->value, ResourceOpportunityStatus::PendingReview->value], true)) {
                    $cycle->update([
                        'status' => ResourceOpportunityStatus::Published->value,
                        'published_at' => $cycle->published_at ?? now(),
                    ]);
                }
            }

            $this->syncLegacyAlertCurationState($cycle, 'curated', ResourceOpportunityStatus::Published->value);
            $message = 'Item publicado na curadoria.';
        }

        if ($action === 'reject') {
            $entryUpdates['queue_status'] = 'rejected';
            $entryUpdates['review_started_at'] = $entry->review_started_at ?? now();
            $entryUpdates['reviewed_at'] = now();
            $entryUpdates['reviewed_by_user_id'] = $user?->id;
            $opportunity->update(['curation_status' => 'rejected']);

            if ($cycle instanceof ResourceOpportunityCycle) {
                $cycle->update([
                    'status' => ResourceOpportunityStatus::Rejected->value,
                ]);
            }

            $this->syncLegacyAlertCurationState($cycle, 'rejected', ResourceOpportunityStatus::Rejected->value);
            $message = 'Item rejeitado na curadoria.';
        }

        $entry->update($entryUpdates);
        $this->recordCurationAudit(
            $action,
            $user,
            $entry,
            $before,
            $this->curationAuditSnapshot($entry),
            $extra
        );

        return $message;
    }

    private function curationQueueStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Na fila',
            'in_review' => 'Em revisão',
            'enriched' => 'Enriquecida',
            'approved' => 'Aprovada',
            'published' => 'Publicada',
            'rejected' => 'Rejeitada',
            default => 'Sem status',
        };
    }

    private function curationQueueStatusTone(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'in_review', 'enriched' => 'info',
            'approved', 'published' => 'success',
            'rejected' => 'danger',
            default => 'neutral',
        };
    }

    private function curationStatusLabel(string $status): string
    {
        return match ($status) {
            'pending_review' => 'Pendente',
            'auto_published' => 'Auto-publicada',
            'curated' => 'Curada',
            'rejected' => 'Rejeitada',
            default => 'Sem curadoria',
        };
    }

    private function curationStatusTone(string $status): string
    {
        return match ($status) {
            'pending_review' => 'warning',
            'auto_published' => 'info',
            'curated' => 'success',
            'rejected' => 'danger',
            default => 'neutral',
        };
    }

    private function curationPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'Urgente',
            'high' => 'Alta',
            'low' => 'Baixa',
            default => 'Normal',
        };
    }

    private function curationPriorityTone(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'low' => 'neutral',
            default => 'info',
        };
    }

    private function resourceStatusLabel(string $status): string
    {
        return match ($status) {
            ResourceOpportunityStatus::Published->value => 'Publicada',
            ResourceOpportunityStatus::ClosingSoon->value => 'Encerrando',
            ResourceOpportunityStatus::Monitoring->value => 'Monitoramento',
            ResourceOpportunityStatus::ClosedRecently->value => 'Encerrada 60d',
            ResourceOpportunityStatus::Archived->value => 'Arquivada',
            ResourceOpportunityStatus::Reopened->value => 'Reaberta',
            ResourceOpportunityStatus::PendingReview->value => 'Pendente',
            ResourceOpportunityStatus::Rejected->value => 'Rejeitada',
            default => $status !== '' ? ucfirst($status) : 'Sem status',
        };
    }

    private function curationSlaState(ResourceCurationQueue $entry): array
    {
        if (!$entry->sla_due_at) {
            return [
                'state' => 'no_sla',
                'label' => 'Sem SLA',
                'tone' => 'neutral',
            ];
        }

        if (in_array((string) $entry->queue_status, ['published', 'rejected'], true)) {
            return [
                'state' => 'closed',
                'label' => 'Fechada',
                'tone' => 'success',
            ];
        }

        if ($entry->sla_due_at->isPast()) {
            return [
                'state' => 'overdue',
                'label' => 'SLA vencido',
                'tone' => 'danger',
            ];
        }

        if ($entry->sla_due_at->lte(now()->addDay())) {
            return [
                'state' => 'due_soon',
                'label' => 'Vence em 24h',
                'tone' => 'warning',
            ];
        }

        return [
            'state' => 'on_track',
            'label' => 'No prazo',
            'tone' => 'info',
        ];
    }

    private function curationAuditSnapshot(ResourceCurationQueue $entry): array
    {
        $entry->loadMissing([
            'opportunity.resourceSource',
            'municipality',
            'assignedTo',
            'reviewedBy',
            'cycle',
        ]);

        return [
            'queue_status' => (string) $entry->queue_status,
            'priority' => (string) $entry->priority,
            'assigned_to_user_id' => $entry->assigned_to_user_id,
            'assigned_to_name' => $entry->assignedTo?->name,
            'reviewed_by_user_id' => $entry->reviewed_by_user_id,
            'reviewed_by_name' => $entry->reviewedBy?->name,
            'decision_notes' => (string) ($entry->decision_notes ?? ''),
            'entered_queue_at' => $entry->entered_queue_at?->toIso8601String(),
            'reviewed_at' => $entry->reviewed_at?->toIso8601String(),
            'published_at' => $entry->published_at?->toIso8601String(),
            'sla_due_at' => $entry->sla_due_at?->toIso8601String(),
            'title' => (string) ($entry->opportunity?->title ?? data_get($entry->source_payload_snapshot, 'title', '')),
            'source_name' => (string) ($entry->resourceSource?->name ?? $entry->opportunity?->resourceSource?->name ?? ''),
            'municipality_name' => (string) ($entry->municipality?->name ?? ''),
            'curation_status' => (string) ($entry->opportunity?->curation_status ?? ''),
            'match_score' => (float) data_get($entry->cycle?->cycle_metadata, 'match_score', data_get($entry->source_payload_snapshot, 'match_score', 0)),
        ];
    }

    private function recordCurationAudit(
        string $event,
        ?User $actor,
        ResourceCurationQueue $entry,
        array $before,
        array $after,
        array $extra = [],
    ): ?Activity {
        if ($before === $after && empty($extra)) {
            return null;
        }

        return activity(self::CURATION_AUDIT_LOG)
            ->performedOn($entry)
            ->causedBy($actor)
            ->event($event)
            ->withProperties(array_merge([
                'before' => $before,
                'after' => $after,
                'changed_fields' => $this->changedAuditFields($before, $after),
            ], $extra))
            ->log($this->curationAuditDescription($event));
    }

    private function changedAuditFields(array $before, array $after): array
    {
        return collect(array_unique(array_merge(array_keys($before), array_keys($after))))
            ->filter(fn (string $key) => ($before[$key] ?? null) !== ($after[$key] ?? null))
            ->values()
            ->all();
    }

    private function curationAuditDescription(string $event): string
    {
        return match ($event) {
            'assign' => 'Fila de curadoria atribuida',
            'apply_suggestion' => 'Sugestao de atribuicao confirmada',
            'reprioritize' => 'Prioridade da curadoria ajustada',
            'start_review' => 'Curadoria iniciada',
            'approve' => 'Curadoria aprovada',
            'publish' => 'Curadoria publicada',
            'reject' => 'Curadoria rejeitada',
            default => 'Curadoria atualizada',
        };
    }

    private function curationAuditEventLabel(string $event): string
    {
        return match ($event) {
            'assign' => 'Atribuição',
            'apply_suggestion' => 'Confirmação de sugestão',
            'reprioritize' => 'Repriorização',
            'start_review' => 'Início de revisão',
            'approve' => 'Aprovação',
            'publish' => 'Publicação',
            'reject' => 'Rejeição',
            default => 'Atualização',
        };
    }

    private function curationAuditPeriodStart(string $period)
    {
        return match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => null,
        };
    }

    private function curationAuditPeriodLabel(string $period): string
    {
        return match ($period) {
            '24h' => 'últimas 24h',
            '7d' => 'últimos 7 dias',
            '30d' => 'últimos 30 dias',
            '90d' => 'últimos 90 dias',
            default => 'período total',
        };
    }

    public function syncStatus(Municipality $municipality)
    {
        $this->expireStaleExecutions($municipality);

        $execution = ApiSyncLog::query()
            ->radarFederalPrograms()
            ->where('municipality_id', $municipality->id)
            ->latest('id')
            ->first();

        return response()->json([
            'ok' => true,
            'execution' => $this->serializeExecution($execution),
        ]);
    }

    // ── Deletar programas desatualizados de um município ─────────────────
    public function clearMunicipality(Municipality $municipality)
    {
        $deleted = FederalProgramAlert::where('municipality_id', $municipality->id)
            ->whereIn('status', [
                ResourceOpportunityStatus::ClosedRecently->value,
                ResourceOpportunityStatus::Archived->value,
                ResourceOpportunityStatus::Rejected->value,
            ])
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => "{$deleted} oportunidade(s) removida(s) de {$municipality->name}.",
        ]);
    }

    private function buildSourceCatalog(array $sourceRunStats)
    {
        return ResourceSource::query()
            ->withCount([
                'canonicalOpportunities as opportunities_count',
                'curationQueueEntries as curation_queue_count',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (ResourceSource $source) use ($sourceRunStats) {
                $pipelineGroup = (string) ($source->pipeline_group ?? '');
                $operationalStatus = (string) ($source->operational_status ?? '');
                $metadata = is_array($source->source_metadata) ? $source->source_metadata : [];
                $resolvedProfile = match ($pipelineGroup) {
                    'group_c_diary_monitor' => $this->diaryMonitorFetcher->resolveProfile($source),
                    'group_b_scraping' => $this->structuredScrapingFetcher->resolveProfile($source),
                    default => [],
                };
                $latestSourceRun = $sourceRunStats[$source->key] ?? null;
                $entrypoints = array_values(array_filter((array) ($resolvedProfile['entrypoints'] ?? [])));
                $pathKeywords = array_values(array_filter((array) ($resolvedProfile['path_keywords'] ?? [])));
                $allowedHosts = array_values(array_filter((array) ($resolvedProfile['allowed_hosts'] ?? [])));
                $requiredTerms = array_values(array_filter((array) ($resolvedProfile['required_terms'] ?? [])));
                $titleTerms = array_values(array_filter((array) ($resolvedProfile['title_terms'] ?? [])));
                $ignoreTerms = array_values(array_filter((array) ($resolvedProfile['ignore_terms'] ?? [])));
                $excludedPathKeywords = array_values(array_filter((array) ($resolvedProfile['excluded_path_keywords'] ?? [])));
                $focusLabel = $this->sourceFocusLabel($source->key, $pipelineGroup);
                $focusNote = $this->sourceFocusNote($pipelineGroup);

                return [
                    'id' => $source->id,
                    'key' => $source->key,
                    'name' => $source->name,
                    'resource_scope' => (string) $source->resource_scope,
                    'capture_method' => (string) $source->capture_method,
                    'pipeline_group' => $pipelineGroup,
                    'pipeline_group_label' => $this->pipelineGroupLabel($pipelineGroup),
                    'refresh_frequency' => (string) $source->refresh_frequency,
                    'operational_status' => $operationalStatus,
                    'operational_status_label' => $this->operationalStatusLabel($operationalStatus),
                    'operational_status_tone' => $this->operationalStatusTone($operationalStatus),
                    'source_url' => $source->source_url,
                    'access_guide' => (string) $source->access_guide,
                    'index_fields' => array_values((array) $source->index_fields),
                    'operational_tags' => array_values((array) $source->operational_tags),
                    'maintenance_notes' => (string) $source->maintenance_notes,
                    'coverage_scope' => (string) data_get($metadata, 'coverage_scope', 'Cobertura operacional não  informada.'),
                    'primary_entrypoint' => (string) data_get($metadata, 'primary_entrypoint', 'Não informado'),
                    'scraping_entrypoints' => $entrypoints,
                    'scraping_entrypoints_text' => implode("\n", $entrypoints),
                    'scraping_path_keywords' => $pathKeywords,
                    'scraping_path_keywords_text' => implode("\n", $pathKeywords),
                    'scraping_allowed_hosts' => $allowedHosts,
                    'scraping_allowed_hosts_text' => implode("\n", $allowedHosts),
                    'scraping_excluded_path_keywords' => $excludedPathKeywords,
                    'scraping_excluded_path_keywords_text' => implode("\n", $excludedPathKeywords),
                    'scraping_required_terms' => $requiredTerms,
                    'scraping_required_terms_text' => implode("\n", $requiredTerms),
                    'scraping_title_terms' => $titleTerms,
                    'scraping_title_terms_text' => implode("\n", $titleTerms),
                    'diary_entrypoints' => $entrypoints,
                    'diary_entrypoints_text' => implode("\n", $entrypoints),
                    'diary_path_keywords' => $pathKeywords,
                    'diary_path_keywords_text' => implode("\n", $pathKeywords),
                    'diary_allowed_hosts' => $allowedHosts,
                    'diary_allowed_hosts_text' => implode("\n", $allowedHosts),
                    'diary_required_terms' => $requiredTerms,
                    'diary_required_terms_text' => implode("\n", $requiredTerms),
                    'diary_title_terms' => $titleTerms,
                    'diary_title_terms_text' => implode("\n", $titleTerms),
                    'diary_ignore_terms' => $ignoreTerms,
                    'diary_ignore_terms_text' => implode("\n", $ignoreTerms),
                    'minimum_score' => (int) ($resolvedProfile['minimum_score'] ?? 0),
                    'require_strong_signal' => (bool) ($resolvedProfile['require_strong_signal'] ?? false),
                    'uses_custom_scraping_profile' => (bool) data_get($metadata, 'uses_custom_scraping_profile', false),
                    'operational_priority' => (string) data_get($metadata, 'operational_priority', 'normal'),
                    'current_readiness' => (string) data_get($metadata, 'current_readiness', 'Catalogada'),
                    'is_priority_focus' => $focusLabel !== null,
                    'focus_badge_label' => $focusLabel,
                    'focus_note' => $focusNote,
                    'requires_human_curation' => (bool) $source->requires_human_curation,
                    'supports_municipality_sync' => (bool) $source->supports_municipality_sync,
                    'is_active' => (bool) $source->is_active,
                    'opportunities_count' => (int) $source->opportunities_count,
                    'curation_queue_count' => (int) $source->curation_queue_count,
                    'latest_source_run' => $latestSourceRun,
                ];
            })
            ->values();
    }

    private function latestSourceRunStats(): array
    {
        return ApiSyncLog::query()
            ->radarFederalProgramSourceRuns()
            ->latest('id')
            ->get()
            ->unique('source')
            ->mapWithKeys(function (ApiSyncLog $log) {
                return [
                    $log->source => [$this->serializeSourceRun($log, true)],
                ];
            })
            ->map(fn (array $items) => $items[0])
            ->all();
    }

    private function latestSourceRunHistory(int $limit = 12)
    {
        return ApiSyncLog::query()
            ->radarFederalProgramSourceRuns()
            ->with('municipality')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (ApiSyncLog $log) => $this->serializeSourceRun($log))
            ->values();
    }

    private function buildSourceCatalogSummary($sourceCatalog): array
    {
        return [
            'total' => $sourceCatalog->count(),
            'active' => $sourceCatalog->where('is_active', true)->count(),
            'live' => $sourceCatalog->where('operational_status', 'live')->count(),
            'requires_curation' => $sourceCatalog->where('requires_human_curation', true)->count(),
            'supports_sync' => $sourceCatalog->where('supports_municipality_sync', true)->count(),
            'groups' => $sourceCatalog
                ->groupBy('pipeline_group')
                ->map(fn ($items, $group) => [
                    'group' => $group,
                    'label' => $this->pipelineGroupLabel((string) $group),
                    'count' => $items->count(),
                ])
                ->sortBy('label')
                ->values(),
        ];
    }

    private function buildSourceRunSummary(array $sourceRunStats, $sourceRunHistory): array
    {
        $latestRuns = collect($sourceRunStats)->values();

        return [
            'tracked_sources' => $latestRuns->count(),
            'healthy_sources' => $latestRuns->where('status', 'success')->count(),
            'failed_sources' => $latestRuns->where('status', 'failed')->count(),
            'records_fetched' => (int) $latestRuns->sum('records_fetched'),
            'recent_failures' => $sourceRunHistory->where('status', 'failed')->count(),
        ];
    }

    private function buildPipelineOperationalSummary($sourceCatalog, string $pipelineGroup): array
    {
        $sources = collect($sourceCatalog)
            ->where('pipeline_group', $pipelineGroup)
            ->values();

        $rows = $sources
            ->map(function (array $source) {
                $latestRun = $source['latest_source_run'] ?? null;
                $recordsFetched = (int) data_get($latestRun, 'records_fetched', 0);
                $runStatus = (string) data_get($latestRun, 'status', '');

                if (!$source['is_active']) {
                    $maturityLabel = 'Inativa';
                    $maturityTone = 'neutral';
                } elseif (!$latestRun) {
                    $maturityLabel = 'Sem coleta recente';
                    $maturityTone = 'warning';
                } elseif ($runStatus === 'failed') {
                    $maturityLabel = 'Com falha';
                    $maturityTone = 'danger';
                } elseif ($recordsFetched <= 0) {
                    $maturityLabel = 'Sem sinal util';
                    $maturityTone = 'warning';
                } else {
                    $maturityLabel = 'Estavel';
                    $maturityTone = 'success';
                }

                return [
                    'key' => $source['key'],
                    'name' => $source['name'],
                    'is_active' => (bool) $source['is_active'],
                    'is_priority_focus' => (bool) $source['is_priority_focus'],
                    'latest_source_run' => $latestRun,
                    'records_fetched' => $recordsFetched,
                    'maturity_label' => $maturityLabel,
                    'maturity_tone' => $maturityTone,
                    'current_readiness' => (string) ($source['current_readiness'] ?? 'Catalogada'),
                ];
            })
            ->sortByDesc('is_priority_focus')
            ->values();

        $activeSources = $rows->where('is_active', true);
        $matureSources = $activeSources->where('maturity_tone', 'success');
        $attentionSources = $activeSources->whereIn('maturity_tone', ['warning', 'danger']);
        $zeroSignalSources = $activeSources->filter(fn (array $item) => $item['records_fetched'] <= 0)->count();

        [$headline, $tone, $message, $detailLabel] = match ($pipelineGroup) {
            'group_c_diary_monitor' => $this->diaryMonitorOperationalNarrative(
                $activeSources->count(),
                $matureSources->count(),
                $attentionSources->count(),
                $zeroSignalSources,
            ),
            default => $this->scrapingOperationalNarrative(
                $activeSources->count(),
                $matureSources->count(),
                $attentionSources->count(),
            ),
        };

        return [
            'group' => $pipelineGroup,
            'label' => $this->pipelineGroupLabel($pipelineGroup),
            'headline' => $headline,
            'tone' => $tone,
            'message' => $message,
            'detail_label' => $detailLabel,
            'total_sources' => $sources->count(),
            'active_sources' => $activeSources->count(),
            'priority_sources' => $rows->where('is_priority_focus', true)->count(),
            'mature_sources' => $matureSources->count(),
            'attention_sources' => $attentionSources->count(),
            'zero_signal_sources' => $zeroSignalSources,
            'rows' => $rows->all(),
        ];
    }

    private function scrapingOperationalNarrative(int $activeSources, int $matureSources, int $attentionSources): array
    {
        if ($attentionSources === 0 && $matureSources > 0) {
            return [
                'Grupo B fechado operacionalmente',
                'success',
                'As fontes ativas do scraping estruturado estao gerando sinal util nas ultimas coletas.',
                'Maturidade por fonte do scraping estruturado',
            ];
        }

        if ($matureSources >= max(1, $activeSources - 1)) {
            return [
                'Grupo B quase fechado',
                'info',
                'O scraping estruturado esta maduro; restam poucas fontes sem sinal util ou ainda em ajuste fino.',
                'Maturidade por fonte do scraping estruturado',
            ];
        }

        return [
            'Grupo B em calibracao operacional',
            'warning',
            'O scraping estruturado esta funcional, mas ainda ha fontes ativas que pedem ajuste para fechar o bloco.',
            'Maturidade por fonte do scraping estruturado',
        ];
    }

    private function diaryMonitorOperationalNarrative(
        int $activeSources,
        int $matureSources,
        int $attentionSources,
        int $zeroSignalSources,
    ): array {
        if ($attentionSources === 0 && $matureSources > 0) {
            return [
                'Grupo C fechado operacionalmente',
                'success',
                'O monitor DOU/DOE esta gerando sinal util nas fontes estaduais e federais configuradas.',
                'Maturidade por fonte do monitor DOU/DOE',
            ];
        }

        if ($matureSources >= max(1, $activeSources - 1)) {
            return [
                'Grupo C quase fechado',
                'info',
                $zeroSignalSources > 0
                    ? 'O monitor DOU/DOE esta maduro; restam poucas fontes em observacao ou sem publicacao util no recorte atual.'
                    : 'O monitor DOU/DOE esta maduro; restam poucos ajustes finos para fechar o bloco.',
                'Maturidade por fonte do monitor DOU/DOE',
            ];
        }

        return [
            'Grupo C em calibracao operacional',
            'warning',
            'O monitor DOU/DOE esta funcional, mas ainda ha fontes ativas que pedem ajuste para fechar o bloco.',
            'Maturidade por fonte do monitor DOU/DOE',
        ];
    }

    private function normalizedHistoryFilters(Request $request): array
    {
        return [
            'status' => (string) $request->query('status', 'all'),
            'municipality_id' => $request->query('municipality_id') ? (string) $request->query('municipality_id') : '',
            'mode' => (string) $request->query('mode', 'all'),
            'operator' => trim((string) $request->query('operator', '')),
            'reason' => trim((string) $request->query('reason', '')),
            'operational_state' => (string) $request->query('operational_state', 'all'),
        ];
    }

    private function buildHistoryQuery(array $filters)
    {
        $historyQuery = ApiSyncLog::query()
            ->radarFederalPrograms()
            ->with('municipality')
            ->latest('id');

        if (!empty($filters['municipality_id'])) {
            $historyQuery->where('municipality_id', $filters['municipality_id']);
        }

        if (in_array($filters['status'] ?? 'all', ['queued', 'running', 'success', 'failed'], true)) {
            $historyQuery->where('status', $filters['status']);
        }

        if (($filters['mode'] ?? 'all') === 'forced') {
            $historyQuery->where('error_details->force', true);
        } elseif (($filters['mode'] ?? 'all') === 'normal') {
            $historyQuery->where(function ($query) {
                $query
                    ->where('error_details->force', false)
                    ->orWhereNull('error_details->force');
            });
        }

        if (($filters['operator'] ?? '') !== '') {
            $needle = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $filters['operator']) . '%';
            $historyQuery->where(function ($query) use ($needle) {
                $query
                    ->whereRaw(
                        "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(error_details, '$.last_operator.name')), '')) LIKE LOWER(?)",
                        [$needle]
                    )
                    ->orWhereRaw(
                        "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(error_details, '$.last_operator.email')), '')) LIKE LOWER(?)",
                        [$needle]
                    )
                    ->orWhereRaw(
                        "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(error_details, '$.triggered_by.name')), '')) LIKE LOWER(?)",
                        [$needle]
                    )
                    ->orWhereRaw(
                        "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(error_details, '$.triggered_by.email')), '')) LIKE LOWER(?)",
                        [$needle]
                    );
            });
        }

        if (($filters['reason'] ?? '') !== '') {
            $needle = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $filters['reason']) . '%';
            $historyQuery->where(function ($query) use ($needle) {
                $query
                    ->whereRaw(
                        "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(error_details, '$.last_operation_reason')), '')) LIKE LOWER(?)",
                        [$needle]
                    )
                    ->orWhereRaw(
                        "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(error_details, '$.operation_reason')), '')) LIKE LOWER(?)",
                        [$needle]
                    )
                    ->orWhereRaw(
                        "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(error_details, '$.stale_closed_reason')), '')) LIKE LOWER(?)",
                        [$needle]
                    );
            });
        }

        $operationalState = $filters['operational_state'] ?? 'all';

        if ($operationalState === 'auto_closed') {
            $historyQuery->where('error_details->stale_auto_closed', true);
        } elseif ($operationalState === 'retried') {
            $historyQuery->where(function ($query) {
                $query
                    ->whereNotNull('error_details->retried_to_log_id')
                    ->orWhereNotNull('error_details->retry_of_log_id');
            });
        } elseif ($operationalState === 'retry_requested') {
            $historyQuery->whereNotNull('error_details->retry_of_log_id');
        } elseif ($operationalState === 'retry_source') {
            $historyQuery->whereNotNull('error_details->retried_to_log_id');
        }

        return $historyQuery;
    }

    private function historyRowPayload(ApiSyncLog $log): array
    {
        return array_merge(
            $this->serializeExecution($log),
            [
                'municipality_id' => $log->municipality_id,
                'municipality_name' => $log->municipality?->name ?? 'Municipio removido',
                'queued_via' => (string) data_get($log->error_details, 'queued_via', 'nao_informado'),
                'queue_name' => (string) data_get($log->error_details, 'queue_name', $this->radarQueueName()),
                'timeline_consolidated' => $this->timelineAsString($this->formattedAuditEvents($log)),
            ],
        );
    }

    private function buildMunicipalitySummary($logs)
    {
        return $logs
            ->groupBy('municipality_id')
            ->map(function ($groupedLogs, $municipalityId) {
                /** @var \Illuminate\Support\Collection<int, ApiSyncLog> $groupedLogs */
                $latest = $groupedLogs->sortByDesc('id')->first();
                $serializedLatest = $latest ? $this->serializeExecution($latest) : [];

                return [
                    'municipality_id' => (int) $municipalityId,
                    'municipality_name' => $latest?->municipality?->name ?? 'Municipio removido',
                    'total' => $groupedLogs->count(),
                    'failed' => $groupedLogs->where('status', 'failed')->count(),
                    'success' => $groupedLogs->where('status', 'success')->count(),
                    'running' => $groupedLogs->where('status', 'running')->count(),
                    'queued' => $groupedLogs->where('status', 'queued')->count(),
                    'auto_closed' => $groupedLogs->filter(fn (ApiSyncLog $log) => (bool) data_get($log->error_details, 'stale_auto_closed', false))->count(),
                    'retried' => $groupedLogs->filter(fn (ApiSyncLog $log) => data_get($log->error_details, 'retried_to_log_id') || data_get($log->error_details, 'retry_of_log_id'))->count(),
                    'latest_status_label' => $serializedLatest['status_label'] ?? '—',
                    'latest_operator_name' => $serializedLatest['operator_name'] ?? null,
                    'latest_reason' => $serializedLatest['operation_reason'] ?? null,
                    'latest_updated_at_human' => $serializedLatest['updated_at_human'] ?? null,
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function exportFilterRows(array $filters): array
    {
        return array_merge(
            [['Exportado em', now()->format('d/m/Y H:i:s')]],
            collect($this->exportFilterLabels($filters))
                ->map(fn ($value, $label) => [$label, $value])
                ->values()
                ->all()
        );
    }

    private function xlsxFilterSheetRows(array $filters): array
    {
        return array_merge(
            [['Filtro', 'Valor']],
            collect($this->exportFilterLabels($filters))
                ->map(fn ($value, $label) => [$label, $value])
                ->values()
                ->all()
        );
    }

    private function exportFilterLabels(array $filters): array
    {
        $municipalityName = 'Todos';

        if (!empty($filters['municipality_id'])) {
            $municipalityName = Municipality::query()
                ->whereKey($filters['municipality_id'])
                ->value('name') ?? 'Municipio não encontrado';
        }

        return [
            'Municipio' => $municipalityName,
            'Status' => match ($filters['status'] ?? 'all') {
                'queued' => 'Na fila',
                'running' => 'Em execução',
                'success' => 'Concluido',
                'failed' => 'Falhou',
                default => 'Todos',
            },
            'Modo' => match ($filters['mode'] ?? 'all') {
                'normal' => 'Normal',
                'forced' => 'Forcado',
                default => 'Todos',
            },
            'Operador' => $filters['operator'] ?: 'Todos',
            'Motivo' => $filters['reason'] ?: 'Todos',
            'Recorte operacional' => match ($filters['operational_state'] ?? 'all') {
                'auto_closed' => 'Autoencerradas',
                'retried' => 'Reenfileiradas',
                'retry_requested' => 'Novos retries',
                'retry_source' => 'Origens retry',
                default => 'Todos',
            },
        ];
    }

    private function historyExportRows(array $historyRows): array
    {
        return collect($historyRows)
            ->map(function (array $row) {
                return [
                    $row['id'],
                    $row['municipality_name'],
                    $row['status_label'],
                    $row['force'] ? 'Forcado' : 'Normal',
                    $row['queued_via'],
                    $row['queue_name'],
                    $row['operator_name'] ?: 'sistema',
                    $row['operator_email'] ?: '',
                    $row['operation_reason'] ?: '',
                    (string) data_get($row, 'result.novos', 0),
                    (string) data_get($row, 'result.atualizados', 0),
                    (string) data_get($row, 'result.descartados', 0),
                    (string) $row['records_fetched'],
                    (string) $row['records_saved'],
                    $row['started_at'] ?: '',
                    $row['finished_at'] ?: '',
                    $row['duration_ms'] !== null ? (string) $row['duration_ms'] : '',
                    $row['was_auto_closed_stale'] ? 'Sim' : 'Nao',
                    $row['is_stale'] ? 'Sim' : 'Nao',
                    $row['error_message'] ?: ($row['stale_reason'] ?: ''),
                    $row['timeline_consolidated'] ?: '',
                ];
            })
            ->values()
            ->all();
    }

    private function summaryExportRows(array $summaryRows): array
    {
        return collect($summaryRows)
            ->map(function (array $row) {
                return [
                    $row['municipality_name'],
                    (string) $row['total'],
                    (string) $row['success'],
                    (string) $row['failed'],
                    (string) $row['running'],
                    (string) $row['queued'],
                    (string) $row['auto_closed'],
                    (string) $row['retried'],
                    $row['latest_status_label'],
                    $row['latest_operator_name'] ?: '',
                    $row['latest_reason'] ?: '',
                    $row['latest_updated_at_human'] ?: '',
                ];
            })
            ->values()
            ->all();
    }

    private function timelineAsString(array $auditEvents): string
    {
        return collect($auditEvents)
            ->map(function (array $event) {
                $parts = array_filter([
                    $event['at'] ?? null,
                    $event['actor_name'] ?? null,
                    $event['label'] ?? null,
                    data_get($event, 'context.reason') ? 'Motivo: ' . data_get($event, 'context.reason') : null,
                    data_get($event, 'context.stale_reason') ? 'Stale: ' . data_get($event, 'context.stale_reason') : null,
                ]);

                return implode(' | ', $parts);
            })
            ->implode("\n");
    }

    private function exportFilename(string $base, string $extension): string
    {
        return $base . '-' . now()->format('Ymd-His') . '.' . $extension;
    }

    private function parseTextareaLines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter(fn (string $item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function sourceFocusLabel(string $sourceKey, string $pipelineGroup): ?string
    {
        return match ($pipelineGroup) {
            'group_b_scraping' => in_array($sourceKey, ['fnde', 'fns', 'bndes', 'funasa', 'fnas', 'caixa', 'finep'], true)
                ? 'Foco atual do Grupo B'
                : null,
            'group_c_diary_monitor' => in_array($sourceKey, ['diario_oficial_uniao', 'programas_estaduais'], true)
                ? 'Foco atual do Grupo C'
                : null,
            default => null,
        };
    }

    private function sourceFocusNote(string $pipelineGroup): ?string
    {
        return match ($pipelineGroup) {
            'group_b_scraping' => 'Endurecimento atual: entrypoints e filtros específicos de scraping.',
            'group_c_diary_monitor' => 'Monitoramento atual: diarios oficiais com filtros por relevancia e pontos configuraveis.',
            default => null,
        };
    }

    private function pipelineGroupLabel(string $group): string
    {
        return match ($group) {
            'group_a_api' => 'Grupo A · APIs oficiais',
            'group_b_scraping' => 'Grupo B · Scraping estruturado',
            'group_c_diary_monitor' => 'Grupo C · Monitor DOU/DOE',
            'group_d_human_curation' => 'Grupo D · Curadoria humana',
            default => 'Sem grupo',
        };
    }

    private function operationalStatusLabel(string $status): string
    {
        return match ($status) {
            'live' => 'Em producao',
            'mapped' => 'Mapeada',
            'pipeline_next' => 'Proxima pipeline',
            'curation_only' => 'Curadoria humana',
            default => 'Catalogada',
        };
    }

    private function operationalStatusTone(string $status): string
    {
        return match ($status) {
            'live' => 'success',
            'pipeline_next' => 'info',
            'curation_only' => 'warning',
            'mapped' => 'neutral',
            default => 'neutral',
        };
    }

    private function activeExecutionForMunicipality(Municipality $municipality): ?ApiSyncLog
    {
        return ApiSyncLog::query()
            ->radarFederalPrograms()
            ->where('municipality_id', $municipality->id)
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();
    }

    private function serializeExecution(?ApiSyncLog $execution): ?array
    {
        if (!$execution) {
            return null;
        }

        $startedAt = $execution->started_at;
        $queuedTooLong = $execution->status === 'queued' && $startedAt?->lt(now()->subMinutes(5));
        $runningTooLong = $execution->status === 'running' && $startedAt?->lt(now()->subMinutes(15));
        $autoClosedStale = (bool) data_get($execution->error_details, 'stale_auto_closed', false);
        $result = is_array($execution->error_details)
            ? ($execution->error_details['result'] ?? null)
            : null;
        $auditEvents = $this->formattedAuditEvents($execution);
        $lastAuditEvent = collect($auditEvents)->last();
        $staleReason = $queuedTooLong
            ? 'Execução aguardando na fila ha mais de 5 minutos.'
            : ($runningTooLong
                ? 'Execução em andamento ha mais de 15 minutos.'
                : data_get($execution->error_details, 'stale_closed_reason'));
        $canRetry = $this->canRetryExecution($execution);

        return [
            'id' => $execution->id,
            'status' => $execution->status,
            'status_label' => match ($execution->status) {
                'queued' => 'Na fila',
                'running' => 'Em execução',
                'success' => 'Concluido',
                'failed' => 'Falhou',
                default => ucfirst((string) $execution->status),
            },
            'status_tone' => match ($execution->status) {
                'queued' => 'warning',
                'running' => 'info',
                'success' => 'success',
                'failed' => 'danger',
                default => 'neutral',
            },
            'is_busy' => in_array($execution->status, ['queued', 'running'], true),
            'records_fetched' => (int) $execution->records_fetched,
            'records_saved' => (int) $execution->records_saved,
            'error_message' => $execution->error_message,
            'duration_ms' => $execution->duration_ms,
            'started_at' => $execution->started_at?->toIso8601String(),
            'started_at_human' => $execution->started_at?->diffForHumans(),
            'finished_at' => $execution->finished_at?->toIso8601String(),
            'finished_at_human' => $execution->finished_at?->diffForHumans(),
            'updated_at_human' => $execution->updated_at?->diffForHumans(),
            'force' => (bool) data_get($execution->error_details, 'force', false),
            'result' => is_array($result) ? $result : null,
            'is_stale' => $queuedTooLong || $runningTooLong || $autoClosedStale,
            'stale_reason' => $staleReason,
            'was_auto_closed_stale' => $autoClosedStale,
            'can_retry' => $canRetry,
            'retried_to_log_id' => data_get($execution->error_details, 'retried_to_log_id'),
            'operator_name' => data_get($execution->error_details, 'last_operator.name', data_get($execution->error_details, 'triggered_by.name')),
            'operator_email' => data_get($execution->error_details, 'last_operator.email', data_get($execution->error_details, 'triggered_by.email')),
            'operation_reason' => data_get($execution->error_details, 'last_operation_reason', data_get($execution->error_details, 'operation_reason')),
            'audit_events' => $auditEvents,
            'last_audit_event' => $lastAuditEvent,
        ];
    }

    private function queueHealthPayload(): array
    {
        $connection = $this->resolveAsyncQueueConnection();
        $jobsPending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $radarQueueName = $this->radarQueueName();
        $radarJobsPending = Schema::hasTable('jobs')
            ? DB::table('jobs')->where('queue', $radarQueueName)->count()
            : 0;
        $otherJobsPending = max(0, $jobsPending - $radarJobsPending);
        $failedJobsTotal = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $baseQuery = ApiSyncLog::query()->radarFederalPrograms();
        $queuedCount = (clone $baseQuery)->where('status', 'queued')->count();
        $runningCount = (clone $baseQuery)->where('status', 'running')->count();
        $successCount = (clone $baseQuery)->where('status', 'success')->count();
        $failedCount = (clone $baseQuery)->where('status', 'failed')->count();
        $stalledQueuedCount = (clone $baseQuery)
            ->where('status', 'queued')
            ->where('started_at', '<', now()->subMinutes(5))
            ->count();
        $longRunningCount = (clone $baseQuery)
            ->where('status', 'running')
            ->where('started_at', '<', now()->subMinutes(15))
            ->count();
        $avgDurationMs = (int) round((clone $baseQuery)
            ->where('status', 'success')
            ->whereNotNull('duration_ms')
            ->avg('duration_ms') ?: 0);
        $lastFailure = (clone $baseQuery)
            ->where('status', 'failed')
            ->latest('id')
            ->first();
        $lastSuccess = (clone $baseQuery)
            ->where('status', 'success')
            ->latest('id')
            ->first();
        $retryableCount = $this->latestRadarExecutionsByMunicipality()
            ->filter(fn (ApiSyncLog $execution) => $execution->municipality && !$this->activeExecutionForMunicipality($execution->municipality))
            ->filter(fn (ApiSyncLog $execution) => $this->canRetryExecution($execution))
            ->count();
        $tone = 'success';
        $headline = 'Fila operacional';
        $message = 'Sem sinais de bloqueio no momento.';

        if ($stalledQueuedCount > 0 || $longRunningCount > 0) {
            $tone = 'danger';
            $headline = 'Fila requer atencao';
            $message = 'Existem execucoes possivelmente travadas ou aguardando processamento alem do esperado.';
        } elseif ($jobsPending > 0 && $runningCount === 0) {
            $tone = 'warning';
            $headline = 'Fila acumulando';
            $message = 'Ha jobs pendentes sem execução ativa no momento. Isso costuma indicar ausencia de worker.';
        } elseif ($runningCount > 0 || $queuedCount > 0) {
            $tone = 'info';
            $headline = 'Fila processando';
            $message = 'Existem sincronizacoes em andamento ou aguardando execução.';
        }

        return [
            'queue_default' => (string) config('queue.default', 'sync'),
            'resolved_connection' => $connection,
            'radar_queue_name' => $radarQueueName,
            'radar_worker_queues' => (string) config('queue.radar_sync_worker_queues', 'default,radar-sync'),
            'jobs_pending' => $jobsPending,
            'radar_jobs_pending' => $radarJobsPending,
            'other_jobs_pending' => $otherJobsPending,
            'failed_jobs_total' => $failedJobsTotal,
            'queued_count' => $queuedCount,
            'running_count' => $runningCount,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'retryable_count' => $retryableCount,
            'stalled_queued_count' => $stalledQueuedCount,
            'long_running_count' => $longRunningCount,
            'avg_duration_ms' => $avgDurationMs,
            'last_failure' => $lastFailure ? $this->serializeExecution($lastFailure) : null,
            'last_success' => $lastSuccess ? $this->serializeExecution($lastSuccess) : null,
            'tone' => $tone,
            'headline' => $headline,
            'message' => $message,
        ];
    }

    private function radarQueueName(): string
    {
        $configured = trim((string) config('queue.radar_sync_queue', 'radar-sync'));

        if ($configured !== '') {
            return $configured;
        }

        $connection = $this->resolveAsyncQueueConnection() ?: (string) config('queue.default', 'sync');
        $fallback = data_get(config("queue.connections.{$connection}"), 'queue', 'default');

        return trim((string) $fallback) ?: 'default';
    }

    private function createQueuedExecution(
        Municipality $municipality,
        bool $force,
        string $queuedVia,
        array $extraDetails = [],
    ): ApiSyncLog {
        $operator = $this->currentOperatorPayload();
        $details = array_merge([
            'force' => $force,
            'queued_via' => $queuedVia,
            'queue_name' => $this->radarQueueName(),
            'triggered_by' => $operator,
        ], $extraDetails);

        $syncLog = ApiSyncLog::query()->create([
            'municipality_id' => $municipality->id,
            'source' => ApiSyncLog::RADAR_EXECUTION_SOURCE,
            'data_type' => ApiSyncLog::RADAR_EXECUTION_DATA_TYPE,
            'status' => 'queued',
            'records_fetched' => 0,
            'records_saved' => 0,
            'started_at' => now(),
            'error_details' => $details,
        ]);

        $this->appendAuditEvent(
            $syncLog,
            'queued',
            'Execução enfileirada no painel do Radar.',
            $operator,
            [
                'queued_via' => $queuedVia,
                'queue_name' => $this->radarQueueName(),
                'force' => $force,
                'reason' => data_get($details, 'operation_reason'),
            ],
        );

        return $syncLog->fresh();
    }

    private function dispatchQueuedExecution(
        ApiSyncLog $syncLog,
        Municipality $municipality,
        bool $force,
        string $connection,
    ): void {
        $job = new SyncFederalProgramsJob(
            municipalityId: $municipality->id,
            syncLogId: $syncLog->id,
            force: $force,
        );
        $job->onQueue($this->radarQueueName());

        Queue::connection($connection)->push($job);
    }

    private function isRadarExecution(ApiSyncLog $execution): bool
    {
        return $execution->data_type === ApiSyncLog::RADAR_EXECUTION_DATA_TYPE
            && in_array($execution->source, [
                ApiSyncLog::RADAR_EXECUTION_SOURCE,
                ApiSyncLog::LEGACY_RADAR_EXECUTION_SOURCE,
            ], true);
    }

    private function resolveMunicipalityFilter(Request $request): ?Municipality
    {
        $municipalityId = $request->integer('municipality_id');

        if (!$municipalityId) {
            return null;
        }

        return Municipality::query()->find($municipalityId);
    }

    private function latestRadarExecutionsByMunicipality(?Municipality $municipality = null)
    {
        $query = ApiSyncLog::query()
            ->radarFederalPrograms()
            ->with('municipality')
            ->latest('id');

        if ($municipality) {
            $query->where('municipality_id', $municipality->id);
        }

        return $query->get()->unique('municipality_id')->values();
    }

    private function expireStaleExecutions(
        ?Municipality $municipality = null,
        ?array $operator = null,
        ?string $reason = null,
        string $origin = 'automatic_monitor',
    ): int
    {
        $query = ApiSyncLog::query()
            ->radarFederalPrograms()
            ->whereIn('status', ['queued', 'running']);

        if ($municipality) {
            $query->where('municipality_id', $municipality->id);
        }

        $expired = 0;

        foreach ($query->get() as $execution) {
            if (!$this->executionIsStale($execution)) {
                continue;
            }

            $this->closeExecutionAsStale($execution, $operator, $reason, $origin);
            $expired++;
        }

        return $expired;
    }

    private function executionIsStale(ApiSyncLog $execution): bool
    {
        return $this->staleReasonForExecution($execution) !== null;
    }

    private function staleReasonForExecution(ApiSyncLog $execution): ?string
    {
        if ($execution->status === 'queued' && $execution->started_at?->lt(now()->subMinutes(5))) {
            return 'Execução encerrada automaticamente apos permanecer na fila por mais de 5 minutos.';
        }

        if ($execution->status === 'running' && $execution->started_at?->lt(now()->subMinutes(15))) {
            return 'Execução encerrada automaticamente apos exceder 15 minutos em andamento.';
        }

        return null;
    }

    private function closeExecutionAsStale(
        ApiSyncLog $execution,
        ?array $operator = null,
        ?string $actionReason = null,
        string $origin = 'automatic_monitor',
    ): ApiSyncLog
    {
        $staleReason = $this->staleReasonForExecution($execution)
            ?? (string) data_get($execution->error_details, 'stale_closed_reason', '');

        if ($staleReason === '') {
            return $execution;
        }

        $details = is_array($execution->error_details) ? $execution->error_details : [];

        if ($execution->status === 'failed' && (bool) data_get($details, 'stale_auto_closed', false)) {
            return $execution;
        }

        $eventActor = $operator ?? $this->systemActorPayload('radar-guard');
        $operationReason = $actionReason ?: 'Execução stale encerrada para proteger a trilha operacional.';

        $execution->update([
            'status' => 'failed',
            'finished_at' => now(),
            'error_message' => $staleReason,
            'error_details' => array_merge($details, [
                'stale_auto_closed' => true,
                'stale_previous_status' => $execution->status,
                'stale_closed_at' => now()->toIso8601String(),
                'stale_closed_reason' => $staleReason,
                'stale_closed_origin' => $origin,
                'last_operation_reason' => $operationReason,
                'last_operator' => $eventActor,
            ]),
        ]);

        $execution = $execution->fresh();

        $this->appendAuditEvent(
            $execution,
            'stale_closed',
            'Execução encerrada por stale.',
            $eventActor,
            [
                'stale_reason' => $staleReason,
                'reason' => $operationReason,
                'origin' => $origin,
                'previous_status' => data_get($details, 'stale_previous_status', $execution->status),
            ],
        );

        return $execution->fresh();
    }

    private function canRetryExecution(ApiSyncLog $execution): bool
    {
        if (data_get($execution->error_details, 'retried_to_log_id')) {
            return false;
        }

        return $execution->status === 'failed' || $this->executionIsStale($execution);
    }

    private function validatedAuditReason(Request $request, string $fallback): string
    {
        $reason = trim((string) $request->input('reason', ''));

        return $reason !== '' ? $reason : $fallback;
    }

    private function currentOperatorPayload(): array
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => (string) $user->role?->value,
            ];
        }

        return $this->systemActorPayload('unknown-admin');
    }

    private function systemActorPayload(string $name): array
    {
        return [
            'id' => null,
            'name' => $name,
            'email' => null,
            'role' => 'system',
        ];
    }

    private function appendAuditEvent(
        ApiSyncLog $execution,
        string $event,
        string $label,
        ?array $actor = null,
        array $context = [],
        array $extraDetails = [],
    ): ApiSyncLog {
        $details = is_array($execution->error_details) ? $execution->error_details : [];
        $timeline = collect(data_get($details, 'audit_timeline', []))
            ->push(array_filter([
                'event' => $event,
                'label' => $label,
                'at' => now()->toIso8601String(),
                'actor' => $actor ?? $this->systemActorPayload('radar-guard'),
                'context' => $context ?: null,
            ], fn ($value) => $value !== null))
            ->values()
            ->all();

        $execution->update([
            'error_details' => array_merge($details, $extraDetails, [
                'audit_timeline' => $timeline,
            ]),
        ]);

        return $execution->fresh();
    }

    private function formattedAuditEvents(ApiSyncLog $execution): array
    {
        $events = data_get($execution->error_details, 'audit_timeline', []);

        if (!is_array($events)) {
            return [];
        }

        return collect($events)
            ->filter(fn ($event) => is_array($event))
            ->map(function (array $event) {
                $context = is_array($event['context'] ?? null) ? $event['context'] : [];

                return [
                    'event' => (string) ($event['event'] ?? 'unknown'),
                    'label' => (string) ($event['label'] ?? 'Evento operacional'),
                    'at' => $event['at'] ?? null,
                    'at_human' => !empty($event['at']) ? \Carbon\Carbon::parse($event['at'])->diffForHumans() : null,
                    'actor_name' => data_get($event, 'actor.name', 'sistema'),
                    'actor_email' => data_get($event, 'actor.email'),
                    'context' => $context,
                ];
            })
            ->values()
            ->all();
    }

    private function serializeSourceRun(ApiSyncLog $log, bool $latestOnly = false): array
    {
        $statusLabel = match ($log->status) {
            'success' => $latestOnly ? 'Última coleta OK' : 'Concluída',
            'failed' => $latestOnly ? 'Última coleta falhou' : 'Falhou',
            'running' => 'Coletando',
            'queued' => 'Na fila',
            default => ucfirst((string) $log->status),
        };

        $statusTone = match ($log->status) {
            'success' => 'success',
            'failed' => 'danger',
            'running' => 'info',
            'queued' => 'warning',
            default => 'neutral',
        };

        return [
            'id' => $log->id,
            'source' => $log->source,
            'source_name' => (string) data_get($log->error_details, 'source_name', $log->source),
            'status' => $log->status,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'records_fetched' => (int) $log->records_fetched,
            'duration_ms' => (int) ($log->duration_ms ?? 0),
            'message' => (string) data_get($log->error_details, 'message', $log->error_message),
            'pipeline_group' => (string) data_get($log->error_details, 'pipeline_group', ''),
            'pipeline_group_label' => $this->pipelineGroupLabel((string) data_get($log->error_details, 'pipeline_group', '')),
            'municipality_name' => $log->municipality?->name ?? 'Município removido',
            'finished_at_human' => $log->finished_at?->diffForHumans(),
            'started_at_human' => $log->started_at?->diffForHumans(),
            'parent_sync_log_id' => data_get($log->error_details, 'parent_sync_log_id'),
            'debug' => is_array(data_get($log->error_details, 'debug')) ? data_get($log->error_details, 'debug') : [],
        ];
    }

    private function resolveAsyncQueueConnection(): ?string
    {
        $default = (string) config('queue.default', 'sync');
        $connections = array_keys((array) config('queue.connections', []));

        if ($default !== 'sync') {
            return $default;
        }

        foreach (['database', 'background', 'deferred'] as $candidate) {
            if (in_array($candidate, $connections, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
