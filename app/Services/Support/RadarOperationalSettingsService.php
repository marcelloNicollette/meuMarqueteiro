<?php

namespace App\Services\Support;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\Models\Activity;

class RadarOperationalSettingsService
{
    private const LOG_NAME = 'radar_operational_settings';

    public function currentSnapshot(): array
    {
        $defaults = SystemSetting::defaults();

        return [
            'mail_runtime_enabled' => (bool) SystemSetting::get('mail_runtime_enabled', $defaults['mail_runtime_enabled']),
            'mail_runtime_host' => (string) SystemSetting::get('mail_runtime_host', $defaults['mail_runtime_host']),
            'mail_runtime_port' => (int) SystemSetting::get('mail_runtime_port', $defaults['mail_runtime_port']),
            'mail_runtime_username' => (string) SystemSetting::get('mail_runtime_username', $defaults['mail_runtime_username']),
            'mail_runtime_password' => (string) SystemSetting::get('mail_runtime_password', $defaults['mail_runtime_password']),
            'mail_runtime_encryption' => (string) SystemSetting::get('mail_runtime_encryption', $defaults['mail_runtime_encryption']),
            'mail_runtime_from_address' => (string) SystemSetting::get('mail_runtime_from_address', $defaults['mail_runtime_from_address']),
            'mail_runtime_from_name' => (string) SystemSetting::get('mail_runtime_from_name', $defaults['mail_runtime_from_name']),
            'mail_runtime_ehlo_domain' => (string) SystemSetting::get('mail_runtime_ehlo_domain', $defaults['mail_runtime_ehlo_domain']),
            'mail_runtime_timeout' => (int) SystemSetting::get('mail_runtime_timeout', $defaults['mail_runtime_timeout']),
            'mail_runtime_test_recipient' => (string) SystemSetting::get('mail_runtime_test_recipient', $defaults['mail_runtime_test_recipient']),
            'radar_sync_snapshot_enabled' => (bool) SystemSetting::get('radar_sync_snapshot_enabled', $defaults['radar_sync_snapshot_enabled']),
            'radar_sync_snapshot_daily_enabled' => (bool) SystemSetting::get('radar_sync_snapshot_daily_enabled', $defaults['radar_sync_snapshot_daily_enabled']),
            'radar_sync_snapshot_weekly_enabled' => (bool) SystemSetting::get('radar_sync_snapshot_weekly_enabled', $defaults['radar_sync_snapshot_weekly_enabled']),
            'radar_sync_snapshot_recipients' => array_values(SystemSetting::get('radar_sync_snapshot_recipients', $defaults['radar_sync_snapshot_recipients'])),
            'radar_sync_snapshot_daily_time' => (string) SystemSetting::get('radar_sync_snapshot_daily_time', $defaults['radar_sync_snapshot_daily_time']),
            'radar_sync_snapshot_weekly_day' => (int) SystemSetting::get('radar_sync_snapshot_weekly_day', $defaults['radar_sync_snapshot_weekly_day']),
            'radar_sync_snapshot_weekly_time' => (string) SystemSetting::get('radar_sync_snapshot_weekly_time', $defaults['radar_sync_snapshot_weekly_time']),
        ];
    }

    public function applySnapshot(array $snapshot): void
    {
        foreach ($this->settingMap() as $key => $meta) {
            $value = $snapshot[$key] ?? $meta['default'];
            SystemSetting::set($key, $value, $meta['type'], $meta['group'], $meta['label']);
        }
    }

