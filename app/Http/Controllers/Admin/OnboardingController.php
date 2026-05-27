<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MandateAxis;
use App\Models\MandatePromise;
use App\Models\MunicipalityDocument;
use App\Models\Municipality;
use App\Models\ProjectThesis;
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

        return view('admin.municipalities.onboarding', compact(
            'municipality',
            'communicationSettings',
            'resolveAiSettings',
            'resolveAiOperationalSummary',
            'mandateAxes',
            'mandatePlanDocuments',
            'mandateExtractionPreview',
            'mandateSummary',
            'projectBankSummary'
        ));
    }

    public function saveVoiceProfile(Request $request, Municipality $municipality)
    {
        $data = $request->validate([
            'tone'       => 'required|string',
            'style'      => 'required|string',
            'vocabulary' => 'required|string',
            'priority_themes' => 'nullable|string',
            'avoid'      => 'nullable|string',
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
        ]);

        $municipality->update(['political_map' => $data]);

        return back()->with('success', 'Mapa político salvo.');
    }

    public function complete(Request $request, Municipality $municipality)
    {
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
}
