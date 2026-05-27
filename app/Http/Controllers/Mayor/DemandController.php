<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\Demand;
use App\Models\DemandEvent;
use App\Models\User;
use App\Services\AI\AssistantService;
use App\Services\Communication\ContentGenerationService;
use App\Services\ResolveAi\ResolveAiNotificationService;
use App\Services\ResolveAi\ResolveAiSettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DemandController extends Controller
{
    public function __construct(
        private readonly ResolveAiSettingsService $resolveAiSettings,
        private readonly ResolveAiNotificationService $resolveAiNotifications,
        private readonly ContentGenerationService $contentGeneration,
        private readonly AssistantService $assistant,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        $municipality = $user->municipality;

        $this->syncComputedStatuses($municipality->id);

        $routeBase = $this->routeBase($user);
        $isSecretaryPanel = $user->isSecretary() || $user->isAdvisor();
        $contactAreas = $this->availableContactAreas($user);
        $creators = $this->availableCreators($user);
        $localities = $municipality->localities()->where('active', true)->get(['id', 'name', 'type', 'zone']);
        $filters = $this->normalizedFilters($request);

        $query = $this->baseDemandQueryForUser($user)
            ->with(['contactArea:id,name', 'registeredBy:id,name']);
        $this->applyFilters($query, $filters);

        $demands = $query
            ->orderByRaw("
                CASE status
                    WHEN 'overdue' THEN 1
                    WHEN 'awaiting_confirmation' THEN 2
                    WHEN 'reopened' THEN 3
                    WHEN 'registered' THEN 4
                    WHEN 'pending' THEN 4
                    WHEN 'in_progress' THEN 5
                    WHEN 'completed' THEN 6
                    WHEN 'resolved' THEN 6
                    ELSE 9
                END
            ")
            ->orderByRaw("
                CASE priority
                    WHEN 'alta' THEN 1
                    WHEN 'media' THEN 2
                    WHEN 'baixa' THEN 3
                    ELSE 9
                END
            ")
            ->orderByRaw('COALESCE(due_at, CAST(due_date AS timestamp), created_at) ASC')
            ->paginate(20)
            ->withQueryString();

        $resolveAiSettings = $this->resolveAiSettings->forMunicipality($municipality);
        $summary = $this->buildSummary($user);
        $windowConfig = $this->buildComparativeWindowConfig($resolveAiSettings);
        $territorialDataset = $this->buildTerritorialDataset($user, $windowConfig);
        $territorialIntelligence = $this->buildTerritorialIntelligence($territorialDataset, $windowConfig);
        $secretariatPerformance = $this->buildSecretariatPerformancePayload($territorialDataset, $windowConfig);
        $territorialTrend = $this->buildTerritorialTrendPayload($territorialDataset, $windowConfig);
        $canCreateDemand = $user->canRegisterResolveAiDemands();
        $lockedContactArea = $isSecretaryPanel ? $user->contactArea : null;

        return view('mayor.demands.index', compact(
            'municipality',
            'demands',
            'contactAreas',
            'creators',
            'localities',
            'filters',
            'summary',
            'territorialIntelligence',
            'secretariatPerformance',
            'territorialTrend',
            'resolveAiSettings',
            'routeBase',
            'canCreateDemand',
            'lockedContactArea',
            'isSecretaryPanel'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        $municipality = $user->municipality;
        if (!$user->canRegisterResolveAiDemands()) {
            abort(403);
        }

        $data = $request->validate([
            'raw_input'  => ['required', 'string', 'max:4000'],
            'input_type' => ['nullable', 'in:text,voice'],
            'locality'   => ['nullable', 'string', 'max:255'],
            'address'    => ['nullable', 'string', 'max:255'],
            'area'       => ['nullable', 'string', 'max:120'],
            'contact_area_id' => ['nullable', 'exists:contact_areas,id'],
            'priority'   => ['nullable', 'in:alta,media,baixa'],
            'due_date'   => ['nullable', 'date'],
            'is_urgent'  => ['nullable', 'boolean'],
            'latitude'   => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'  => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $contactAreaId = $this->resolveContactAreaIdForUser($user, $data['contact_area_id'] ?? null);
        $areaName = $data['area'] ?? null;
        if (!empty($contactAreaId)) {
            $ca = $municipality->contactAreas()->where('id', $contactAreaId)->first();
            if ($ca) $areaName = $ca->name;
        }

        $demand = Demand::create([
            'municipality_id' => $municipality->id,
            'registered_by'   => $user->id,
            'input_type'      => $data['input_type'] ?? 'text',
            'raw_input'       => $data['raw_input'],
            'title'           => Str::limit(trim($data['raw_input']), 90),
            'description'     => null,
            'area'            => $areaName,
            'locality'        => $data['locality'] ?? null,
            'address'         => $data['address'] ?? null,
            'contact_area_id' => $contactAreaId,
            'priority'        => $data['priority'] ?? 'media',
            'due_date'        => $data['due_date'] ?? null,
            'due_at'          => $this->resolveDueAt($municipality, $data['priority'] ?? 'media', $data['due_date'] ?? null),
            'is_urgent'       => (bool) ($data['is_urgent'] ?? false),
            'status'          => 'registered',
            'latitude'        => $data['latitude'] ?? null,
            'longitude'       => $data['longitude'] ?? null,
        ]);

        $this->recordEvent(
            $demand,
            'registered',
            $user,
            'Demanda registrada e pronta para encaminhamento.',
            [
                'priority' => $demand->priority,
                'contact_area_id' => $demand->contact_area_id,
                'locality' => $demand->locality,
                'due_at' => $demand->due_at?->toIso8601String(),
            ]
        );
        $this->resolveAiNotifications->dispatchRegistered($demand->loadMissing(['municipality', 'contactArea', 'registeredBy', 'notifications']));

        return redirect()->route($this->routeBase($user) . '.index')
            ->with('success', 'Demanda registrada no Resolve ai.');
    }

    public function show(Demand $demand): View
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if (!$this->canAccessDemand($user, $demand)) {
            abort(403);
        }

        $this->syncDemandStatus($demand);
        $municipality = $user->municipality;
        $contactAreas = $this->availableContactAreas($user);
        $localities = $municipality->localities()->where('active', true)->get(['id', 'name', 'type', 'zone']);
        $demand->load([
            'comments.user',
            'events.user',
            'notifications',
            'contactArea',
            'registeredBy',
        ]);

        $resolveAiSettings = $this->resolveAiSettings->forMunicipality($municipality);
        $routeBase = $this->routeBase($user);
        $isSecretaryPanel = $user->isSecretary() || $user->isAdvisor();
        $canCreateDemand = $user->canRegisterResolveAiDemands();

        return view('mayor.demands.show', compact('demand', 'municipality', 'contactAreas', 'localities', 'resolveAiSettings', 'routeBase', 'isSecretaryPanel', 'canCreateDemand'));
    }

    public function updateStatus(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if (!$this->canAccessDemand($user, $demand)) {
            abort(403);
        }

        $data = $request->validate([
            'action' => ['required', 'in:acknowledge,progress,complete,confirm,reopen'],
            'message' => ['nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'max:51200', 'mimes:jpg,jpeg,png,mp4,pdf,doc,docx'],
        ]);

        $this->syncDemandStatus($demand);

        $action = (string) $data['action'];
        $message = trim((string) ($data['message'] ?? ''));
        $payload = [];
        $eventType = $action;
        $eventMessage = $message;

        if ($action === 'acknowledge' || $action === 'progress') {
            $payload['status'] = 'in_progress';
            $payload['acknowledged_at'] = $demand->acknowledged_at ?? now();
            $payload['last_progress_at'] = now();
            $eventType = $action === 'acknowledge' ? 'acknowledged' : 'progress_updated';
            $eventMessage = $message !== '' ? $message : ($action === 'acknowledge'
                ? 'Recebimento registrado e demanda colocada em andamento.'
                : 'Atualização de andamento registrada.');
        }

        if ($action === 'complete') {
            if (
                $this->resolveAiSettings->requiresAttachment($demand->municipality, (string) $demand->priority) &&
                !$request->hasFile('attachment') &&
                !$demand->completion_attachment_path
            ) {
                return redirect()
                    ->back()
                    ->withErrors(['attachment' => 'Esta prioridade exige comprovante de conclusão no Resolve ai.']);
            }

            $payload['status'] = 'awaiting_confirmation';
            $payload['completion_requested_at'] = now();
            $payload['completion_note'] = $message !== '' ? $message : $demand->completion_note;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('demands/completions', 'public');
                $payload['completion_attachment_path'] = $path;
                $payload['completion_attachment_name'] = $file->getClientOriginalName();
                $payload['completion_attachment_mime'] = $file->getClientMimeType();
                $payload['completion_attachment_size'] = $file->getSize();
            }

            $eventType = 'completion_requested';
            $eventMessage = $message !== '' ? $message : 'Conclusão informada e enviada para confirmação do criador.';
        }

        if ($action === 'confirm') {
            $payload['status'] = 'completed';
            $payload['confirmed_at'] = now();
            $payload['resolved_at'] = now();
            $eventType = 'completion_confirmed';
            $eventMessage = $message !== '' ? $message : 'Conclusão confirmada e fluxo encerrado.';
        }

        if ($action === 'reopen') {
            if ($message === '') {
                return redirect()
                    ->back()
                    ->withErrors(['message' => 'Informe a justificativa para reabrir a demanda.']);
            }

            $payload['status'] = 'reopened';
            $payload['reopened_at'] = now();
            $payload['reopened_reason'] = $message;
            $payload['confirmed_at'] = null;
            $payload['resolved_at'] = null;
            $eventType = 'reopened';
            $eventMessage = $message;
        }

        $demand->update($payload);
        $demand->refresh()->loadMissing(['municipality', 'contactArea', 'registeredBy', 'notifications']);
        $this->recordEvent($demand, $eventType, $user, $eventMessage, [
            'status' => $demand->status,
        ]);

        if ($action === 'complete') {
            $this->resolveAiNotifications->dispatchCompletionRequested($demand);
        }
        if ($action === 'confirm') {
            $this->resolveAiNotifications->dispatchCompletionConfirmed($demand);
        }
        if ($action === 'reopen') {
            $this->resolveAiNotifications->dispatchReopened($demand);
        }

        return redirect()->route($this->routeBase($user) . '.show', $demand)
            ->with('success', 'Fluxo da demanda atualizado.');
    }

    public function update(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if (!$this->canAccessDemand($user, $demand)) {
            abort(403);
        }

        $data = $request->validate([
            'priority' => ['required', 'in:alta,media,baixa'],
            'due_date' => ['nullable', 'date'],
            'locality' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_area_id' => ['nullable', 'exists:contact_areas,id'],
        ]);

        $contactArea = null;
        $contactAreaId = $this->resolveContactAreaIdForUser($user, $data['contact_area_id'] ?? null);
        if (!empty($contactAreaId)) {
            $contactArea = $user->municipality?->contactAreas()->where('id', $contactAreaId)->first();
        }

        $demand->update([
            'priority' => $data['priority'],
            'due_date' => $data['due_date'] ?? null,
            'due_at' => $this->resolveDueAt($user->municipality, $data['priority'], $data['due_date'] ?? null),
            'locality' => $data['locality'] ?? $demand->locality,
            'address' => $data['address'] ?? $demand->address,
            'contact_area_id' => $contactAreaId,
            'area' => $contactArea?->name ?? $demand->area,
        ]);
        $this->recordEvent(
            $demand,
            'details_updated',
            $user,
            'Prioridade, prazo ou encaminhamento foram atualizados.',
            [
                'priority' => $demand->priority,
                'due_at' => $demand->due_at?->toIso8601String(),
                'contact_area_id' => $demand->contact_area_id,
            ]
        );

        return redirect()->route($this->routeBase($user) . '.show', $demand)
            ->with('success', 'Dados da demanda atualizados.');
    }

    public function addComment(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);
        if (!$this->canAccessDemand($user, $demand)) {
            abort(403);
        }

        $data = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $comment = $demand->comments()->create([
            'user_id' => $user->id,
            'comment' => $data['comment'],
        ]);
        $demand->update([
            'last_progress_at' => now(),
        ]);
        $this->recordEvent(
            $demand,
            'progress_note',
            $user,
            $data['comment'],
            ['comment_id' => $comment->id]
        );

        return redirect()->route($this->routeBase($user) . '.show', $demand)
            ->with('success', 'Atualização registrada no histórico.');
    }

    public function generateCommunicationDraft(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);

        if (!$this->canAccessDemand($user, $demand) || !$user->isMayor()) {
            abort(403);
        }

        if (!$this->isDemandReadyForStorytelling($demand)) {
            return back()->with('warning', 'A integração com Comunicação só é liberada para demandas concluídas ou aguardando confirmação.');
        }

        $data = $request->validate([
            'channel' => ['nullable', 'in:instagram,facebook,whatsapp'],
        ]);

        $content = $this->contentGeneration->generateDemandCompletionContent(
            demand: $demand->loadMissing(['contactArea', 'registeredBy', 'municipality']),
            channel: $data['channel'] ?? 'instagram',
            municipality: $user->municipality,
            mayor: $user,
        );

        return redirect()->route('mayor.content.index', [
            'tab' => 'post',
            'content' => $content->id,
        ])->with('success', 'Rascunho de comunicação gerado a partir da demanda.');
    }

    public function openStrategicConversation(Request $request, Demand $demand)
    {
        $user = Auth::user();
        if (!$user instanceof User) abort(401);

        if (!$this->canAccessDemand($user, $demand) || !$user->isMayor()) {
            abort(403);
        }

        if (!$this->isDemandReadyForStorytelling($demand)) {
            return back()->with('warning', 'A conversa estratégica só é liberada para demandas concluídas ou aguardando confirmação.');
        }

        $data = $request->validate([
            'mode' => ['required', 'in:narrative,followup'],
        ]);

        $mode = $data['mode'];
        $conversation = $user->conversations()->create([
            'municipality_id' => $user->municipality_id,
            'origin_module' => 'resolve_ai',
            'title' => ($mode === 'narrative' ? 'Resolve ai: narrativa' : 'Resolve ai: cobranca') . ' - ' . Str::limit($demand->title ?: $demand->raw_input, 55),
            'auto_tags' => ['resolve_ai', 'demanda_concluida', $mode === 'narrative' ? 'comunicação' : 'planejamento'],
            'context' => [
                'origin_module' => 'resolve_ai',
                'demand_id' => $demand->id,
                'demand_status' => $demand->status,
                'integration_mode' => $mode,
            ],
            'is_active' => true,
            'last_message_at' => now(),
        ]);

        $this->assistant->chat(
            userMessage: $mode === 'narrative'
                ? $this->buildNarrativeConversationPrompt($demand)
                : $this->buildFollowupConversationPrompt($demand),
            mayor: $user,
            conversation: $conversation,
        );

        return redirect()->route('mayor.chat.show', $conversation);
    }

    public function storeVoice(Request $request)
    {
        return response()->json(['ok' => false], 501);
    }

    private function normalizedFilters(Request $request): array
    {
        return [
            'status' => (string) $request->query('status', 'all'),
            'priority' => (string) $request->query('priority', 'all'),
            'contact_area_id' => $request->query('contact_area_id') ? (string) $request->query('contact_area_id') : '',
            'creator_id' => $request->query('creator_id') ? (string) $request->query('creator_id') : '',
            'locality' => trim((string) $request->query('locality', '')),
            'period' => (string) $request->query('period', 'all'),
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (($filters['priority'] ?? 'all') !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (filled($filters['contact_area_id'] ?? null)) {
            $query->where('contact_area_id', (int) $filters['contact_area_id']);
        }

        if (filled($filters['creator_id'] ?? null)) {
            $query->where('registered_by', (int) $filters['creator_id']);
        }

        if (($filters['locality'] ?? '') !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('locality', 'like', '%' . $filters['locality'] . '%')
                    ->orWhere('address', 'like', '%' . $filters['locality'] . '%')
                    ->orWhere('title', 'like', '%' . $filters['locality'] . '%')
                    ->orWhere('raw_input', 'like', '%' . $filters['locality'] . '%');
            });
        }

        if (($filters['period'] ?? 'all') !== 'all') {
            $start = match ($filters['period']) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                '90d' => now()->subDays(90),
                default => null,
            };

            if ($start) {
                $query->where('created_at', '>=', $start);
            }
        }
    }

    private function buildSummary(User $user): array
    {
        $baseQuery = $this->baseDemandQueryForUser($user);
        $currentMonthStart = now()->startOfMonth();
        $now = now();
        $openStatuses = ['registered', 'in_progress', 'overdue', 'reopened', 'awaiting_confirmation', 'pending'];
        $settings = $this->resolveAiSettings->forMunicipality($user->municipality);
        $inactivityFollowupHours = (int) ($settings['inactivity_followup_hours'] ?? 48);
        $overdueRepeatHours = (int) ($settings['overdue_repeat_hours'] ?? 24);
        $byArea = $this->baseDemandQueryForUser($user)
            ->selectRaw('COALESCE(area, ?) as area_name, COUNT(*) as total', ['Sem pasta'])
            ->groupBy('area_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->area_name,
                'total' => (int) $row->total,
            ])
            ->all();
        $proactiveDemands = $this->baseDemandQueryForUser($user)
            ->whereIn('status', ['registered', 'in_progress', 'reopened', 'pending', 'overdue'])
            ->get([
                'id',
                'status',
                'created_at',
                'last_progress_at',
                'acknowledged_at',
                'reopened_at',
                'due_at',
            ]);
        $stalledTotal = $proactiveDemands
            ->filter(function (Demand $demand) use ($inactivityFollowupHours, $now) {
                if (!in_array($demand->status, ['registered', 'in_progress', 'reopened', 'pending'], true)) {
                    return false;
                }

                $anchor = $this->resolveAiActivityAnchor($demand);

                return $anchor && $anchor->copy()->addHours($inactivityFollowupHours)->lte($now);
            })
            ->count();
        $overdueRepeatDueTotal = $proactiveDemands
            ->filter(function (Demand $demand) use ($overdueRepeatHours, $now) {
                return $demand->status === 'overdue'
                    && $demand->due_at
                    && $demand->due_at->copy()->addHours($overdueRepeatHours)->lte($now);
            })
            ->count();

        return [
            'open_total' => (clone $baseQuery)->whereIn('status', $openStatuses)->count(),
            'overdue_total' => (clone $baseQuery)->where('status', 'overdue')->count(),
            'awaiting_confirmation_total' => (clone $baseQuery)->where('status', 'awaiting_confirmation')->count(),
            'completed_month' => (clone $baseQuery)
                ->whereIn('status', ['completed', 'resolved'])
                ->where(function ($builder) use ($currentMonthStart) {
                    $builder
                        ->where('confirmed_at', '>=', $currentMonthStart)
                        ->orWhere('resolved_at', '>=', $currentMonthStart);
                })
                ->count(),
            'stalled_total' => $stalledTotal,
            'overdue_repeat_due_total' => $overdueRepeatDueTotal,
            'by_area' => $byArea,
        ];
    }

    private function buildTerritorialDataset(User $user, array $windowConfig)
    {
        $demands = $this->baseDemandQueryForUser($user)
            ->with('contactArea:id,name')
            ->where('created_at', '>=', now()->subDays($windowConfig['lookback_days']))
            ->get([
                'id',
                'status',
                'title',
                'raw_input',
                'completion_note',
                'locality',
                'area',
                'contact_area_id',
                'created_at',
                'resolved_at',
                'confirmed_at',
                'due_at',
            ]);

        return $demands->map(function (Demand $demand) {
            $normalizedLocality = $this->normalizeTerritorialLabel((string) ($demand->locality ?: 'Sem localidade'));
            $theme = $this->resolveDemandTheme($demand);
            $areaName = (string) ($demand->contactArea?->name ?? $demand->area ?? 'Sem secretaria');
            $resolvedAt = $demand->resolved_at ?? $demand->confirmed_at;
            $isCompleted = in_array($demand->status, ['completed', 'resolved'], true);

            return [
                'id' => $demand->id,
                'status' => (string) $demand->status,
                'locality' => $normalizedLocality,
                'theme' => $theme,
                'area_name' => $areaName,
                'is_open' => in_array($demand->status, ['registered', 'pending', 'in_progress', 'reopened', 'overdue', 'awaiting_confirmation'], true),
                'is_overdue' => $demand->status === 'overdue',
                'is_completed' => $isCompleted,
                'created_at' => $demand->created_at,
                'resolved_at' => $resolvedAt,
                'resolution_hours' => ($isCompleted && $resolvedAt)
                    ? max((float) $demand->created_at->diffInHours($resolvedAt), 0.0)
                    : null,
                'resolved_on_time' => (bool) ($isCompleted && $resolvedAt && (!$demand->due_at || $resolvedAt->lte($demand->due_at))),
            ];
        });
    }

    private function buildTerritorialIntelligence($enriched, array $windowConfig): array
    {
        $enriched = collect($enriched);

        $hotspots = $enriched
            ->groupBy('locality')
            ->map(function ($items, $locality) {
                $themeCounts = $items->groupBy('theme')->map->count()->sortDesc();

                return [
                    'locality' => (string) $locality,
                    'total' => $items->count(),
                    'open_total' => $items->where('is_open', true)->count(),
                    'overdue_total' => $items->where('is_overdue', true)->count(),
                    'completed_total' => $items->where('is_completed', true)->count(),
                    'top_theme' => (string) ($themeCounts->keys()->first() ?? 'Geral'),
                    'last_seen_at' => optional($items->max('created_at')),
                ];
            })
            ->sortByDesc(fn (array $item) => ($item['overdue_total'] * 3) + ($item['open_total'] * 2) + $item['total'])
            ->take(6)
            ->values()
            ->all();

        $themes = $enriched
            ->groupBy('theme')
            ->map(function ($items, $theme) {
                $localityCounts = $items->groupBy('locality')->map->count()->sortDesc();
                $areaCounts = $items->groupBy('area_name')->map->count()->sortDesc();

                return [
                    'theme' => (string) $theme,
                    'total' => $items->count(),
                    'open_total' => $items->where('is_open', true)->count(),
                    'overdue_total' => $items->where('is_overdue', true)->count(),
                    'top_locality' => (string) ($localityCounts->keys()->first() ?? 'Sem localidade'),
                    'top_area' => (string) ($areaCounts->keys()->first() ?? 'Sem secretaria'),
                ];
            })
            ->sortByDesc(fn (array $item) => ($item['open_total'] * 2) + ($item['overdue_total'] * 3) + $item['total'])
            ->take(8)
            ->values()
            ->all();

        $areas = $enriched
            ->groupBy('area_name')
            ->map(function ($items, $areaName) {
                $localityCounts = $items->groupBy('locality')->map->count()->sortDesc();
                $themeCounts = $items->groupBy('theme')->map->count()->sortDesc();

                return [
                    'area_name' => (string) $areaName,
                    'total' => $items->count(),
                    'open_total' => $items->where('is_open', true)->count(),
                    'overdue_total' => $items->where('is_overdue', true)->count(),
                    'completed_total' => $items->where('is_completed', true)->count(),
                    'top_locality' => (string) ($localityCounts->keys()->first() ?? 'Sem localidade'),
                    'top_themes' => $themeCounts->keys()->take(3)->values()->all(),
                ];
            })
            ->sortByDesc(fn (array $item) => ($item['open_total'] * 2) + ($item['overdue_total'] * 3) + $item['total'])
            ->take(6)
            ->values()
            ->all();

        return [
            'window_label' => $windowConfig['lookback_label'],
            'hotspots' => $hotspots,
            'themes' => $themes,
            'areas' => $areas,
        ];
    }

    private function buildSecretariatPerformancePayload($enriched, array $windowConfig): array
    {
        $enriched = collect($enriched);
        $now = now();
        $recentStart = $now->copy()->subDays($windowConfig['recent_days']);
        $previousStart = $now->copy()->subDays($windowConfig['recent_days'] + $windowConfig['previous_days']);
        $comparisonLabel = $windowConfig['comparison_label'];

        $areas = $enriched
            ->groupBy('area_name')
            ->map(function ($items, $areaName) use ($recentStart, $previousStart) {
                $total = $items->count();
                $completed = $items->where('is_completed', true);
                $openTotal = $items->where('is_open', true)->count();
                $overdueTotal = $items->where('is_overdue', true)->count();
                $topLocality = (string) ($items->groupBy('locality')->map->count()->sortDesc()->keys()->first() ?? 'Sem localidade');
                $topTheme = (string) ($items->groupBy('theme')->map->count()->sortDesc()->keys()->first() ?? 'Atendimento geral');

                $resolutionRate = $this->calculateRate($completed->count(), $total);
                $overdueRate = $this->calculateRate($overdueTotal, $total);
                $backlogRate = $this->calculateRate($openTotal, $total);
                $onTimeRate = $this->calculateRate($completed->where('resolved_on_time', true)->count(), $completed->count());
                $avgResolutionHours = $completed->pluck('resolution_hours')->filter(fn ($value) => $value !== null)->avg();

                $recentItems = $items->filter(fn (array $item) => $item['created_at'] && $item['created_at']->gte($recentStart));
                $previousItems = $items->filter(fn (array $item) => $item['created_at'] && $item['created_at']->gte($previousStart) && $item['created_at']->lt($recentStart));
                $recentResolutionRate = $this->calculateRate($recentItems->where('is_completed', true)->count(), $recentItems->count());
                $previousResolutionRate = $this->calculateRate($previousItems->where('is_completed', true)->count(), $previousItems->count());
                $recentOverdueRate = $this->calculateRate($recentItems->where('is_overdue', true)->count(), $recentItems->count());
                $previousOverdueRate = $this->calculateRate($previousItems->where('is_overdue', true)->count(), $previousItems->count());
                $recentBacklogRate = $this->calculateRate($recentItems->where('is_open', true)->count(), $recentItems->count());
                $previousBacklogRate = $this->calculateRate($previousItems->where('is_open', true)->count(), $previousItems->count());
                $scoreDelta = ($recentResolutionRate - $previousResolutionRate)
                    + (($previousOverdueRate - $recentOverdueRate) * 0.8)
                    + (($previousBacklogRate - $recentBacklogRate) * 0.5);
                $score = $this->calculateSecretariatScore($resolutionRate, $overdueRate, $onTimeRate, $backlogRate, $scoreDelta);

                return [
                    'area_name' => (string) $areaName,
                    'total' => $total,
                    'open_total' => $openTotal,
                    'overdue_total' => $overdueTotal,
                    'completed_total' => $completed->count(),
                    'resolution_rate' => $resolutionRate,
                    'overdue_rate' => $overdueRate,
                    'backlog_rate' => $backlogRate,
                    'on_time_rate' => $onTimeRate,
                    'avg_resolution_hours' => $avgResolutionHours ? round((float) $avgResolutionHours, 1) : null,
                    'avg_resolution_label' => $avgResolutionHours ? $this->formatHoursAsDuration((float) $avgResolutionHours) : 'Sem fechamento',
                    'score' => $score,
                    'score_label' => $score >= 78 ? 'Melhor desempenho' : ($score >= 58 ? 'Execução estável' : 'Atenção'),
                    'score_tone' => $score >= 78 ? 'good' : ($score >= 58 ? 'neutral' : 'risk'),
                    'top_locality' => $topLocality,
                    'top_theme' => $topTheme,
                    'recent_total' => $recentItems->count(),
                    'previous_total' => $previousItems->count(),
                    'recent_resolution_rate' => $recentResolutionRate,
                    'previous_resolution_rate' => $previousResolutionRate,
                    'recent_overdue_rate' => $recentOverdueRate,
                    'previous_overdue_rate' => $previousOverdueRate,
                    'recent_backlog_rate' => $recentBacklogRate,
                    'previous_backlog_rate' => $previousBacklogRate,
                    'score_delta' => round($scoreDelta, 1),
                    'trend_direction' => $scoreDelta >= 8 ? 'up' : ($scoreDelta <= -8 ? 'down' : 'stable'),
                    'trend_label' => $scoreDelta >= 8
                        ? 'Melhorando'
                        : ($scoreDelta <= -8 ? 'Piorando' : 'Estável'),
                ];
            })
            ->sortByDesc(fn (array $item) => ($item['score'] * 1000) + max($item['total'], 1))
            ->values()
            ->all();

        return [
            'window_label' => $windowConfig['lookback_label'],
            'comparison_label' => $comparisonLabel,
            'areas' => array_slice($areas, 0, 6),
        ];
    }

    private function buildTerritorialTrendPayload($enriched, array $windowConfig): array
    {
        $enriched = collect($enriched);
        $now = now();
        $recentStart = $now->copy()->subDays($windowConfig['recent_days']);
        $previousStart = $now->copy()->subDays($windowConfig['recent_days'] + $windowConfig['previous_days']);

        $comparisons = $enriched
            ->groupBy(fn (array $item) => $item['locality'] . '||' . $item['theme'])
            ->map(function ($items, $key) use ($recentStart, $previousStart) {
                [$locality, $theme] = explode('||', (string) $key, 2);
                $recentItems = $items->filter(fn (array $item) => $item['created_at'] && $item['created_at']->gte($recentStart));
                $previousItems = $items->filter(fn (array $item) => $item['created_at'] && $item['created_at']->gte($previousStart) && $item['created_at']->lt($recentStart));
                $recentTotal = $recentItems->count();
                $previousTotal = $previousItems->count();
                $delta = $recentTotal - $previousTotal;
                $recentResolutionRate = $this->calculateRate($recentItems->where('is_completed', true)->count(), $recentTotal);
                $previousResolutionRate = $this->calculateRate($previousItems->where('is_completed', true)->count(), $previousTotal);
                $recentOverdueRate = $this->calculateRate($recentItems->where('is_overdue', true)->count(), $recentTotal);
                $previousOverdueRate = $this->calculateRate($previousItems->where('is_overdue', true)->count(), $previousTotal);
                $recentBacklogRate = $this->calculateRate($recentItems->where('is_open', true)->count(), $recentTotal);
                $previousBacklogRate = $this->calculateRate($previousItems->where('is_open', true)->count(), $previousTotal);
                $executionDelta = ($recentResolutionRate - $previousResolutionRate)
                    + (($previousOverdueRate - $recentOverdueRate) * 0.8)
                    + (($previousBacklogRate - $recentBacklogRate) * 0.5);
                $dominantArea = (string) ($items->groupBy('area_name')->map->count()->sortDesc()->keys()->first() ?? 'Sem secretaria');

                return [
                    'locality' => (string) $locality,
                    'theme' => (string) $theme,
                    'area_name' => $dominantArea,
                    'recent_total' => $recentTotal,
                    'previous_total' => $previousTotal,
                    'delta_total' => $delta,
                    'recent_overdue_total' => $recentItems->where('is_overdue', true)->count(),
                    'previous_overdue_total' => $previousItems->where('is_overdue', true)->count(),
                    'recent_resolution_rate' => $recentResolutionRate,
                    'previous_resolution_rate' => $previousResolutionRate,
                    'recent_overdue_rate' => $recentOverdueRate,
                    'previous_overdue_rate' => $previousOverdueRate,
                    'recent_backlog_rate' => $recentBacklogRate,
                    'previous_backlog_rate' => $previousBacklogRate,
                    'execution_delta' => round($executionDelta, 1),
                ];
            })
            ->filter(fn (array $item) => $item['recent_total'] > 0 || $item['previous_total'] > 0)
            ->values();

        $rising = $comparisons
            ->filter(fn (array $item) => $item['delta_total'] > 0)
            ->sortByDesc(fn (array $item) => ($item['delta_total'] * 10) + ($item['recent_overdue_total'] * 3) + $item['recent_total'])
            ->take(6)
            ->map(fn (array $item) => $this->formatTerritorialTrendItem($item, 'rise'))
            ->values()
            ->all();

        $falling = $comparisons
            ->filter(fn (array $item) => $item['delta_total'] < 0)
            ->sortBy(fn (array $item) => ($item['delta_total'] * 10) - ($item['recent_overdue_total'] * 3))
            ->take(6)
            ->map(fn (array $item) => $this->formatTerritorialTrendItem($item, 'drop'))
            ->values()
            ->all();

        $execution = $comparisons
            ->filter(fn (array $item) => abs((float) $item['execution_delta']) >= 8 && $item['recent_total'] > 0)
            ->sortByDesc(fn (array $item) => abs((float) $item['execution_delta']) + $item['recent_total'])
            ->take(6)
            ->map(fn (array $item) => $this->formatTerritorialTrendItem($item, 'execution'))
            ->values()
            ->all();

        return [
            'window_label' => $windowConfig['lookback_label'],
            'comparison_label' => $windowConfig['comparison_label'],
            'rising' => $rising,
            'falling' => $falling,
            'execution' => $execution,
        ];
    }

    private function baseDemandQueryForUser(User $user)
    {
        $query = Demand::query()->where('municipality_id', $user->municipality_id);

        if (($user->isSecretary() || $user->isAdvisor()) && $user->contact_area_id) {
            $query->where('contact_area_id', $user->contact_area_id);
        }

        return $query;
    }

    private function routeBase(User $user): string
    {
        return $user->isMayor() ? 'mayor.mandato.demands' : 'resolve-ai.demands';
    }

    private function canAccessDemand(User $user, Demand $demand): bool
    {
        if ($demand->municipality_id !== $user->municipality_id) {
            return false;
        }

        if ($user->isMayor()) {
            return true;
        }

        return $user->contact_area_id !== null && (int) $demand->contact_area_id === (int) $user->contact_area_id;
    }

    private function resolveContactAreaIdForUser(User $user, ?int $requestedContactAreaId): ?int
    {
        if ($user->isSecretary() || $user->isAdvisor()) {
            return $user->contact_area_id;
        }

        return $requestedContactAreaId;
    }

    private function availableContactAreas(User $user)
    {
        $query = $user->municipality->contactAreas()->where('active', true)->orderBy('name');

        if (($user->isSecretary() || $user->isAdvisor()) && $user->contact_area_id) {
            $query->where('id', $user->contact_area_id);
        }

        return $query->get();
    }

    private function availableCreators(User $user)
    {
        $query = User::query()
            ->whereIn('id', $this->baseDemandQueryForUser($user)->select('registered_by')->distinct())
            ->active()
            ->orderBy('name');

        return $query->get(['id', 'name']);
    }

    private function isDemandReadyForStorytelling(Demand $demand): bool
    {
        return in_array($demand->status, ['awaiting_confirmation', 'completed', 'resolved'], true);
    }

    private function resolveAiActivityAnchor(Demand $demand)
    {
        return collect([
            $demand->last_progress_at,
            $demand->acknowledged_at,
            $demand->reopened_at,
            $demand->created_at,
        ])->filter()->sortDesc()->first();
    }

    private function resolveDemandTheme(Demand $demand): string
    {
        $text = $this->normalizeThemeText(implode(' ', array_filter([
            $demand->title,
            $demand->raw_input,
            $demand->completion_note,
        ])));

        $themeKeywords = [
            'Iluminacao publica' => ['lampada', 'poste', 'iluminacao', 'escuro', 'luz apagada'],
            'Vias e mobilidade' => ['buraco', 'asfalto', 'calcada', 'transito', 'sinalizacao', 'ponte', 'estrada', 'pavimentacao'],
            'Limpeza urbana' => ['lixo', 'entulho', 'limpeza', 'capina', 'mato alto', 'coleta'],
            'Agua e saneamento' => ['agua', 'esgoto', 'drenagem', 'alagamento', 'vazamento', 'saneamento'],
            'Saude' => ['saude', 'posto', 'ubs', 'medico', 'consulta', 'remedio', 'ambulancia'],
            'Educacao' => ['escola', 'creche', 'professor', 'aluno', 'educacao', 'merenda'],
            'Assistencia social' => ['social', 'familia', 'beneficio', 'assistencia', 'cras', 'cadastro'],
            'Seguranca e ordem' => ['seguranca', 'guarda', 'violencia', 'risco', 'fiscalizacao'],
            'Habitacao e obras' => ['casa', 'moradia', 'habitacao', 'obra', 'reforma', 'construcao'],
            'Esporte e lazer' => ['quadra', 'praca', 'esporte', 'lazer', 'campo', 'parque'],
        ];

        foreach ($themeKeywords as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($text, $this->normalizeThemeText($keyword))) {
                    return $theme;
                }
            }
        }

        return 'Atendimento geral';
    }

    private function normalizeThemeText(string $text): string
    {
        return Str::of(Str::ascii($text))
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    private function normalizeTerritorialLabel(string $label): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $label)) ?: 'Sem localidade';

        return Str::title(Str::lower($normalized));
    }

    private function calculateRate(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    private function calculateSecretariatScore(
        float $resolutionRate,
        float $overdueRate,
        float $onTimeRate,
        float $backlogRate,
        float $scoreDelta
    ): float
    {
        $trendContribution = max(min($scoreDelta, 20), -20) + 20;
        $score = ($resolutionRate * 0.35)
            + ($onTimeRate * 0.25)
            + ((100 - $overdueRate) * 0.2)
            + ((100 - $backlogRate) * 0.1)
            + ($trendContribution * 0.1);

        return round(max(min($score, 100), 0), 1);
    }

    private function buildComparativeWindowConfig(array $resolveAiSettings): array
    {
        $recentDays = max(7, (int) ($resolveAiSettings['comparative_recent_window_days'] ?? 90));
        $previousDays = max(7, (int) ($resolveAiSettings['comparative_previous_window_days'] ?? 90));
        $lookbackDays = $recentDays + $previousDays;

        return [
            'recent_days' => $recentDays,
            'previous_days' => $previousDays,
            'lookback_days' => $lookbackDays,
            'lookback_label' => 'Últimos ' . $lookbackDays . ' dias',
            'comparison_label' => 'Últimos ' . $recentDays . ' dias vs ' . $previousDays . ' dias anteriores',
        ];
    }

    private function formatHoursAsDuration(float $hours): string
    {
        if ($hours < 24) {
            return round($hours, 1) . 'h';
        }

        return round($hours / 24, 1) . ' dias';
    }

    private function formatTerritorialTrendItem(array $item, string $mode): array
    {
        $deltaPrefix = $item['delta_total'] > 0 ? '+' : '';

        return [
            'locality' => $item['locality'],
            'theme' => $item['theme'],
            'area_name' => $item['area_name'],
            'recent_total' => $item['recent_total'],
            'previous_total' => $item['previous_total'],
            'delta_total' => $item['delta_total'],
            'delta_label' => $deltaPrefix . $item['delta_total'],
            'recent_overdue_total' => $item['recent_overdue_total'],
            'recent_resolution_rate' => $item['recent_resolution_rate'],
            'previous_resolution_rate' => $item['previous_resolution_rate'],
            'recent_overdue_rate' => $item['recent_overdue_rate'],
            'previous_overdue_rate' => $item['previous_overdue_rate'],
            'recent_backlog_rate' => $item['recent_backlog_rate'],
            'previous_backlog_rate' => $item['previous_backlog_rate'],
            'execution_delta' => $item['execution_delta'],
            'trend_label' => match ($mode) {
                'rise' => 'Reincidência em alta',
                'drop' => 'Reincidência em queda',
                default => $item['execution_delta'] > 0 ? 'Execução melhorando' : 'Execução piorando',
            },
            'meta_label' => match ($mode) {
                'rise', 'drop' => 'Janela recente: ' . $item['recent_total'] . ' · Janela anterior: ' . $item['previous_total'],
                default => 'Resolução: ' . $item['previous_resolution_rate'] . '% -> ' . $item['recent_resolution_rate'] . '%',
            },
        ];
    }

    private function buildNarrativeConversationPrompt(Demand $demand): string
    {
        $lines = [];
        $lines[] = 'Quero transformar esta demanda concluida do Resolve ai em narrativa política e prestacao de contas.';
        $lines[] = '';
        $lines[] = 'DADOS DA DEMANDA:';
        $lines[] = '- Título: ' . ($demand->title ?: 'Demanda operacional');
        $lines[] = '- Registro original: ' . trim((string) $demand->raw_input);
        if ($demand->locality) $lines[] = '- Localidade: ' . $demand->locality;
        if ($demand->address) $lines[] = '- Endereco: ' . $demand->address;
        if ($demand->contactArea?->name || $demand->area) $lines[] = '- Secretaria: ' . ($demand->contactArea?->name ?? $demand->area);
        if ($demand->completion_note) $lines[] = '- Entrega executada: ' . trim($demand->completion_note);
        if ($demand->completion_attachment_name) $lines[] = '- Existe comprovante/anexo: ' . $demand->completion_attachment_name;
        $lines[] = '';
        $lines[] = 'ENTREGA OBRIGATORIA:';
        $lines[] = '1) Resumo da entrega em linguagem publica.';
        $lines[] = '2) Narrativa política do antes, durante e depois.';
        $lines[] = '3) O que comunicar sem exagero ou promessa indevida.';
        $lines[] = '4) Ganchos para redes sociais, video curto e discurso.';
        $lines[] = '5) Riscos de comunicação e cuidados de tom.';

        return implode("\n", $lines);
    }

    private function buildFollowupConversationPrompt(Demand $demand): string
    {
        $lines = [];
        $lines[] = 'Quero montar a cobranca e o acompanhamento estrategico apos esta demanda concluida do Resolve ai.';
        $lines[] = '';
        $lines[] = 'DADOS DA DEMANDA:';
        $lines[] = '- Título: ' . ($demand->title ?: 'Demanda operacional');
        $lines[] = '- Registro original: ' . trim((string) $demand->raw_input);
        if ($demand->locality) $lines[] = '- Localidade: ' . $demand->locality;
        if ($demand->contactArea?->name || $demand->area) $lines[] = '- Secretaria: ' . ($demand->contactArea?->name ?? $demand->area);
        if ($demand->priority) $lines[] = '- Prioridade: ' . $demand->priority;
        if ($demand->completion_note) $lines[] = '- Entrega executada: ' . trim($demand->completion_note);
        $lines[] = '';
        $lines[] = 'ENTREGA OBRIGATORIA:';
        $lines[] = '1) Checklist de cobranca pos-entrega para não deixar o tema morrer.';
        $lines[] = '2) O que ainda precisa ser confirmado em campo.';
        $lines[] = '3) Indicadores simples para acompanhar se o problema foi realmente resolvido.';
        $lines[] = '4) Como cobrar secretaria, equipe e comunicação nos proximos 7 dias.';
        $lines[] = '5) Próximas 3 acoes praticas para hoje.';

        return implode("\n", $lines);
    }

    private function resolveDueAt($municipality, string $priority, ?string $dueDate = null): Carbon
    {
        if ($dueDate) {
            return Carbon::parse($dueDate)->endOfDay();
        }

        return now()->addHours($this->resolveAiSettings->hoursForPriority($municipality, $priority));
    }

    private function syncComputedStatuses(int $municipalityId): void
    {
        Demand::query()
            ->where('municipality_id', $municipalityId)
            ->whereIn('status', ['registered', 'in_progress', 'reopened', 'pending'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->update(['status' => 'overdue']);
    }

    private function syncDemandStatus(Demand $demand): void
    {
        if (
            in_array((string) $demand->status, ['registered', 'in_progress', 'reopened', 'pending'], true) &&
            $demand->due_at &&
            $demand->due_at->isPast()
        ) {
            $demand->update(['status' => 'overdue']);
            $demand->refresh();
        }
    }

    private function recordEvent(
        Demand $demand,
        string $eventType,
        ?User $user,
        ?string $message = null,
        array $metadata = []
    ): DemandEvent {
        return $demand->events()->create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }
}
