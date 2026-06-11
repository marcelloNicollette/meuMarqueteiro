<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\MandateAction;
use App\Models\MandateAxis;
use App\Models\MandatePromise;
use App\Models\MorningBriefing;
use App\Models\Project;
use App\Models\User;
use App\Services\Mandato\MandateActionProgressService;
use App\Services\Mandato\MandateAxisCatalogService;
use App\Services\Mandato\MandateCommunicationSuggestionService;
use App\Services\Mandato\MandateProjectionService;
use App\Services\Mandato\MandatePromiseLinkingService;
use App\Services\Mandato\MandateResolveAiEvidenceSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MandatoController extends Controller
{
    public function __construct(
        private readonly MandateAxisCatalogService $axisCatalog,
        private readonly MandatePromiseLinkingService $promiseLinking,
        private readonly MandateActionProgressService $actionProgress,
        private readonly MandateCommunicationSuggestionService $communicationSuggestions,
        private readonly MandateProjectionService $projection,
        private readonly MandateResolveAiEvidenceSuggestionService $resolveAiSuggestions,
    ) {}

    private function currentMayor(): User
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        return $user;
    }

    // ── Painel principal ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $municipality = $this->currentMayor()->municipality;
        $activeArea = $request->string('area')->toString() ?: 'dashboard';
        $activeArea = in_array($activeArea, ['dashboard', 'commitments', 'actions', 'briefings'], true)
            ? $activeArea
            : 'dashboard';

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->with(['promises' => fn($q) => $q->where('is_active', true)->withCount('actions')])
            ->get();

        $allPromises = $axes->flatMap->promises->values();
        $partialStatuses = ['partial_25', 'partial_50', 'partial_75'];
        $fulfilledPromises = $allPromises->where('status', 'fulfilled');
        $partialPromises = $allPromises->whereIn('status', $partialStatuses);
        $pendingPromises = $allPromises->where('status', 'pending');
        $pendingWithoutActionsByAxis = $axes->map(function (MandateAxis $axis) {
            $items = $axis->promises
                ->where('status', 'pending')
                ->filter(fn (MandatePromise $promise) => (int) ($promise->actions_count ?? 0) === 0)
                ->map(function (MandatePromise $promise) use ($axis) {
                    $serialized = $this->serializePromise($promise);
                    $serialized['axis_id'] = $axis->id;
                    $serialized['axis_name'] = $axis->name;
                    $serialized['axis_icon'] = $axis->icon;

                    return $serialized;
                })
                ->values()
                ->all();

            if (empty($items)) {
                return null;
            }

            return [
                'axis_id' => $axis->id,
                'axis_name' => $axis->name,
                'axis_icon' => $axis->icon,
                'axis_description' => $axis->description,
                'count' => count($items),
                'items' => $items,
            ];
        })->filter()->values()->all();
        $pendingWithoutActions = collect($pendingWithoutActionsByAxis)
            ->flatMap(fn (array $axisGroup) => $axisGroup['items'] ?? [])
            ->values();
        $resolveAiSuggestionsByPromise = $this->resolveAiSuggestions
            ->buildForOpenPromises($municipality, $pendingWithoutActions);
        $pendingWithoutActionsByAxis = collect($pendingWithoutActionsByAxis)
            ->map(function (array $axisGroup) use ($resolveAiSuggestionsByPromise) {
                $axisGroup['items'] = collect($axisGroup['items'] ?? [])
                    ->map(function (array $promise) use ($resolveAiSuggestionsByPromise) {
                        $promise['radar_suggestions'] = [];
                        $promise['resolve_ai_suggestions'] = $resolveAiSuggestionsByPromise[(int) $promise['id']] ?? [];

                        return $promise;
                    })
                    ->all();

                return $axisGroup;
            })
            ->all();

        $actionsBaseQuery = MandateAction::where('municipality_id', $municipality->id)->with(['axis', 'promises', 'milestones']);
        $allActions = (clone $actionsBaseQuery)->get();
        $recentActions = (clone $actionsBaseQuery)->orderByDesc('created_at')->limit(8)->get();

        $actionFilters = [
            'axis' => $request->string('action_axis')->toString() ?: 'all',
            'status' => $request->string('action_status')->toString() ?: 'all',
            'search' => trim($request->string('action_search')->toString()),
        ];
        $reviewPromiseId = (int) $request->integer('promise_review');
        $reviewPromise = $reviewPromiseId > 0
            ? $allPromises->firstWhere('id', $reviewPromiseId)
            : null;

        $filteredActionsQuery = clone $actionsBaseQuery;
        if ($actionFilters['axis'] !== 'all') {
            $filteredActionsQuery->where('mandate_axis_id', $actionFilters['axis']);
        }
        if ($actionFilters['status'] !== 'all') {
            $filteredActionsQuery->where('status', $actionFilters['status']);
        }
        if ($actionFilters['search'] !== '') {
            $needle = $actionFilters['search'];
            $filteredActionsQuery->where(function ($query) use ($needle) {
                $query->where('title', 'like', '%' . $needle . '%')
                    ->orWhere('description', 'like', '%' . $needle . '%')
                    ->orWhere('secretaria', 'like', '%' . $needle . '%')
                    ->orWhere('region', 'like', '%' . $needle . '%');
            });
        }

        $actions = $filteredActionsQuery
            ->orderByRaw("CASE status WHEN 'concluido' THEN 5 WHEN 'em_andamento' THEN 4 WHEN 'nao_iniciado' THEN 3 WHEN 'planejado' THEN 2 WHEN 'suspenso' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $todayBriefing = $municipality->morningBriefings()
            ->whereDate('date', today())
            ->first();

        $recentBriefings = $municipality->morningBriefings()
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        $projectionBoard = $this->projection->calculate($municipality);
        $projectionAxisMap = collect($projectionBoard['axis_alerts'] ?? [])
            ->keyBy('axis_id');

        $dashboardBoard = [
            'totals' => [
                'total_promises' => $allPromises->count(),
                'global_attendance' => $allPromises->count() > 0 ? (int) round((($fulfilledPromises->count() + $partialPromises->count()) / $allPromises->count()) * 100) : 0,
                'fulfilled_promises' => $fulfilledPromises->count(),
                'partial_promises' => $partialPromises->count(),
                'pending_promises' => $pendingPromises->count(),
                'total_actions' => $allActions->count(),
                'completed_actions' => $allActions->where('status', 'concluido')->count(),
                'running_actions' => $allActions->where('status', 'em_andamento')->count(),
                'not_started_actions' => $allActions->where('status', 'nao_iniciado')->count(),
                'planned_actions' => $allActions->where('status', 'planejado')->count(),
                'suspended_actions' => $allActions->where('status', 'suspenso')->count(),
            ],
            'axis_rows' => $axes->map(function (MandateAxis $axis) use ($partialStatuses, $projectionAxisMap) {
                $projectionAxis = $projectionAxisMap->get($axis->id, []);

                return [
                    'id' => $axis->id,
                    'name' => $axis->name,
                    'icon' => $axis->icon,
                    'description' => $axis->description,
                    'score' => $axis->score,
                    'score_color' => $axis->score_color,
                    'fulfilled' => $axis->promises->where('status', 'fulfilled')->count(),
                    'partial' => $axis->promises->whereIn('status', $partialStatuses)->count(),
                    'pending' => $axis->promises->where('status', 'pending')->count(),
                    'promise_total' => $axis->promises->count(),
                    'projected_gap' => (int) ($projectionAxis['gap'] ?? 0),
                    'projection_risk_level' => $projectionAxis['risk_level'] ?? 'ok',
                ];
            })->values()->all(),
            'pending_without_actions' => $pendingWithoutActionsByAxis,
            'recent_actions' => $recentActions->map(fn (MandateAction $action) => $this->serializeAction($action))->values()->all(),
            'projection' => $projectionBoard,
        ];

        $commitmentsBoard = [
            'totals' => [
                'total' => $allPromises->count(),
                'fulfilled' => $fulfilledPromises->count(),
                'partial' => $partialPromises->count(),
                'pending' => $pendingPromises->count(),
                'without_actions' => $allPromises->filter(fn (MandatePromise $promise) => (int) ($promise->actions_count ?? 0) === 0)->count(),
            ],
            'axes' => $axes->map(function (MandateAxis $axis) {
                return [
                    'id' => $axis->id,
                    'name' => $axis->name,
                    'icon' => $axis->icon,
                    'description' => $axis->description,
                    'score' => $axis->score,
                    'score_color' => $axis->score_color,
                    'promise_counts' => $axis->promise_counts,
                    'promises' => $axis->promises->map(fn (MandatePromise $promise) => $this->serializePromise($promise))->values()->all(),
                ];
            })->values()->all(),
            'pending_focus_axes' => $pendingWithoutActionsByAxis,
        ];

        $actionsBoard = [
            'filters' => $actionFilters,
            'options' => [
                'axes' => $axes->map(fn (MandateAxis $axis) => [
                    'id' => $axis->id,
                    'name' => $axis->name,
                    'icon' => $axis->icon,
                ])->values()->all(),
            ],
            'review_promise' => $reviewPromise ? [
                ...$this->serializePromise($reviewPromise),
                'axis_id' => $reviewPromise->mandate_axis_id,
                'axis_name' => $reviewPromise->axis?->name,
                'axis_icon' => $reviewPromise->axis?->icon,
            ] : null,
            'totals' => [
                'total' => $allActions->count(),
                'completed' => $allActions->where('status', 'concluido')->count(),
                'running' => $allActions->where('status', 'em_andamento')->count(),
                'not_started' => $allActions->where('status', 'nao_iniciado')->count(),
                'planned' => $allActions->where('status', 'planejado')->count(),
                'suspended' => $allActions->where('status', 'suspenso')->count(),
            ],
            'items' => $actions->through(fn (MandateAction $action) => $this->serializeAction($action)),
        ];

        $briefingsBoard = [
            'today' => $todayBriefing,
            'recent' => $recentBriefings,
            'total' => $recentBriefings->count(),
        ];

        return view('mayor.mandato.shell', compact(
            'municipality',
            'activeArea',
            'dashboardBoard',
            'commitmentsBoard',
            'actionsBoard',
            'briefingsBoard'
        ));
    }

    // ── Drill-down de um eixo ─────────────────────────────────────────

    public function eixo($axisId)
    {
        $municipality = $this->currentMayor()->municipality;

        $axis = MandateAxis::where('municipality_id', $municipality->id)
            ->with([
                'promises' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with([
                        'axis',
                        'actions' => fn ($actionQuery) => $actionQuery
                            ->with(['axis', 'promises', 'milestones'])
                            ->orderByDesc('created_at'),
                    ])
                    ->orderBy('order'),
                'actions' => fn ($query) => $query
                    ->with(['axis', 'promises', 'milestones'])
                    ->orderByDesc('created_at'),
            ])
            ->findOrFail($axisId);

        $partialStatuses = ['partial_25', 'partial_50', 'partial_75'];
        $promises = $axis->promises;
        $actions = $axis->actions;

        $axisBoard = [
            'summary' => [
                'score' => $axis->score,
                'score_color' => $axis->score_color,
                'counts' => $axis->promise_counts,
                'actions_total' => $actions->count(),
                'actions_in_progress' => $actions->where('status', 'em_andamento')->count(),
                'actions_completed' => $actions->where('status', 'concluido')->count(),
                'actions_planned' => $actions->whereIn('status', ['planejado', 'nao_iniciado'])->count(),
                'pending_without_actions' => $promises->filter(fn (MandatePromise $promise) => $promise->actions->isEmpty())->count(),
            ],
            'promises' => $promises->map(function (MandatePromise $promise) use ($actions) {
                $serialized = $this->serializePromise($promise);
                $serialized['linked_actions'] = $promise->actions
                    ->map(fn (MandateAction $action) => $this->serializeAction($action))
                    ->values()
                    ->all();
                $serialized['actions_in_axis'] = $actions->whereIn('id', $promise->actions->pluck('id'))->count();

                return $serialized;
            })->values()->all(),
            'actions' => $actions->map(fn (MandateAction $action) => $this->serializeAction($action))->values()->all(),
            'promise_breakdown' => [
                'fulfilled' => $promises->where('status', 'fulfilled')->count(),
                'partial' => $promises->whereIn('status', $partialStatuses)->count(),
                'pending' => $promises->where('status', 'pending')->count(),
            ],
        ];

        return view('mayor.mandato.eixo', compact('axis', 'municipality', 'axisBoard'));
    }

    // ── CRUD de Ações ────────────────────────────────────────────────

    public function acoes(Request $request)
    {
        return redirect()->route('mayor.mandato.painel', array_filter([
            'area' => 'actions',
            'action_axis' => $request->string('axis')->toString() ?: null,
            'action_status' => $request->string('status')->toString() ?: null,
            'action_search' => trim($request->string('search')->toString()) ?: null,
        ]));
    }

    public function createAcao(Request $request)
    {
        $municipality = $this->currentMayor()->municipality;

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->with(['promises' => fn($q) => $q->where('is_active', true)->orderBy('order')])
            ->get();

        $axisPrefillId = null;
        $programArea = $request->query('program_area');
        if ($programArea) {
            $needle = Str::slug((string) $programArea);
            $axisPrefillId = $axes->first(function ($axis) use ($needle) {
                $name = Str::slug((string) $axis->name);
                return $needle !== '' && Str::contains($name, $needle);
            })?->id;
        }

        $promisePrefillIds = collect(Arr::wrap($request->query('promise')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $projects = $this->availableProjects($municipality->id);

        return view('mayor.mandato.acao-create', compact('axes', 'municipality', 'axisPrefillId', 'promisePrefillIds', 'projects'));
    }

    public function storeAcao(Request $request)
    {
        $municipality = $this->currentMayor()->municipality;

        $data = $request->validate([
            'mandate_axis_id'       => 'required|exists:mandate_axes,id',
            'project_id'            => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->where('municipality_id', $municipality->id)
                ),
            ],
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'secretaria'            => 'nullable|string|max:255',
            'status'                => 'required|in:planejado,nao_iniciado,em_andamento,concluido,suspenso',
            'start_date'            => 'nullable|date',
            'end_date'              => 'nullable|date|after_or_equal:start_date',
            'physical_progress'     => 'nullable|integer|min:0|max:100',
            'uses_milestones_progress' => 'boolean',
            'investment'            => 'nullable|numeric|min:0',
            'funding_source'        => 'nullable|string|max:255',
            'region'                => 'nullable|string|max:255',
            'beneficiaries'         => 'nullable|integer|min:0',
            'proof_url'             => 'nullable|url|max:500',
            'is_public'             => 'boolean',
            'promises'              => 'nullable|array',
            'promises.*.id'         => 'exists:mandate_promises,id',
            'promises.*.level'      => 'integer|in:0,25,50,75,100',
            'promises.*.justification' => 'nullable|string',
            'milestones'            => 'nullable|array',
            'milestones.*.id'       => 'nullable|integer',
            'milestones.*.title'    => 'nullable|string|max:255',
            'milestones.*.due_date' => 'nullable|date',
            'milestones.*.completed' => 'nullable',
        ]);
        $selectedPromises = $this->selectedPromisesFromPayload($data['promises'] ?? []);

        $actor = $this->currentMayor();

        DB::transaction(function () use ($actor, $data, $municipality, $request, $selectedPromises) {
            $action = MandateAction::create(array_merge(
                $data,
                [
                    'municipality_id' => $municipality->id,
                    'is_public' => $request->boolean('is_public'),
                    'uses_milestones_progress' => $request->boolean('uses_milestones_progress'),
                ]
            ));

            // Vincular promessas com nível de atendimento
            if (!empty($selectedPromises)) {
                foreach ($selectedPromises as $p) {
                    $action->promises()->attach($p['id'], [
                        'fulfillment_level'         => $p['level'] ?? 0,
                        'fulfillment_justification' => $p['justification'] ?? null,
                    ]);

                    // Recalcular score da promessa
                    MandatePromise::find($p['id'])?->recalculateScore();
                }
            }

            $this->actionProgress->syncMilestones(
                action: $action,
                milestones: Arr::wrap($request->input('milestones', [])),
                usesMilestonesProgress: $request->boolean('uses_milestones_progress'),
                actor: $actor,
            );

            $action->refresh();

            $this->actionProgress->recordProgressSnapshot(
                action: $action,
                actor: $actor,
                beforeProgress: 0,
                beforeStatus: null,
                force: true,
            );

            if ((string) $action->status === 'concluido') {
                $this->communicationSuggestions->syncCompletedAction($action, $actor);
            }
        });

        return redirect()->route('mayor.mandato.painel')
            ->with('success', 'Ação cadastrada com sucesso.');
    }

    public function editAcao($id)
    {
        $municipality = $this->currentMayor()->municipality;

        $action = MandateAction::where('municipality_id', $municipality->id)
            ->with([
                'project',
                'promises',
                'milestones.completedByUser',
                'progressLogs.user',
                'progressLogs.milestone',
            ])
            ->findOrFail($id);

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->with(['promises' => fn($q) => $q->where('is_active', true)->orderBy('order')])
            ->get();

        $projects = $this->availableProjects($municipality->id);

        return view('mayor.mandato.acao-edit', compact('action', 'axes', 'municipality', 'projects'));
    }

    public function updateAcao(Request $request, $id)
    {
        $municipality = $this->currentMayor()->municipality;
        $action = MandateAction::where('municipality_id', $municipality->id)->findOrFail($id);

        $data = $request->validate([
            'mandate_axis_id'       => 'required|exists:mandate_axes,id',
            'project_id'            => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query->where('municipality_id', $municipality->id)
                ),
            ],
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'secretaria'            => 'nullable|string|max:255',
            'status'                => 'required|in:planejado,nao_iniciado,em_andamento,concluido,suspenso',
            'start_date'            => 'nullable|date',
            'end_date'              => 'nullable|date|after_or_equal:start_date',
            'physical_progress'     => 'nullable|integer|min:0|max:100',
            'uses_milestones_progress' => 'boolean',
            'investment'            => 'nullable|numeric|min:0',
            'funding_source'        => 'nullable|string|max:255',
            'region'                => 'nullable|string|max:255',
            'beneficiaries'         => 'nullable|integer|min:0',
            'proof_url'             => 'nullable|url|max:500',
            'is_public'             => 'boolean',
            'promises'              => 'nullable|array',
            'promises.*.id'         => 'exists:mandate_promises,id',
            'promises.*.level'      => 'integer|in:0,25,50,75,100',
            'promises.*.justification' => 'nullable|string',
            'milestones'            => 'nullable|array',
            'milestones.*.id'       => 'nullable|integer',
            'milestones.*.title'    => 'nullable|string|max:255',
            'milestones.*.due_date' => 'nullable|date',
            'milestones.*.completed' => 'nullable',
        ]);
        $selectedPromises = $this->selectedPromisesFromPayload($data['promises'] ?? []);

        $actor = $this->currentMayor();

        DB::transaction(function () use ($action, $actor, $data, $request, $selectedPromises) {
            $beforeProgress = (int) ($action->physical_progress ?? 0);
            $beforeStatus = (string) $action->status;

            $action->update(array_merge($data, [
                'is_public' => $request->boolean('is_public'),
                'uses_milestones_progress' => $request->boolean('uses_milestones_progress'),
            ]));

            // Pegar promessas antigas para recalcular depois
            $oldPromiseIds = $action->promises()->pluck('mandate_promises.id')->toArray();

            // Reatribuir promessas
            $action->promises()->detach();
            $newPromiseIds = [];

            if (!empty($selectedPromises)) {
                foreach ($selectedPromises as $p) {
                    $action->promises()->attach($p['id'], [
                        'fulfillment_level'         => $p['level'] ?? 0,
                        'fulfillment_justification' => $p['justification'] ?? null,
                    ]);
                    $newPromiseIds[] = $p['id'];
                }
            }

            // Recalcular scores de todas as promessas afetadas
            foreach (array_unique(array_merge($oldPromiseIds, $newPromiseIds)) as $pid) {
                MandatePromise::find($pid)?->recalculateScore();
            }

            $this->actionProgress->syncMilestones(
                action: $action,
                milestones: Arr::wrap($request->input('milestones', [])),
                usesMilestonesProgress: $request->boolean('uses_milestones_progress'),
                actor: $actor,
            );

            $action->refresh();

            $this->actionProgress->recordProgressSnapshot(
                action: $action,
                actor: $actor,
                beforeProgress: $beforeProgress,
                beforeStatus: $beforeStatus,
            );

            if ((string) $action->status === 'concluido') {
                $this->communicationSuggestions->syncCompletedAction($action, $actor);
            }
        });

        return redirect()->route('mayor.mandato.painel')
            ->with('success', 'Ação atualizada com sucesso.');
    }

    public function destroyAcao($id)
    {
        $municipality = $this->currentMayor()->municipality;
        $action = MandateAction::where('municipality_id', $municipality->id)->findOrFail($id);

        $promiseIds = $action->promises()->pluck('mandate_promises.id')->toArray();
        $action->delete();

        foreach ($promiseIds as $pid) {
            MandatePromise::find($pid)?->recalculateScore();
        }

        return back()->with('success', 'Ação removida.');
    }

    // ── CRUD de Eixos ────────────────────────────────────────────────

    public function eixos()
    {
        $municipality = $this->currentMayor()->municipality;

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->orderBy('order')
            ->withCount('promises')
            ->get();

        return view('mayor.mandato.eixos', compact('axes', 'municipality'));
    }

    public function storeEixo(Request $request)
    {
        $municipality = $this->currentMayor()->municipality;

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:20',
        ]);

        $maxOrder = MandateAxis::where('municipality_id', $municipality->id)->max('order') ?? 0;

        MandateAxis::create(array_merge($data, [
            'municipality_id' => $municipality->id,
            'order'           => $maxOrder + 1,
        ]));

        return back()->with('success', 'Eixo criado.');
    }

    public function updateEixo(Request $request, $id)
    {
        $municipality = $this->currentMayor()->municipality;
        $axis = MandateAxis::where('municipality_id', $municipality->id)->findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:20',
        ]);

        $axis->update($data);
        return back()->with('success', 'Eixo atualizado.');
    }

    public function destroyEixo($id)
    {
        $municipality = $this->currentMayor()->municipality;
        $axis = MandateAxis::where('municipality_id', $municipality->id)->findOrFail($id);
        $axis->delete();
        return back()->with('success', 'Eixo removido.');
    }

    // ── CRUD de Promessas ─────────────────────────────────────────────

    public function storePromise(Request $request)
    {
        $municipality = $this->currentMayor()->municipality;

        $data = $request->validate([
            'mandate_axis_id' => 'required|exists:mandate_axes,id',
            'text'            => 'required|string',
            'keywords'        => 'nullable|string',
            'specificity'     => 'nullable|in:quantitativo,qualitativo',
        ]);

        $maxOrder = MandatePromise::where('mandate_axis_id', $data['mandate_axis_id'])->max('order') ?? 0;

        MandatePromise::create(array_merge($data, [
            'municipality_id' => $municipality->id,
            'order'           => $maxOrder + 1,
            'keywords'        => collect(explode(',', (string) ($data['keywords'] ?? '')))->map(fn ($keyword) => trim($keyword))->filter()->values()->all(),
        ]));

        $this->promiseLinking->ensurePromiseEmbeddings($municipality);

        return back()->with('success', 'Promessa adicionada.');
    }

    public function seedDefaultAxes()
    {
        $municipality = $this->currentMayor()->municipality;

        if (MandateAxis::where('municipality_id', $municipality->id)->exists()) {
            return back()->with('success', 'Eixos já configurados.');
        }

        $this->axisCatalog->ensureDefaultAxes($municipality);

        return back()->with('success', '9 eixos padrão importados com sucesso. Agora cadastre as promessas de cada eixo.');
    }

    public function destroyPromise($id)
    {
        $municipality = $this->currentMayor()->municipality;
        $promise = MandatePromise::where('municipality_id', $municipality->id)->findOrFail($id);
        $promise->delete();

        $this->promiseLinking->ensurePromiseEmbeddings($municipality);

        return back()->with('success', 'Promessa removida.');
    }

    public function suggestPromises(Request $request)
    {
        $municipality = $this->currentMayor()->municipality;

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'mandate_axis_id' => 'nullable|integer|exists:mandate_axes,id',
        ]);

        $suggestions = $this->promiseLinking->suggestForAction(
            municipality: $municipality,
            title: $data['title'],
            description: $data['description'] ?? null,
            axisId: $data['mandate_axis_id'] ?? null,
        );

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }

    private function selectedPromisesFromPayload(array $promises): array
    {
        return collect($promises)
            ->filter(fn ($promise) => is_array($promise) && !empty($promise['id']))
            ->map(fn (array $promise) => [
                'id' => (int) $promise['id'],
                'level' => isset($promise['level']) ? (int) $promise['level'] : 0,
                'justification' => $promise['justification'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function serializePromise(MandatePromise $promise): array
    {
        return [
            'id' => $promise->id,
            'axis_id' => $promise->mandate_axis_id,
            'axis_name' => $promise->axis?->name,
            'text' => $promise->text,
            'score' => (int) $promise->score,
            'status' => $promise->status,
            'status_label' => $promise->status_label,
            'status_color' => $promise->status_color,
            'keywords' => $promise->keywords ?? [],
            'specificity' => $promise->specificity,
            'actions_count' => (int) ($promise->actions_count
                ?? ($promise->relationLoaded('actions') ? $promise->actions->count() : $promise->actions()->count())),
        ];
    }

    private function serializeAction(MandateAction $action): array
    {
        return [
            'id' => $action->id,
            'title' => $action->title,
            'description' => $action->description,
            'status' => $action->status,
            'status_label' => $action->status_label,
            'status_color' => $action->status_color,
            'physical_progress' => (int) ($action->physical_progress ?? 0),
            'uses_milestones_progress' => (bool) ($action->uses_milestones_progress ?? false),
            'axis_id' => $action->mandate_axis_id,
            'axis_name' => $action->axis?->name,
            'axis_icon' => $action->axis?->icon,
            'secretaria' => $action->secretaria,
            'investment_formatted' => $action->investment_formatted,
            'region' => $action->region,
            'beneficiaries' => $action->beneficiaries,
            'proof_url' => $action->proof_url,
            'is_public' => (bool) $action->is_public,
            'start_date' => $action->start_date?->format('d/m/Y'),
            'end_date' => $action->end_date?->format('d/m/Y'),
            'created_at_human' => $action->created_at?->diffForHumans(),
            'milestones_total' => $action->relationLoaded('milestones') ? $action->milestones->count() : $action->milestones()->count(),
            'milestones_completed' => $action->relationLoaded('milestones')
                ? $action->milestones->filter(fn ($milestone) => (bool) $milestone->completed_at)->count()
                : $action->milestones()->whereNotNull('completed_at')->count(),
            'promises' => $action->promises->map(fn (MandatePromise $promise) => [
                'id' => $promise->id,
                'text' => $promise->text,
                'level' => (int) ($promise->pivot->fulfillment_level ?? 0),
            ])->values()->all(),
        ];
    }

    private function availableProjects(int $municipalityId)
    {
        return Project::query()
            ->where('municipality_id', $municipalityId)
            ->orderBy('title')
            ->get(['id', 'title', 'status']);
    }
}