    public function normalizePayload(array $validated, array $currentSnapshot, bool $keepStoredSecretWhenBlank = true): array
    {
        $recipientList = collect(explode(',', (string) ($validated['radar_sync_snapshot_recipients'] ?? '')))
            ->map(fn (string $email) => trim($email))
            ->filter()
            ->values()
            ->all();

        return [
            'mail_runtime_enabled' => (bool) ($validated['mail_runtime_enabled'] ?? false),
            'mail_runtime_host' => (string) ($validated['mail_runtime_host'] ?? ''),
            'mail_runtime_port' => (int) ($validated['mail_runtime_port'] ?? 587),
            'mail_runtime_username' => (string) ($validated['mail_runtime_username'] ?? ''),
            'mail_runtime_password' => $keepStoredSecretWhenBlank && empty($validated['mail_runtime_password'])
                ? (string) ($currentSnapshot['mail_runtime_password'] ?? '')
                : (string) ($validated['mail_runtime_password'] ?? ''),
            'mail_runtime_encryption' => (string) ($validated['mail_runtime_encryption'] ?? 'tls'),
            'mail_runtime_from_address' => (string) ($validated['mail_runtime_from_address'] ?? ''),
            'mail_runtime_from_name' => (string) ($validated['mail_runtime_from_name'] ?? ''),
            'mail_runtime_ehlo_domain' => (string) ($validated['mail_runtime_ehlo_domain'] ?? ''),
            'mail_runtime_timeout' => (int) ($validated['mail_runtime_timeout'] ?? 15),
            'mail_runtime_test_recipient' => (string) ($validated['mail_runtime_test_recipient'] ?? ''),
            'radar_sync_snapshot_enabled' => (bool) ($validated['radar_sync_snapshot_enabled'] ?? false),
            'radar_sync_snapshot_daily_enabled' => (bool) ($validated['radar_sync_snapshot_daily_enabled'] ?? false),
            'radar_sync_snapshot_weekly_enabled' => (bool) ($validated['radar_sync_snapshot_weekly_enabled'] ?? false),
            'radar_sync_snapshot_recipients' => $recipientList,
            'radar_sync_snapshot_daily_time' => (string) ($validated['radar_sync_snapshot_daily_time'] ?? '08:10'),
            'radar_sync_snapshot_weekly_day' => (int) ($validated['radar_sync_snapshot_weekly_day'] ?? 1),
            'radar_sync_snapshot_weekly_time' => (string) ($validated['radar_sync_snapshot_weekly_time'] ?? '08:30'),
        ];
    }

    public function recordUpdate(?User $actor, array $before, array $after, string $event = 'updated', array $extra = []): ?Activity
    {
        if ($before === $after) {
            return null;
        }

        return activity(self::LOG_NAME)
            ->causedBy($actor)
            ->withProperties(array_merge([
                'before_masked' => $this->maskedSnapshot($before),
                'after_masked' => $this->maskedSnapshot($after),
                'changed_keys' => $this->changedKeys($before, $after),
                'before_encrypted' => Crypt::encryptString(json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                'after_encrypted' => Crypt::encryptString(json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            ], $extra))
            ->event($event)
            ->log(match ($event) {
                'rollback' => 'Rollback das configuracoes operacionais do Radar',
                default => 'Configuracoes operacionais do Radar atualizadas',
            });
    }

    public function rollback(Activity $activity, ?User $actor): ?Activity
    {
        $targetSnapshot = $this->decryptSnapshot((string) $activity->getExtraProperty('before_encrypted', ''));

        if ($targetSnapshot === null) {
            return null;
        }

        $current = $this->currentSnapshot();
        $this->applySnapshot($targetSnapshot);

        return $this->recordUpdate($actor, $current, $targetSnapshot, 'rollback', [
            'rollback_source_activity_id' => $activity->id,
        ]);
    }

    public function history(int $limit = 12): Collection
    {
        return Activity::query()
            ->where('log_name', self::LOG_NAME)
            ->with('causer')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (Activity $activity) {
                $changedKeys = collect($activity->getExtraProperty('changed_keys', []))
                    ->map(fn (string $key) => $this->settingLabel($key))
                    ->values()
                    ->all();

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'created_at' => $activity->created_at,
                    'created_at_human' => $activity->created_at?->diffForHumans(),
                    'causer_name' => $activity->causer?->name ?? 'sistema',
                    'causer_email' => $activity->causer?->email,
                    'changed_keys' => $changedKeys,
                    'before_masked' => (array) $activity->getExtraProperty('before_masked', []),
                    'after_masked' => (array) $activity->getExtraProperty('after_masked', []),
                    'rollback_source_activity_id' => $activity->getExtraProperty('rollback_source_activity_id'),
                    'can_rollback' => !empty($activity->getExtraProperty('before_encrypted')),
                ];
            })
            ->values();
    }

    public function isAuditActivity(Activity $activity): bool
    {
        return $activity->log_name === self::LOG_NAME;
    }

    public function maskedSnapshot(array $snapshot): array
    {
        $masked = $snapshot;

        if (array_key_exists('mail_runtime_password', $masked)) {
            $masked['mail_runtime_password'] = $masked['mail_runtime_password'] !== '' ? '********' : '';
        }

        if (array_key_exists('radar_sync_snapshot_recipients', $masked)) {
            $masked['radar_sync_snapshot_recipients'] = implode(', ', (array) $masked['radar_sync_snapshot_recipients']);
        }

        return $masked;
    }

