<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\MunicipalityCoverageAlert;
use App\Models\User;
use App\Services\Support\CoverageAlertExportService;
use App\Services\Support\MunicipalityConfigurationStatusService;
use App\Services\Support\MunicipalityCoverageAlertService;
use App\Services\Support\MunicipalityCoverageExecutiveMailGovernanceService;
use App\Services\Support\MunicipalityCoverageExecutiveService;
use App\Services\Support\MunicipalityCoverageExecutiveReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MunicipalityCoverageAlertController extends Controller
{
    public function __construct(
        private readonly MunicipalityCoverageAlertService $coverageAlerts,
        private readonly CoverageAlertExportService $export,
        private readonly MunicipalityConfigurationStatusService $configurationStatus,
        private readonly MunicipalityCoverageExecutiveService $executive,
        private readonly MunicipalityCoverageExecutiveReportService $report,
        private readonly MunicipalityCoverageExecutiveMailGovernanceService $mailGovernance,
    ) {}

    public function index(Request $request): View
    {
        $savedFilters = $this->savedFilters($request->user());
        $filters = $this->normalizedFilters($request, $savedFilters);

        if ($filters['preset'] === 'critical_active') {
            $filters = $this->coverageAlerts->activateCriticalPreset($filters);
        } elseif (str_starts_with($filters['preset'], 'saved:')) {
            $savedKey = Str::after($filters['preset'], 'saved:');
            if (isset($savedFilters[$savedKey]['filters']) && is_array($savedFilters[$savedKey]['filters'])) {
                $filters = array_merge($filters, $savedFilters[$savedKey]['filters']);
                $filters['preset'] = 'saved:' . $savedKey;
            }
        }

        $query = $this->buildFilteredQuery($filters);

        $alerts = $query
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderByRaw("case when severity = 'high' then 0 when severity = 'medium' then 1 else 2 end")
            ->latest('last_detected_at')
            ->paginate(20)
            ->withQueryString();
        $alerts->setCollection(
            $alerts->getCollection()->map(function (MunicipalityCoverageAlert $alert) {
                $alert->setAttribute('workflow_snapshot', $this->coverageAlerts->workflowSnapshot($alert));

                return $alert;
            })
        );

        $allAlertsQuery = MunicipalityCoverageAlert::query();
        $summary = [
            'total' => (clone $allAlertsQuery)->count(),
            'active' => (clone $allAlertsQuery)->where('status', 'active')->count(),
            'resolved' => (clone $allAlertsQuery)->where('status', 'resolved')->count(),
            'high' => (clone $allAlertsQuery)->where('severity', 'high')->count(),
            'medium' => (clone $allAlertsQuery)->where('severity', 'medium')->count(),
        ];

        $municipalityOptions = Municipality::query()
            ->where('subscription_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $adminOptions = User::query()
            ->admins()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $eventTypeOptions = $this->executive->eventTypeOptions();
        $slaByType = $this->executive->slaByType();
        $comparison = $this->executive->coverageComparison(5);
        $executiveRanking = $this->executive->executiveRankingWithTrend(10);
        $snapshotHistory = $this->executive->recentSnapshots(6);
        $executiveSummary = $this->executive->currentSummary();
        $temporalComparison = $this->executive->temporalSnapshotComparison(6);
        $improvementCurve = $this->executive->municipalityImprovementCurve(8);
        $mailingGovernance = $this->mailGovernance->panelData();
        $myQueue = $this->coverageAlerts->personalQueueFor($request->user(), 8);
        $myQueueSummary = $this->coverageAlerts->personalQueueSummary($myQueue);
        $summary = array_merge($summary, [
            'tracked_municipalities' => $executiveSummary['tracked_municipalities'] ?? 0,
            'average_configuration_score' => $executiveSummary['average_configuration_score'] ?? 0,
            'average_executive_score' => $executiveSummary['average_executive_score'] ?? 0,
            'sla_breaches_total' => $executiveSummary['sla_breaches_total'] ?? 0,
            'my_owned_alerts' => $myQueueSummary['total'] ?? 0,
            'my_owner_sla_breached' => $myQueueSummary['breached'] ?? 0,
        ]);

        $recurrenceByMunicipality = MunicipalityCoverageAlert::query()
            ->select([
                'municipality_id',
                DB::raw('COUNT(*) as alerts_total'),
                DB::raw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_total"),
                DB::raw("SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_total"),
                DB::raw("SUM(CASE WHEN first_detected_at >= '" . now()->subDays(30)->toDateTimeString() . "' THEN 1 ELSE 0 END) as last_30d_total"),
                DB::raw('MAX(last_detected_at) as last_detected_at'),
            ])
            ->with('municipality:id,name')
            ->groupBy('municipality_id')
            ->orderByDesc('last_30d_total')
            ->orderByDesc('alerts_total')
            ->limit(6)
            ->get();

        $trendDays = collect(range(13, 0))
            ->map(fn (int $daysAgo) => now()->copy()->subDays($daysAgo)->startOfDay())
            ->push(now()->copy()->startOfDay())
            ->unique(fn (Carbon $day) => $day->toDateString())
            ->values();

        $createdTrend = MunicipalityCoverageAlert::query()
            ->selectRaw('DATE(first_detected_at) as day, COUNT(*) as total')
            ->whereNotNull('first_detected_at')
            ->whereDate('first_detected_at', '>=', now()->subDays(14)->toDateString())
            ->groupBy(DB::raw('DATE(first_detected_at)'))
            ->pluck('total', 'day');

        $resolvedTrend = MunicipalityCoverageAlert::query()
            ->selectRaw('DATE(resolved_at) as day, COUNT(*) as total')
            ->whereNotNull('resolved_at')
            ->whereDate('resolved_at', '>=', now()->subDays(14)->toDateString())
            ->groupBy(DB::raw('DATE(resolved_at)'))
            ->pluck('total', 'day');

        $trend = $trendDays->map(function (Carbon $day) use ($createdTrend, $resolvedTrend) {
            $key = $day->toDateString();

            return [
                'day' => $key,
                'label' => $day->format('d/m'),
                'created' => (int) ($createdTrend[$key] ?? 0),
                'resolved' => (int) ($resolvedTrend[$key] ?? 0),
            ];
        });

        $trendSummary = [
            'created_last_14d' => (int) $trend->sum('created'),
            'resolved_last_14d' => (int) $trend->sum('resolved'),
            'resolution_balance' => (int) $trend->sum('resolved') - (int) $trend->sum('created'),
        ];

        return view('admin/coverage-alerts/index', compact(
            'alerts',
            'filters',
            'summary',
            'municipalityOptions',
            'adminOptions',
            'eventTypeOptions',
            'savedFilters',
            'slaByType',
            'comparison',
            'executiveRanking',
            'snapshotHistory',
            'temporalComparison',
            'improvementCurve',
            'mailingGovernance',
            'myQueue',
            'myQueueSummary',
            'recurrenceByMunicipality',
            'trend',
            'trendSummary'
        ));
    }

    public function municipality(Municipality $municipality): View
    {
        $timeline = MunicipalityCoverageAlert::query()
            ->where('municipality_id', $municipality->id)
            ->latest('last_detected_at')
            ->get()
            ->map(function (MunicipalityCoverageAlert $alert) {
                $alert->setAttribute('workflow_snapshot', $this->coverageAlerts->workflowSnapshot($alert));

                return $alert;
            });

        $configurationSummary = $this->configurationStatus->summarize($municipality->loadMissing('mayor'));
        $timelineStats = [
            'total' => $timeline->count(),
            'active' => $timeline->where('status', 'active')->count(),
            'resolved' => $timeline->where('status', 'resolved')->count(),
            'high' => $timeline->where('severity', 'high')->count(),
            'last_detected_at' => optional($timeline->max('last_detected_at')),
        ];

        $timelineByDay = $timeline
            ->groupBy(fn (MunicipalityCoverageAlert $alert) => optional($alert->last_detected_at)->format('Y-m-d') ?? 'sem-data')
            ->map(function ($items, $day) {
                return [
                    'day' => $day,
                    'label' => $day !== 'sem-data' ? Carbon::parse($day)->format('d/m/Y') : 'Sem data',
                    'items' => $items->values(),
                ];
            })
            ->values();

        return view('admin/coverage-alerts/municipality', compact(
            'municipality',
            'timeline',
            'timelineStats',
            'timelineByDay',
            'configurationSummary'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $savedFilters = $this->savedFilters($request->user());
        $filters = $this->normalizedFilters($request, $savedFilters);

        if ($filters['preset'] === 'critical_active') {
            $filters = $this->coverageAlerts->activateCriticalPreset($filters);
        } elseif (str_starts_with($filters['preset'], 'saved:')) {
            $savedKey = Str::after($filters['preset'], 'saved:');
            if (isset($savedFilters[$savedKey]['filters'])) {
                $filters = array_merge($filters, $savedFilters[$savedKey]['filters']);
            }
        }

        $rows = $this->buildFilteredQuery($filters)
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderByRaw("case when severity = 'high' then 0 when severity = 'medium' then 1 else 2 end")
            ->latest('last_detected_at')
            ->get()
            ->map(fn (MunicipalityCoverageAlert $alert) => $this->exportRow($alert))
            ->values()
            ->all();

        return $this->export->downloadCsv(
            $this->exportFilename('central-alertas-cobertura', 'csv'),
            $this->exportFilterRows($filters, $savedFilters),
            ['ID', 'Municipio', 'Frente', 'Titulo', 'Severidade', 'Status', 'Owner', 'Acknowledge por', 'Acknowledge em', 'Primeira deteccao', 'Ultima deteccao', 'Resolvido em', 'Mensagem', 'Acao'],
            $rows
        );
    }

    public function exportXlsx(Request $request): BinaryFileResponse
    {
        $savedFilters = $this->savedFilters($request->user());
        $filters = $this->normalizedFilters($request, $savedFilters);

        if ($filters['preset'] === 'critical_active') {
            $filters = $this->coverageAlerts->activateCriticalPreset($filters);
        } elseif (str_starts_with($filters['preset'], 'saved:')) {
            $savedKey = Str::after($filters['preset'], 'saved:');
            if (isset($savedFilters[$savedKey]['filters'])) {
                $filters = array_merge($filters, $savedFilters[$savedKey]['filters']);
            }
        }

        $alerts = $this->buildFilteredQuery($filters)
            ->latest('last_detected_at')
            ->get();

        return $this->export->downloadXlsx(
            $this->exportFilename('central-alertas-cobertura', 'xlsx'),
            [
                [
                    'name' => 'Filtros',
                    'rows' => $this->xlsxFilterSheetRows($filters, $savedFilters),
                ],
                [
                    'name' => 'Alertas',
                    'rows' => array_merge([
                        ['ID', 'Municipio', 'Frente', 'Titulo', 'Severidade', 'Status', 'Owner', 'Acknowledge por', 'Acknowledge em', 'Primeira deteccao', 'Ultima deteccao', 'Resolvido em', 'Mensagem', 'Acao'],
                    ], $alerts->map(fn (MunicipalityCoverageAlert $alert) => $this->exportRow($alert))->all()),
                ],
            ]
        );
    }

    public function exportExecutiveRankingCsv(Request $request): StreamedResponse
    {
        $limit = $this->rankingLimit($request);
        $ranking = $this->executive->executiveRankingWithTrend($limit);
        $temporalComparison = $this->executive->temporalSnapshotComparison(6);

        return $this->export->downloadCsv(
            $this->exportFilename('ranking-executivo-cobertura', 'csv'),
            $this->executiveRankingSummaryRows($temporalComparison, $ranking),
            [
                'Posicao',
                'Posicao anterior',
                'Delta posicao',
                'Municipio',
                'Score executivo',
                'Score anterior',
                'Delta score',
                'Tendencia',
                'Score configuracao',
                'Reincidencia 30d',
                'Breaches SLA',
                'Alertas ativos',
                'Alertas resolvidos',
            ],
            $this->executiveRankingExportRows($ranking)
        );
    }

    public function exportExecutiveRankingXlsx(Request $request): BinaryFileResponse
    {
        $limit = $this->rankingLimit($request);
        $ranking = $this->executive->executiveRankingWithTrend($limit);
        $temporalComparison = $this->executive->temporalSnapshotComparison(6);
        $improvementCurve = $this->executive->municipalityImprovementCurve(12);

        return $this->export->downloadXlsx(
            $this->exportFilename('ranking-executivo-cobertura', 'xlsx'),
            [
                [
                    'name' => 'Resumo Executivo',
                    'rows' => $this->executiveRankingSummaryRows($temporalComparison, $ranking),
                ],
                [
                    'name' => 'Ranking Executivo',
                    'rows' => array_merge([
                        [
                            'Posicao',
                            'Posicao anterior',
                            'Delta posicao',
                            'Municipio',
                            'Score executivo',
                            'Score anterior',
                            'Delta score',
                            'Tendencia',
                            'Score configuracao',
                            'Reincidencia 30d',
                            'Breaches SLA',
                            'Alertas ativos',
                            'Alertas resolvidos',
                        ],
                    ], $this->executiveRankingExportRows($ranking)),
                ],
                [
                    'name' => 'Curva Municipios',
                    'rows' => $this->improvementCurveSheetRows($improvementCurve),
                ],
                [
                    'name' => 'Serie Temporal',
                    'rows' => $this->temporalSeriesSheetRows($temporalComparison),
                ],
            ]
        );
    }

    public function exportExecutiveRankingPdf(Request $request): Response
    {
        return $this->report->pdfDownload('manual', $this->rankingLimit($request));
    }

    public function previewMailing(string $period): View
    {
        $period = $this->validatedPeriod($period);
        $approval = $this->mailGovernance->approvalForPeriod($period);
        $payload = $this->report->buildPayload($period, $this->mailGovernance->rankingLimit(), $approval);

        return view('admin.coverage-alerts.mailing-preview', [
            'period' => $period,
            'periodLabel' => $this->mailGovernance->periodLabel($period),
            'approval' => $approval,
            'payload' => $payload,
            'mailingGovernance' => $this->mailGovernance->panelData(),
            'recipients' => $this->mailGovernance->recipients(),
        ]);
    }

    public function assignOwner(Request $request, MunicipalityCoverageAlert $alert): RedirectResponse
    {
        $data = $request->validate([
            'owner_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $owner = null;
        if (!empty($data['owner_user_id'])) {
            $owner = User::query()
                ->admins()
                ->active()
                ->findOrFail((int) $data['owner_user_id']);
        }

        $this->coverageAlerts->assignOwner($alert, $owner, $request->user());

        return back()->with('success', $owner
            ? 'Owner do alerta atualizado para ' . $owner->name . '.'
            : 'Owner do alerta removido.');
    }

    public function acknowledge(Request $request, MunicipalityCoverageAlert $alert): RedirectResponse
    {
        $this->coverageAlerts->acknowledge($alert, $request->user());

        return back()->with('success', 'Alerta acknowledged com sucesso.');
    }

    public function unacknowledge(MunicipalityCoverageAlert $alert): RedirectResponse
    {
        $this->coverageAlerts->unacknowledge($alert);

        return back()->with('success', 'Acknowledge removido do alerta.');
    }

    public function approveMailing(Request $request, string $period): RedirectResponse
    {
        $period = $this->validatedPeriod($period);
        $data = $request->validate([
            'level' => 'nullable|in:level_one,level_two',
        ]);

        try {
            $this->mailGovernance->approve($period, $request->user(), (string) ($data['level'] ?? 'level_one'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()])->withInput();
        }

        return back()->with('success', 'Mailing ' . strtolower($this->mailGovernance->periodLabel($period)) . ' aprovado para o próximo disparo.');
    }

    public function revokeMailing(string $period): RedirectResponse
    {
        $period = $this->validatedPeriod($period);
        $this->mailGovernance->revoke($period);

        return back()->with('success', 'Aprovação do mailing ' . strtolower($this->mailGovernance->periodLabel($period)) . ' revogada.');
    }

    public function addComment(Request $request, MunicipalityCoverageAlert $alert): RedirectResponse
    {
        $data = $request->validate([
            'comment' => 'required|string|min:3|max:1000',
        ]);

        $this->coverageAlerts->addInternalComment($alert, $request->user(), (string) $data['comment']);

        return back()->with('success', 'Comentário interno registrado no alerta.');
    }

    public function saveFilter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'severity' => 'nullable|string|max:20',
            'municipality_id' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:20',
            'event_type' => 'nullable|string|max:80',
            'search' => 'nullable|string|max:200',
            'preset' => 'nullable|string|max:80',
        ]);

        /** @var User $user */
        $user = $request->user();
        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $savedFilters = $this->savedFilters($user);
        $key = Str::slug($data['name']);
        if ($key === '') {
            $key = 'filtro-' . now()->format('YmdHis');
        }

        $savedFilters[$key] = [
            'name' => trim($data['name']),
            'filters' => [
                'severity' => (string) ($data['severity'] ?? 'all'),
                'municipality_id' => (string) ($data['municipality_id'] ?? ''),
                'status' => (string) ($data['status'] ?? 'active'),
                'event_type' => (string) ($data['event_type'] ?? 'all'),
                'search' => trim((string) ($data['search'] ?? '')),
                'preset' => (string) ($data['preset'] ?? ''),
            ],
        ];

        data_set($preferences, 'admin.coverage_alerts.saved_filters', $savedFilters);
        $user->update(['preferences' => $preferences]);

        return back()->with('success', 'Filtro salvo para este admin.');
    }

    public function deleteFilter(Request $request, string $filterKey): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $savedFilters = $this->savedFilters($user);
        unset($savedFilters[$filterKey]);
        data_set($preferences, 'admin.coverage_alerts.saved_filters', $savedFilters);
        $user->update(['preferences' => $preferences]);

        return back()->with('success', 'Filtro salvo removido.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => 'required|in:resolve_selected,recheck_selected,acknowledge_selected,assign_me_selected',
            'selected_alert_ids' => 'required|array|min:1',
            'selected_alert_ids.*' => 'integer|exists:municipality_coverage_alerts,id',
        ]);

        $selectedIds = $data['selected_alert_ids'];

        if ($data['action'] === 'resolve_selected') {
            $affected = $this->coverageAlerts->resolveSelected($selectedIds, $request->user());

            return back()->with('success', $affected . ' alerta(s) marcados como resolvidos.');
        }

        if ($data['action'] === 'acknowledge_selected') {
            $affected = $this->coverageAlerts->acknowledgeSelected($selectedIds, $request->user());

            return back()->with('success', $affected . ' alerta(s) marcados com acknowledge.');
        }

        if ($data['action'] === 'assign_me_selected') {
            $affected = $this->coverageAlerts->assignSelectedToOwner($selectedIds, $request->user(), $request->user());

            return back()->with('success', $affected . ' alerta(s) atribuídos para você.');
        }

        $affectedMunicipalities = $this->coverageAlerts->recheckSelected($selectedIds);

        return back()->with('success', 'Cobertura revalidada para ' . $affectedMunicipalities . ' município(s).');
    }

    private function normalizedFilters(Request $request, array $savedFilters = []): array
    {
        $filters = [
            'severity' => (string) $request->query('severity', 'all'),
            'municipality_id' => (string) $request->query('municipality_id', ''),
            'status' => (string) $request->query('status', 'active'),
            'event_type' => (string) $request->query('event_type', 'all'),
            'search' => trim((string) $request->query('search', '')),
            'preset' => (string) $request->query('preset', ''),
        ];

        if ($request->query->count() === 0 && isset($savedFilters['default']['filters']) && is_array($savedFilters['default']['filters'])) {
            return array_merge($filters, $savedFilters['default']['filters']);
        }

        return $filters;
    }

    private function buildFilteredQuery(array $filters)
    {
        return MunicipalityCoverageAlert::query()
            ->with('municipality')
            ->when($filters['severity'] !== 'all', fn ($builder) => $builder->where('severity', $filters['severity']))
            ->when($filters['status'] !== 'all', fn ($builder) => $builder->where('status', $filters['status']))
            ->when($filters['event_type'] !== 'all', fn ($builder) => $builder->where('event_type', $filters['event_type']))
            ->when($filters['municipality_id'] !== '', fn ($builder) => $builder->where('municipality_id', (int) $filters['municipality_id']))
            ->when($filters['search'] !== '', function ($builder) use ($filters) {
                $builder->where(function ($nested) use ($filters) {
                    $nested->where('title', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('message', 'like', '%' . $filters['search'] . '%')
                        ->orWhereHas('municipality', fn ($municipalityQuery) => $municipalityQuery->where('name', 'like', '%' . $filters['search'] . '%'));
                });
            });
    }

    private function savedFilters(User $user): array
    {
        $saved = data_get($user->preferences, 'admin.coverage_alerts.saved_filters', []);

        return is_array($saved) ? $saved : [];
    }

    private function exportFilename(string $base, string $extension): string
    {
        return Str::slug($base) . '-' . now()->format('Ymd-His') . '.' . $extension;
    }

    private function exportFilterRows(array $filters, array $savedFilters): array
    {
        $presetLabel = $filters['preset'] === 'critical_active'
            ? 'Ativos críticos'
            : (str_starts_with($filters['preset'], 'saved:') ? (data_get($savedFilters, Str::after($filters['preset'], 'saved:') . '.name') ?? $filters['preset']) : 'Nenhum');

        return [
            ['Filtro', 'Valor'],
            ['Preset', $presetLabel],
            ['Severidade', $filters['severity']],
            ['Município', $filters['municipality_id'] !== '' ? $filters['municipality_id'] : 'Todos'],
            ['Status', $filters['status']],
            ['Frente', $filters['event_type']],
            ['Busca', $filters['search'] !== '' ? $filters['search'] : ''],
        ];
    }

    private function xlsxFilterSheetRows(array $filters, array $savedFilters): array
    {
        return $this->exportFilterRows($filters, $savedFilters);
    }

    private function exportRow(MunicipalityCoverageAlert $alert): array
    {
        return [
            $alert->id,
            $alert->municipality?->name ?? '',
            $alert->event_type,
            $alert->title,
            $alert->severity,
            $alert->status,
            (string) data_get($alert->metadata, 'workflow.owner_name', ''),
            (string) data_get($alert->metadata, 'workflow.acknowledged_by_name', ''),
            data_get($alert->metadata, 'workflow.acknowledged_at')
                ? Carbon::parse((string) data_get($alert->metadata, 'workflow.acknowledged_at'))->format('d/m/Y H:i')
                : '',
            optional($alert->first_detected_at)->format('d/m/Y H:i'),
            optional($alert->last_detected_at)->format('d/m/Y H:i'),
            optional($alert->resolved_at)->format('d/m/Y H:i'),
            (string) $alert->message,
            (string) $alert->action_url,
        ];
    }

    private function rankingLimit(Request $request): int
    {
        return max(5, min(50, (int) $request->query('limit', 20)));
    }

    private function validatedPeriod(string $period): string
    {
        $normalized = strtolower($period);

        abort_unless(in_array($normalized, ['daily', 'weekly'], true), 404);

        return $normalized;
    }

    private function executiveRankingSummaryRows(array $temporalComparison, Collection $ranking): array
    {
        return [
            ['Indicador', 'Valor'],
            ['Municípios no ranking', (string) $ranking->count()],
            ['Score executivo médio atual', (string) data_get($temporalComparison, 'current.average_executive_score', 0)],
            ['Delta score executivo médio', $this->formatSignedNumber((int) data_get($temporalComparison, 'deltas.average_executive_score', 0))],
            ['Score de configuração médio atual', (string) data_get($temporalComparison, 'current.average_configuration_score', 0)],
            ['Delta score de configuração', $this->formatSignedNumber((int) data_get($temporalComparison, 'deltas.average_configuration_score', 0))],
            ['Alertas ativos atuais', (string) data_get($temporalComparison, 'current.active_alerts', 0)],
            ['Delta alertas ativos', $this->formatSignedNumber((int) data_get($temporalComparison, 'deltas.active_alerts', 0))],
            ['Breaches de SLA atuais', (string) data_get($temporalComparison, 'current.sla_breaches_total', 0)],
            ['Delta breaches de SLA', $this->formatSignedNumber((int) data_get($temporalComparison, 'deltas.sla_breaches_total', 0))],
            ['Ultimo snapshot', data_get($temporalComparison, 'latest_snapshot.captured_at')?->format('d/m/Y H:i') ?? 'Sem snapshot'],
            ['Snapshot anterior', data_get($temporalComparison, 'previous_snapshot.captured_at')?->format('d/m/Y H:i') ?? 'Sem snapshot anterior'],
        ];
    }

    private function executiveRankingExportRows(Collection $ranking): array
    {
        return $ranking->map(function (array $row) {
            return [
                $row['position'],
                $row['previous_position'] ?? '',
                $this->formatSignedNumber($row['position_delta'] ?? null),
                $row['municipality_name'],
                $row['executive_score'],
                $row['previous_executive_score'] ?? '',
                $this->formatSignedNumber($row['executive_score_delta'] ?? null),
                $this->trendLabel((string) ($row['trend_direction'] ?? 'stable')),
                $row['score'],
                $row['recurrence_30d'],
                $row['sla_breaches_total'],
                $row['active_alerts_total'],
                $row['resolved_alerts_total'],
            ];
        })->values()->all();
    }

    private function improvementCurveSheetRows(Collection $improvementCurve): array
    {
        $rows = [[
            'Municipio',
            'Primeiro score',
            'Ultimo score',
            'Delta',
            'Tendencia',
            'Pontos',
        ]];

        foreach ($improvementCurve as $entry) {
            $points = collect($entry['points'] ?? [])
                ->map(fn (array $point) => ($point['label'] ?? '—') . ': ' . ($point['score'] ?? 0))
                ->implode(' | ');

            $rows[] = [
                $entry['municipality_name'],
                $entry['first_score'],
                $entry['last_score'],
                $this->formatSignedNumber($entry['delta'] ?? null),
                $this->trendLabel((string) ($entry['trend_direction'] ?? 'stable')),
                $points,
            ];
        }

        return $rows;
    }

    private function temporalSeriesSheetRows(array $temporalComparison): array
    {
        $rows = [[
            'Data',
            'Score executivo medio',
            'Score configuracao medio',
            'Alertas ativos',
            'Breaches SLA',
        ]];

        foreach ((array) data_get($temporalComparison, 'series', []) as $point) {
            $rows[] = [
                $point['label'] ?? '—',
                $point['average_executive_score'] ?? 0,
                $point['average_configuration_score'] ?? 0,
                $point['active_alerts'] ?? 0,
                $point['sla_breaches_total'] ?? 0,
            ];
        }

        return $rows;
    }

    private function trendLabel(string $direction): string
    {
        return match ($direction) {
            'up' => 'Melhora',
            'down' => 'Piora',
            default => 'Estavel',
        };
    }

    private function formatSignedNumber(int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value > 0) {
            return '+' . (string) $value;
        }

        return (string) $value;
    }
}
