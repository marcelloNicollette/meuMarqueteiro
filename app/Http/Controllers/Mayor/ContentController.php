<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\ContentTemplate;
use App\Models\Demand;
use App\Models\DemandEvent;
use App\Models\GeneratedContent;
use App\Models\MentionKeyword;
use App\Models\SocialMention;
use App\Models\User;
use App\Services\WebPushService;
use App\Services\Communication\CommunicationSettingsService;
use App\Services\Communication\ContentGenerationService;
use App\Services\Support\MunicipalityConfigurationStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function __construct(
        private ContentGenerationService $service,
        private CommunicationSettingsService $communicationSettings,
        private WebPushService $webPush,
        private MunicipalityConfigurationStatusService $configurationStatus,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $activeArea = $request->string('area')->toString() ?: 'produce';
        $activeArea = in_array($activeArea, ['produce', 'mentions', 'operations', 'archive'], true) ? $activeArea : 'produce';
        $contents = $user->municipality
            ->generatedContents()
            ->with('user:id,name,role')
            ->orderByDesc('created_at')
            ->get();

        $filters = [
            'status' => $request->string('status')->toString() ?: 'all',
            'type' => $request->string('type')->toString() ?: 'all',
            'channel' => $request->string('channel')->toString() ?: 'all',
            'tone' => $request->string('tone')->toString() ?: 'all',
            'creator_profile' => $request->string('creator_profile')->toString() ?: 'all',
            'period' => $request->string('period')->toString() ?: 'all',
            'search' => trim($request->string('search')->toString()),
        ];

        $filteredContents = $contents->filter(function (GeneratedContent $content) use ($filters) {
            if ($filters['status'] !== 'all' && $content->status !== $filters['status']) {
                return false;
            }

            if ($filters['type'] !== 'all' && !$this->contentMatchesTypeFilter($content, $filters['type'])) {
                return false;
            }

            if ($filters['channel'] !== 'all' && (string) ($content->channel ?? 'interno') !== $filters['channel']) {
                return false;
            }

            if ($filters['tone'] !== 'all' && (string) ($content->tone ?? 'neutro') !== $filters['tone']) {
                return false;
            }

            if ($filters['creator_profile'] !== 'all') {
                $role = $content->user?->role?->value ?? (string) ($content->user?->role ?? '');
                if ($role !== $filters['creator_profile']) {
                    return false;
                }
            }

            if ($filters['period'] !== 'all') {
                $threshold = match ($filters['period']) {
                    '7d' => now()->subDays(7),
                    '30d' => now()->subDays(30),
                    '90d' => now()->subDays(90),
                    default => null,
                };

                if ($threshold && (!$content->created_at || $content->created_at->lt($threshold))) {
                    return false;
                }
            }

            if ($filters['search'] !== '') {
                $haystack = collect([
                    $content->title,
                    $content->content,
                    implode(' ', $content->tags ?? []),
                    data_get($content->metadata, 'theme'),
                    data_get($content->metadata, 'origin_module'),
                    $content->channel,
                    $content->tone,
                    $content->user?->name,
                    $content->user?->role?->label(),
                    data_get($content->metadata, 'archive.reference_note'),
                    data_get($content->metadata, 'archive.outcome_note'),
                ])->filter()->implode(' ');

                if (!str_contains(mb_strtolower($haystack), mb_strtolower($filters['search']))) {
                    return false;
                }
            }

            return true;
        })->values();

        $posts = $filteredContents->filter(fn ($c) => str_starts_with($c->type, 'post') || in_array($c->type, ['discurso', 'comunicado'], true));
        $entrevistas = $filteredContents->where('type', 'entrevista');
        $crises = $filteredContents->where('type', 'crise');
        $images = $filteredContents->where('type', 'imagem_instagram');
        $initialTab = request()->string('tab')->toString();
        $initialTab = in_array($initialTab, ['post', 'image', 'interview', 'crisis'], true) ? $initialTab : 'post';

        $initialContentId = request()->integer('content');
        $initialContentId = $filteredContents->contains('id', $initialContentId) ? $initialContentId : null;
        $initialMentionSeed = null;
        $initialReuseSeed = null;

        if ($request->filled('mention')) {
            $mention = SocialMention::where('municipality_id', $user->municipality_id)
                ->find($request->integer('mention'));

            if ($mention) {
                $initialTab = 'crisis';
                $initialMentionSeed = [
                    'id' => $mention->id,
                    'title' => $mention->title,
                    'content' => $mention->content,
                    'source_label' => $mention->source_label,
                    'author' => $mention->author,
                    'url' => $mention->url,
                    'sentiment_label' => $mention->sentiment_label,
                    'sentiment_key' => $mention->sentiment,
                    'published_at_human' => $mention->time_ago,
                ];
            }
        }

        if ($request->filled('reuse')) {
            $reuseContent = $contents->firstWhere('id', $request->integer('reuse'));
            if ($reuseContent) {
                $serializedReuse = $this->serializeContent($reuseContent);
                $initialReuseSeed = [
                    'id' => $serializedReuse['id'],
                    'type' => $serializedReuse['type'],
                    'type_label' => $serializedReuse['type_label'],
                    'title' => $serializedReuse['title'],
                    'content' => $serializedReuse['content'],
                    'channel' => $serializedReuse['channel'],
                    'tone' => $serializedReuse['tone'],
                    'variations' => $serializedReuse['variations'],
                    'archive_memory' => $serializedReuse['archive_memory'],
                    'template' => $serializedReuse['template'],
                    'playbook' => $serializedReuse['playbook'],
                ];
                $initialTab = $this->resolveReuseTab((string) $reuseContent->type);
            }
        }

        $summary = [
            'total' => $contents->count(),
            'draft' => $contents->where('status', 'draft')->count(),
            'approved' => $contents->where('status', 'approved')->count(),
            'published' => $contents->where('status', 'published')->count(),
            'archived' => $contents->where('status', 'archived')->count(),
        ];

        $slaConfig = $this->resolveEditorialSlaConfig();
        $editorialBoard = $this->buildEditorialBoard($contents, $filteredContents, $slaConfig);
        $contentTemplates = $user->municipality
            ->contentTemplates()
            ->whereIn('kind', ['post', 'image'])
            ->get()
            ->map(fn (ContentTemplate $template) => $this->serializeTemplate($template))
            ->values()
            ->all();
        $editorialPlaybooks = $this->buildEditorialPlaybooks();
        $mentionsBoard = $this->buildMentionsBoard($user, $request);
        $operationsBoard = $this->buildOperationsBoard($user, $request);
        $archiveBoard = $this->buildArchiveBoard($filteredContents, $contents);

        return view('mayor.content.index', compact(
            'activeArea',
            'contents',
            'filteredContents',
            'posts',
            'entrevistas',
            'crises',
            'images',
            'summary',
            'editorialBoard',
            'contentTemplates',
            'editorialPlaybooks',
            'mentionsBoard',
            'operationsBoard',
            'archiveBoard',
            'filters',
            'initialTab',
            'initialContentId',
            'initialMentionSeed',
            'initialReuseSeed',
        ));
    }

    private function buildMentionsBoard(User $user, Request $request): array
    {
        $municipality = $user->municipality;
        $filter = $request->string('mention_filter')->toString() ?: 'all';
        $source = $request->string('mention_source')->toString() ?: 'all';
        $days = max(1, min(30, $request->integer('mention_days') ?: 7));
        $since = now()->subDays($days);

        $query = SocialMention::where('municipality_id', $municipality->id)
            ->where('created_at', '>=', $since)
            ->orderByDesc('published_at');

        if ($filter === 'unread') {
            $query->where('is_read', false);
        } elseif ($filter !== 'all') {
            $query->where('sentiment', $filter);
        }

        if ($source !== 'all') {
            $query->where('source', $source);
        }

        $mentions = $query->limit(20)->get();
        $stats = [
            'total' => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->count(),
            'positive' => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->where('sentiment', 'positive')->count(),
            'negative' => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->where('sentiment', 'negative')->count(),
            'neutral' => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->where('sentiment', 'neutral')->count(),
            'urgent' => SocialMention::where('municipality_id', $municipality->id)->where('created_at', '>=', $since)->where('sentiment', 'urgent')->count(),
            'unread' => SocialMention::where('municipality_id', $municipality->id)->where('is_read', false)->count(),
        ];

        $sourceOptions = SocialMention::where('municipality_id', $municipality->id)
            ->where('created_at', '>=', $since)
            ->select('source')
            ->distinct()
            ->pluck('source')
            ->filter()
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => $this->mapMentionSourceLabel($value),
            ])
            ->values()
            ->all();

        $keywords = MentionKeyword::where('municipality_id', $municipality->id)
            ->orderBy('type')
            ->get();
        $configSummary = $this->configurationStatus->summarize($municipality);

        $segments = collect([
            ['key' => 'positive', 'label' => 'Positivas', 'count' => $stats['positive'], 'color' => '#1e7e48'],
            ['key' => 'neutral', 'label' => 'Neutras', 'count' => $stats['neutral'], 'color' => '#94a3b8'],
            ['key' => 'negative', 'label' => 'Negativas', 'count' => $stats['negative'], 'color' => '#b52b2b'],
            ['key' => 'urgent', 'label' => 'Urgentes', 'count' => $stats['urgent'], 'color' => '#ea580c'],
        ])->map(function (array $segment) use ($stats) {
            $total = max(1, (int) ($stats['total'] ?? 0));
            $segment['percent'] = round(($segment['count'] / $total) * 100, 1);
            return $segment;
        })->all();

        return [
            'filters' => [
                'filter' => $filter,
                'source' => $source,
                'days' => $days,
            ],
            'stats' => $stats,
            'keywords' => $keywords,
            'source_options' => $sourceOptions,
            'reputation_segments' => $segments,
            'mentions' => $mentions,
            'configuration' => [
                'score' => $configSummary['score'],
                'status' => $configSummary['status'],
                'summary_label' => $configSummary['summary_label'],
                'active_channels' => $configSummary['active_channels'],
                'monitoring_terms' => $configSummary['monitoring_terms'],
                'monitoring_portals' => $configSummary['monitoring_portals'],
                'keywords_total' => $configSummary['monitoring_keywords_total'],
                'pra_hoje_time' => $configSummary['pra_hoje_time'],
                'issues' => $configSummary['issues'],
            ],
        ];
    }

    private function buildOperationsBoard(User $user, Request $request): array
    {
        $municipalityId = (int) $user->municipality_id;
        $this->syncOperationalDemandStatuses($municipalityId);

        $filters = [
            'type' => $request->string('operation_type')->toString() ?: 'all',
            'contact_area_id' => $request->integer('operation_contact_area_id') ?: null,
            'priority' => $request->string('operation_priority')->toString() ?: 'all',
            'period' => $request->string('operation_period')->toString() ?: '30d',
            'search' => trim($request->string('operation_search')->toString()),
        ];

        $query = Demand::query()
            ->where('municipality_id', $municipalityId)
            ->with([
                'contactArea:id,name,contact_name',
                'registeredBy:id,name',
            ]);

        if ($filters['contact_area_id']) {
            $query->where('contact_area_id', $filters['contact_area_id']);
        }

        if ($filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if ($filters['period'] !== 'all') {
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

        if ($filters['search'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('raw_input', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('completion_note', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('locality', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('address', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('area', 'like', '%' . $filters['search'] . '%');
            });
        }

        $serialized = $query
            ->orderByRaw('COALESCE(due_at, CAST(due_date AS timestamp), created_at) ASC')
            ->get()
            ->map(fn (Demand $demand) => $this->serializeOperationDemand($demand))
            ->filter(function (array $entry) use ($filters) {
                if ($filters['type'] !== 'all' && $entry['type_key'] !== $filters['type']) {
                    return false;
                }

                return true;
            })
            ->values();

        $columns = collect([
            ['key' => 'entry', 'label' => 'Entrada', 'subtitle' => 'Demandas recém-chegadas, ainda sem dono claro ou sem prazo fechado.'],
            ['key' => 'planning', 'label' => 'Em planejamento', 'subtitle' => 'Itens já encaminhados, com prazo e verificação operacional em curso.'],
            ['key' => 'production', 'label' => 'Em produção', 'subtitle' => 'Execução ativa da peça, cobertura ou resposta em andamento.'],
            ['key' => 'approval', 'label' => 'Em aprovação', 'subtitle' => 'Conteúdos ou entregas aguardando confirmação, revisão ou sinal verde.'],
            ['key' => 'completed', 'label' => 'Concluída', 'subtitle' => 'Itens finalizados, publicados ou prontos para memória e narrativa.'],
        ])->map(function (array $column) use ($serialized) {
            $items = $serialized
                ->where('column_key', $column['key'])
                ->sortBy([
                    ['is_overdue', 'desc'],
                    ['sort_timestamp', 'asc'],
                ])
                ->values();

            return array_merge($column, [
                'total' => $items->count(),
                'items' => $items->all(),
            ]);
        })->all();

        $storySuggestions = $serialized
            ->filter(fn (array $entry) => $entry['story_ready'])
            ->sortByDesc('story_priority_score')
            ->take(6)
            ->values()
            ->all();

        $deadlines = $serialized
            ->filter(fn (array $entry) => in_array($entry['column_key'], ['entry', 'planning', 'production', 'approval'], true) && !empty($entry['due_at_iso']))
            ->sortBy('sort_timestamp')
            ->take(8)
            ->values()
            ->all();

        $recentActivity = DemandEvent::query()
            ->whereHas('demand', function ($builder) use ($municipalityId) {
                $builder->where('municipality_id', $municipalityId);
            })
            ->with([
                'demand:id,title,status',
                'user:id,name',
            ])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (DemandEvent $event) => [
                'id' => $event->id,
                'title' => $event->demand?->title ?: 'Demanda operacional',
                'event_label' => $this->mapOperationEventLabel((string) $event->event_type),
                'message' => $event->message ?: 'Atualização operacional registrada.',
                'user_name' => $event->user?->name ?: 'Sistema',
                'created_at_human' => $event->created_at?->diffForHumans() ?: 'agora',
                'show_url' => $event->demand ? route('mayor.mandato.demands.show', $event->demand) : null,
            ])
            ->all();

        $typeMix = $serialized
            ->groupBy('type_label')
            ->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'total' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'filters' => $filters,
            'summary' => [
                'entry_total' => collect($columns)->firstWhere('key', 'entry')['total'] ?? 0,
                'planning_total' => collect($columns)->firstWhere('key', 'planning')['total'] ?? 0,
                'production_total' => collect($columns)->firstWhere('key', 'production')['total'] ?? 0,
                'approval_total' => collect($columns)->firstWhere('key', 'approval')['total'] ?? 0,
                'completed_total' => collect($columns)->firstWhere('key', 'completed')['total'] ?? 0,
                'overdue_total' => $serialized->where('is_overdue', true)->count(),
                'story_ready_total' => $serialized->where('story_ready', true)->count(),
            ],
            'columns' => $columns,
            'story_suggestions' => $storySuggestions,
            'deadlines' => $deadlines,
            'recent_activity' => $recentActivity,
            'type_mix' => $typeMix,
            'contact_areas' => $user->municipality->contactAreas()->where('active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function serializeOperationDemand(Demand $demand): array
    {
        $type = $this->resolveOperationDemandType($demand);
        $column = $this->resolveOperationDemandColumn($demand);
        $priorityBadge = $this->resolveOperationPriorityBadge((string) $demand->priority);
        $statusBadge = $this->resolveOperationStatusBadge((string) $demand->status);
        $originLabel = $this->resolveOperationDemandOrigin($demand, $type['key']);
        $storyReady = in_array((string) $demand->status, ['awaiting_confirmation', 'completed', 'resolved'], true);
        $dueAt = $demand->due_at ?? ($demand->due_date ? Carbon::parse($demand->due_date)->endOfDay() : null);

        return [
            'id' => $demand->id,
            'title' => $demand->title ?: Str::limit((string) $demand->raw_input, 90),
            'copy' => Str::limit((string) ($demand->completion_note ?: $demand->raw_input), 180),
            'type_key' => $type['key'],
            'type_label' => $type['label'],
            'type_hint' => $type['hint'],
            'column_key' => $column['key'],
            'column_label' => $column['label'],
            'status' => (string) $demand->status,
            'status_label' => $statusBadge['label'],
            'status_badge' => $statusBadge,
            'priority' => (string) $demand->priority,
            'priority_label' => $priorityBadge['label'],
            'priority_badge' => $priorityBadge,
            'responsible_label' => $demand->contactArea?->name ?? ($demand->area ?: 'Sem pasta definida'),
            'responsible_contact' => $demand->contactArea?->contact_name,
            'requested_by' => $demand->registeredBy?->name ?? 'Sistema',
            'locality' => $demand->locality ?: ($demand->address ?: 'Sem localidade'),
            'origin_label' => $originLabel,
            'channel_label' => $this->resolveOperationDemandChannel($type['key']),
            'resource_hint' => $this->resolveOperationResourceHint($type['key'], $storyReady, !empty($demand->completion_attachment_path)),
            'has_attachment' => !empty($demand->completion_attachment_path),
            'story_ready' => $storyReady,
            'story_priority_score' => ($storyReady ? 100 : 0) + (!empty($demand->completion_attachment_path) ? 20 : 0) + (($demand->priority === 'alta') ? 10 : 0),
            'is_overdue' => (string) $demand->status === 'overdue' || ($dueAt && $dueAt->isPast() && !in_array((string) $demand->status, ['completed', 'resolved'], true)),
            'due_at_human' => $dueAt ? $dueAt->format('d/m/Y H:i') : 'Sem prazo definido',
            'due_at_iso' => $dueAt?->toIso8601String(),
            'created_at_human' => $demand->created_at?->diffForHumans() ?: 'agora',
            'sort_timestamp' => ($dueAt ?? $demand->created_at)->getTimestamp(),
            'show_url' => route('mayor.mandato.demands.show', $demand),
            'context_url' => $type['key'] === 'mandate_delivery'
                ? route('mayor.mandato.painel', ['area' => 'dashboard'])
                : null,
            'context_label' => $type['key'] === 'mandate_delivery'
                ? 'Abrir Mandato'
                : null,
        ];
    }

    private function resolveOperationDemandType(Demand $demand): array
    {
        if ((string) $demand->input_type === 'mandato_action_completed') {
            return [
                'key' => 'mandate_delivery',
                'label' => 'Mandato em conteudo',
                'hint' => 'Acao concluida do Mandato pronta para pauta de prestacao de contas.',
            ];
        }

        $text = Str::of(Str::ascii(implode(' ', array_filter([
            $demand->title,
            $demand->raw_input,
            $demand->completion_note,
        ]))))->lower()->value();

        if (
            str_contains($text, 'evento') ||
            str_contains($text, 'inauguracao') ||
            str_contains($text, 'visita tecnica') ||
            str_contains($text, 'audiencia publica') ||
            str_contains($text, 'agenda') ||
            str_contains($text, 'cobertura')
        ) {
            return [
                'key' => 'event_coverage',
                'label' => 'Cobertura de evento',
                'hint' => 'Solenidades, visitas, agenda e cobertura institucional.',
            ];
        }

        if (
            str_contains($text, 'imprensa') ||
            str_contains($text, 'jornalista') ||
            str_contains($text, 'release') ||
            str_contains($text, 'entrevista') ||
            str_contains($text, 'radio') ||
            str_contains($text, 'coletiva')
        ) {
            return [
                'key' => 'press_service',
                'label' => 'Atendimento à imprensa',
                'hint' => 'Respostas, releases e organização de entrevistas.',
            ];
        }

        if (
            str_contains($text, 'crise') ||
            str_contains($text, 'denuncia') ||
            str_contains($text, 'viral') ||
            str_contains($text, 'fake news') ||
            str_contains($text, 'repercussao') ||
            str_contains($text, 'mencao')
        ) {
            return [
                'key' => 'crisis_monitoring',
                'label' => 'Monitoramento de crise',
                'hint' => 'Situações sensíveis que exigem resposta e alinhamento rápido.',
            ];
        }

        if (in_array((string) $demand->status, ['awaiting_confirmation', 'completed', 'resolved'], true)) {
            return [
                'key' => 'resolve_story',
                'label' => 'Demanda convertida em conteúdo',
                'hint' => 'Entrega concluída que pode virar prestação de contas e narrativa.',
            ];
        }

        return [
            'key' => 'content_production',
            'label' => 'Produção de conteúdo',
            'hint' => 'Posts, releases, discursos e peças editoriais do dia a dia.',
        ];
    }

    private function resolveOperationDemandColumn(Demand $demand): array
    {
        $status = (string) $demand->status;

        if (in_array($status, ['completed', 'resolved'], true)) {
            return ['key' => 'completed', 'label' => 'Concluída'];
        }

        if ($status === 'awaiting_confirmation') {
            return ['key' => 'approval', 'label' => 'Em aprovação'];
        }

        if (in_array($status, ['in_progress', 'overdue'], true)) {
            return ['key' => 'production', 'label' => 'Em produção'];
        }

        if (in_array($status, ['pending', 'reopened'], true) || ($status === 'registered' && ($demand->contact_area_id || $demand->due_at || $demand->due_date))) {
            return ['key' => 'planning', 'label' => 'Em planejamento'];
        }

        return ['key' => 'entry', 'label' => 'Entrada'];
    }

    private function mapOperationColumnLabel(string $columnKey): string
    {
        return match ($columnKey) {
            'planning' => 'Em planejamento',
            'production' => 'Em produção',
            'approval' => 'Em aprovação',
            'completed' => 'Concluída',
            default => 'Entrada',
        };
    }

    private function resolveOperationDemandOrigin(Demand $demand, string $typeKey): string
    {
        if ($typeKey === 'mandate_delivery') {
            return 'Mandato';
        }

        if ($typeKey === 'resolve_story') {
            return 'Resolve ai';
        }

        if ($typeKey === 'event_coverage') {
            return 'Agenda institucional';
        }

        if ($typeKey === 'crisis_monitoring') {
            return 'Menções / Crise';
        }

        return $demand->contactArea?->name ? 'Secretaria / pasta' : 'Gabinete / equipe';
    }

    private function resolveOperationDemandChannel(string $typeKey): string
    {
        return match ($typeKey) {
            'event_coverage' => 'Cobertura + redes',
            'press_service' => 'Imprensa / release',
            'mandate_delivery' => 'Prestacao de contas',
            'resolve_story' => 'Prestação de contas',
            'crisis_monitoring' => 'Resposta / nota',
            default => 'Instagram / Facebook / WhatsApp',
        };
    }

    private function resolveOperationResourceHint(string $typeKey, bool $storyReady, bool $hasAttachment): string
    {
        if ($storyReady && $hasAttachment) {
            return 'Comprovante anexado e pronto para virar comunicação.';
        }

        return match ($typeKey) {
            'event_coverage' => 'Checar equipe, transporte e equipamentos antes da cobertura.',
            'press_service' => 'Checar porta-voz, briefing e tempo de resposta à imprensa.',
            'crisis_monitoring' => 'Checar alinhamento com crise, nota e próximos passos.',
            'mandate_delivery' => 'Transformar a entrega concluida em narrativa de balanco, prestacao de contas e cumprimento de compromisso.',
            'resolve_story' => 'Organizar narrativa pública da entrega e prova de campo.',
            default => 'Fechar briefing, canal e prazo operacional da peça.',
        };
    }

    private function resolveOperationPriorityBadge(string $priority): array
    {
        return match ($priority) {
            'alta' => ['label' => 'Alta', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
            'baixa' => ['label' => 'Baixa', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
            default => ['label' => 'Média', 'bg' => '#fff7ed', 'color' => '#c2410c'],
        };
    }

    private function resolveOperationStatusBadge(string $status): array
    {
        return match ($status) {
            'registered', 'pending' => ['label' => 'Registrada', 'bg' => '#fffbeb', 'color' => '#b45309'],
            'in_progress' => ['label' => 'Em andamento', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
            'overdue' => ['label' => 'Atrasada', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
            'awaiting_confirmation' => ['label' => 'Aguardando confirmação', 'bg' => '#f5f3ff', 'color' => '#7c3aed'],
            'reopened' => ['label' => 'Reaberta', 'bg' => '#fff7ed', 'color' => '#c2410c'],
            default => ['label' => 'Concluída', 'bg' => '#ecfdf5', 'color' => '#047857'],
        };
    }

    private function mapOperationEventLabel(string $eventType): string
    {
        return match ($eventType) {
            'registered' => 'Entrada registrada',
            'acknowledged' => 'Recebida pela equipe',
            'progress_updated', 'progress_note' => 'Andamento atualizado',
            'completion_requested' => 'Enviada para aprovação',
            'completion_confirmed' => 'Concluída',
            'reopened' => 'Reaberta',
            'details_updated' => 'Detalhes ajustados',
            default => Str::headline(str_replace('_', ' ', $eventType)),
        };
    }

    private function syncOperationalDemandStatuses(int $municipalityId): void
    {
        Demand::query()
            ->where('municipality_id', $municipalityId)
            ->whereIn('status', ['registered', 'in_progress', 'reopened', 'pending'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->update(['status' => 'overdue']);
    }

    private function buildArchiveBoard(Collection $filteredContents, Collection $allContents): array
    {
        $visibleFiltered = $filteredContents
            ->reject(fn (GeneratedContent $content) => !empty(data_get($content->metadata, 'archive.deleted_at')))
            ->values();

        $recent = $visibleFiltered
            ->sortByDesc('created_at')
            ->take(16)
            ->map(fn (GeneratedContent $content) => $this->serializeContent($content))
            ->values()
            ->all();

        $channelOptions = $allContents
            ->map(fn (GeneratedContent $content) => (string) ($content->channel ?: 'interno'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $channel) => [
                'value' => $channel,
                'label' => $this->mapArchiveChannelLabel($channel),
            ])
            ->all();

        $toneOptions = $allContents
            ->map(fn (GeneratedContent $content) => (string) ($content->tone ?: 'neutro'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $tone) => [
                'value' => $tone,
                'label' => $this->mapArchiveToneLabel($tone),
            ])
            ->all();

        $creatorProfiles = $allContents
            ->map(function (GeneratedContent $content) {
                $role = $content->user?->role;
                $value = $role?->value ?? (string) $role;
                $label = method_exists($role, 'label') ? $role->label() : Str::headline($value);
                return $value ? ['value' => $value, 'label' => $label] : null;
            })
            ->filter()
            ->unique('value')
            ->values()
            ->all();

        $crisisMemory = $visibleFiltered
            ->filter(fn (GeneratedContent $content) => (string) $content->type === 'crise')
            ->sortByDesc('created_at')
            ->take(6)
            ->map(fn (GeneratedContent $content) => $this->serializeContent($content))
            ->values()
            ->all();

        $mediaTrainingMemory = $visibleFiltered
            ->filter(fn (GeneratedContent $content) => (string) $content->type === 'entrevista')
            ->sortByDesc('created_at')
            ->take(6)
            ->map(fn (GeneratedContent $content) => $this->serializeContent($content))
            ->values()
            ->all();

        $sessionGroups = $visibleFiltered
            ->sortByDesc('created_at')
            ->groupBy(function (GeneratedContent $content) {
                $session = $this->buildGenerationSessionSnapshot($content);

                return $session['id'] ?? ('legacy-' . $content->id);
            })
            ->take(8)
            ->map(function (Collection $group) {
                $latest = $group->sortByDesc('created_at')->first();
                $session = $latest ? $this->buildGenerationSessionSnapshot($latest) : null;

                return [
                    'id' => $session['id'] ?? null,
                    'label' => $session['label'] ?? 'Sessão editorial',
                    'item_total' => $group->count(),
                    'last_created_at_human' => $latest?->created_at?->diffForHumans(),
                    'types' => $group->map(fn (GeneratedContent $content) => $this->contentTypeLabel((string) $content->type))->unique()->values()->all(),
                    'items' => $group->take(4)->map(fn (GeneratedContent $content) => $this->serializeContent($content))->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'totals' => [
                'total' => $visibleFiltered->count(),
                'published' => $visibleFiltered->where('status', 'published')->count(),
                'approved' => $visibleFiltered->where('status', 'approved')->count(),
                'draft' => $visibleFiltered->where('status', 'draft')->count(),
                'deleted_total' => $filteredContents->count() - $visibleFiltered->count(),
                'sessions_total' => count($sessionGroups),
                'versions_total' => $visibleFiltered->sum(fn (GeneratedContent $content) => max(count($content->variations ?? []), 1)),
            ],
            'options' => [
                'channels' => $channelOptions,
                'tones' => $toneOptions,
                'creator_profiles' => $creatorProfiles,
            ],
            'recent_items' => $recent,
            'session_groups' => $sessionGroups,
            'crisis_memory' => $crisisMemory,
            'media_training_memory' => $mediaTrainingMemory,
        ];
    }

    private function mapMentionSourceLabel(string $source): string
    {
        return match ($source) {
            'google_news' => 'Google News',
            'nitter' => 'Twitter/X',
            'rss' => 'RSS',
            'manual_whatsapp' => 'WhatsApp manual',
            'manual_news' => 'Portal manual',
            'manual_social' => 'Rede social manual',
            'manual_manual' => 'Manual',
            default => Str::headline(str_replace('_', ' ', $source)),
        };
    }

    private function mapArchiveChannelLabel(string $channel): string
    {
        return match ($channel) {
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'whatsapp' => 'WhatsApp',
            'discurso' => 'Discurso',
            'interno' => 'Interno',
            default => Str::headline(str_replace('_', ' ', $channel)),
        };
    }

    private function mapArchiveToneLabel(string $tone): string
    {
        return match ($tone) {
            'celebratorio' => 'Celebratório',
            'tecnico' => 'Técnico',
            'empatico' => 'Empático',
            'informativo' => 'Informativo',
            'institucional' => 'Institucional',
            'neutro' => 'Neutro',
            default => Str::headline(str_replace('_', ' ', $tone)),
        };
    }

    private function resolveReuseTab(string $type): string
    {
        return match ($type) {
            'entrevista' => 'interview',
            'crise' => 'crisis',
            'imagem_instagram' => 'image',
            default => 'post',
        };
    }

    public function generatePost(Request $request)
    {
        $request->validate([
            'theme'   => 'required|string|max:1000',
            'channel' => 'nullable|string|in:instagram,facebook,whatsapp,discurso',
            'channels' => 'nullable|array|min:1',
            'channels.*' => 'string|in:instagram,facebook,whatsapp,discurso',
            'tones'   => 'nullable|array',
            'format' => 'nullable|string|max:40',
            'template_id' => 'nullable|integer',
            'playbook_id' => 'nullable|string|max:120',
        ]);

        try {
            $template = $this->resolveTemplate($request->integer('template_id'), 'post');
            $playbook = $this->resolveEditorialPlaybook($request->string('playbook_id')->toString(), 'post');
            $tones = $request->tones ?: ($playbook['default_tones'] ?? ($template?->default_tones ?: ['celebratorio', 'tecnico', 'empatico']));
            $templatePayload = $template ? $this->serializeTemplate($template) : [];
            $channels = collect($request->input('channels', []))
                ->push($request->input('channel'))
                ->map(fn ($channel) => trim((string) $channel))
                ->filter()
                ->unique()
                ->values();

            if ($channels->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Selecione ao menos um canal para gerar a peça.',
                ], 422);
            }

            if ($request->filled('format')) {
                $templatePayload['format'] = $request->string('format')->toString();
            }

            $batchId = (string) Str::uuid();
            $generated = $channels->map(function (string $channel, int $index) use ($request, $tones, $templatePayload, $playbook, $batchId, $channels) {
                $content = $this->service->generateSocialPost(
                    theme: $request->theme,
                    channel: $channel,
                    municipality: auth()->user()->municipality,
                    mayor: auth()->user(),
                    tones: $tones,
                    template: $templatePayload,
                    playbook: $playbook ?? [],
                );

                $metadata = is_array($content->metadata) ? $content->metadata : [];
                $metadata['generation_batch'] = [
                    'id' => $batchId,
                    'channel_index' => $index + 1,
                    'channel_total' => $channels->count(),
                    'requested_channels' => $channels->all(),
                    'generated_at' => now()->toIso8601String(),
                ];
                $metadata['generation_session'] = $this->makeGenerationSessionMeta(
                    label: 'Lote multicanal',
                    id: $batchId,
                );
                $metadata = $this->appendGenerationAuditEntry($metadata, 'generated', [
                    'provider' => data_get($metadata, 'provider'),
                    'channel' => $channel,
                    'tone_total' => count($tones),
                    'theme' => $request->theme,
                ]);

                $content->update(['metadata' => $metadata]);
                $this->notifyApproverForContent($content->fresh());

                return $this->serializeContent($content->fresh());
            })->values();

            return response()->json([
                'success'    => true,
                'content' => $generated->first(),
                'contents' => $generated->all(),
                'batch' => [
                    'id' => $batchId,
                    'channel_total' => $channels->count(),
                    'tone_total' => count($tones),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function interviewPrep(Request $request)
    {
        $request->validate([
            'context'          => 'required|string',
            'sensitive_topics' => 'nullable|string',
            'playbook_id' => 'nullable|string|max:120',
        ]);

        try {
            $playbook = $this->resolveEditorialPlaybook($request->string('playbook_id')->toString(), 'interview');
            $context = $request->context;
            if ($request->sensitive_topics) {
                $context .= "\n\nTemas sensíveis a evitar ou tratar com cuidado: " . $request->sensitive_topics;
            }

            $result = $this->service->prepareInterview(
                context: $context,
                municipality: auth()->user()->municipality,
                mayor: auth()->user(),
                playbook: $playbook ?? [],
            );

            $content = GeneratedContent::create([
                'municipality_id' => auth()->user()->municipality_id,
                'user_id'         => auth()->id(),
                'type'            => 'entrevista',
                'channel'         => 'interno',
                'title'           => 'Prep. Entrevista — ' . now()->format('d/m/Y H:i'),
                'content'         => $result,
                'variations'      => [],
                'tone'            => 'tecnico',
                'status'          => 'draft',
                'tags'            => ['entrevista', 'gerado_ia'],
                'metadata'        => [
                    'provider' => 'anthropic',
                    'playbook' => $playbook ? $this->compactPlaybookForContent($playbook) : null,
                    'generation_session' => $this->makeGenerationSessionMeta('Preparação de entrevista'),
                    'generation_log' => [[
                        'action' => 'generated',
                        'executed_at' => now()->toIso8601String(),
                        'provider' => 'anthropic',
                    ]],
                ],
            ]);

            $this->notifyApproverForContent($content);

            return response()->json([
                'success' => true,
                'content' => $this->serializeContent($content),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function crisisResponse(Request $request)
    {
        $request->validate([
            'crisis_description' => 'required|string',
            'playbook_id' => 'nullable|string|max:120',
        ]);

        try {
            $playbook = $this->resolveEditorialPlaybook($request->string('playbook_id')->toString(), 'crisis');
            $result = $this->service->crisisResponse(
                crisisDescription: $request->crisis_description,
                municipality: auth()->user()->municipality,
                mayor: auth()->user(),
                playbook: $playbook ?? [],
            );

            $content = GeneratedContent::create([
                'municipality_id' => auth()->user()->municipality_id,
                'user_id'         => auth()->id(),
                'type'            => 'crise',
                'channel'         => 'interno',
                'title'           => 'Gestão de Crise — ' . now()->format('d/m/Y H:i'),
                'content'         => $result['content'],
                'variations'      => [],
                'tone'            => 'tecnico',
                'status'          => 'draft',
                'tags'            => ['crise', 'gerado_ia'],
                'metadata'        => [
                    'provider' => $result['provider'],
                    'playbook' => $playbook ? $this->compactPlaybookForContent($playbook) : null,
                    'historical_check' => $result['historical_check'] ?? null,
                    'historical_references' => $result['historical_references'] ?? [],
                    'generation_session' => $this->makeGenerationSessionMeta('Roteiro de crise'),
                    'generation_log' => [[
                        'action' => 'generated',
                        'executed_at' => now()->toIso8601String(),
                        'provider' => $result['provider'],
                    ]],
                    'crisis' => [
                        'summary' => $result['summary'] ?? null,
                        'sections' => $result['sections'] ?? [],
                        'updated_at' => now()->toIso8601String(),
                        'updated_by' => auth()->user()->name,
                        'iterations' => [[
                            'id' => (string) Str::uuid(),
                            'type' => 'initial',
                            'summary' => $result['summary'] ?? 'Roteiro inicial de crise gerado.',
                            'affected_sections' => array_keys($result['sections'] ?? []),
                            'created_at' => now()->toIso8601String(),
                            'created_by' => auth()->user()->name,
                        ]],
                    ],
                ],
            ]);

            $this->notifyApproverForContent($content);

            return response()->json([
                'success' => true,
                'content' => $this->serializeContent($content),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function evolveCrisis(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);
        abort_if($content->type !== 'crise', 422, 'A evolução incremental só se aplica a roteiros de crise.');

        $data = $request->validate([
            'update_context' => 'required|string|max:3000',
            'affected_sections' => 'nullable|array|min:1',
            'affected_sections.*' => 'string|in:severity_analysis,positioning,timing,official_note,next_steps',
            'playbook_id' => 'nullable|string|max:120',
        ]);

        $storedPlaybook = data_get($content->metadata, 'playbook');
        $playbook = $request->filled('playbook_id')
            ? $this->resolveEditorialPlaybook($request->string('playbook_id')->toString(), 'crisis')
            : (is_array($storedPlaybook) ? $storedPlaybook : []);

        $result = $this->service->evolveCrisisResponse(
            content: $content,
            municipality: auth()->user()->municipality,
            mayor: auth()->user(),
            updateContext: trim((string) $data['update_context']),
            affectedSections: $data['affected_sections'] ?? [],
            playbook: $playbook ?? [],
        );

        $metadata = is_array($content->metadata) ? $content->metadata : [];
        $crisis = is_array(data_get($metadata, 'crisis')) ? data_get($metadata, 'crisis') : [];
        $iterations = collect($crisis['iterations'] ?? [])
            ->push([
                'id' => (string) Str::uuid(),
                'type' => 'evolution',
                'summary' => $result['summary'] ?? 'Roteiro de crise evoluído.',
                'update_context' => trim((string) $data['update_context']),
                'affected_sections' => $result['affected_sections'] ?? ($data['affected_sections'] ?? []),
                'created_at' => now()->toIso8601String(),
                'created_by' => auth()->user()->name,
            ])
            ->take(-12)
            ->values()
            ->all();

        $crisis['summary'] = $result['summary'] ?? ($crisis['summary'] ?? null);
        $crisis['sections'] = $result['sections'] ?? [];
        $crisis['updated_at'] = now()->toIso8601String();
        $crisis['updated_by'] = auth()->user()->name;
        $crisis['iterations'] = $iterations;

        $metadata['provider'] = $result['provider'] ?? data_get($metadata, 'provider');
        $metadata['historical_check'] = $result['historical_check'] ?? data_get($metadata, 'historical_check');
        $metadata['historical_references'] = $result['historical_references'] ?? data_get($metadata, 'historical_references', []);
        $metadata['crisis'] = $crisis;

        $metadata = $this->appendEditorialHistory($content, [
            'type' => 'crisis_evolve',
            'instruction' => trim((string) $data['update_context']),
            'provider' => $result['provider'] ?? null,
            'affected_sections' => $result['affected_sections'] ?? ($data['affected_sections'] ?? []),
        ]);

        $metadata['crisis'] = $crisis;
        $metadata['historical_check'] = $result['historical_check'] ?? data_get($metadata, 'historical_check');
        $metadata['historical_references'] = $result['historical_references'] ?? data_get($metadata, 'historical_references', []);
        $metadata['playbook'] = !empty($playbook) ? $this->compactPlaybookForContent($playbook) : data_get($metadata, 'playbook');
        $metadata['provider'] = $result['provider'] ?? data_get($metadata, 'provider');
        $metadata = $this->appendGenerationAuditEntry($metadata, 'crisis_evolve', [
            'provider' => $result['provider'] ?? null,
            'affected_sections' => $result['affected_sections'] ?? ($data['affected_sections'] ?? []),
        ]);

        $content->update([
            'content' => $result['content'],
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function moveOperationDemand(Request $request, Demand $demand)
    {
        $user = auth()->user();
        abort_if((int) $demand->municipality_id !== (int) $user->municipality_id, 403);

        $data = $request->validate([
            'column_key' => 'required|string|in:entry,planning,production,approval,completed',
        ]);

        $previousColumn = $this->resolveOperationDemandColumn($demand);
        $columnKey = (string) $data['column_key'];
        $now = now();

        $payload = match ($columnKey) {
            'entry' => [
                'status' => 'registered',
                'completion_requested_at' => null,
                'confirmed_at' => null,
                'resolved_at' => null,
            ],
            'planning' => [
                'status' => 'pending',
                'completion_requested_at' => null,
                'confirmed_at' => null,
                'resolved_at' => null,
            ],
            'production' => [
                'status' => 'in_progress',
                'acknowledged_at' => $demand->acknowledged_at ?? $now,
                'last_progress_at' => $now,
                'completion_requested_at' => null,
                'confirmed_at' => null,
                'resolved_at' => null,
            ],
            'approval' => [
                'status' => 'awaiting_confirmation',
                'acknowledged_at' => $demand->acknowledged_at ?? $now,
                'last_progress_at' => $demand->last_progress_at ?? $now,
                'completion_requested_at' => $demand->completion_requested_at ?? $now,
                'confirmed_at' => null,
                'resolved_at' => null,
            ],
            'completed' => [
                'status' => 'completed',
                'acknowledged_at' => $demand->acknowledged_at ?? $now,
                'last_progress_at' => $demand->last_progress_at ?? $now,
                'completion_requested_at' => $demand->completion_requested_at ?? $now,
                'confirmed_at' => $demand->confirmed_at ?? $now,
                'resolved_at' => $demand->resolved_at ?? $now,
            ],
        };

        $demand->update($payload);

        DemandEvent::create([
            'demand_id' => $demand->id,
            'user_id' => $user->id,
            'event_type' => 'communication_board_moved',
            'message' => 'Movida no Núcleo de Operação de ' . $previousColumn['label'] . ' para ' . $this->mapOperationColumnLabel($columnKey) . '.',
            'metadata' => [
                'from_column' => $previousColumn['key'],
                'to_column' => $columnKey,
                'status' => $payload['status'],
            ],
        ]);

        return response()->json([
            'success' => true,
            'item' => $this->serializeOperationDemand($demand->fresh(['contactArea:id,name,contact_name', 'registeredBy:id,name'])),
            'column_label' => $this->mapOperationColumnLabel($columnKey),
        ]);
    }

    public function show(GeneratedContent $content)
    {
        $this->authorizeContent($content);

        return response()->json($this->serializeContent($content));
    }

    public function update(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'variations' => 'nullable|array',
            'archive_reference_note' => 'nullable|string|max:2000',
            'archive_outcome_note' => 'nullable|string|max:2000',
        ]);

        $payload = collect($data)->only(['title', 'content', 'variations'])->all();

        if (array_key_exists('archive_reference_note', $data) || array_key_exists('archive_outcome_note', $data)) {
            $metadata = is_array($content->metadata) ? $content->metadata : [];
            $archive = is_array(data_get($metadata, 'archive')) ? data_get($metadata, 'archive') : [];
            $archive['reference_note'] = trim((string) ($data['archive_reference_note'] ?? '')) ?: null;
            $archive['outcome_note'] = trim((string) ($data['archive_outcome_note'] ?? '')) ?: null;
            $archive['updated_at'] = now()->toIso8601String();
            $archive['updated_by'] = auth()->user()->name;
            $metadata['archive'] = $archive;
            $payload['metadata'] = $metadata;
        }

        $content->update($payload);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function approve(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $approvalWorkflow = $this->resolveApprovalWorkflow($content);
        abort_unless($this->userMatchesApprovalWorkflow(auth()->user(), $approvalWorkflow), 403, 'Este perfil não  está configurado como aprovador desta peça.');

        $metadata = $this->appendCollaborationEntry(
            content: $content,
            user: auth()->user(),
            action: 'approved',
            note: trim((string) ($data['note'] ?? '')) ?: null,
        );

        $content->update([
            'status' => 'approved',
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function publish(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $data = $request->validate([
            'published_url' => 'nullable|url|max:2048',
        ]);

        $approvalWorkflow = $this->resolveApprovalWorkflow($content);
        abort_if($content->status !== 'approved', 422, 'A peça precisa estar aprovada antes de publicar.');
        if (!empty($approvalWorkflow['required_role'])) {
            $approvedByRequiredRole = collect($this->serializeCollaborationEntries($content))
                ->contains(fn (array $entry) => $entry['action'] === 'approved' && $entry['user_role_value'] === $approvalWorkflow['required_role']);

            abort_unless($approvedByRequiredRole, 422, 'A publicação depende da aprovação final do perfil configurado para esta peça.');
        }

        $content->update([
            'status' => 'published',
            'published_at' => now(),
            'published_url' => $data['published_url'] ?? $content->published_url,
        ]);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function schedule(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $data = $request->validate([
            'planned_at' => 'nullable|date',
            'editorial_note' => 'nullable|string|max:255',
        ]);

        $metadata = is_array($content->metadata) ? $content->metadata : [];
        $editorial = is_array(data_get($metadata, 'editorial')) ? data_get($metadata, 'editorial') : [];
        $previousPlannedAt = $this->resolvePlannedAt($content);

        $plannedAt = !empty($data['planned_at']) ? Carbon::parse($data['planned_at']) : null;

        if ($plannedAt) {
            $editorial['planned_at'] = $plannedAt->toIso8601String();
            $sameDayAsBefore = $previousPlannedAt?->isSameDay($plannedAt) ?? false;
            if (!$sameDayAsBefore || !isset($editorial['sequence'])) {
                $editorial['sequence'] = $this->nextEditorialSequence($content, $plannedAt);
            }
        } else {
            unset($editorial['planned_at']);
            unset($editorial['sequence']);
        }

        if (array_key_exists('editorial_note', $data)) {
            if (!empty($data['editorial_note'])) {
                $editorial['note'] = $data['editorial_note'];
            } else {
                unset($editorial['note']);
            }
        }

        if (empty($editorial)) {
            unset($metadata['editorial']);
        } else {
            $metadata['editorial'] = $editorial;
        }

        $content->update(['metadata' => $metadata]);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function reorderSchedule(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $data = $request->validate([
            'direction' => 'required|string|in:up,down',
        ]);

        $plannedAt = $this->resolvePlannedAt($content);
        abort_if(!$plannedAt, 422, 'A peça precisa estar agendada para ser reordenada.');

        $scheduledContents = auth()->user()->municipality
            ->generatedContents()
            ->get()
            ->filter(function (GeneratedContent $item) use ($plannedAt) {
                $itemPlannedAt = $this->resolvePlannedAt($item);

                return $itemPlannedAt && $itemPlannedAt->isSameDay($plannedAt);
            })
            ->sortBy(function (GeneratedContent $item) {
                $serialized = $this->serializeContent($item);

                return sprintf(
                    '%04d-%s-%s',
                    (int) ($serialized['editorial_sequence'] ?? 9999),
                    $serialized['planned_at'] ?? '9999-99-99T99:99:99',
                    $serialized['created_at_iso'] ?? '9999-99-99T99:99:99'
                );
            })
            ->values();

        $currentIndex = $scheduledContents->search(fn (GeneratedContent $item) => (int) $item->id === (int) $content->id);
        abort_if($currentIndex === false, 422, 'A peça agendada não  foi encontrada na fila do dia.');

        $targetIndex = $data['direction'] === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        abort_if(!isset($scheduledContents[$targetIndex]), 422, 'Nao ha outra peça para trocar de posicao nessa direcao.');

        /** @var GeneratedContent $neighbor */
        $neighbor = $scheduledContents[$targetIndex];
        $currentSequence = $this->resolveEditorialSequence($content);
        $neighborSequence = $this->resolveEditorialSequence($neighbor);

        $this->updateEditorialSequence($content, $neighborSequence);
        $this->updateEditorialSequence($neighbor, $currentSequence);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function archive(GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $content->update(['status' => 'archived']);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function removeFromArchive(GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $metadata = is_array($content->metadata) ? $content->metadata : [];
        $archive = is_array(data_get($metadata, 'archive')) ? data_get($metadata, 'archive') : [];
        $archive['deleted_at'] = now()->toIso8601String();
        $archive['deleted_by'] = auth()->user()->name;
        $metadata['archive'] = $archive;
        $metadata = $this->appendGenerationAuditEntry($metadata, 'archive_removed', [
            'deleted_by' => auth()->user()->name,
        ]);

        $content->update([
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function collaborate(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);

        $data = $request->validate([
            'action' => 'required|string|in:observation,changes_requested,approved',
            'note' => 'required|string|max:1000',
        ]);

        $approvalWorkflow = $this->resolveApprovalWorkflow($content);
        if ($data['action'] === 'approved') {
            abort_unless($this->userMatchesApprovalWorkflow(auth()->user(), $approvalWorkflow), 403, 'Este perfil não  está configurado como aprovador desta peça.');
        }

        $metadata = $this->appendCollaborationEntry(
            content: $content,
            user: auth()->user(),
            action: (string) $data['action'],
            note: trim((string) $data['note']),
        );

        $nextStatus = match ($data['action']) {
            'approved' => 'approved',
            'changes_requested' => 'draft',
            default => $content->status,
        };

        $content->update([
            'status' => $nextStatus,
            'metadata' => $metadata,
        ]);

        if ($data['action'] === 'changes_requested') {
            $this->notifyApproverForContent($content->fresh());
        }

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'kind' => 'required|string|in:post,image,interview,crisis',
            'channel' => 'nullable|string|max:40',
            'format' => 'nullable|string|max:40',
            'tone' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:400',
            'instruction' => 'nullable|string|max:2000',
            'default_tones' => 'nullable|array',
            'default_tones.*' => 'string|max:40',
            'default_payload' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template = ContentTemplate::create([
            'municipality_id' => auth()->user()->municipality_id,
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'kind' => $data['kind'],
            'channel' => $data['channel'] ?? null,
            'format' => $data['format'] ?? null,
            'tone' => $data['tone'] ?? null,
            'description' => $data['description'] ?? null,
            'instruction' => $data['instruction'] ?? null,
            'default_tones' => $data['default_tones'] ?? [],
            'default_payload' => $data['default_payload'] ?? [],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'template' => $this->serializeTemplate($template),
        ]);
    }

    public function updateTemplate(Request $request, ContentTemplate $template)
    {
        $this->authorizeTemplate($template);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'kind' => 'required|string|in:post,image,interview,crisis',
            'channel' => 'nullable|string|max:40',
            'format' => 'nullable|string|max:40',
            'tone' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:400',
            'instruction' => 'nullable|string|max:2000',
            'default_tones' => 'nullable|array',
            'default_tones.*' => 'string|max:40',
            'default_payload' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update([
            'name' => $data['name'],
            'kind' => $data['kind'],
            'channel' => $data['channel'] ?? null,
            'format' => $data['format'] ?? null,
            'tone' => $data['tone'] ?? null,
            'description' => $data['description'] ?? null,
            'instruction' => $data['instruction'] ?? null,
            'default_tones' => $data['default_tones'] ?? [],
            'default_payload' => $data['default_payload'] ?? [],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'template' => $this->serializeTemplate($template->fresh()),
        ]);
    }

    public function destroyTemplate(ContentTemplate $template)
    {
        $this->authorizeTemplate($template);
        $template->delete();

        return response()->json([
            'success' => true,
            'template_id' => $template->id,
        ]);
    }

    public function refine(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);
        abort_if($content->type === 'imagem_instagram', 422, 'Refino de texto não se aplica a imagens.');

        $data = $request->validate([
            'instruction' => 'required|string|max:1000',
            'selected_text' => 'nullable|string',
            'target_tone' => 'nullable|string|max:50',
            'target_channel' => 'nullable|string|max:50',
            'variation_index' => 'nullable|integer|min:0|max:20',
        ]);

        $result = $this->service->refineContent(
            content: $content,
            municipality: auth()->user()->municipality,
            mayor: auth()->user(),
            instruction: $data['instruction'],
            selectedText: $data['selected_text'] ?? null,
            targetTone: $data['target_tone'] ?? null,
            targetChannel: $data['target_channel'] ?? null,
        );

        $variations = $content->variations ?? [];
        if (is_array($variations) && array_key_exists('variation_index', $data) && isset($variations[$data['variation_index']])) {
            $variations[$data['variation_index']]['content'] = $result['content'];
            $variations[$data['variation_index']]['tone'] = $result['tone'];
        }

        $metadata = $this->appendEditorialHistory($content, [
            'type' => 'refine',
            'instruction' => $data['instruction'],
            'provider' => $result['provider'],
            'notes' => $result['notes'] ?? [],
        ]);
        $metadata = $this->appendGenerationAuditEntry($metadata, 'refine', [
            'provider' => $result['provider'],
            'instruction' => $data['instruction'],
        ]);

        $content->update([
            'title' => $result['title'],
            'content' => $result['content'],
            'tone' => $result['tone'],
            'variations' => $variations,
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function generateVariations(Request $request, GeneratedContent $content)
    {
        $this->authorizeContent($content);
        abort_if($content->type === 'imagem_instagram', 422, 'Variacoes assistidas não se aplicam a imagens.');

        $data = $request->validate([
            'instruction' => 'nullable|string|max:1000',
            'base_text' => 'nullable|string',
            'target_channel' => 'nullable|string|max:50',
            'tones' => 'nullable|array',
            'tones.*' => 'string|max:50',
        ]);

        $result = $this->service->generateAssistedVariations(
            content: $content,
            municipality: auth()->user()->municipality,
            mayor: auth()->user(),
            instruction: trim((string) ($data['instruction'] ?? 'Crie novas versoes publicaveis para ampliar as opcoes editoriais.')),
            tones: $data['tones'] ?? ['celebratorio', 'tecnico', 'empatico'],
            baseText: $data['base_text'] ?? null,
            targetChannel: $data['target_channel'] ?? null,
        );

        $mergedVariations = collect($content->variations ?? [])
            ->merge($result['variations'])
            ->filter(fn ($variation) => !empty($variation['content']))
            ->unique(fn ($variation) => mb_strtolower(trim((string) ($variation['content'] ?? ''))))
            ->values()
            ->take(8)
            ->all();

        $metadata = $this->appendEditorialHistory($content, [
            'type' => 'variations',
            'instruction' => $data['instruction'] ?? null,
            'provider' => $result['provider'],
            'generated_total' => count($result['variations']),
        ]);
        $metadata = $this->appendGenerationAuditEntry($metadata, 'variations', [
            'provider' => $result['provider'],
            'generated_total' => count($result['variations']),
        ]);

        $content->update([
            'title' => $result['title'],
            'variations' => $mergedVariations,
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'content' => $this->serializeContent($content->fresh()),
        ]);
    }

    public function generateImage(Request $request)
    {
        $request->validate([
            'theme'       => 'required|string|max:1000',
            'image_style' => 'required|string',
            'format'      => 'required|string',
            'color_tone'  => 'nullable|string',
            'template_id' => 'nullable|integer',
        ]);

        try {
            $municipality = auth()->user()->municipality;
            $template = $this->resolveTemplate($request->integer('template_id'), 'image');
            $templatePayload = $template?->default_payload ?? [];

            $imageStyle = (string) ($templatePayload['image_style'] ?? $request->image_style);
            $imageFormat = (string) ($templatePayload['format'] ?? $request->format);
            $colorTone = (string) ($templatePayload['color_tone'] ?? ($request->color_tone ?: 'governo'));

            $styleMap = [
                'moderno'      => 'clean modern government design, professional photography style, bright and optimistic',
                'tradicional'  => 'traditional Brazilian municipal government style, formal, trustworthy',
                'vibrante'     => 'vibrant colorful illustration style, energetic, community-focused',
                'minimalista'  => 'minimalist flat design, simple shapes, clean typography space',
                'fotografico'  => 'realistic photographic style, candid government action shot',
                'aquarela'     => 'watercolor illustration style, warm and approachable, Brazilian cultural elements',
            ];

            $formatMap = [
                'feed'      => 'square 1:1 format, Instagram feed post, with space for text overlay at bottom',
                'stories'   => 'vertical 9:16 format, Instagram Stories, full-bleed background, central composition',
                'carrossel' => 'square 1:1 format, first slide of carousel, clear visual hierarchy',
            ];

            $colorMap = [
                'governo'   => 'Brazilian government colors: green and yellow and blue',
                'neutro'    => 'neutral palette: white, light gray, navy blue, professional tones',
                'terra'     => 'warm earth tones: terracotta, ochre, warm beige, Brazilian landscape',
                'vibrante'  => 'vibrant saturated colors: coral, teal, golden yellow, energetic palette',
            ];

            $styleDesc  = $styleMap[$imageStyle] ?? $styleMap['moderno'];
            $formatDesc = $formatMap[$imageFormat] ?? $formatMap['feed'];
            $colorDesc  = $colorMap[$colorTone] ?? $colorMap['neutro'];
            $templateInstruction = trim((string) ($template?->instruction ?? ''));
            $templateDescription = trim((string) ($template?->description ?? ''));

            $anthropicKey = env('ANTHROPIC_API_KEY');

            $systemPrompt = "Voce e um especialista em design grafico para comunicação política municipal brasileira e expert em criar prompts para geradores de imagem com IA (DALL-E 3, Midjourney). Crie prompts de imagem detalhados em INGLES, apropriados para publicacoes oficiais de prefeituras brasileiras. NUNCA inclua texto, palavras ou letras dentro das imagens. Retorne APENAS JSON valido, sem markdown.";

            $pop = number_format($municipality->population ?? 0, 0, ',', '.');
            $jsonExample = '{"prompts":[{"label":"Opcao 1 - nome criativo","prompt":"prompt detalhado em ingles","negative_prompt":"text, words, letters, numbers, watermark, blurry, low quality","description":"descrição curta em portugues","caption_suggestion":"legenda com emojis para Instagram","hashtags":"hashtags relevantes"}],"design_tips":["dica 1","dica 2","dica 3"]}';

            $userPrompt = "Crie prompts de imagem para Instagram de uma prefeitura brasileira.\n\n"
                . "MUNICIPIO: {$municipality->name} / {$municipality->state}\n"
                . "POPULACAO: {$pop} habitantes\n"
                . "REGIAO: {$municipality->region}\n\n"
                . "TEMA: {$request->theme}\n"
                . "ESTILO: {$styleDesc}\n"
                . "FORMATO: {$formatDesc}\n"
                . "CORES: {$colorDesc}\n";

            if ($template) {
                $userPrompt .= "TEMPLATE VISUAL: {$template->name}\n";
                if ($templateDescription !== '') {
                    $userPrompt .= "DESCRICAO DO TEMPLATE: {$templateDescription}\n";
                }
                if ($templateInstruction !== '') {
                    $userPrompt .= "INSTRUCOES EDITORIAIS DO TEMPLATE: {$templateInstruction}\n";
                }
            }

            $userPrompt .= "\n"
                . "Gere 3 opcoes de prompts diferentes para o mesmo tema.\n\n"
                . "Retorne SOMENTE este JSON:\n"
                . $jsonExample;

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key'         => $anthropicKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 2000,
                'messages'   => [['role' => 'user', 'content' => $userPrompt]],
                'system'     => $systemPrompt,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Erro na API: ' . $response->status());
            }

            $raw    = $response->json()['content'][0]['text'] ?? '';
            $clean  = trim(preg_replace(['/^```json\s*/m', '/```\s*$/m'], '', $raw));
            $parsed = json_decode($clean, true);

            if (!$parsed || !isset($parsed['prompts'])) {
                throw new \Exception('Resposta invalida da IA.');
            }

            $content = \App\Models\GeneratedContent::create([
                'municipality_id' => auth()->user()->municipality_id,
                'user_id'         => auth()->id(),
                'type'            => 'imagem_instagram',
                'channel'         => 'instagram',
                'title'           => 'Imagem Instagram - ' . \Illuminate\Support\Str::limit($request->theme, 50),
                'content'         => json_encode($parsed),
                'variations'      => [],
                'tone'            => $request->image_style,
                'status'          => 'draft',
                'tags'            => ['imagem', 'instagram', 'gerado_ia'],
                'metadata'        => [
                    'theme'       => $request->theme,
                    'image_style' => $imageStyle,
                    'format'      => $imageFormat,
                    'color_tone'  => $colorTone,
                    'generation_session' => $this->makeGenerationSessionMeta('Geração de imagem'),
                    'generation_log' => [[
                        'action' => 'generated',
                        'executed_at' => now()->toIso8601String(),
                        'provider' => 'anthropic',
                    ]],
                    'template'    => $template ? [
                        'id' => $template->id,
                        'name' => $template->name,
                        'kind' => $template->kind,
                    ] : null,
                ],
            ]);

            $this->notifyApproverForContent($content);

            return response()->json([
                'success' => true,
                'content' => $this->serializeContent($content),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('generateImage erro: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function authorizeContent(GeneratedContent $content): void
    {
        abort_unless((int) $content->municipality_id === (int) auth()->user()->municipality_id, 403);
    }

    private function authorizeTemplate(ContentTemplate $template): void
    {
        abort_unless((int) $template->municipality_id === (int) auth()->user()->municipality_id, 403);
    }

    private function resolveTemplate(?int $templateId, string $kind): ?ContentTemplate
    {
        if (!$templateId) {
            return null;
        }

        $template = ContentTemplate::query()
            ->where('municipality_id', auth()->user()->municipality_id)
            ->where('kind', $kind)
            ->where('is_active', true)
            ->find($templateId);

        return $template instanceof ContentTemplate ? $template : null;
    }

    private function contentMatchesTypeFilter(GeneratedContent $content, string $type): bool
    {
        return match ($type) {
            'post' => str_starts_with($content->type, 'post') || in_array($content->type, ['discurso', 'comunicado'], true),
            'image' => $content->type === 'imagem_instagram',
            'interview' => $content->type === 'entrevista',
            'crisis' => $content->type === 'crise',
            default => true,
        };
    }

    private function buildEditorialBoard(Collection $contents, Collection $filteredContents, array $slaConfig): array
    {
        $now = now();
        $start = $now->copy()->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $serialized = $contents
            ->map(fn (GeneratedContent $content) => $this->serializeContent($content, $slaConfig))
            ->sortBy([
                ['planned_at', 'asc'],
                ['editorial_sequence', 'asc'],
                ['created_at_iso', 'desc'],
            ])
            ->values();

        $calendarDays = collect(range(0, 6))->map(function (int $offset) use ($serialized, $start) {
            $day = $start->copy()->addDays($offset);

            $entries = $serialized
                ->filter(fn (array $item) => !empty($item['planned_at']) && Carbon::parse($item['planned_at'])->isSameDay($day))
                ->values()
                ->all();

            return [
                'date' => $day->toDateString(),
                'label' => ucfirst($day->translatedFormat('D')),
                'display' => $day->format('d/m'),
                'entries' => $entries,
            ];
        })->all();

        $calendarMonth = $this->buildMonthlyCalendar($serialized, $now);

        $scheduledUpcoming = $serialized
            ->filter(fn (array $item) => !empty($item['planned_at']) && Carbon::parse($item['planned_at'])->betweenIncluded($start, $end))
            ->count();

        $overduePlanned = $serialized
            ->filter(fn (array $item) => !empty($item['planned_at'])
                && Carbon::parse($item['planned_at'])->lt($now)
                && !in_array($item['status'], ['published', 'archived'], true))
            ->count();

        $readyToPublish = $serialized->where('status', 'approved')->count();
        $needsReview = $serialized->where('status', 'draft')->count();
        $publishedThisWeek = $contents
            ->filter(fn (GeneratedContent $content) => $content->published_at && $content->published_at->betweenIncluded($start, $end))
            ->count();

        $channelMix = $contents
            ->groupBy(fn (GeneratedContent $content) => $content->channel ?: 'interno')
            ->map(fn (Collection $group, string $channel) => [
                'channel' => $channel,
                'label' => ucfirst($channel),
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $originMix = $contents
            ->groupBy(fn (GeneratedContent $content) => data_get($content->metadata, 'origin_module') ?: 'manual')
            ->map(fn (Collection $group, string $origin) => [
                'origin' => $origin,
                'label' => $origin === 'manual' ? 'Manual' : str_replace('_', ' ', ucfirst($origin)),
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $performance = $this->buildEditorialPerformance($contents, $now);
        $slaBoard = $this->buildEditorialSlaBoard($serialized, $contents, $slaConfig, $now);

        $focusQueue = $filteredContents
            ->map(fn (GeneratedContent $content) => $this->serializeContent($content, $slaConfig))
            ->sortBy(function (array $item) {
                return sprintf(
                    '%s-%s-%s-%s-%04d',
                    $item['sla']['sort_priority'] ?? '9',
                    $item['status'] === 'approved' ? '0' : ($item['status'] === 'draft' ? '1' : '2'),
                    $item['planned_at'] ?: '9999-99-99T99:99:99',
                    $item['sla']['due_at'] ?? '9999-99-99T99:99:99',
                    (int) ($item['editorial_sequence'] ?? 9999)
                );
            })
            ->take(12)
            ->values()
            ->all();

        return [
            'governance' => [
                'scheduled_upcoming' => $scheduledUpcoming,
                'overdue_planned' => $overduePlanned,
                'ready_to_publish' => $readyToPublish,
                'needs_review' => $needsReview,
                'published_this_week' => $publishedThisWeek,
            ],
            'calendar_days' => $calendarDays,
            'calendar_month' => $calendarMonth,
            'channel_mix' => $channelMix,
            'origin_mix' => $originMix,
            'performance' => $performance,
            'focus_queue' => $focusQueue,
            'sla' => $slaBoard,
        ];
    }

    private function serializeContent(GeneratedContent $content, ?array $slaConfig = null): array
    {
        $slaConfig ??= $this->resolveEditorialSlaConfig();
        $plannedAt = $this->resolvePlannedAt($content);
        $editorialHistory = collect(data_get($content->metadata, 'editorial.history', []))->values();
        $lastEditorialAction = $editorialHistory->last();
        $collaborationEntries = $this->serializeCollaborationEntries($content);
        $sla = $this->buildContentSlaSnapshot($content, $slaConfig, $collaborationEntries);
        $versionHistory = $this->buildArchiveVersionHistory($content);
        $archiveMemory = $this->buildArchiveMemorySnapshot($content);
        $crisisPlan = $this->buildCrisisPlanSnapshot($content);
        $generationSession = $this->buildGenerationSessionSnapshot($content);
        $generationAudit = $this->buildGenerationAuditSnapshot($content);
        $creatorRole = $content->user?->role;

        return [
            'id' => $content->id,
            'type' => (string) $content->type,
            'type_label' => $this->contentTypeLabel((string) $content->type),
            'is_post_like' => str_starts_with((string) $content->type, 'post') || in_array($content->type, ['discurso', 'comunicado'], true),
            'is_text_refinable' => $content->type !== 'imagem_instagram',
            'channel' => (string) ($content->channel ?? ''),
            'channel_label' => $this->mapArchiveChannelLabel((string) ($content->channel ?: 'interno')),
            'tone' => (string) ($content->tone ?? ''),
            'tone_label' => $this->mapArchiveToneLabel((string) ($content->tone ?: 'neutro')),
            'title' => (string) ($content->title ?? ''),
            'content' => (string) $content->content,
            'variations' => $content->variations ?? [],
            'variation_count' => count($content->variations ?? []),
            'version_history' => $versionHistory,
            'version_count' => count($versionHistory),
            'status' => (string) ($content->status ?? 'draft'),
            'status_label' => $this->contentStatusLabel((string) ($content->status ?? 'draft')),
            'published_at' => $content->published_at?->toIso8601String(),
            'published_at_human' => $content->published_at?->format('d/m/Y H:i'),
            'published_url' => $content->published_url,
            'tags' => $content->tags ?? [],
            'metadata' => $content->metadata ?? [],
            'planned_at' => $plannedAt?->toIso8601String(),
            'planned_at_human' => $plannedAt?->format('d/m/Y H:i'),
            'planned_date' => $plannedAt?->toDateString(),
            'planned_time' => $plannedAt?->format('H:i'),
            'is_schedule_overdue' => $plannedAt?->lt(now()) && !in_array($content->status, ['published', 'archived'], true),
            'editorial_sequence' => $this->resolveEditorialSequence($content),
            'editorial_note' => data_get($content->metadata, 'editorial.note'),
            'last_editorial_ai_action' => $lastEditorialAction,
            'collaboration_entries' => $collaborationEntries,
            'collaboration_summary' => [
                'total' => count($collaborationEntries),
                'approvals' => collect($collaborationEntries)->where('action', 'approved')->count(),
                'changes_requested' => collect($collaborationEntries)->where('action', 'changes_requested')->count(),
                'observations' => collect($collaborationEntries)->where('action', 'observation')->count(),
            ],
            'created_at_human' => $content->created_at?->diffForHumans(),
            'created_at_iso' => $content->created_at?->toIso8601String(),
            'creator_name' => $content->user?->name ?: 'Equipe',
            'creator_profile' => $creatorRole?->value ?? (string) $creatorRole,
            'creator_profile_label' => method_exists($creatorRole, 'label') ? $creatorRole->label() : ($creatorRole ? Str::headline((string) $creatorRole) : 'Equipe'),
            'origin_module' => data_get($content->metadata, 'origin_module'),
            'template' => data_get($content->metadata, 'template'),
            'playbook' => data_get($content->metadata, 'playbook'),
            'historical_check' => data_get($content->metadata, 'historical_check'),
            'historical_references' => data_get($content->metadata, 'historical_references', []),
            'crisis_plan' => $crisisPlan,
            'approval_workflow' => $this->resolveApprovalWorkflow($content),
            'generation_session' => $generationSession,
            'generation_audit' => $generationAudit,
            'archive_deleted' => !empty(data_get($content->metadata, 'archive.deleted_at')),
            'archive_memory' => $archiveMemory,
            'sla' => $sla,
            'prompts' => $content->type === 'imagem_instagram'
                ? (json_decode((string) $content->content, true)['prompts'] ?? [])
                : [],
            'design_tips' => $content->type === 'imagem_instagram'
                ? (json_decode((string) $content->content, true)['design_tips'] ?? [])
                : [],
        ];
    }

    private function resolveEditorialSlaConfig(): array
    {
        $settings = $this->communicationSettings->forMunicipality(auth()->user()->municipality);
        $sla = (array) ($settings['sla'] ?? []);

        return [
            'draft_review_hours' => max(1, (int) ($sla['draft_review_hours'] ?? 24)),
            'approved_publish_hours' => max(1, (int) ($sla['approved_publish_hours'] ?? 24)),
            'scheduled_lead_hours' => max(1, (int) ($sla['scheduled_lead_hours'] ?? 6)),
        ];
    }

    private function resolveApprovalWorkflow(GeneratedContent $content): array
    {
        $settings = $this->communicationSettings->forMunicipality($content->municipality);
        $approval = (array) ($settings['approval'] ?? []);
        $contentTypeKey = $this->resolveApprovalTypeKey($content);
        $requiredRole = (string) ($approval[$contentTypeKey] ?? 'mayor');

        return [
            'type_key' => $contentTypeKey,
            'type_label' => match ($contentTypeKey) {
                'image' => 'Imagem',
                'interview' => 'Entrevista',
                'crisis' => 'Crise',
                default => 'Post',
            },
            'required_role' => $requiredRole,
            'required_role_label' => $this->approvalRoleLabel($requiredRole),
            'can_current_user_approve' => $this->userMatchesApprovalWorkflow(auth()->user(), ['required_role' => $requiredRole]),
        ];
    }

    private function resolveApprovalTypeKey(GeneratedContent $content): string
    {
        return match (true) {
            $content->type === 'imagem_instagram' => 'image',
            $content->type === 'entrevista' => 'interview',
            $content->type === 'crise' => 'crisis',
            default => 'post',
        };
    }

    private function approvalRoleLabel(string $role): string
    {
        return match ($role) {
            'advisor' => 'Assessor',
            'secretary' => 'Secretário',
            default => 'Prefeito',
        };
    }

    private function userMatchesApprovalWorkflow(?User $user, array $workflow): bool
    {
        if (!$user) {
            return false;
        }

        $requiredRole = (string) ($workflow['required_role'] ?? 'mayor');
        $userRole = $user->role?->value ?? (string) $user->role;

        return $userRole === $requiredRole;
    }

    private function notifyApproverForContent(GeneratedContent $content): void
    {
        $workflow = $this->resolveApprovalWorkflow($content);
        $requiredRole = (string) ($workflow['required_role'] ?? '');

        if ($requiredRole === '') {
            return;
        }

        User::query()
            ->where('municipality_id', $content->municipality_id)
            ->where('is_active', true)
            ->where('role', $requiredRole)
            ->get()
            ->each(function (User $user) use ($content, $workflow) {
                if ((int) $user->id === (int) $content->user_id) {
                    return;
                }

                $this->webPush->sendToUser($user, [
                    'title' => 'Peça aguardando aprovação',
                    'body' => $workflow['type_label'] . ': ' . Str::limit((string) ($content->title ?: $content->content), 90),
                    'url' => route('mayor.content.index', ['area' => 'produce', 'content' => $content->id]),
                    'tag' => 'communication-approval-' . $content->id,
                ]);
            });
    }

    private function buildEditorialSlaBoard(Collection $serialized, Collection $contents, array $slaConfig, Carbon $now): array
    {
        $activeItems = $serialized
            ->filter(fn (array $item) => !in_array($item['status'], ['published', 'archived'], true))
            ->values();

        $stageDefinitions = [
            'draft_review' => 'Revisão inicial',
            'approved_release' => 'Aprovacao pronta',
            'scheduled_execution' => 'Execução agendada',
        ];

        $stages = collect($stageDefinitions)->map(function (string $label, string $stageKey) use ($activeItems) {
            $items = $activeItems->filter(fn (array $item) => ($item['sla']['stage_key'] ?? null) === $stageKey)->values();
            $total = $items->count();
            $overdueTotal = $items->where('sla.status_key', 'overdue')->count();
            $atRiskTotal = $items->where('sla.status_key', 'at_risk')->count();
            $withinSlaTotal = max($total - $overdueTotal, 0);
            $withinSlaRate = $total > 0 ? round(($withinSlaTotal / $total) * 100, 1) : 100.0;
            $avgElapsedHours = $total > 0 ? round($items->avg(fn (array $item) => (float) ($item['sla']['hours_elapsed'] ?? 0)), 1) : 0.0;

            return [
                'stage_key' => $stageKey,
                'label' => $label,
                'total' => $total,
                'overdue_total' => $overdueTotal,
                'at_risk_total' => $atRiskTotal,
                'within_sla_total' => $withinSlaTotal,
                'within_sla_rate' => $withinSlaRate,
                'avg_elapsed_hours' => $avgElapsedHours,
                'status_key' => $overdueTotal > 0 ? 'overdue' : ($atRiskTotal > 0 ? 'at_risk' : 'on_track'),
                'top_items' => $items
                    ->sortBy([
                        [fn (array $item) => $item['sla']['sort_priority'] ?? '9', 'asc'],
                        [fn (array $item) => $item['sla']['hours_overdue'] ?? 0, 'desc'],
                        [fn (array $item) => $item['sla']['hours_remaining'] ?? 9999, 'asc'],
                    ])
                    ->take(3)
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        $criticalItems = $activeItems
            ->filter(fn (array $item) => in_array($item['sla']['status_key'] ?? '', ['overdue', 'at_risk'], true))
            ->sortBy([
                [fn (array $item) => $item['sla']['sort_priority'] ?? '9', 'asc'],
                [fn (array $item) => $item['sla']['hours_overdue'] ?? 0, 'desc'],
                [fn (array $item) => $item['sla']['hours_remaining'] ?? 9999, 'asc'],
            ])
            ->take(8)
            ->values()
            ->all();

        $recentPublished = $contents->filter(fn (GeneratedContent $content) => $content->published_at && $content->published_at->gte($now->copy()->subDays(30)->startOfDay()));
        $publishedOnTimeTotal = $recentPublished->filter(function (GeneratedContent $content) use ($slaConfig) {
            $deadline = $this->resolveContentSlaDeadline($content, $slaConfig);

            return $deadline && $content->published_at && $content->published_at->lte($deadline);
        })->count();

        return [
            'config' => [
                'draft_review_hours' => $slaConfig['draft_review_hours'],
                'approved_publish_hours' => $slaConfig['approved_publish_hours'],
                'scheduled_lead_hours' => $slaConfig['scheduled_lead_hours'],
            ],
            'totals' => [
                'active_total' => $activeItems->count(),
                'overdue_total' => $activeItems->where('sla.status_key', 'overdue')->count(),
                'at_risk_total' => $activeItems->where('sla.status_key', 'at_risk')->count(),
                'on_track_total' => $activeItems->where('sla.status_key', 'on_track')->count(),
                'published_recent_total' => $recentPublished->count(),
                'published_on_time_total' => $publishedOnTimeTotal,
                'published_on_time_rate' => $recentPublished->count() > 0 ? round(($publishedOnTimeTotal / $recentPublished->count()) * 100, 1) : 100.0,
            ],
            'stages' => $stages,
            'critical_items' => $criticalItems,
            'window_label' => 'SLA dos ultimos 30 dias na publicacao e leitura atual por etapa',
        ];
    }

    private function buildContentSlaSnapshot(GeneratedContent $content, array $slaConfig, array $collaborationEntries = []): array
    {
        $now = now();
        $plannedAt = $this->resolvePlannedAt($content);
        $approvedAt = $this->resolveLatestCollaborationActionAt($collaborationEntries, 'approved');
        $changesRequestedAt = $this->resolveLatestCollaborationActionAt($collaborationEntries, 'changes_requested');
        $status = (string) ($content->status ?? 'draft');

        if (in_array($status, ['published', 'archived'], true)) {
            $deadline = $this->resolveContentSlaDeadline($content, $slaConfig, $approvedAt, $plannedAt);

            return [
                'stage_key' => 'completed',
                'stage_label' => $status === 'published' ? 'Publicado' : 'Arquivado',
                'status_key' => 'complete',
                'status_label' => $status === 'published' ? 'Concluido' : 'Encerrado',
                'status_class' => 'complete',
                'hours_elapsed' => 0,
                'hours_remaining' => null,
                'hours_overdue' => 0,
                'due_at' => $deadline?->toIso8601String(),
                'due_at_human' => $deadline?->format('d/m/Y H:i'),
                'summary' => $status === 'published'
                    ? ($deadline && $content->published_at && $content->published_at->lte($deadline) ? 'Publicado dentro do SLA' : 'Publicado fora do SLA')
                    : 'Peca arquivada sem SLA ativo',
                'sort_priority' => '9',
            ];
        }

        $stageKey = 'draft_review';
        $stageLabel = 'Revisão inicial';
        $baseAt = $changesRequestedAt ?? $content->created_at ?? $now;
        $dueAt = $baseAt->copy()->addHours($slaConfig['draft_review_hours']);
        $riskLeadHours = max((int) ceil($slaConfig['draft_review_hours'] * 0.2), 2);

        if ($plannedAt) {
            $stageKey = 'scheduled_execution';
            $stageLabel = 'Execução agendada';
            $baseAt = $approvedAt ?? $content->created_at ?? $now;
            $dueAt = $plannedAt->copy();
            $riskLeadHours = $slaConfig['scheduled_lead_hours'];
        } elseif ($status === 'approved') {
            $stageKey = 'approved_release';
            $stageLabel = 'Aprovacao pronta';
            $baseAt = $approvedAt ?? $content->updated_at ?? $content->created_at ?? $now;
            $dueAt = $baseAt->copy()->addHours($slaConfig['approved_publish_hours']);
            $riskLeadHours = max((int) ceil($slaConfig['approved_publish_hours'] * 0.25), 2);
        }

        $hoursRemaining = (int) floor(($dueAt->getTimestamp() - $now->getTimestamp()) / 3600);
        $hoursElapsed = (int) max(floor(($now->getTimestamp() - $baseAt->getTimestamp()) / 3600), 0);
        $isOverdue = $now->gt($dueAt);
        $isAtRisk = !$isOverdue && $hoursRemaining <= $riskLeadHours;
        $statusKey = $isOverdue ? 'overdue' : ($isAtRisk ? 'at_risk' : 'on_track');
        $statusClass = $statusKey;
        $statusLabel = match ($statusKey) {
            'overdue' => 'SLA vencido',
            'at_risk' => 'SLA em risco',
            default => 'Dentro do SLA',
        };

        $summary = match ($statusKey) {
            'overdue' => 'Estourado ha ' . abs($hoursRemaining) . 'h',
            'at_risk' => 'Vence em ' . max($hoursRemaining, 0) . 'h',
            default => 'Folga de ' . max($hoursRemaining, 0) . 'h',
        };

        return [
            'stage_key' => $stageKey,
            'stage_label' => $stageLabel,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'hours_elapsed' => $hoursElapsed,
            'hours_remaining' => $hoursRemaining,
            'hours_overdue' => $isOverdue ? abs($hoursRemaining) : 0,
            'due_at' => $dueAt->toIso8601String(),
            'due_at_human' => $dueAt->format('d/m/Y H:i'),
            'summary' => $summary,
            'sort_priority' => $isOverdue ? '0' : ($isAtRisk ? '1' : '2'),
        ];
    }

    private function resolveContentSlaDeadline(
        GeneratedContent $content,
        array $slaConfig,
        ?Carbon $approvedAt = null,
        ?Carbon $plannedAt = null
    ): ?Carbon {
        $plannedAt ??= $this->resolvePlannedAt($content);

        if ($plannedAt) {
            return $plannedAt->copy();
        }

        $approvedAt ??= $this->resolveLatestCollaborationActionAt($this->serializeCollaborationEntries($content), 'approved');
        if ($approvedAt) {
            return $approvedAt->copy()->addHours($slaConfig['approved_publish_hours']);
        }

        return $content->created_at?->copy()->addHours($slaConfig['draft_review_hours']);
    }

    private function resolveLatestCollaborationActionAt(array $entries, string $action): ?Carbon
    {
        $value = collect($entries)
            ->first(fn (array $entry) => ($entry['action'] ?? null) === $action);

        if (!$value || empty($value['created_at'])) {
            return null;
        }

        try {
            return Carbon::parse((string) $value['created_at']);
        } catch (\Throwable) {
            return null;
        }
    }

    private function appendEditorialHistory(GeneratedContent $content, array $payload): array
    {
        $metadata = is_array($content->metadata) ? $content->metadata : [];
        $editorial = is_array(data_get($metadata, 'editorial')) ? data_get($metadata, 'editorial') : [];
        $history = collect($editorial['history'] ?? [])
            ->push(array_filter(array_merge($payload, [
                'executed_at' => now()->toIso8601String(),
            ]), fn ($value) => $value !== null && $value !== []))
            ->take(-10)
            ->values()
            ->all();

        $editorial['history'] = $history;
        $metadata['editorial'] = $editorial;

        return $metadata;
    }

    private function makeGenerationSessionMeta(string $label, ?string $id = null): array
    {
        return [
            'id' => $id ?: (string) Str::uuid(),
            'label' => $label,
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function appendGenerationAuditEntry(array $metadata, string $action, array $payload = []): array
    {
        $log = collect($metadata['generation_log'] ?? [])
            ->push(array_filter(array_merge($payload, [
                'action' => $action,
                'executed_at' => now()->toIso8601String(),
            ]), fn ($value) => $value !== null && $value !== [] && $value !== ''))
            ->take(-15)
            ->values()
            ->all();

        $metadata['generation_log'] = $log;

        return $metadata;
    }

    private function appendCollaborationEntry(GeneratedContent $content, User $user, string $action, ?string $note): array
    {
        $metadata = is_array($content->metadata) ? $content->metadata : [];
        $editorial = is_array(data_get($metadata, 'editorial')) ? data_get($metadata, 'editorial') : [];
        $collaboration = collect($editorial['collaboration'] ?? [])
            ->push(array_filter([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => method_exists($user->role, 'label') ? $user->role?->label() : (string) ($user->role?->value ?? $user->role),
                'user_role_value' => (string) ($user->role?->value ?? $user->role),
                'action' => $action,
                'note' => $note,
                'created_at' => now()->toIso8601String(),
            ], fn ($value) => $value !== null && $value !== ''))
            ->take(-20)
            ->values()
            ->all();

        $editorial['collaboration'] = $collaboration;
        $metadata['editorial'] = $editorial;

        return $metadata;
    }

    private function serializeCollaborationEntries(GeneratedContent $content): array
    {
        return collect(data_get($content->metadata, 'editorial.collaboration', []))
            ->filter(fn ($entry) => is_array($entry))
            ->map(function (array $entry) {
                $createdAt = null;

                if (!empty($entry['created_at'])) {
                    try {
                        $createdAt = Carbon::parse((string) $entry['created_at']);
                    } catch (\Throwable) {
                        $createdAt = null;
                    }
                }

                $action = (string) ($entry['action'] ?? 'observation');

                return [
                    'user_id' => $entry['user_id'] ?? null,
                    'user_name' => (string) ($entry['user_name'] ?? 'Operador'),
                    'user_role' => (string) ($entry['user_role'] ?? ''),
                    'user_role_value' => (string) ($entry['user_role_value'] ?? Str::lower((string) ($entry['user_role'] ?? ''))),
                    'action' => $action,
                    'action_label' => match ($action) {
                        'approved' => 'Aprovou',
                        'changes_requested' => 'Pediu ajuste',
                        default => 'Observou',
                    },
                    'note' => (string) ($entry['note'] ?? ''),
                    'created_at' => $createdAt?->toIso8601String(),
                    'created_at_human' => $createdAt?->diffForHumans(),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    private function buildGenerationSessionSnapshot(GeneratedContent $content): array
    {
        $session = data_get($content->metadata, 'generation_session');
        $batchId = data_get($content->metadata, 'generation_batch.id');

        if (is_array($session) && !empty($session['id'])) {
            return [
                'id' => (string) $session['id'],
                'label' => (string) ($session['label'] ?? 'Sessão editorial'),
                'created_at' => (string) ($session['created_at'] ?? ($content->created_at?->toIso8601String() ?? '')),
            ];
        }

        if (!empty($batchId)) {
            return [
                'id' => (string) $batchId,
                'label' => 'Lote multicanal',
                'created_at' => (string) (data_get($content->metadata, 'generation_batch.generated_at') ?? ($content->created_at?->toIso8601String() ?? '')),
            ];
        }

        return [
            'id' => 'legacy-' . $content->id,
            'label' => 'Sessão editorial',
            'created_at' => $content->created_at?->toIso8601String(),
        ];
    }

    private function buildGenerationAuditSnapshot(GeneratedContent $content): array
    {
        $storedLog = collect(data_get($content->metadata, 'generation_log', []))
            ->filter(fn ($entry) => is_array($entry))
            ->map(function (array $entry) {
                $executedAt = null;
                if (!empty($entry['executed_at'])) {
                    try {
                        $executedAt = Carbon::parse((string) $entry['executed_at']);
                    } catch (\Throwable) {
                        $executedAt = null;
                    }
                }

                return [
                    'action' => (string) ($entry['action'] ?? 'generated'),
                    'label' => match ((string) ($entry['action'] ?? 'generated')) {
                        'refine' => 'Refino assistido',
                        'variations' => 'Novas variações',
                        'crisis_evolve' => 'Evolução de crise',
                        'archive_removed' => 'Removido do arquivo',
                        default => 'Geração inicial',
                    },
                    'provider' => (string) ($entry['provider'] ?? data_get($content->metadata, 'provider', '')),
                    'executed_at' => $executedAt?->toIso8601String(),
                    'executed_at_human' => $executedAt?->diffForHumans(),
                ];
            })
            ->sortByDesc('executed_at')
            ->values()
            ->all();

        if (!empty($storedLog)) {
            return $storedLog;
        }

        return [[
            'action' => 'generated',
            'label' => 'Geração inicial',
            'provider' => (string) data_get($content->metadata, 'provider', ''),
            'executed_at' => $content->created_at?->toIso8601String(),
            'executed_at_human' => $content->created_at?->diffForHumans(),
        ]];
    }

    private function buildCrisisPlanSnapshot(GeneratedContent $content): ?array
    {
        if ($content->type !== 'crise') {
            return null;
        }

        $crisis = is_array(data_get($content->metadata, 'crisis')) ? data_get($content->metadata, 'crisis') : [];
        $sections = collect(data_get($crisis, 'sections', []))
            ->filter(fn ($value, $key) => in_array($key, ['severity_analysis', 'positioning', 'timing', 'official_note', 'next_steps'], true))
            ->map(function ($value, $key) {
                return [
                    'key' => $key,
                    'label' => match ($key) {
                        'severity_analysis' => 'Gravidade e diagnóstico',
                        'positioning' => 'Posicionamento recomendado',
                        'timing' => 'Timing e canais',
                        'official_note' => 'Minuta de nota oficial',
                        'next_steps' => 'Próximos passos em 24h',
                        default => Str::headline((string) $key),
                    },
                    'content' => trim((string) $value),
                ];
            })
            ->values()
            ->all();

        $iterations = collect(data_get($crisis, 'iterations', []))
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $createdAt = null;
                if (!empty($item['created_at'])) {
                    try {
                        $createdAt = Carbon::parse((string) $item['created_at']);
                    } catch (\Throwable) {
                        $createdAt = null;
                    }
                }

                return [
                    'id' => $item['id'] ?? null,
                    'type' => (string) ($item['type'] ?? 'evolution'),
                    'summary' => (string) ($item['summary'] ?? ''),
                    'update_context' => (string) ($item['update_context'] ?? ''),
                    'affected_sections' => array_values(array_filter(collect($item['affected_sections'] ?? [])->map(fn ($value) => (string) $value)->all())),
                    'created_by' => (string) ($item['created_by'] ?? 'Equipe'),
                    'created_at' => $createdAt?->toIso8601String(),
                    'created_at_human' => $createdAt?->diffForHumans(),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();

        return [
            'summary' => (string) ($crisis['summary'] ?? ''),
            'sections' => $sections,
            'updated_at' => data_get($crisis, 'updated_at'),
            'updated_by' => (string) data_get($crisis, 'updated_by', ''),
            'iterations' => $iterations,
            'iteration_count' => count($iterations),
        ];
    }

    private function serializeTemplate(ContentTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => (string) $template->name,
            'kind' => (string) $template->kind,
            'kind_label' => match ((string) $template->kind) {
                'image' => 'Imagem',
                'interview' => 'Entrevista',
                'crisis' => 'Crise',
                default => 'Comunicação',
            },
            'channel' => (string) ($template->channel ?? ''),
            'format' => (string) ($template->format ?? ''),
            'tone' => (string) ($template->tone ?? ''),
            'description' => (string) ($template->description ?? ''),
            'instruction' => (string) ($template->instruction ?? ''),
            'default_tones' => $template->default_tones ?? [],
            'default_payload' => $template->default_payload ?? [],
            'is_active' => (bool) $template->is_active,
            'updated_at_human' => $template->updated_at?->diffForHumans(),
        ];
    }

    private function buildEditorialPlaybooks(): array
    {
        return [
            [
                'id' => 'delivery-territory',
                'name' => 'Prestacao de contas territorial',
                'situation_label' => 'Entrega concluida no bairro ou regiao',
                'target_tab' => 'post',
                'target_tab_label' => 'Comunicação',
                'description' => 'Transforma uma entrega executada em peca publicavel com foco no beneficio concreto para a populacao.',
                'instruction' => 'Abrir com a entrega ja realizada, territorializar a mensagem, citar o impacto pratico e fechar como prestacao de contas objetiva.',
                'suggested_channel' => 'instagram',
                'suggested_format' => 'carrossel',
                'default_tones' => ['celebratorio', 'empatico', 'informativo'],
                'starter_text' => 'Ex: entrega concluida no bairro, o que mudou na pratica, quem foi beneficiado e qual a proxima frente do mandato nesse territorio.',
                'checklist' => [
                    'Abrir com a entrega concreta, não com promessa',
                    'Citar bairro, regiao ou equipamento publico',
                    'Traduzir o impacto direto para a vida do cidadao',
                    'Fechar como prestacao de contas clara',
                ],
                'workflow' => [
                    'Gerar a peca com o fato principal',
                    'Revisar se o texto evita burocrates',
                    'Passar pela aprovacao colaborativa',
                    'Agendar no melhor horario do canal',
                ],
            ],
            [
                'id' => 'service-agenda',
                'name' => 'Agenda de servico publico',
                'situation_label' => 'Acao, servico ou mutirao que vai acontecer',
                'target_tab' => 'post',
                'target_tab_label' => 'Comunicação',
                'description' => 'Organiza pecas de convocacao e utilidade publica com instrucoes claras de data, local e publico.',
                'instruction' => 'Abrir com a utilidade do servico, informar quando e onde acontece, orientar quem deve participar e finalizar com chamada objetiva.',
                'suggested_channel' => 'whatsapp',
                'suggested_format' => 'whatsapp',
                'default_tones' => ['informativo', 'empatico'],
                'starter_text' => 'Ex: mutirao, campanha, atendimento ou agenda publica com data, horario, local, publico-alvo e documento necessário.',
                'checklist' => [
                    'Explicar servico, data, horario e local',
                    'Dizer para quem a agenda e importante',
                    'Incluir orientacao pratica de comparecimento',
                    'Evitar excesso de contexto politico',
                ],
                'workflow' => [
                    'Gerar texto utilitario e direto',
                    'Validar informacoes operacionais',
                    'Agendar conforme antecedencia do SLA',
                    'Publicar no canal de maior alcance imediato',
                ],
            ],
            [
                'id' => 'interview-sensitive',
                'name' => 'Entrevista com tema sensivel',
                'situation_label' => 'Sabatina, radio ou coletiva com tema de pressao',
                'target_tab' => 'interview',
                'target_tab_label' => 'Entrevista',
                'description' => 'Prepara respostas para ambientes de maior tensao política, com foco em postura, mensagens-chave e riscos.',
                'instruction' => 'Priorizar perguntas duras, respostas curtas, postura segura, reconhecimento do problema sem assumir informacao não comprovada e ponte para entrega concreta.',
                'starter_text' => 'Ex: entrevista ao vivo sobre obra atrasada, fila da saude, oposicao pressionando ou tema com repercussao local.',
                'sensitive_topics' => 'obra atrasada, oposicao, promessa em cobranca, desgaste de imagem',
                'checklist' => [
                    'Antecipar as 5 perguntas mais duras',
                    'Ter resposta curta e publicavel para cada uma',
                    'Mapear o que não deve ser dito',
                    'Fechar com mensagem de comando e entrega',
                ],
                'workflow' => [
                    'Gerar preparacao da entrevista',
                    'Revisar riscos sensiveis com a equipe',
                    'Ajustar frases de abertura e fechamento',
                    'Registrar observacoes finais antes da agenda',
                ],
            ],
            [
                'id' => 'crisis-fast-response',
                'name' => 'Resposta rápida a critica ou crise',
                'situation_label' => 'Video viral, critica escalada ou narrativa adversa',
                'target_tab' => 'crisis',
                'target_tab_label' => 'Crise',
                'description' => 'Ativa um fluxo de resposta publica com avaliacao de gravidade, posicionamento, timing e proximos passos.',
                'instruction' => 'Priorizar risco reputacional, o que dizer, o que não dizer, nota curta, timing de resposta e plano das próximas 24 horas.',
                'starter_text' => 'Ex: video viral, obra parada, denuncia em circulacao, fala recortada ou narrativa da oposicao ganhando tracao.',
                'checklist' => [
                    'Classificar gravidade e urgência',
                    'Definir posicionamento central',
                    'Separar canais de resposta e timing',
                    'Sair com nota, acao e proximo passo concreto',
                ],
                'workflow' => [
                    'Gerar orientacao de crise',
                    'Revisar com colaboracao editorial',
                    'Aprovar a linha de resposta',
                    'Publicar e monitorar repercussao',
                ],
            ],
        ];
    }

    private function resolveEditorialPlaybook(?string $playbookId, ?string $targetTab = null): ?array
    {
        if (!$playbookId) {
            return null;
        }

        $playbook = collect($this->buildEditorialPlaybooks())
            ->first(fn (array $item) => (string) ($item['id'] ?? '') === (string) $playbookId);

        if (!$playbook) {
            return null;
        }

        if ($targetTab && ($playbook['target_tab'] ?? null) !== $targetTab) {
            return null;
        }

        return $playbook;
    }

    private function compactPlaybookForContent(array $playbook): array
    {
        return [
            'id' => $playbook['id'] ?? null,
            'name' => $playbook['name'] ?? null,
            'situation_label' => $playbook['situation_label'] ?? null,
            'target_tab' => $playbook['target_tab'] ?? null,
            'target_tab_label' => $playbook['target_tab_label'] ?? null,
            'description' => $playbook['description'] ?? null,
            'instruction' => $playbook['instruction'] ?? null,
            'suggested_channel' => $playbook['suggested_channel'] ?? null,
            'suggested_format' => $playbook['suggested_format'] ?? null,
            'checklist' => $playbook['checklist'] ?? [],
            'workflow' => $playbook['workflow'] ?? [],
        ];
    }

    private function resolvePlannedAt(GeneratedContent $content): ?Carbon
    {
        $value = data_get($content->metadata, 'editorial.planned_at');

        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveEditorialSequence(GeneratedContent $content): int
    {
        return (int) data_get($content->metadata, 'editorial.sequence', 9999);
    }

    private function nextEditorialSequence(GeneratedContent $content, Carbon $plannedAt): int
    {
        $maxSequence = auth()->user()->municipality
            ->generatedContents()
            ->get()
            ->filter(function (GeneratedContent $item) use ($plannedAt, $content) {
                if ((int) $item->id === (int) $content->id) {
                    return false;
                }

                $itemPlannedAt = $this->resolvePlannedAt($item);

                return $itemPlannedAt && $itemPlannedAt->isSameDay($plannedAt);
            })
            ->map(fn (GeneratedContent $item) => $this->resolveEditorialSequence($item))
            ->max();

        return (int) max((int) $maxSequence, 0) + 1;
    }

    private function updateEditorialSequence(GeneratedContent $content, int $sequence): void
    {
        $metadata = is_array($content->metadata) ? $content->metadata : [];
        $editorial = is_array(data_get($metadata, 'editorial')) ? data_get($metadata, 'editorial') : [];
        $editorial['sequence'] = $sequence;
        $metadata['editorial'] = $editorial;

        $content->update(['metadata' => $metadata]);
    }

    private function buildMonthlyCalendar(Collection $serialized, Carbon $reference): array
    {
        $monthStart = $reference->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $monthEnd = $reference->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $days = collect();

        for ($cursor = $monthStart->copy(); $cursor->lte($monthEnd); $cursor->addDay()) {
            $entries = $serialized
                ->filter(fn (array $item) => !empty($item['planned_at']) && Carbon::parse($item['planned_at'])->isSameDay($cursor))
                ->values()
                ->all();

            $days->push([
                'date' => $cursor->toDateString(),
                'day_number' => $cursor->day,
                'label' => ucfirst($cursor->translatedFormat('D')),
                'is_current_month' => $cursor->isSameMonth($reference),
                'is_today' => $cursor->isToday(),
                'entries' => $entries,
                'scheduled_total' => count($entries),
                'published_total' => collect($entries)->where('status', 'published')->count(),
                'approved_total' => collect($entries)->where('status', 'approved')->count(),
            ]);
        }

        return [
            'month_label' => ucfirst($reference->translatedFormat('F \d\e Y')),
            'days' => $days->all(),
        ];
    }

    private function buildEditorialPerformance(Collection $contents, Carbon $reference): array
    {
        $windowStart = $reference->copy()->subDays(30)->startOfDay();
        $recentContents = $contents->filter(fn (GeneratedContent $content) => $content->created_at && $content->created_at->gte($windowStart));

        $dimensions = [
            'channel' => fn (GeneratedContent $content) => $content->channel ?: 'interno',
            'type' => fn (GeneratedContent $content) => $this->contentTypeLabel((string) $content->type),
            'origin' => fn (GeneratedContent $content) => data_get($content->metadata, 'origin_module') ?: 'Manual',
        ];

        $cards = [];

        foreach ($dimensions as $dimension => $resolver) {
            $rows = $recentContents
                ->groupBy($resolver)
                ->map(function (Collection $group, string $label) use ($recentContents, $dimension) {
                    $publishedTotal = $group->filter(fn (GeneratedContent $content) => $content->published_at !== null)->count();
                    $approvedTotal = $group->where('status', 'approved')->count();
                    $draftTotal = $group->where('status', 'draft')->count();
                    $scheduledTotal = $group->filter(fn (GeneratedContent $content) => $this->resolvePlannedAt($content) !== null)->count();
                    $share = $recentContents->count() > 0 ? round(($group->count() / $recentContents->count()) * 100, 1) : 0.0;

                    return [
                        'label' => $dimension === 'origin' ? str_replace('_', ' ', (string) $label) : (string) $label,
                        'total' => $group->count(),
                        'published_total' => $publishedTotal,
                        'approved_total' => $approvedTotal,
                        'draft_total' => $draftTotal,
                        'scheduled_total' => $scheduledTotal,
                        'publish_rate' => $group->count() > 0 ? round(($publishedTotal / $group->count()) * 100, 1) : 0.0,
                        'share' => $share,
                    ];
                })
                ->sortByDesc('published_total')
                ->take(5)
                ->values()
                ->all();

            $cards[$dimension] = [
                'dimension' => $dimension,
                'title' => match ($dimension) {
                    'type' => 'Desempenho por tipo',
                    'origin' => 'Desempenho por origem',
                    default => 'Desempenho por canal',
                },
                'subtitle' => 'Ultimos 30 dias',
                'rows' => $rows,
            ];
        }

        return [
            'window_label' => 'Ultimos 30 dias',
            'totals' => [
                'created_total' => $recentContents->count(),
                'published_total' => $recentContents->filter(fn (GeneratedContent $content) => $content->published_at !== null)->count(),
                'scheduled_total' => $recentContents->filter(fn (GeneratedContent $content) => $this->resolvePlannedAt($content) !== null)->count(),
            ],
            'cards' => array_values($cards),
        ];
    }

    private function contentTypeLabel(string $type): string
    {
        return match ($type) {
            'imagem_instagram' => 'Imagem IA',
            'entrevista' => 'Entrevista',
            'crise' => 'Crise',
            'discurso' => 'Discurso',
            'comunicado' => 'Comunicado',
            default => str_starts_with($type, 'post') ? 'Comunicação' : ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function contentStatusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Aprovado',
            'published' => 'Publicado',
            'archived' => 'Arquivado',
            default => 'Rascunho',
        };
    }

    private function buildArchiveVersionHistory(GeneratedContent $content): array
    {
        $versions = [[
            'key' => 'current',
            'label' => 'Versão principal',
            'tone' => $this->mapArchiveToneLabel((string) ($content->tone ?: 'neutro')),
            'content' => Str::limit((string) $content->content, 220),
            'created_at_human' => $content->updated_at?->diffForHumans() ?: $content->created_at?->diffForHumans(),
        ]];

        foreach (($content->variations ?? []) as $index => $variation) {
            $versions[] = [
                'key' => 'variation-' . $index,
                'label' => 'Variação ' . ($index + 1),
                'tone' => $this->mapArchiveToneLabel((string) ($variation['tone'] ?? 'neutro')),
                'content' => Str::limit((string) ($variation['content'] ?? ''), 220),
                'created_at_human' => $content->updated_at?->diffForHumans() ?: $content->created_at?->diffForHumans(),
            ];
        }

        return $versions;
    }

    private function buildArchiveMemorySnapshot(GeneratedContent $content): array
    {
        $archive = is_array(data_get($content->metadata, 'archive')) ? data_get($content->metadata, 'archive') : [];

        return [
            'reference_note' => $archive['reference_note'] ?? null,
            'outcome_note' => $archive['outcome_note'] ?? null,
            'updated_at' => $archive['updated_at'] ?? null,
            'updated_by' => $archive['updated_by'] ?? null,
        ];
    }
}
