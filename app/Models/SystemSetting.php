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
        ];
    }
}
