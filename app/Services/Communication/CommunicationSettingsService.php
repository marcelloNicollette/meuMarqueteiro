<?php

namespace App\Services\Communication;

use App\Models\Municipality;

class CommunicationSettingsService
{
    public function defaults(): array
    {
        return [
            'sla' => [
                'draft_review_hours' => 24,
                'approved_publish_hours' => 24,
                'scheduled_lead_hours' => 6,
            ],
            'approval' => [
                'post' => 'mayor',
                'image' => 'advisor',
                'interview' => 'secretary',
                'crisis' => 'mayor',
            ],
        ];
    }

    public function forMunicipality(Municipality $municipality): array
    {
        $settings = (array) ($municipality->settings ?? []);
        $module = (array) ($settings['communication'] ?? []);
        $defaults = $this->defaults();

        return [
            'sla' => [
                'draft_review_hours' => max(1, (int) ($module['sla']['draft_review_hours'] ?? $defaults['sla']['draft_review_hours'])),
                'approved_publish_hours' => max(1, (int) ($module['sla']['approved_publish_hours'] ?? $defaults['sla']['approved_publish_hours'])),
                'scheduled_lead_hours' => max(1, (int) ($module['sla']['scheduled_lead_hours'] ?? $defaults['sla']['scheduled_lead_hours'])),
            ],
            'approval' => [
                'post' => $this->normalizeApproverRole($module['approval']['post'] ?? $defaults['approval']['post']),
                'image' => $this->normalizeApproverRole($module['approval']['image'] ?? $defaults['approval']['image']),
                'interview' => $this->normalizeApproverRole($module['approval']['interview'] ?? $defaults['approval']['interview']),
                'crisis' => $this->normalizeApproverRole($module['approval']['crisis'] ?? $defaults['approval']['crisis']),
            ],
        ];
    }

    public function save(Municipality $municipality, array $payload): Municipality
    {
        $settings = (array) ($municipality->settings ?? []);
        $settings['communication'] = $this->normalizePayload($payload);

        $municipality->update([
            'settings' => $settings,
            'onboarding_status' => $municipality->onboarding_status === 'pending' ? 'in_progress' : $municipality->onboarding_status,
        ]);

        return $municipality->refresh();
    }

    public function normalizePayload(array $payload): array
    {
        return [
            'sla' => [
                'draft_review_hours' => max(1, (int) ($payload['sla']['draft_review_hours'] ?? $payload['communication_sla_draft_review_hours'] ?? 24)),
                'approved_publish_hours' => max(1, (int) ($payload['sla']['approved_publish_hours'] ?? $payload['communication_sla_approved_publish_hours'] ?? 24)),
                'scheduled_lead_hours' => max(1, (int) ($payload['sla']['scheduled_lead_hours'] ?? $payload['communication_sla_scheduled_lead_hours'] ?? 6)),
            ],
            'approval' => [
                'post' => $this->normalizeApproverRole($payload['approval']['post'] ?? $payload['communication_approver_post'] ?? 'mayor'),
                'image' => $this->normalizeApproverRole($payload['approval']['image'] ?? $payload['communication_approver_image'] ?? 'advisor'),
                'interview' => $this->normalizeApproverRole($payload['approval']['interview'] ?? $payload['communication_approver_interview'] ?? 'secretary'),
                'crisis' => $this->normalizeApproverRole($payload['approval']['crisis'] ?? $payload['communication_approver_crisis'] ?? 'mayor'),
            ],
        ];
    }

    private function normalizeApproverRole(mixed $role): string
    {
        $allowed = ['mayor', 'secretary', 'advisor'];
        $normalized = trim(strtolower((string) $role));

        return in_array($normalized, $allowed, true) ? $normalized : 'mayor';
    }
}
