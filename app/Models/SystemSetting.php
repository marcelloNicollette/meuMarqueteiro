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

    // Defaults do sistema — usados se não houver entrada no banco
    public static function defaults(): array
    {
        return [
            // IA
            'ai_default_provider'    => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'anthropic_model'        => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
            'anthropic_api_key'      => env('ANTHROPIC_API_KEY', ''),
            'openai_model'           => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'openai_api_key'         => env('OPENAI_API_KEY', ''),
            'gemini_model'           => env('GEMINI_MODEL', 'gemini-1.5-pro'),
            'gemini_api_key'         => env('GEMINI_API_KEY', ''),
            'voyage_api_key'         => env('VOYAGE_API_KEY', ''),
        ];
    }
}
