<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    // ─── Helpers estáticos ────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            "setting_{$key}",
            300,
            fn() =>
            static::where('key', $key)->first()
        );

        if (!$setting) return $default;

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general', string $label = ''): void
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type, 'group' => $group, 'label' => $label]
        );

        Cache::forget("setting_{$key}");
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->get()->keyBy('key')->toArray();
    }

    // Defaults do sistema — usados se não  houver entrada no banco
    public static function defaults(): array
    {
        return [
            // IA
            'ai_default_provider'    => env('AI_DEFAULT_PROVIDER', 'openai'),
            'anthropic_model'        => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
            'anthropic_api_key'      => env('ANTHROPIC_API_KEY', ''),
            'openai_model'           => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'openai_api_key'         => env('OPENAI_API_KEY', ''),
            'openai_audio_transcription_model' => env('OPENAI_AUDIO_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
            'openai_audio_speech_model' => env('OPENAI_AUDIO_SPEECH_MODEL', 'gpt-4o-mini-tts'),
            'openai_audio_voice'     => env('OPENAI_AUDIO_VOICE', 'alloy'),
            'openai_audio_cache_ttl_minutes' => (int) env('CHAT_AUDIO_CACHE_TTL_MINUTES', 60),
            'gemini_model'           => env('GEMINI_MODEL', 'gemini-1.5-pro'),
            'gemini_api_key'         => env('GEMINI_API_KEY', ''),
            'voyage_api_key'         => env('VOYAGE_API_KEY', ''),

            // Mail runtime
            'mail_runtime_enabled'   => (bool) env('MAIL_RUNTIME_ENABLED', false),
            'mail_runtime_host'      => env('MAIL_HOST', 'smtp.gmail.com'),
            'mail_runtime_port'      => (int) env('MAIL_PORT', 587),
            'mail_runtime_username'  => env('MAIL_USERNAME', ''),
            'mail_runtime_password'  => env('MAIL_PASSWORD', ''),
            'mail_runtime_encryption' => env('MAIL_SCHEME', 'tls'),
            'mail_runtime_from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'mail_runtime_from_name' => env('MAIL_FROM_NAME', 'Meu Marqueteiro'),
            'mail_runtime_ehlo_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
            'mail_runtime_timeout'   => 15,
            'mail_runtime_test_recipient' => '',

            // Radar snapshots
            'radar_sync_snapshot_enabled' => (bool) env('RADAR_SYNC_SNAPSHOT_ENABLED', false),
            'radar_sync_snapshot_daily_enabled' => (bool) env('RADAR_SYNC_SNAPSHOT_DAILY_ENABLED', true),
            'radar_sync_snapshot_weekly_enabled' => (bool) env('RADAR_SYNC_SNAPSHOT_WEEKLY_ENABLED', true),
            'radar_sync_snapshot_recipients' => array_values(array_filter(array_map(
                static fn (string $email) => trim($email),
                explode(',', (string) env('RADAR_SYNC_SNAPSHOT_RECIPIENTS', ''))
            ))),
            'radar_sync_snapshot_daily_time' => env('RADAR_SYNC_SNAPSHOT_DAILY_TIME', '08:10'),
            'radar_sync_snapshot_weekly_day' => (int) env('RADAR_SYNC_SNAPSHOT_WEEKLY_DAY', 1),
            'radar_sync_snapshot_weekly_time' => env('RADAR_SYNC_SNAPSHOT_WEEKLY_TIME', '08:30'),

            // Cobertura municipal - mailing executivo
            'coverage_executive_mail_enabled' => (bool) env('COVERAGE_EXECUTIVE_MAIL_ENABLED', false),
            'coverage_executive_mail_daily_enabled' => (bool) env('COVERAGE_EXECUTIVE_MAIL_DAILY_ENABLED', true),
            'coverage_executive_mail_weekly_enabled' => (bool) env('COVERAGE_EXECUTIVE_MAIL_WEEKLY_ENABLED', true),
            'coverage_executive_mail_recipients' => array_values(array_filter(array_map(
                static fn (string $email) => trim($email),
                explode(',', (string) env('COVERAGE_EXECUTIVE_MAIL_RECIPIENTS', ''))
            ))),
            'coverage_executive_mail_daily_time' => env('COVERAGE_EXECUTIVE_MAIL_DAILY_TIME', '09:10'),
            'coverage_executive_mail_weekly_day' => (int) env('COVERAGE_EXECUTIVE_MAIL_WEEKLY_DAY', 1),
            'coverage_executive_mail_weekly_time' => env('COVERAGE_EXECUTIVE_MAIL_WEEKLY_TIME', '09:20'),
            'coverage_executive_mail_ranking_limit' => (int) env('COVERAGE_EXECUTIVE_MAIL_RANKING_LIMIT', 15),
            'coverage_executive_mail_requires_approval' => (bool) env('COVERAGE_EXECUTIVE_MAIL_REQUIRES_APPROVAL', true),
            'coverage_executive_mail_two_level_approval' => (bool) env('COVERAGE_EXECUTIVE_MAIL_TWO_LEVEL_APPROVAL', true),
            'coverage_executive_mail_distinct_approvers' => (bool) env('COVERAGE_EXECUTIVE_MAIL_DISTINCT_APPROVERS', true),
            'coverage_executive_mail_level_one_label' => env('COVERAGE_EXECUTIVE_MAIL_LEVEL_ONE_LABEL', 'Operações'),
            'coverage_executive_mail_level_two_label' => env('COVERAGE_EXECUTIVE_MAIL_LEVEL_TWO_LABEL', 'Diretoria'),
            'coverage_executive_mail_approval_state' => [
                'daily' => [
                    'level_one' => [
                        'approved' => false,
                        'approved_at' => null,
                        'approved_by_user_id' => null,
                        'approved_by_name' => null,
                        'approved_by_role' => null,
                        'approved_until' => null,
                    ],
                    'level_two' => [
                        'approved' => false,
                        'approved_at' => null,
                        'approved_by_user_id' => null,
                        'approved_by_name' => null,
                        'approved_by_role' => null,
                        'approved_until' => null,
                    ],
                ],
                'weekly' => [
                    'level_one' => [
                        'approved' => false,
                        'approved_at' => null,
                        'approved_by_user_id' => null,
                        'approved_by_name' => null,
                        'approved_by_role' => null,
                        'approved_until' => null,
                    ],
                    'level_two' => [
                        'approved' => false,
                        'approved_at' => null,
                        'approved_by_user_id' => null,
                        'approved_by_name' => null,
                        'approved_by_role' => null,
                        'approved_until' => null,
                    ],
                ],
            ],
            'coverage_executive_mail_identity_name' => env('COVERAGE_EXECUTIVE_MAIL_IDENTITY_NAME', env('MAIL_FROM_NAME', 'Meu Marqueteiro')),
            'coverage_executive_mail_identity_department' => env('COVERAGE_EXECUTIVE_MAIL_IDENTITY_DEPARTMENT', 'Central Executiva de Cobertura'),
            'coverage_executive_mail_identity_tagline' => env('COVERAGE_EXECUTIVE_MAIL_IDENTITY_TAGLINE', 'Governança municipal com leitura executiva e trilha institucional'),
            'coverage_executive_mail_identity_logo' => env('COVERAGE_EXECUTIVE_MAIL_IDENTITY_LOGO', '/images/logo-borda-black.png'),
            'coverage_executive_mail_identity_accent_color' => env('COVERAGE_EXECUTIVE_MAIL_IDENTITY_ACCENT_COLOR', '#111827'),
            'coverage_executive_mail_identity_secondary_color' => env('COVERAGE_EXECUTIVE_MAIL_IDENTITY_SECONDARY_COLOR', '#1d4ed8'),
            'coverage_executive_mail_signature_primary_name' => env('COVERAGE_EXECUTIVE_MAIL_SIGNATURE_PRIMARY_NAME', 'Diretoria Executiva'),
            'coverage_executive_mail_signature_primary_role' => env('COVERAGE_EXECUTIVE_MAIL_SIGNATURE_PRIMARY_ROLE', 'Coordenação de Governança'),
            'coverage_executive_mail_signature_secondary_name' => env('COVERAGE_EXECUTIVE_MAIL_SIGNATURE_SECONDARY_NAME', 'Operações Institucionais'),
            'coverage_executive_mail_signature_secondary_role' => env('COVERAGE_EXECUTIVE_MAIL_SIGNATURE_SECONDARY_ROLE', 'Supervisão de Cobertura'),

            // Cobertura municipal - SLA do owner e notificações
            'coverage_alert_owner_sla_high_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_HIGH_HOURS', 4),
            'coverage_alert_owner_sla_medium_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_MEDIUM_HOURS', 12),
            'coverage_alert_owner_sla_default_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_DEFAULT_HOURS', 24),
            'coverage_alert_owner_sla_admin_high_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_ADMIN_HIGH_HOURS', 4),
            'coverage_alert_owner_sla_admin_medium_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_ADMIN_MEDIUM_HOURS', 12),
            'coverage_alert_owner_sla_admin_default_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_ADMIN_DEFAULT_HOURS', 24),
            'coverage_alert_owner_sla_mayor_high_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_MAYOR_HIGH_HOURS', 6),
            'coverage_alert_owner_sla_mayor_medium_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_MAYOR_MEDIUM_HOURS', 16),
            'coverage_alert_owner_sla_mayor_default_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_MAYOR_DEFAULT_HOURS', 30),
            'coverage_alert_owner_sla_secretary_high_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_SECRETARY_HIGH_HOURS', 5),
            'coverage_alert_owner_sla_secretary_medium_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_SECRETARY_MEDIUM_HOURS', 14),
            'coverage_alert_owner_sla_secretary_default_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_SECRETARY_DEFAULT_HOURS', 28),
            'coverage_alert_owner_sla_advisor_high_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_ADVISOR_HIGH_HOURS', 4),
            'coverage_alert_owner_sla_advisor_medium_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_ADVISOR_MEDIUM_HOURS', 10),
            'coverage_alert_owner_sla_advisor_default_hours' => (int) env('COVERAGE_ALERT_OWNER_SLA_ADVISOR_DEFAULT_HOURS', 20),
            'coverage_alert_owner_warning_minutes' => (int) env('COVERAGE_ALERT_OWNER_WARNING_MINUTES', 60),
            'coverage_alert_owner_notifications_enabled' => (bool) env('COVERAGE_ALERT_OWNER_NOTIFICATIONS_ENABLED', true),
        ];
    }
}
