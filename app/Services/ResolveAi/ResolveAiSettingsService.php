<?php

namespace App\Services\ResolveAi;

use App\Models\Municipality;

class ResolveAiSettingsService
{
    public function defaults(): array
    {
        return [
            'priority_hours' => [
                'alta' => 48,
                'media' => 168,
                'baixa' => 360,
            ],
            'alert_lead_hours' => 24,
            'inactivity_followup_hours' => 48,
            'overdue_repeat_hours' => 24,
            'comparative_recent_window_days' => 90,
            'comparative_previous_window_days' => 90,
            'channels' => [
                'internal' => true,
                'email' => true,
                'whatsapp' => false,
            ],
            'attachment_required_priorities' => ['alta'],
        ];
    }

    public function forMunicipality(Municipality $municipality): array
    {
        $settings = (array) ($municipality->settings ?? []);
        $module = (array) ($settings['resolve_ai'] ?? []);
        $defaults = $this->defaults();

        return [
            'priority_hours' => [
                'alta' => max(1, (int) ($module['priority_hours']['alta'] ?? $defaults['priority_hours']['alta'])),
                'media' => max(1, (int) ($module['priority_hours']['media'] ?? $defaults['priority_hours']['media'])),
                'baixa' => max(1, (int) ($module['priority_hours']['baixa'] ?? $defaults['priority_hours']['baixa'])),
            ],
            'alert_lead_hours' => max(1, (int) ($module['alert_lead_hours'] ?? $defaults['alert_lead_hours'])),
            'inactivity_followup_hours' => max(1, (int) ($module['inactivity_followup_hours'] ?? $defaults['inactivity_followup_hours'])),
            'overdue_repeat_hours' => max(1, (int) ($module['overdue_repeat_hours'] ?? $defaults['overdue_repeat_hours'])),
            'comparative_recent_window_days' => max(7, (int) ($module['comparative_recent_window_days'] ?? $defaults['comparative_recent_window_days'])),
            'comparative_previous_window_days' => max(7, (int) ($module['comparative_previous_window_days'] ?? $defaults['comparative_previous_window_days'])),
            'channels' => [
                'internal' => (bool) ($module['channels']['internal'] ?? $defaults['channels']['internal']),
                'email' => (bool) ($module['channels']['email'] ?? $defaults['channels']['email']),
                'whatsapp' => (bool) ($module['channels']['whatsapp'] ?? $defaults['channels']['whatsapp']),
            ],
            'attachment_required_priorities' => array_values(array_intersect(
                ['alta', 'media', 'baixa'],
                array_map('strval', (array) ($module['attachment_required_priorities'] ?? $defaults['attachment_required_priorities']))
            )),
        ];
    }

    public function save(Municipality $municipality, array $payload): Municipality
    {
        $settings = (array) ($municipality->settings ?? []);
        $settings['resolve_ai'] = $this->normalizePayload($payload);

        $municipality->update([
            'settings' => $settings,
            'onboarding_status' => $municipality->onboarding_status === 'pending' ? 'in_progress' : $municipality->onboarding_status,
        ]);

        return $municipality->refresh();
    }

    public function normalizePayload(array $payload): array
    {
        return [
            'priority_hours' => [
                'alta' => max(1, (int) ($payload['priority_hours']['alta'] ?? $payload['resolve_ai_priority_alta_hours'] ?? 48)),
                'media' => max(1, (int) ($payload['priority_hours']['media'] ?? $payload['resolve_ai_priority_media_hours'] ?? 168)),
                'baixa' => max(1, (int) ($payload['priority_hours']['baixa'] ?? $payload['resolve_ai_priority_baixa_hours'] ?? 360)),
            ],
            'alert_lead_hours' => max(1, (int) ($payload['alert_lead_hours'] ?? $payload['resolve_ai_alert_lead_hours'] ?? 24)),
            'inactivity_followup_hours' => max(1, (int) ($payload['inactivity_followup_hours'] ?? $payload['resolve_ai_inactivity_followup_hours'] ?? 48)),
            'overdue_repeat_hours' => max(1, (int) ($payload['overdue_repeat_hours'] ?? $payload['resolve_ai_overdue_repeat_hours'] ?? 24)),
            'comparative_recent_window_days' => max(7, (int) ($payload['comparative_recent_window_days'] ?? $payload['resolve_ai_comparative_recent_window_days'] ?? 90)),
            'comparative_previous_window_days' => max(7, (int) ($payload['comparative_previous_window_days'] ?? $payload['resolve_ai_comparative_previous_window_days'] ?? 90)),
            'channels' => [
                'internal' => (bool) ($payload['channels']['internal'] ?? $payload['resolve_ai_channel_internal'] ?? false),
                'email' => (bool) ($payload['channels']['email'] ?? $payload['resolve_ai_channel_email'] ?? false),
                'whatsapp' => (bool) ($payload['channels']['whatsapp'] ?? $payload['resolve_ai_channel_whatsapp'] ?? false),
            ],
            'attachment_required_priorities' => array_values(array_intersect(
                ['alta', 'media', 'baixa'],
                array_map('strval', (array) ($payload['attachment_required_priorities'] ?? $payload['resolve_ai_attachment_required_priorities'] ?? ['alta']))
            )),
        ];
    }

    public function hoursForPriority(Municipality $municipality, string $priority): int
    {
        $settings = $this->forMunicipality($municipality);

        return (int) ($settings['priority_hours'][$priority] ?? $settings['priority_hours']['media']);
    }

    public function isChannelEnabled(Municipality $municipality, string $channel): bool
    {
        $settings = $this->forMunicipality($municipality);

        return (bool) ($settings['channels'][$channel] ?? false);
    }

    public function requiresAttachment(Municipality $municipality, string $priority): bool
    {
        $settings = $this->forMunicipality($municipality);

        return in_array($priority, $settings['attachment_required_priorities'], true);
    }
}