    private function changedKeys(array $before, array $after): array
    {
        return collect(array_keys($this->settingMap()))
            ->filter(fn (string $key) => ($before[$key] ?? null) !== ($after[$key] ?? null))
            ->values()
            ->all();
    }

    private function decryptSnapshot(string $encrypted): ?array
    {
        if ($encrypted === '') {
            return null;
        }

        try {
            $json = Crypt::decryptString($encrypted);
            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function settingMap(): array
    {
        $defaults = SystemSetting::defaults();

        return [
            'mail_runtime_enabled' => ['type' => 'boolean', 'group' => 'mail', 'label' => 'SMTP runtime ativo', 'default' => $defaults['mail_runtime_enabled']],
            'mail_runtime_host' => ['type' => 'string', 'group' => 'mail', 'label' => 'Host SMTP', 'default' => $defaults['mail_runtime_host']],
            'mail_runtime_port' => ['type' => 'string', 'group' => 'mail', 'label' => 'Porta SMTP', 'default' => $defaults['mail_runtime_port']],
            'mail_runtime_username' => ['type' => 'string', 'group' => 'mail', 'label' => 'Usuário SMTP', 'default' => $defaults['mail_runtime_username']],
            'mail_runtime_password' => ['type' => 'secret', 'group' => 'mail', 'label' => 'Senha SMTP', 'default' => $defaults['mail_runtime_password']],
            'mail_runtime_encryption' => ['type' => 'string', 'group' => 'mail', 'label' => 'Criptografia SMTP', 'default' => $defaults['mail_runtime_encryption']],
            'mail_runtime_from_address' => ['type' => 'string', 'group' => 'mail', 'label' => 'E-mail remetente', 'default' => $defaults['mail_runtime_from_address']],
            'mail_runtime_from_name' => ['type' => 'string', 'group' => 'mail', 'label' => 'Nome remetente', 'default' => $defaults['mail_runtime_from_name']],
            'mail_runtime_ehlo_domain' => ['type' => 'string', 'group' => 'mail', 'label' => 'EHLO SMTP', 'default' => $defaults['mail_runtime_ehlo_domain']],
            'mail_runtime_timeout' => ['type' => 'string', 'group' => 'mail', 'label' => 'Timeout SMTP', 'default' => $defaults['mail_runtime_timeout']],
            'mail_runtime_test_recipient' => ['type' => 'string', 'group' => 'mail', 'label' => 'Destinatário de teste SMTP', 'default' => $defaults['mail_runtime_test_recipient']],
            'radar_sync_snapshot_enabled' => ['type' => 'boolean', 'group' => 'radar_operations', 'label' => 'Snapshot Radar ativo', 'default' => $defaults['radar_sync_snapshot_enabled']],
            'radar_sync_snapshot_daily_enabled' => ['type' => 'boolean', 'group' => 'radar_operations', 'label' => 'Snapshot diário ativo', 'default' => $defaults['radar_sync_snapshot_daily_enabled']],
            'radar_sync_snapshot_weekly_enabled' => ['type' => 'boolean', 'group' => 'radar_operations', 'label' => 'Snapshot semanal ativo', 'default' => $defaults['radar_sync_snapshot_weekly_enabled']],
            'radar_sync_snapshot_recipients' => ['type' => 'json', 'group' => 'radar_operations', 'label' => 'Destinatários do Radar', 'default' => $defaults['radar_sync_snapshot_recipients']],
            'radar_sync_snapshot_daily_time' => ['type' => 'string', 'group' => 'radar_operations', 'label' => 'Horário diário do Radar', 'default' => $defaults['radar_sync_snapshot_daily_time']],
            'radar_sync_snapshot_weekly_day' => ['type' => 'string', 'group' => 'radar_operations', 'label' => 'Dia semanal do Radar', 'default' => $defaults['radar_sync_snapshot_weekly_day']],
            'radar_sync_snapshot_weekly_time' => ['type' => 'string', 'group' => 'radar_operations', 'label' => 'Horário semanal do Radar', 'default' => $defaults['radar_sync_snapshot_weekly_time']],
        ];
    }

    private function settingLabel(string $key): string
    {
        return (string) data_get($this->settingMap(), "{$key}.label", $key);
    }
}
