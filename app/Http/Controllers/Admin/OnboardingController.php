<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MandateAxis;
use App\Models\MandatePromise;
use App\Models\MentionKeyword;
use App\Models\MunicipalityDocument;
use App\Models\Municipality;
use App\Models\ProjectThesis;
use App\Models\User;
use App\Services\Communication\CommunicationSettingsService;
use App\Services\Mandato\MandateAxisCatalogService;
use App\Services\Mandato\MandatePromiseExtractionService;
use App\Services\Mandato\MandatePromiseLinkingService;
use App\Services\Projects\ProjectBankLibraryService;
use App\Services\ResolveAi\ResolveAiSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly CommunicationSettingsService $communicationSettings,
        private readonly ResolveAiSettingsService $resolveAiSettings,
        private readonly MandateAxisCatalogService $mandateAxisCatalog,
        private readonly MandatePromiseExtractionService $mandateExtraction,
        private readonly MandatePromiseLinkingService $mandateLinking,
        private readonly ProjectBankLibraryService $projectBankLibrary,
    ) {}

    public function show(Municipality $municipality)
    {
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $mayor = $municipality->mayor()->first();
        $municipalUsers = $municipality->users()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'email']);
        $communicationSettings = $this->communicationSettings->forMunicipality($municipality);
        $resolveAiSettings = $this->resolveAiSettings->forMunicipality($municipality);
        $resolveAiOperationalSummary = [
            'areas_total' => $municipality->contactAreas()->count(),
            'areas_ready' => $municipality->contactAreas()
                ->where('active', true)
                ->where(function ($query) {
                    $query->whereNotNull('notification_email')->orWhereNotNull('email');
                })
                ->count(),
            'localities_total' => $municipality->localities()->count(),
            'localities_active' => $municipality->localities()->where('active', true)->count(),
        ];
        $mandateAxes = MandateAxis::query()
            ->where('municipality_id', $municipality->id)
            ->orderBy('order')
            ->get();
        $mandatePlanDocuments = $municipality->documents()
            ->where('type', 'programa_governo')
            ->latest()
            ->get();
        $mandateExtractionPreview = data_get($municipality->settings, 'mandato.extraction_preview.items', []);
        $mandateSummary = [
            'documents_total' => $mandatePlanDocuments->count(),
            'promises_total' => MandatePromise::where('municipality_id', $municipality->id)->count(),
            'preview_total' => count($mandateExtractionPreview),
        ];
        $projectBankSettings = (array) data_get($municipality->settings, 'project_bank', []);
        $projectBankSummary = [
            'theses_total' => (int) data_get(
                $projectBankSettings,
                'library_size',
                ProjectThesis::query()->where('municipality_id', $municipality->id)->count()
            ),
            'bootstrapped_at' => data_get($projectBankSettings, 'bootstrapped_at'),
            'last_curated_at' => data_get($projectBankSettings, 'last_curated_at'),
            'needs_refresh' => (bool) data_get($projectBankSettings, 'needs_refresh', false),
            'refresh_reason' => (string) data_get($projectBankSettings, 'refresh_recommended_reason', ''),
        ];
        $municipalityProfile = [
            'biome' => (string) data_get($settings, 'municipality_profile.biome', ''),
            'mayor_full_name' => (string) data_get($settings, 'municipality_profile.mandate.mayor_full_name', $mayor?->name ?? ''),
            'mayor_preferred_name' => (string) data_get($mayor?->preferences, 'preferred_name', data_get($settings, 'municipality_profile.mandate.mayor_preferred_name', '')),
            'party' => (string) data_get($settings, 'municipality_profile.mandate.party', ''),
            'term_start_date' => (string) data_get($settings, 'municipality_profile.mandate.term_start_date', ''),
            'term_end_date' => (string) data_get($settings, 'municipality_profile.mandate.term_end_date', data_get($settings, 'mandato.term_end_date', '')),
            'government_summary' => (string) data_get($settings, 'municipality_profile.mandate.government_summary', ''),
            'priority_projects' => (string) data_get($settings, 'municipality_profile.mandate.priority_projects', ''),
            'quantitative_goals' => (string) data_get($settings, 'municipality_profile.mandate.quantitative_goals', ''),
            'economy_primary' => (string) data_get($settings, 'municipality_profile.economy_primary', data_get($settings, 'economia_principal', '')),
            'local_challenges' => (string) data_get($settings, 'municipality_profile.local_challenges', data_get($settings, 'desafios', '')),
            'local_potentials' => (string) data_get($settings, 'municipality_profile.local_potentials', data_get($settings, 'potenciais', '')),
        ];
        $monitoringTerms = (string) data_get($settings, 'communication.monitoring.terms_text', '');
        if ($monitoringTerms === '') {
            $monitoringTerms = MentionKeyword::query()
                ->where('municipality_id', $municipality->id)
                ->where('type', 'topic')
                ->where('is_active', true)
                ->orderBy('keyword')
                ->pluck('keyword')
                ->implode(', ');
        }
        $communicationContext = [
            'channels' => [
                'instagram' => [
                    'active' => (bool) data_get($settings, 'communication.channels.instagram.active', false),
                    'url' => (string) data_get($settings, 'communication.channels.instagram.url', ''),
                ],
                'facebook' => [
                    'active' => (bool) data_get($settings, 'communication.channels.facebook.active', false),
                    'url' => (string) data_get($settings, 'communication.channels.facebook.url', ''),
                ],
                'whatsapp' => [
                    'active' => (bool) data_get($settings, 'communication.channels.whatsapp.active', false),
                    'url' => (string) data_get($settings, 'communication.channels.whatsapp.url', ''),
                ],
                'youtube' => [
                    'active' => (bool) data_get($settings, 'communication.channels.youtube.active', false),
                    'url' => (string) data_get($settings, 'communication.channels.youtube.url', ''),
                ],
                'tiktok' => [
                    'active' => (bool) data_get($settings, 'communication.channels.tiktok.active', false),
                    'url' => (string) data_get($settings, 'communication.channels.tiktok.url', ''),
                ],
            ],
            'monitoring_portals' => (string) data_get($settings, 'communication.monitoring.portals', ''),
            'monitoring_terms' => $monitoringTerms,
            'responsible_user_id' => data_get($settings, 'communication.monitoring.responsible_user_id'),
            'visual_palette' => (string) data_get($settings, 'communication.visual_identity.palette', ''),
            'visual_typography' => (string) data_get($settings, 'communication.visual_identity.typography', ''),
            'visual_logo' => (string) data_get($settings, 'communication.visual_identity.logo', ''),
            'visual_style' => (string) data_get($settings, 'communication.visual_identity.style', ''),
            'visual_references' => (string) data_get($settings, 'communication.visual_identity.references', ''),
            'suppliers_notes' => (string) data_get($settings, 'communication.suppliers.notes', ''),
            'sensitivity_historical_topics' => (string) data_get($settings, 'communication.sensitivities.historical_topics', ''),
            'sensitivity_tense_groups' => (string) data_get($settings, 'communication.sensitivities.tense_groups', ''),
            'sensitivity_controversial_projects' => (string) data_get($settings, 'communication.sensitivities.controversial_projects', ''),
            'sensitivity_electoral_topics' => (string) data_get($settings, 'communication.sensitivities.electoral_topics', ''),
            'sensitivity_crisis_history' => (string) data_get($settings, 'communication.sensitivities.crisis_history', ''),
        ];
        $notificationSettings = [
            'channels' => [
                'platform' => (bool) data_get($settings, 'notifications.channels.platform', true),
                'email' => (bool) data_get($settings, 'notifications.channels.email', false),
                'whatsapp' => (bool) data_get($settings, 'notifications.channels.whatsapp', false),
            ],
            'pra_hoje' => [
                'enabled' => (bool) data_get($mayor?->preferences, 'pra_hoje.enabled', true),
                'delivery_time' => (string) data_get($mayor?->preferences, 'pra_hoje.delivery_time', '07:30'),
                'email_enabled' => (bool) data_get($mayor?->preferences, 'pra_hoje.email_enabled', false),
            ],
        ];

        return view('admin.municipalities.onboarding', compact(
            'municipality',
            'mayor',
            'municipalUsers',
            'communicationSettings',
            'communicationContext',
            'municipalityProfile',
            'notificationSettings',
            'resolveAiSettings',
            'resolveAiOperationalSummary',
            'mandateAxes',
            'mandatePlanDocuments',
            'mandateExtractionPreview',
            'mandateSummary',
            'projectBankSummary'
        ));
    }

    public function saveMunicipalityProfile(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'biome' => 'nullable|string|max:120',
            'mayor_full_name' => 'nullable|string|max:255',
            'mayor_preferred_name' => 'nullable|string|max:120',
            'party' => 'nullable|string|max:120',
            'term_start_date' => 'nullable|date',
            'term_end_date' => 'nullable|date|after_or_equal:term_start_date',
            'government_summary' => 'nullable|string',
            'priority_projects' => 'nullable|string',
            'quantitative_goals' => 'nullable|string',
            'economy_primary' => 'nullable|string|max:255',
            'local_challenges' => 'nullable|string',
            'local_potentials' => 'nullable|string',
        ]);

        $settings = is_array($municipality->settings) ? $municipality->settings : [];

        data_set($settings, 'municipality_profile.biome', $this->nullableString($data['biome'] ?? null));
        data_set($settings, 'municipality_profile.mandate.mayor_full_name', $this->nullableString($data['mayor_full_name'] ?? null));
        data_set($settings, 'municipality_profile.mandate.party', $this->nullableString($data['party'] ?? null));
        data_set($settings, 'municipality_profile.mandate.term_start_date', $this->nullableString($data['term_start_date'] ?? null));
        data_set($settings, 'municipality_profile.mandate.term_end_date', $this->nullableString($data['term_end_date'] ?? null));
        data_set($settings, 'municipality_profile.mandate.government_summary', $this->nullableString($data['government_summary'] ?? null));
        data_set($settings, 'municipality_profile.mandate.priority_projects', $this->nullableString($data['priority_projects'] ?? null));
        data_set($settings, 'municipality_profile.mandate.quantitative_goals', $this->nullableString($data['quantitative_goals'] ?? null));
        data_set($settings, 'municipality_profile.economy_primary', $this->nullableString($data['economy_primary'] ?? null));
        data_set($settings, 'municipality_profile.local_challenges', $this->nullableString($data['local_challenges'] ?? null));
        data_set($settings, 'municipality_profile.local_potentials', $this->nullableString($data['local_potentials'] ?? null));

        data_set($settings, 'partido', $this->nullableString($data['party'] ?? null));
        data_set($settings, 'mandato.term_end_date', $this->nullableString($data['term_end_date'] ?? null));
        data_set($settings, 'resumo_programa', $this->nullableString($data['government_summary'] ?? null));
        data_set($settings, 'lista_projetos', $this->nullableString($data['priority_projects'] ?? null));
        data_set($settings, 'economia_principal', $this->nullableString($data['economy_primary'] ?? null));
        data_set($settings, 'desafios', $this->nullableString($data['local_challenges'] ?? null));
        data_set($settings, 'potenciais', $this->nullableString($data['local_potentials'] ?? null));

        $municipality->update([
            'settings' => $settings,
            'onboarding_status' => $municipality->onboarding_status === 'pending' ? 'in_progress' : $municipality->onboarding_status,
        ]);

        $mayor = $municipality->mayor()->first();
        if ($mayor instanceof User) {
            $preferences = is_array($mayor->preferences) ? $mayor->preferences : [];

            if ($preferredName = $this->nullableString($data['mayor_preferred_name'] ?? null)) {
                data_set($preferences, 'preferred_name', $preferredName);
            }

            $mayor->update(array_filter([
                'name' => $this->nullableString($data['mayor_full_name'] ?? null),
                'preferences' => $preferences,
            ], fn ($value, $key) => $key === 'preferences' || $value !== null, ARRAY_FILTER_USE_BOTH));
        }

        return back()->with('success', 'Base institucional do municipio salva.');
    }

    public function saveVoiceProfile(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'tone'       => 'required|string',
            'style'      => 'required|string',
            'vocabulary' => 'required|string',
            'priority_themes' => 'nullable|string',
            'avoid'      => 'nullable|string',
            'political_positioning' => 'nullable|string',
            'key_flags' => 'nullable|string',
            'avoid_public_topics' => 'nullable|string',
            'historical_context' => 'nullable|string',
            'political_adversaries' => 'nullable|string',
            'political_allies' => 'nullable|string',
            'communication_references' => 'nullable|string',
        ]);

        $municipality->update([
            'voice_profile'     => $data,
            'onboarding_status' => 'in_progress',
        ]);

        return back()->with('success', 'Perfil de voz salvo.');
    }

    public function savePoliticalMap(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'allies'   => 'nullable|string',
            'neutral'  => 'nullable|string',
            'opposition'=> 'nullable|string',
            'notes'    => 'nullable|string',
            'state_allies' => 'nullable|string',
            'federal_allies' => 'nullable|string',
            'local_press' => 'nullable|string',
            'community_leaders' => 'nullable|string',
            'local_sensitivities' => 'nullable|string',
        ]);

        $municipality->update(['political_map' => $data]);

        return back()->with('success', 'Mapa político salvo.');
    }

    public function saveCommunicationContext(Request $request, Municipality $municipality)
    {
        $validated = $request->validate([
            'communication_channel_instagram_active' => 'nullable|boolean',
            'communication_channel_instagram_url' => 'nullable|url|max:255',
            'communication_channel_facebook_active' => 'nullable|boolean',
            'communication_channel_facebook_url' => 'nullable|url|max:255',
            'communication_channel_whatsapp_active' => 'nullable|boolean',
            'communication_channel_whatsapp_url' => 'nullable|string|max:255',
            'communication_channel_youtube_active' => 'nullable|boolean',
            'communication_channel_youtube_url' => 'nullable|url|max:255',
            'communication_channel_tiktok_active' => 'nullable|boolean',
            'communication_channel_tiktok_url' => 'nullable|url|max:255',
            'communication_monitoring_portals' => 'nullable|string',
            'communication_monitoring_terms' => 'nullable|string',
            'communication_responsible_user_id' => 'nullable|integer|exists:users,id',
            'communication_visual_palette' => 'nullable|string|max:500',
            'communication_visual_typography' => 'nullable|string|max:255',
            'communication_visual_logo' => 'nullable|string|max:255',
            'communication_visual_style' => 'nullable|string|max:255',
            'communication_visual_references' => 'nullable|string',
            'communication_suppliers_notes' => 'nullable|string',
            'communication_sensitivity_historical_topics' => 'nullable|string',
            'communication_sensitivity_tense_groups' => 'nullable|string',
            'communication_sensitivity_controversial_projects' => 'nullable|string',
            'communication_sensitivity_electoral_topics' => 'nullable|string',
            'communication_sensitivity_crisis_history' => 'nullable|string',
        ]);

        if (!empty($validated['communication_responsible_user_id'])) {
            $belongsToMunicipality = $municipality->users()
                ->where('id', (int) $validated['communication_responsible_user_id'])
                ->exists();

            if (!$belongsToMunicipality) {
                return back()->withErrors([
                    'communication_responsible_user_id' => 'O responsavel de comunicacao precisa pertencer a este municipio.',
                ]);
            }
        }

        $settings = is_array($municipality->settings) ? $municipality->settings : [];

        foreach (['instagram', 'facebook', 'whatsapp', 'youtube', 'tiktok'] as $channel) {
            data_set($settings, "communication.channels.{$channel}.active", $request->boolean("communication_channel_{$channel}_active"));
            data_set($settings, "communication.channels.{$channel}.url", $this->nullableString($validated["communication_channel_{$channel}_url"] ?? null));
        }

        data_set($settings, 'communication.monitoring.portals', $this->nullableString($validated['communication_monitoring_portals'] ?? null));
        data_set($settings, 'communication.monitoring.terms_text', $this->normalizeCommaSeparatedText($validated['communication_monitoring_terms'] ?? null));
        data_set($settings, 'communication.monitoring.responsible_user_id', !empty($validated['communication_responsible_user_id']) ? (int) $validated['communication_responsible_user_id'] : null);
        data_set($settings, 'communication.visual_identity.palette', $this->nullableString($validated['communication_visual_palette'] ?? null));
        data_set($settings, 'communication.visual_identity.typography', $this->nullableString($validated['communication_visual_typography'] ?? null));
        data_set($settings, 'communication.visual_identity.logo', $this->nullableString($validated['communication_visual_logo'] ?? null));
        data_set($settings, 'communication.visual_identity.style', $this->nullableString($validated['communication_visual_style'] ?? null));
        data_set($settings, 'communication.visual_identity.references', $this->nullableString($validated['communication_visual_references'] ?? null));
        data_set($settings, 'communication.suppliers.notes', $this->nullableString($validated['communication_suppliers_notes'] ?? null));
        data_set($settings, 'communication.sensitivities.historical_topics', $this->nullableString($validated['communication_sensitivity_historical_topics'] ?? null));
        data_set($settings, 'communication.sensitivities.tense_groups', $this->nullableString($validated['communication_sensitivity_tense_groups'] ?? null));
        data_set($settings, 'communication.sensitivities.controversial_projects', $this->nullableString($validated['communication_sensitivity_controversial_projects'] ?? null));
        data_set($settings, 'communication.sensitivities.electoral_topics', $this->nullableString($validated['communication_sensitivity_electoral_topics'] ?? null));
        data_set($settings, 'communication.sensitivities.crisis_history', $this->nullableString($validated['communication_sensitivity_crisis_history'] ?? null));
        data_set(
            $settings,
            'sensibilidades',
            $this->joinNonEmpty([
                $validated['communication_sensitivity_historical_topics'] ?? null,
                $validated['communication_sensitivity_tense_groups'] ?? null,
                $validated['communication_sensitivity_controversial_projects'] ?? null,
                $validated['communication_sensitivity_electoral_topics'] ?? null,
                $validated['communication_sensitivity_crisis_history'] ?? null,
            ])
        );

        $this->syncMentionKeywords(
            $municipality,
            $settings,
            $this->normalizeList($validated['communication_monitoring_terms'] ?? null),
            $this->nullableString(data_get($settings, 'municipality_profile.mandate.mayor_full_name', $municipality->mayor?->name))
        );

        $municipality->update([
            'settings' => $settings,
            'onboarding_status' => $municipality->onboarding_status === 'pending' ? 'in_progress' : $municipality->onboarding_status,
        ]);

        return back()->with('success', 'Contexto transversal de comunicacao salvo.');
    }

    public function saveNotificationSettings(Request $request, Municipality $municipality)
    {
        $validated = $request->validate([
            'notifications_channel_platform' => 'nullable|boolean',
            'notifications_channel_email' => 'nullable|boolean',
            'notifications_channel_whatsapp' => 'nullable|boolean',
            'pra_hoje_enabled' => 'nullable|boolean',
            'pra_hoje_delivery_time' => 'required|date_format:H:i',
            'pra_hoje_email_enabled' => 'nullable|boolean',
        ]);

        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        data_set($settings, 'notifications.channels.platform', $request->boolean('notifications_channel_platform', true));
        data_set($settings, 'notifications.channels.email', $request->boolean('notifications_channel_email'));
        data_set($settings, 'notifications.channels.whatsapp', $request->boolean('notifications_channel_whatsapp'));

        $municipality->update([
            'settings' => $settings,
            'onboarding_status' => $municipality->onboarding_status === 'pending' ? 'in_progress' : $municipality->onboarding_status,
        ]);

        $mayor = $municipality->mayor()->first();
        if ($mayor instanceof User) {
            $preferences = is_array($mayor->preferences) ? $mayor->preferences : [];
            data_set($preferences, 'pra_hoje.enabled', $request->boolean('pra_hoje_enabled'));
            data_set($preferences, 'pra_hoje.delivery_time', $validated['pra_hoje_delivery_time']);
            data_set($preferences, 'pra_hoje.email_enabled', $request->boolean('pra_hoje_email_enabled'));

            $mayor->update(['preferences' => $preferences]);
        }

        return back()->with('success', 'Notificacoes e configuracao do Pra hoje salvas.');
    }

    public function complete(Request $request, Municipality $municipality)
    {
        $checklist = $this->resolveOnboardingChecklist($municipality->fresh());
        $pending = collect($checklist)
            ->filter(fn ($done) => $done !== true)
            ->keys()
            ->values()
            ->all();

        if (!empty($pending)) {
            return back()->with('error', 'Ainda faltam blocos obrigatorios do modulo Configuracoes: ' . implode(', ', $pending) . '.');
        }

        $message = 'Onboarding concluído! O prefeito já pode acessar o sistema.';

        $municipality->update([
            'onboarding_status'      => 'completed',
            'onboarding_completed_at'=> now(),
        ]);

        try {
            $this->projectBankLibrary->ensureLibraryForMunicipality(
                $municipality->refresh(),
                force: true,
                reason: 'onboarding_complete'
            );

            $message = 'Onboarding concluído e Banco de Projetos inicializado para o município.';
        } catch (\Throwable $e) {
            report($e);
            $message = 'Onboarding concluído. O Banco de Projetos ficou sinalizado para curadoria na próxima manutenção.';
            $this->projectBankLibrary->markRefreshRecommended($municipality->refresh(), 'onboarding_complete_failed');
        }

        return redirect()->route('admin.municipalities.show', $municipality)
            ->with('success', $message);
    }

    public function uploadDocuments(Request $request, Municipality $municipality)
    {
        $validated = $request->validate([
            'government_plan_file' => 'required|file|mimes:pdf,doc,docx,txt|max:30720',
        ]);

        $file = $validated['government_plan_file'];
        $path = $file->store('municipality-documents/' . $municipality->id . '/mandato', 'local');

        $document = MunicipalityDocument::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plano de governo',
            'type' => 'programa_governo',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'original_filename' => $file->getClientOriginalName(),
            'indexing_status' => 'processing',
            'uploaded_by' => auth()->id(),
        ]);

        try {
            $preview = $this->mandateExtraction->extractFromGovernmentPlan($municipality, $document);
            $settings = $municipality->settings ?? [];
            data_set($settings, 'mandato.extraction_preview', $preview);
            data_set($settings, 'mandato.plan_document_id', $document->id);
            $municipality->update(['settings' => $settings, 'onboarding_status' => 'in_progress']);
            $this->projectBankLibrary->markRefreshRecommended($municipality->refresh(), 'government_plan_uploaded');

            return back()->with('success', 'Plano de governo enviado e compromissos extraídos para revisão da equipe.');
        } catch (\Throwable $e) {
            $document->update([
                'indexing_status' => 'failed',
                'indexing_error' => $e->getMessage(),
            ]);

            return back()->with('success', 'Plano recebido, mas a extração não  conseguiu gerar uma lista revisável ainda. Verifique o arquivo e tente novamente.');
        }
    }

    public function saveMandateCommitments(Request $request, Municipality $municipality)
    {
        $validated = $request->validate([
            'commitments' => 'required|array|min:1',
            'commitments.*.enabled' => 'nullable|boolean',
            'commitments.*.text' => 'required|string',
            'commitments.*.axis_id' => 'required|exists:mandate_axes,id',
            'commitments.*.keywords' => 'nullable|string',
            'commitments.*.specificity' => 'required|in:quantitativo,qualitativo',
            'commitments.*.source_document_id' => 'nullable|integer|exists:municipality_documents,id',
        ]);

        DB::transaction(function () use ($municipality, $validated) {
            foreach ($validated['commitments'] as $item) {
                if (!(bool) ($item['enabled'] ?? false)) {
                    continue;
                }

                $text = trim((string) $item['text']);
                if ($text === '') {
                    continue;
                }

                $keywords = collect(explode(',', (string) ($item['keywords'] ?? '')))
                    ->map(fn ($keyword) => trim($keyword))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $existing = MandatePromise::query()
                    ->where('municipality_id', $municipality->id)
                    ->whereRaw('LOWER(text) = ?', [mb_strtolower($text)])
                    ->first();

                $payload = [
                    'mandate_axis_id' => (int) $item['axis_id'],
                    'text' => $text,
                    'keywords' => $keywords,
                    'specificity' => $item['specificity'],
                    'is_active' => true,
                    'source_document_id' => $item['source_document_id'] ?? null,
                ];

                if ($existing) {
                    $existing->update($payload);
                    continue;
                }

                $nextOrder = MandatePromise::where('mandate_axis_id', $item['axis_id'])->max('order') ?? 0;

                MandatePromise::create([
                    ...$payload,
                    'municipality_id' => $municipality->id,
                    'order' => $nextOrder + 1,
                ]);
            }

            $settings = $municipality->settings ?? [];
            Arr::forget($settings, 'mandato.extraction_preview');
            $municipality->update(['settings' => $settings, 'onboarding_status' => 'in_progress']);
        });

        $this->mandateLinking->ensurePromiseEmbeddings($municipality, true);
        $this->projectBankLibrary->markRefreshRecommended($municipality->refresh(), 'mandate_commitments_updated');

        return back()->with('success', 'Base inicial de compromissos do Mandato salva com sucesso.');
    }

    public function triggerDataIngestion(Request $request, Municipality $municipality)
    {
        return back()->with('success', 'Ingestão de dados iniciada.');
    }

    public function saveResolveAiSettings(Request $request, Municipality $municipality)
    {
        $validated = $request->validate([
            'resolve_ai_priority_alta_hours' => 'required|integer|min:1|max:720',
            'resolve_ai_priority_media_hours' => 'required|integer|min:1|max:1440',
            'resolve_ai_priority_baixa_hours' => 'required|integer|min:1|max:2160',
            'resolve_ai_alert_lead_hours' => 'required|integer|min:1|max:168',
            'resolve_ai_inactivity_followup_hours' => 'required|integer|min:1|max:720',
            'resolve_ai_overdue_repeat_hours' => 'required|integer|min:1|max:168',
            'resolve_ai_comparative_recent_window_days' => 'required|integer|min:7|max:365',
            'resolve_ai_comparative_previous_window_days' => 'required|integer|min:7|max:365',
            'resolve_ai_channel_internal' => 'nullable|boolean',
            'resolve_ai_channel_email' => 'nullable|boolean',
            'resolve_ai_channel_whatsapp' => 'nullable|boolean',
            'resolve_ai_attachment_required_priorities' => 'nullable|array',
            'resolve_ai_attachment_required_priorities.*' => 'in:alta,media,baixa',
        ]);

        $this->resolveAiSettings->save($municipality, [
            ...$validated,
            'resolve_ai_channel_internal' => $request->boolean('resolve_ai_channel_internal'),
            'resolve_ai_channel_email' => $request->boolean('resolve_ai_channel_email'),
            'resolve_ai_channel_whatsapp' => $request->boolean('resolve_ai_channel_whatsapp'),
            'resolve_ai_attachment_required_priorities' => $request->input('resolve_ai_attachment_required_priorities', []),
        ]);

        return back()->with('success', 'Configurações operacionais do Resolve ai salvas.');
    }

    public function saveCommunicationSettings(Request $request, Municipality $municipality)
    {
        $validated = $request->validate([
            'communication_sla_draft_review_hours' => 'required|integer|min:1|max:720',
            'communication_sla_approved_publish_hours' => 'required|integer|min:1|max:720',
            'communication_sla_scheduled_lead_hours' => 'required|integer|min:1|max:168',
            'communication_approver_post' => 'required|string|in:mayor,secretary,advisor',
            'communication_approver_image' => 'required|string|in:mayor,secretary,advisor',
            'communication_approver_interview' => 'required|string|in:mayor,secretary,advisor',
            'communication_approver_crisis' => 'required|string|in:mayor,secretary,advisor',
        ]);

        $this->communicationSettings->save($municipality, $validated);

        return back()->with('success', 'Configurações operacionais do Comunicação salvas.');
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeList(?string $value): array
    {
        return collect(preg_split('/[\n,;]+/', (string) $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique(fn ($item) => mb_strtolower($item))
            ->values()
            ->all();
    }

    private function normalizeCommaSeparatedText(?string $value): ?string
    {
        $items = $this->normalizeList($value);

        return empty($items) ? null : implode(', ', $items);
    }

    private function joinNonEmpty(array $values): ?string
    {
        $parts = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        return empty($parts) ? null : implode(' | ', $parts);
    }

    private function syncMentionKeywords(Municipality $municipality, array &$settings, array $terms, ?string $mayorName = null): void
    {
        $previousManagedTerms = collect((array) data_get($settings, 'communication.monitoring.managed_topic_terms', []))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        $currentTerms = collect($terms)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        $termsToDisable = $previousManagedTerms
            ->reject(fn ($term) => $currentTerms->contains(fn ($current) => mb_strtolower($current) === mb_strtolower($term)))
            ->values();

        if ($termsToDisable->isNotEmpty()) {
            MentionKeyword::query()
                ->where('municipality_id', $municipality->id)
                ->where('type', 'topic')
                ->whereIn('keyword', $termsToDisable->all())
                ->update(['is_active' => false]);
        }

        foreach ($currentTerms as $term) {
            MentionKeyword::query()->updateOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'keyword' => $term,
                    'type' => 'topic',
                ],
                [
                    'is_active' => true,
                    'alert_negative' => true,
                ]
            );
        }

        MentionKeyword::query()->updateOrCreate(
            [
                'municipality_id' => $municipality->id,
                'keyword' => $municipality->name,
                'type' => 'city',
            ],
            [
                'is_active' => true,
                'alert_negative' => true,
            ]
        );

        if ($mayorName) {
            MentionKeyword::query()->updateOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'keyword' => $mayorName,
                    'type' => 'mayor',
                ],
                [
                    'is_active' => true,
                    'alert_negative' => true,
                ]
            );
        }

        data_set($settings, 'communication.monitoring.managed_topic_terms', $currentTerms->all());
    }

    private function resolveOnboardingChecklist(Municipality $municipality): array
    {
        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $mayor = $municipality->mayor()->first();
        $communicationChannels = collect((array) data_get($settings, 'communication.channels', []));

        return [
            'base institucional' => !empty(data_get($settings, 'municipality_profile.mandate.mayor_full_name'))
                && !empty(data_get($settings, 'municipality_profile.mandate.party'))
                && !empty(data_get($settings, 'municipality_profile.mandate.term_start_date'))
                && !empty(data_get($settings, 'municipality_profile.mandate.term_end_date')),
            'perfil estrategico' => !empty($municipality->voice_profile),
            'mapa politico' => !empty($municipality->political_map),
            'contexto de comunicacao' => $communicationChannels->contains(fn ($channel) => !empty($channel['active']))
                && !empty(data_get($settings, 'communication.monitoring.terms_text')),
            'notificacoes' => $mayor instanceof User
                && !empty(data_get($mayor->preferences, 'pra_hoje.delivery_time')),
        ];
    }
}
