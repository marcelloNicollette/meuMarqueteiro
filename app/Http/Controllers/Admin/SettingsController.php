<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Support\MunicipalityConfigurationStatusService;
use App\Services\Support\RadarOperationalSettingsService;
use App\Services\Support\RuntimeMailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

class SettingsController extends Controller
{
    public function __construct(
        private readonly RuntimeMailConfigService $runtimeMail,
        private readonly RadarOperationalSettingsService $radarOperationalSettings,
        private readonly MunicipalityConfigurationStatusService $configurationStatus,
    ) {}

    public function index()
    {
        $defaults = SystemSetting::defaults();

        $ai = [
            'ai_default_provider' => SystemSetting::get('ai_default_provider', $defaults['ai_default_provider']),
            'anthropic_model'     => SystemSetting::get('anthropic_model',     $defaults['anthropic_model']),
            'anthropic_api_key'   => SystemSetting::get('anthropic_api_key',   $defaults['anthropic_api_key']),
            'openai_model'        => SystemSetting::get('openai_model',        $defaults['openai_model']),
            'openai_api_key'      => SystemSetting::get('openai_api_key',      $defaults['openai_api_key']),
            'openai_audio_transcription_model' => SystemSetting::get('openai_audio_transcription_model', $defaults['openai_audio_transcription_model']),
            'openai_audio_speech_model' => SystemSetting::get('openai_audio_speech_model', $defaults['openai_audio_speech_model']),
            'openai_audio_voice'  => SystemSetting::get('openai_audio_voice',  $defaults['openai_audio_voice']),
            'openai_audio_cache_ttl_minutes' => SystemSetting::get('openai_audio_cache_ttl_minutes', $defaults['openai_audio_cache_ttl_minutes']),
            'gemini_model'        => SystemSetting::get('gemini_model',        $defaults['gemini_model']),
            'gemini_api_key'      => SystemSetting::get('gemini_api_key',      $defaults['gemini_api_key']),
            'voyage_api_key'      => SystemSetting::get('voyage_api_key',      $defaults['voyage_api_key']),
        ];

        $mail = [
            'mail_runtime_enabled' => (bool) SystemSetting::get('mail_runtime_enabled', $defaults['mail_runtime_enabled']),
            'mail_runtime_host' => SystemSetting::get('mail_runtime_host', $defaults['mail_runtime_host']),
            'mail_runtime_port' => SystemSetting::get('mail_runtime_port', $defaults['mail_runtime_port']),
            'mail_runtime_username' => SystemSetting::get('mail_runtime_username', $defaults['mail_runtime_username']),
            'mail_runtime_password' => SystemSetting::get('mail_runtime_password', $defaults['mail_runtime_password']),
            'mail_runtime_encryption' => SystemSetting::get('mail_runtime_encryption', $defaults['mail_runtime_encryption']),
            'mail_runtime_from_address' => SystemSetting::get('mail_runtime_from_address', $defaults['mail_runtime_from_address']),
            'mail_runtime_from_name' => SystemSetting::get('mail_runtime_from_name', $defaults['mail_runtime_from_name']),
            'mail_runtime_ehlo_domain' => SystemSetting::get('mail_runtime_ehlo_domain', $defaults['mail_runtime_ehlo_domain']),
            'mail_runtime_timeout' => SystemSetting::get('mail_runtime_timeout', $defaults['mail_runtime_timeout']),
            'mail_runtime_test_recipient' => SystemSetting::get('mail_runtime_test_recipient', $defaults['mail_runtime_test_recipient']),
        ];

        $coverageOps = [
            'coverage_executive_mail_enabled' => (bool) SystemSetting::get('coverage_executive_mail_enabled', $defaults['coverage_executive_mail_enabled']),
            'coverage_executive_mail_daily_enabled' => (bool) SystemSetting::get('coverage_executive_mail_daily_enabled', $defaults['coverage_executive_mail_daily_enabled']),
            'coverage_executive_mail_weekly_enabled' => (bool) SystemSetting::get('coverage_executive_mail_weekly_enabled', $defaults['coverage_executive_mail_weekly_enabled']),
            'coverage_executive_mail_recipients' => implode(', ', SystemSetting::get('coverage_executive_mail_recipients', $defaults['coverage_executive_mail_recipients'])),
            'coverage_executive_mail_daily_time' => SystemSetting::get('coverage_executive_mail_daily_time', $defaults['coverage_executive_mail_daily_time']),
            'coverage_executive_mail_weekly_day' => (int) SystemSetting::get('coverage_executive_mail_weekly_day', $defaults['coverage_executive_mail_weekly_day']),
            'coverage_executive_mail_weekly_time' => SystemSetting::get('coverage_executive_mail_weekly_time', $defaults['coverage_executive_mail_weekly_time']),
            'coverage_executive_mail_ranking_limit' => (int) SystemSetting::get('coverage_executive_mail_ranking_limit', $defaults['coverage_executive_mail_ranking_limit']),
            'coverage_executive_mail_requires_approval' => (bool) SystemSetting::get('coverage_executive_mail_requires_approval', $defaults['coverage_executive_mail_requires_approval']),
            'coverage_executive_mail_two_level_approval' => (bool) SystemSetting::get('coverage_executive_mail_two_level_approval', $defaults['coverage_executive_mail_two_level_approval']),
            'coverage_executive_mail_distinct_approvers' => (bool) SystemSetting::get('coverage_executive_mail_distinct_approvers', $defaults['coverage_executive_mail_distinct_approvers']),
            'coverage_executive_mail_level_one_label' => SystemSetting::get('coverage_executive_mail_level_one_label', $defaults['coverage_executive_mail_level_one_label']),
            'coverage_executive_mail_level_two_label' => SystemSetting::get('coverage_executive_mail_level_two_label', $defaults['coverage_executive_mail_level_two_label']),
            'coverage_executive_mail_identity_name' => SystemSetting::get('coverage_executive_mail_identity_name', $defaults['coverage_executive_mail_identity_name']),
            'coverage_executive_mail_identity_department' => SystemSetting::get('coverage_executive_mail_identity_department', $defaults['coverage_executive_mail_identity_department']),
            'coverage_executive_mail_identity_tagline' => SystemSetting::get('coverage_executive_mail_identity_tagline', $defaults['coverage_executive_mail_identity_tagline']),
            'coverage_executive_mail_identity_logo' => SystemSetting::get('coverage_executive_mail_identity_logo', $defaults['coverage_executive_mail_identity_logo']),
            'coverage_executive_mail_identity_accent_color' => SystemSetting::get('coverage_executive_mail_identity_accent_color', $defaults['coverage_executive_mail_identity_accent_color']),
            'coverage_executive_mail_identity_secondary_color' => SystemSetting::get('coverage_executive_mail_identity_secondary_color', $defaults['coverage_executive_mail_identity_secondary_color']),
            'coverage_executive_mail_signature_primary_name' => SystemSetting::get('coverage_executive_mail_signature_primary_name', $defaults['coverage_executive_mail_signature_primary_name']),
            'coverage_executive_mail_signature_primary_role' => SystemSetting::get('coverage_executive_mail_signature_primary_role', $defaults['coverage_executive_mail_signature_primary_role']),
            'coverage_executive_mail_signature_secondary_name' => SystemSetting::get('coverage_executive_mail_signature_secondary_name', $defaults['coverage_executive_mail_signature_secondary_name']),
            'coverage_executive_mail_signature_secondary_role' => SystemSetting::get('coverage_executive_mail_signature_secondary_role', $defaults['coverage_executive_mail_signature_secondary_role']),
            'coverage_alert_owner_warning_minutes' => (int) SystemSetting::get('coverage_alert_owner_warning_minutes', $defaults['coverage_alert_owner_warning_minutes']),
            'coverage_alert_owner_notifications_enabled' => (bool) SystemSetting::get('coverage_alert_owner_notifications_enabled', $defaults['coverage_alert_owner_notifications_enabled']),
            'coverage_alert_owner_sla_high_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_high_hours', $defaults['coverage_alert_owner_sla_high_hours']),
            'coverage_alert_owner_sla_medium_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_medium_hours', $defaults['coverage_alert_owner_sla_medium_hours']),
            'coverage_alert_owner_sla_default_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_default_hours', $defaults['coverage_alert_owner_sla_default_hours']),
            'coverage_alert_owner_sla_admin_high_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_admin_high_hours', $defaults['coverage_alert_owner_sla_admin_high_hours']),
            'coverage_alert_owner_sla_admin_medium_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_admin_medium_hours', $defaults['coverage_alert_owner_sla_admin_medium_hours']),
            'coverage_alert_owner_sla_admin_default_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_admin_default_hours', $defaults['coverage_alert_owner_sla_admin_default_hours']),
            'coverage_alert_owner_sla_mayor_high_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_mayor_high_hours', $defaults['coverage_alert_owner_sla_mayor_high_hours']),
            'coverage_alert_owner_sla_mayor_medium_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_mayor_medium_hours', $defaults['coverage_alert_owner_sla_mayor_medium_hours']),
            'coverage_alert_owner_sla_mayor_default_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_mayor_default_hours', $defaults['coverage_alert_owner_sla_mayor_default_hours']),
            'coverage_alert_owner_sla_secretary_high_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_secretary_high_hours', $defaults['coverage_alert_owner_sla_secretary_high_hours']),
            'coverage_alert_owner_sla_secretary_medium_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_secretary_medium_hours', $defaults['coverage_alert_owner_sla_secretary_medium_hours']),
            'coverage_alert_owner_sla_secretary_default_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_secretary_default_hours', $defaults['coverage_alert_owner_sla_secretary_default_hours']),
            'coverage_alert_owner_sla_advisor_high_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_advisor_high_hours', $defaults['coverage_alert_owner_sla_advisor_high_hours']),
            'coverage_alert_owner_sla_advisor_medium_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_advisor_medium_hours', $defaults['coverage_alert_owner_sla_advisor_medium_hours']),
            'coverage_alert_owner_sla_advisor_default_hours' => (int) SystemSetting::get('coverage_alert_owner_sla_advisor_default_hours', $defaults['coverage_alert_owner_sla_advisor_default_hours']),
        ];
        $coverageOwnerSlaUsers = User::query()
            ->admins()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'preferences'])
            ->map(function (User $user) {
                $prefs = is_array($user->preferences) ? $user->preferences : [];

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role?->label() ?? 'Admin',
                    'sla' => [
                        'high' => (int) data_get($prefs, 'coverage_alerts.owner_sla_hours.high', 0),
                        'medium' => (int) data_get($prefs, 'coverage_alerts.owner_sla_hours.medium', 0),
                        'default' => (int) data_get($prefs, 'coverage_alerts.owner_sla_hours.default', 0),
                    ],
                ];
            })
            ->values();

        $mailRuntimeStatus = [
            'active_mailer' => $this->runtimeMail->activeMailerName(),
            'runtime_enabled' => $this->runtimeMail->shouldUseRuntimeSmtp(),
        ];
        $radarOperationalHistory = $this->radarOperationalSettings->history();
        $municipalityConfigSummaries = $this->configurationStatus->summarizeCollection(
            \App\Models\Municipality::query()
                ->where('subscription_active', true)
                ->with('mayor')
                ->orderBy('name')
                ->get()
        );
        $configExecutiveSummary = $this->configurationStatus->aggregate($municipalityConfigSummaries);
        $configAttentionMunicipalities = $municipalityConfigSummaries
            ->sortBy([
                ['score', 'asc'],
                ['municipality_name', 'asc'],
            ])
            ->take(6)
            ->values();

        return view('admin.settings.index', compact(
            'ai',
            'mail',
            'coverageOps',
            'coverageOwnerSlaUsers',
            'mailRuntimeStatus',
            'radarOperationalHistory',
            'configExecutiveSummary',
            'configAttentionMunicipalities'
        ));
    }

    public function saveAI(Request $request)
    {
        $request->validate([
            'ai_default_provider' => 'required|in:anthropic,openai,gemini',
            'anthropic_model'     => 'required|string',
            'anthropic_api_key'   => 'nullable|string',
            'openai_model'        => 'required|string',
            'openai_api_key'      => 'nullable|string',
            'openai_audio_transcription_model' => 'required|string',
            'openai_audio_speech_model' => 'required|string',
            'openai_audio_voice'  => 'required|string',
            'openai_audio_cache_ttl_minutes' => 'required|integer|min:5|max:1440',
            'gemini_model'        => 'required|string',
            'gemini_api_key'      => 'nullable|string',
            'voyage_api_key'      => 'nullable|string',
        ]);

        SystemSetting::set('ai_default_provider', $request->ai_default_provider, 'string', 'ai', 'Provider padrão');
        SystemSetting::set('anthropic_model',     $request->anthropic_model,     'string', 'ai', 'Modelo Anthropic');
        SystemSetting::set('openai_model',        $request->openai_model,        'string', 'ai', 'Modelo OpenAI');
        SystemSetting::set('openai_audio_transcription_model', $request->openai_audio_transcription_model, 'string', 'ai', 'Modelo de transcricao OpenAI');
        SystemSetting::set('openai_audio_speech_model', $request->openai_audio_speech_model, 'string', 'ai', 'Modelo de fala OpenAI');
        SystemSetting::set('openai_audio_voice',  $request->openai_audio_voice,  'string', 'ai', 'Voz OpenAI do chat');
        SystemSetting::set('openai_audio_cache_ttl_minutes', (string) $request->integer('openai_audio_cache_ttl_minutes'), 'string', 'ai', 'TTL do cache de audio');
        SystemSetting::set('gemini_model',        $request->gemini_model,        'string', 'ai', 'Modelo Gemini');

        if ($request->filled('anthropic_api_key')) {
            SystemSetting::set('anthropic_api_key', $request->anthropic_api_key, 'secret', 'ai', 'Chave Anthropic');
        }
        if ($request->filled('openai_api_key')) {
            SystemSetting::set('openai_api_key', $request->openai_api_key, 'secret', 'ai', 'Chave OpenAI');
        }
        if ($request->filled('gemini_api_key')) {
            SystemSetting::set('gemini_api_key', $request->gemini_api_key, 'secret', 'ai', 'Chave Gemini');
        }
        if ($request->filled('voyage_api_key')) {
            SystemSetting::set('voyage_api_key', $request->voyage_api_key, 'secret', 'ai', 'Chave Voyage AI');
        }

        Artisan::call('config:clear');

        return back()->with('success', 'Configurações de IA salvas com sucesso.');
    }

    public function saveOperational(Request $request)
    {
        $validated = $request->validate([
            'mail_runtime_enabled' => 'nullable|boolean',
            'mail_runtime_host' => 'required|string|max:255',
            'mail_runtime_port' => 'required|integer|min:1|max:65535',
            'mail_runtime_username' => 'nullable|string|max:255',
            'mail_runtime_password' => 'nullable|string|max:255',
            'mail_runtime_encryption' => 'required|in:none,tls,ssl',
            'mail_runtime_from_address' => 'required|email',
            'mail_runtime_from_name' => 'required|string|max:255',
            'mail_runtime_ehlo_domain' => 'nullable|string|max:255',
            'mail_runtime_timeout' => 'required|integer|min:5|max:120',
            'mail_runtime_test_recipient' => 'nullable|email',
            'coverage_executive_mail_enabled' => 'nullable|boolean',
            'coverage_executive_mail_daily_enabled' => 'nullable|boolean',
            'coverage_executive_mail_weekly_enabled' => 'nullable|boolean',
            'coverage_executive_mail_recipients' => 'nullable|string',
            'coverage_executive_mail_daily_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'coverage_executive_mail_weekly_day' => 'required|integer|min:0|max:6',
            'coverage_executive_mail_weekly_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'coverage_executive_mail_ranking_limit' => 'required|integer|min:5|max:50',
            'coverage_executive_mail_requires_approval' => 'nullable|boolean',
            'coverage_executive_mail_two_level_approval' => 'nullable|boolean',
            'coverage_executive_mail_distinct_approvers' => 'nullable|boolean',
            'coverage_executive_mail_level_one_label' => 'required|string|max:80',
            'coverage_executive_mail_level_two_label' => 'required|string|max:80',
            'coverage_executive_mail_identity_name' => 'required|string|max:255',
            'coverage_executive_mail_identity_department' => 'nullable|string|max:255',
            'coverage_executive_mail_identity_tagline' => 'nullable|string|max:255',
            'coverage_executive_mail_identity_logo' => 'nullable|string|max:255',
            'coverage_executive_mail_identity_accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'coverage_executive_mail_identity_secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'coverage_executive_mail_signature_primary_name' => 'required|string|max:255',
            'coverage_executive_mail_signature_primary_role' => 'required|string|max:255',
            'coverage_executive_mail_signature_secondary_name' => 'nullable|string|max:255',
            'coverage_executive_mail_signature_secondary_role' => 'nullable|string|max:255',
            'coverage_alert_owner_warning_minutes' => 'required|integer|min:15|max:720',
            'coverage_alert_owner_notifications_enabled' => 'nullable|boolean',
            'coverage_alert_owner_sla_high_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_medium_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_default_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_admin_high_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_admin_medium_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_admin_default_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_mayor_high_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_mayor_medium_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_mayor_default_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_secretary_high_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_secretary_medium_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_secretary_default_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_advisor_high_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_advisor_medium_hours' => 'required|integer|min:1|max:240',
            'coverage_alert_owner_sla_advisor_default_hours' => 'required|integer|min:1|max:240',
            'coverage_owner_sla_overrides' => 'nullable|array',
            'coverage_owner_sla_overrides.*.high' => 'nullable|integer|min:0|max:240',
            'coverage_owner_sla_overrides.*.medium' => 'nullable|integer|min:0|max:240',
            'coverage_owner_sla_overrides.*.default' => 'nullable|integer|min:0|max:240',
        ]);

        $coverageRecipientList = collect(explode(',', (string) ($validated['coverage_executive_mail_recipients'] ?? '')))
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->values()
            ->all();

        foreach ($coverageRecipientList as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(["Destinatário inválido no mailing executivo: {$email}"])->withInput();
            }
        }

        $before = $this->radarOperationalSettings->currentSnapshot();
        $after = $this->radarOperationalSettings->normalizePayload([
            ...$validated,
            'mail_runtime_enabled' => $request->boolean('mail_runtime_enabled'),
        ], $before);

        $this->radarOperationalSettings->applySnapshot($after);
        $this->radarOperationalSettings->recordUpdate($request->user(), $before, $after);
        SystemSetting::set('coverage_executive_mail_enabled', $request->boolean('coverage_executive_mail_enabled'), 'boolean', 'coverage_operations', 'Mailing executivo ativo');
        SystemSetting::set('coverage_executive_mail_daily_enabled', $request->boolean('coverage_executive_mail_daily_enabled'), 'boolean', 'coverage_operations', 'Mailing executivo diario ativo');
        SystemSetting::set('coverage_executive_mail_weekly_enabled', $request->boolean('coverage_executive_mail_weekly_enabled'), 'boolean', 'coverage_operations', 'Mailing executivo semanal ativo');
        SystemSetting::set('coverage_executive_mail_recipients', $coverageRecipientList, 'json', 'coverage_operations', 'Destinatarios do mailing executivo');
        SystemSetting::set('coverage_executive_mail_daily_time', (string) $validated['coverage_executive_mail_daily_time'], 'string', 'coverage_operations', 'Horario diario do mailing executivo');
        SystemSetting::set('coverage_executive_mail_weekly_day', (int) $validated['coverage_executive_mail_weekly_day'], 'string', 'coverage_operations', 'Dia semanal do mailing executivo');
        SystemSetting::set('coverage_executive_mail_weekly_time', (string) $validated['coverage_executive_mail_weekly_time'], 'string', 'coverage_operations', 'Horario semanal do mailing executivo');
        SystemSetting::set('coverage_executive_mail_ranking_limit', (int) $validated['coverage_executive_mail_ranking_limit'], 'string', 'coverage_operations', 'Limite do ranking executivo no mailing');
        SystemSetting::set('coverage_executive_mail_requires_approval', $request->boolean('coverage_executive_mail_requires_approval'), 'boolean', 'coverage_operations', 'Aprovação manual do mailing executivo');
        SystemSetting::set('coverage_executive_mail_two_level_approval', $request->boolean('coverage_executive_mail_two_level_approval'), 'boolean', 'coverage_operations', 'Aprovação em dois níveis do mailing executivo');
        SystemSetting::set('coverage_executive_mail_distinct_approvers', $request->boolean('coverage_executive_mail_distinct_approvers'), 'boolean', 'coverage_operations', 'Aprovadores distintos no mailing executivo');
        SystemSetting::set('coverage_executive_mail_level_one_label', (string) $validated['coverage_executive_mail_level_one_label'], 'string', 'coverage_operations', 'Rótulo do nível 1 do mailing executivo');
        SystemSetting::set('coverage_executive_mail_level_two_label', (string) $validated['coverage_executive_mail_level_two_label'], 'string', 'coverage_operations', 'Rótulo do nível 2 do mailing executivo');
        SystemSetting::set('coverage_executive_mail_identity_name', (string) $validated['coverage_executive_mail_identity_name'], 'string', 'coverage_operations', 'Nome institucional do mailing executivo');
        SystemSetting::set('coverage_executive_mail_identity_department', (string) ($validated['coverage_executive_mail_identity_department'] ?? ''), 'string', 'coverage_operations', 'Departamento institucional do mailing executivo');
        SystemSetting::set('coverage_executive_mail_identity_tagline', (string) ($validated['coverage_executive_mail_identity_tagline'] ?? ''), 'string', 'coverage_operations', 'Tagline institucional do mailing executivo');
        SystemSetting::set('coverage_executive_mail_identity_logo', (string) ($validated['coverage_executive_mail_identity_logo'] ?? ''), 'string', 'coverage_operations', 'Logo institucional do mailing executivo');
        SystemSetting::set('coverage_executive_mail_identity_accent_color', strtoupper((string) $validated['coverage_executive_mail_identity_accent_color']), 'string', 'coverage_operations', 'Cor principal do mailing executivo');
        SystemSetting::set('coverage_executive_mail_identity_secondary_color', strtoupper((string) $validated['coverage_executive_mail_identity_secondary_color']), 'string', 'coverage_operations', 'Cor secundária do mailing executivo');
        SystemSetting::set('coverage_executive_mail_signature_primary_name', (string) $validated['coverage_executive_mail_signature_primary_name'], 'string', 'coverage_operations', 'Assinatura principal do mailing executivo');
        SystemSetting::set('coverage_executive_mail_signature_primary_role', (string) $validated['coverage_executive_mail_signature_primary_role'], 'string', 'coverage_operations', 'Cargo da assinatura principal do mailing executivo');
        SystemSetting::set('coverage_executive_mail_signature_secondary_name', (string) ($validated['coverage_executive_mail_signature_secondary_name'] ?? ''), 'string', 'coverage_operations', 'Assinatura secundária do mailing executivo');
        SystemSetting::set('coverage_executive_mail_signature_secondary_role', (string) ($validated['coverage_executive_mail_signature_secondary_role'] ?? ''), 'string', 'coverage_operations', 'Cargo da assinatura secundária do mailing executivo');
        SystemSetting::set('coverage_alert_owner_notifications_enabled', $request->boolean('coverage_alert_owner_notifications_enabled'), 'boolean', 'coverage_operations', 'Notificações de SLA do owner');
        SystemSetting::set('coverage_alert_owner_warning_minutes', (int) $validated['coverage_alert_owner_warning_minutes'], 'string', 'coverage_operations', 'Minutos de antecedência da notificação do owner');
        foreach ([
            'coverage_alert_owner_sla_high_hours',
            'coverage_alert_owner_sla_medium_hours',
            'coverage_alert_owner_sla_default_hours',
            'coverage_alert_owner_sla_admin_high_hours',
            'coverage_alert_owner_sla_admin_medium_hours',
            'coverage_alert_owner_sla_admin_default_hours',
            'coverage_alert_owner_sla_mayor_high_hours',
            'coverage_alert_owner_sla_mayor_medium_hours',
            'coverage_alert_owner_sla_mayor_default_hours',
            'coverage_alert_owner_sla_secretary_high_hours',
            'coverage_alert_owner_sla_secretary_medium_hours',
            'coverage_alert_owner_sla_secretary_default_hours',
            'coverage_alert_owner_sla_advisor_high_hours',
            'coverage_alert_owner_sla_advisor_medium_hours',
            'coverage_alert_owner_sla_advisor_default_hours',
        ] as $slaKey) {
            SystemSetting::set($slaKey, (int) $validated[$slaKey], 'string', 'coverage_operations', 'SLA do owner');
        }

        $userOverrides = (array) ($validated['coverage_owner_sla_overrides'] ?? []);
        $users = User::query()->admins()->active()->get();
        foreach ($users as $user) {
            $preferences = is_array($user->preferences) ? $user->preferences : [];
            $override = (array) ($userOverrides[$user->id] ?? []);
            $clean = [
                'high' => (int) ($override['high'] ?? 0),
                'medium' => (int) ($override['medium'] ?? 0),
                'default' => (int) ($override['default'] ?? 0),
            ];
            $nonZero = array_filter($clean, fn (int $value) => $value > 0);

            if ($nonZero === []) {
                data_forget($preferences, 'coverage_alerts.owner_sla_hours');
            } else {
                data_set($preferences, 'coverage_alerts.owner_sla_hours', $clean);
            }

            $user->update(['preferences' => $preferences]);
        }

        return back()->with('success', 'Configurações operacionais de SMTP e cobertura executiva salvas com sucesso.');
    }

    public function rollbackOperational(Activity $activity, Request $request)
    {
        if (!$this->radarOperationalSettings->isAuditActivity($activity)) {
            return response()->json([
                'ok' => false,
                'message' => 'Registro de auditoria operacional não encontrado.',
            ], 404);
        }

        $rolledBack = $this->radarOperationalSettings->rollback($activity, $request->user());

        if (!$rolledBack) {
            return response()->json([
                'ok' => false,
                'message' => 'Não foi possível restaurar este snapshot operacional.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Configuração operacional restaurada com sucesso.',
            'rollback_activity_id' => $rolledBack->id,
        ]);
    }

    public function testConnection(Request $request)
    {
        $provider = $request->provider ?? SystemSetting::get('ai_default_provider', 'anthropic');

        try {
            $service  = app(\App\Services\AI\AIProviderService::class)->withProvider($provider);
            $response = $service->chat([
                ['role' => 'user', 'content' => 'Responda apenas: ok'],
            ], ['max_tokens' => 10]);

            return response()->json([
                'success'  => true,
                'provider' => $provider,
                'model'    => $response->model,
                'response' => $response->content,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function testMailRuntime(Request $request)
    {
        $recipient = trim((string) $request->input('recipient', SystemSetting::get('mail_runtime_test_recipient', '')));

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'error' => 'Informe um destinatário de teste válido para o SMTP.',
            ], 422);
        }

        try {
            $this->runtimeMail->sendRaw(
                [$recipient],
                'Teste SMTP do Meu Assistente',
                "SMTP configurado com sucesso.\n\nEnviado em " . now()->format('d/m/Y H:i:s')
            );

            return response()->json([
                'success' => true,
                'mailer' => $this->runtimeMail->activeMailerName(),
                'recipient' => $recipient,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ─── Integrações Externas ─────────────────────────────────────────────

    private function getIntegracoes(): array
    {
        return [
            'ibge_municípios' => ['grupo' => 'socioeconomico', 'nome' => 'IBGE — Cidades e MUNIC', 'descrição' => 'População, domicílios, renda, escolaridade e estrutura de gestão municipal.', 'url' => 'https://servicodados.ibge.gov.br/api/docs', 'gratuita' => true, 'requer_chave' => false],
            'ibge_populacao'  => ['grupo' => 'socioeconomico', 'nome' => 'IBGE — Estimativas populacionais', 'descrição' => 'Atualização anual de população por município.', 'url' => 'https://servicodados.ibge.gov.br/api/docs/agregados', 'gratuita' => true, 'requer_chave' => false],
            'atlas_brasil'    => ['grupo' => 'socioeconomico', 'nome' => 'Atlas Brasil (PNUD)', 'descrição' => 'IDH municipal, vulnerabilidade social e índices de desenvolvimento por dimensão.', 'url' => 'http://www.atlasbrasil.org.br', 'gratuita' => true, 'requer_chave' => false],
            'ipea_data'       => ['grupo' => 'socioeconomico', 'nome' => 'IPEA Data', 'descrição' => 'Indicadores regionais e séries históricas socioeconômicas.', 'url' => 'http://www.ipeadata.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'siconfi'         => ['grupo' => 'fiscal', 'nome' => 'SICONFI (STN)', 'descrição' => 'Balanços, RREO, RGF, receitas e despesas por função e subfunção.', 'url' => 'https://siconfi.tesouro.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'finbra'          => ['grupo' => 'fiscal', 'nome' => 'FINBRA (STN)', 'descrição' => 'Comparativo fiscal entre municípios — benchmark orçamentário.', 'url' => 'https://www.tesourotransparente.gov.br/ckan/dataset/finbra', 'gratuita' => true, 'requer_chave' => false],
            'transparencia'   => ['grupo' => 'fiscal', 'nome' => 'Portal da Transparência Federal', 'descrição' => 'Transferências federais, convênios, emendas parlamentares e dados de execução pública.', 'url' => 'https://api.portaldatransparencia.gov.br/swagger-ui/index.html', 'gratuita' => true, 'requer_chave' => true],
            'datasus'         => ['grupo' => 'saude', 'nome' => 'DATASUS', 'descrição' => 'Mortalidade, produção ambulatorial e hospitalar, cobertura vacinal.', 'url' => 'https://datasus.saude.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'fns'             => ['grupo' => 'saude', 'nome' => 'FNS — Fundo Nacional de Saúde', 'descrição' => 'Repasses por bloco de financiamento e tetos de MAC.', 'url' => 'https://www.fns.saude.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'fnde'            => ['grupo' => 'educacao', 'nome' => 'FNDE', 'descrição' => 'Repasses de FUNDEB, PNAE, PNATE e obras do PAR.', 'url' => 'https://www.fnde.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'inep_censo'      => ['grupo' => 'educacao', 'nome' => 'INEP — Censo Escolar', 'descrição' => 'Matrículas, docentes e infraestrutura escolar por unidade.', 'url' => 'https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/microdados', 'gratuita' => true, 'requer_chave' => false],
            'inep_ideb'       => ['grupo' => 'educacao', 'nome' => 'INEP — IDEB', 'descrição' => 'Resultados de aprendizagem por escola e rede.', 'url' => 'https://www.gov.br/inep/pt-br/areas-de-atuacao/pesquisas-estatisticas-e-indicadores/ideb', 'gratuita' => true, 'requer_chave' => false],
            'snis'            => ['grupo' => 'infraestrutura', 'nome' => 'SNIS — Saneamento', 'descrição' => 'Indicadores de saneamento básico: água, esgoto e resíduos sólidos.', 'url' => 'https://www.gov.br/mdr/pt-br/assuntos/saneamento/snis', 'gratuita' => true, 'requer_chave' => false],
            'aneel'           => ['grupo' => 'infraestrutura', 'nome' => 'ANEEL / SIGEL', 'descrição' => 'Energia elétrica, concessões e iluminação pública.', 'url' => 'https://dadosabertos.aneel.gov.br', 'gratuita' => true, 'requer_chave' => false],
            'transferegov'    => ['grupo' => 'captação', 'nome' => 'Portal da Transparência para Captação', 'descrição' => 'Compatibilidade da captação: usa a API do Portal da Transparência para convênios, repasses e emendas.', 'url' => 'https://api.portaldatransparencia.gov.br/swagger-ui/index.html', 'gratuita' => true, 'requer_chave' => true],
            'bndes'           => ['grupo' => 'captação', 'nome' => 'BNDES — Linhas municipais', 'descrição' => 'Crédito para infraestrutura, saneamento e mobilidade.', 'url' => 'https://www.bndes.gov.br/wps/portal/site/home/transparencia/dados-abertos', 'gratuita' => true, 'requer_chave' => false],
        ];
    }

    public function integrations()
    {
        $todasApis = $this->getIntegracoes();

        $integrations = [];
        foreach ($todasApis as $key => $api) {
            $api['ativo'] = (bool) SystemSetting::get("integration_{$key}_ativo", false);
            $api['chave'] = SystemSetting::get("integration_{$key}_chave", '');
            $integrations[$key] = $api;
        }

        $grupos = [];
        foreach ($integrations as $key => $api) {
            $grupos[$api['grupo']][$key] = $api;
        }

        $grupoLabels = [
            'socioeconomico' => 'Dados Socioeconômicos e Demográficos',
            'fiscal'         => 'Dados Fiscais e Orçamentários',
            'saude'          => 'Saúde',
            'educacao'       => 'Educação',
            'infraestrutura' => 'Infraestrutura, Saneamento e Meio Ambiente',
            'captação'       => 'Captação de Recursos e Programas Federais',
        ];

        return view('admin.settings.integrations', compact('integrations', 'grupos', 'grupoLabels'));
    }

    public function saveIntegrations(Request $request)
    {
        $ativos = $request->input('ativos', []);
        $chaves = $request->input('chaves', []);

        foreach ($this->getIntegracoes() as $key => $api) {
            SystemSetting::set("integration_{$key}_ativo", in_array($key, $ativos) ? '1' : '0', 'boolean', 'integrations', $api['nome']);
            if (!empty($chaves[$key])) {
                SystemSetting::set("integration_{$key}_chave", $chaves[$key], 'secret', 'integrations', $api['nome'] . ' — chave');
                if ($key === 'transferegov') {
                    SystemSetting::set('integration_transparencia_chave', $chaves[$key], 'secret', 'integrations', 'Portal da Transparência Federal — chave');
                }
            } elseif ($key === 'transferegov') {
                $legacyKey = SystemSetting::get('integration_transferegov_chave', '');
                if (!empty($legacyKey) && empty(SystemSetting::get('integration_transparencia_chave', ''))) {
                    SystemSetting::set('integration_transparencia_chave', $legacyKey, 'secret', 'integrations', 'Portal da Transparência Federal — chave');
                }
            }
        }

        return back()->with('success', 'Integrações salvas com sucesso.');
    }
}
