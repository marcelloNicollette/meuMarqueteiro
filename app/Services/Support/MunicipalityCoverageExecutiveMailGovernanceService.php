<?php

namespace App\Services\Support;

use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;

class MunicipalityCoverageExecutiveMailGovernanceService
{
    public function settings(): array
    {
        $defaults = SystemSetting::defaults();

        return [
            'enabled' => (bool) SystemSetting::get('coverage_executive_mail_enabled', $defaults['coverage_executive_mail_enabled']),
            'daily_enabled' => (bool) SystemSetting::get('coverage_executive_mail_daily_enabled', $defaults['coverage_executive_mail_daily_enabled']),
            'weekly_enabled' => (bool) SystemSetting::get('coverage_executive_mail_weekly_enabled', $defaults['coverage_executive_mail_weekly_enabled']),
            'recipients' => array_values(SystemSetting::get('coverage_executive_mail_recipients', $defaults['coverage_executive_mail_recipients'])),
            'daily_time' => (string) SystemSetting::get('coverage_executive_mail_daily_time', $defaults['coverage_executive_mail_daily_time']),
            'weekly_day' => (int) SystemSetting::get('coverage_executive_mail_weekly_day', $defaults['coverage_executive_mail_weekly_day']),
            'weekly_time' => (string) SystemSetting::get('coverage_executive_mail_weekly_time', $defaults['coverage_executive_mail_weekly_time']),
            'ranking_limit' => max(5, min(50, (int) SystemSetting::get('coverage_executive_mail_ranking_limit', $defaults['coverage_executive_mail_ranking_limit']))),
            'requires_approval' => (bool) SystemSetting::get('coverage_executive_mail_requires_approval', $defaults['coverage_executive_mail_requires_approval']),
            'two_level_approval' => (bool) SystemSetting::get('coverage_executive_mail_two_level_approval', $defaults['coverage_executive_mail_two_level_approval']),
            'distinct_approvers' => (bool) SystemSetting::get('coverage_executive_mail_distinct_approvers', $defaults['coverage_executive_mail_distinct_approvers']),
            'level_one_label' => (string) SystemSetting::get('coverage_executive_mail_level_one_label', $defaults['coverage_executive_mail_level_one_label']),
            'level_two_label' => (string) SystemSetting::get('coverage_executive_mail_level_two_label', $defaults['coverage_executive_mail_level_two_label']),
            'institution_name' => (string) SystemSetting::get('coverage_executive_mail_identity_name', $defaults['coverage_executive_mail_identity_name']),
            'institution_department' => (string) SystemSetting::get('coverage_executive_mail_identity_department', $defaults['coverage_executive_mail_identity_department']),
            'institution_tagline' => (string) SystemSetting::get('coverage_executive_mail_identity_tagline', $defaults['coverage_executive_mail_identity_tagline']),
            'logo_path' => (string) SystemSetting::get('coverage_executive_mail_identity_logo', $defaults['coverage_executive_mail_identity_logo']),
            'accent_color' => (string) SystemSetting::get('coverage_executive_mail_identity_accent_color', $defaults['coverage_executive_mail_identity_accent_color']),
            'secondary_color' => (string) SystemSetting::get('coverage_executive_mail_identity_secondary_color', $defaults['coverage_executive_mail_identity_secondary_color']),
            'primary_signer_name' => (string) SystemSetting::get('coverage_executive_mail_signature_primary_name', $defaults['coverage_executive_mail_signature_primary_name']),
            'primary_signer_role' => (string) SystemSetting::get('coverage_executive_mail_signature_primary_role', $defaults['coverage_executive_mail_signature_primary_role']),
            'secondary_signer_name' => (string) SystemSetting::get('coverage_executive_mail_signature_secondary_name', $defaults['coverage_executive_mail_signature_secondary_name']),
            'secondary_signer_role' => (string) SystemSetting::get('coverage_executive_mail_signature_secondary_role', $defaults['coverage_executive_mail_signature_secondary_role']),
        ];
    }

    public function identity(): array
    {
        $settings = $this->settings();
        $logoPath = trim((string) ($settings['logo_path'] ?? ''));
        $absoluteLogoPath = $logoPath !== '' ? public_path(ltrim($logoPath, '/')) : '';

        return [
            'institution_name' => $settings['institution_name'],
            'department' => $settings['institution_department'],
            'tagline' => $settings['institution_tagline'],
            'logo_path' => $logoPath,
            'logo_absolute_path' => is_file($absoluteLogoPath) ? $absoluteLogoPath : null,
            'accent_color' => $this->normalizeHexColor((string) $settings['accent_color'], '#111827'),
            'secondary_color' => $this->normalizeHexColor((string) $settings['secondary_color'], '#1d4ed8'),
        ];
    }

    public function signatures(): array
    {
        $settings = $this->settings();

        return [
            [
                'name' => $settings['primary_signer_name'],
                'role' => $settings['primary_signer_role'],
                'initials' => $this->initials($settings['primary_signer_name']),
            ],
            [
                'name' => $settings['secondary_signer_name'],
                'role' => $settings['secondary_signer_role'],
                'initials' => $this->initials($settings['secondary_signer_name']),
            ],
        ];
    }

    public function recipients(): array
    {
        return $this->settings()['recipients'];
    }

    public function rankingLimit(): int
    {
        return $this->settings()['ranking_limit'];
    }

    public function requiresApproval(): bool
    {
        return $this->settings()['requires_approval'];
    }

    public function approvalState(): array
    {
        $raw = SystemSetting::get('coverage_executive_mail_approval_state', SystemSetting::defaults()['coverage_executive_mail_approval_state']);

        return is_array($raw) ? $raw : SystemSetting::defaults()['coverage_executive_mail_approval_state'];
    }

    public function approvalForPeriod(string $period): array
    {
        $period = $this->normalizePeriod($period);
        $settings = $this->settings();
        $state = $this->normalizedPeriodState((array) data_get($this->approvalState(), $period, []));
        $levelOne = $this->normalizeApprovalLevel((array) data_get($state, 'level_one', []));
        $levelTwo = $this->normalizeApprovalLevel((array) data_get($state, 'level_two', []));
        $levelOneApproved = $this->isLevelApproved($levelOne);
        $levelTwoApproved = $settings['two_level_approval'] ? $this->isLevelApproved($levelTwo) : true;
        $approved = $levelOneApproved && $levelTwoApproved;
        $approvedUntil = $approved
            ? ($settings['two_level_approval'] ? min((string) $levelOne['approved_until'], (string) $levelTwo['approved_until']) : $levelOne['approved_until'])
            : null;
        $finalApprover = $settings['two_level_approval'] && $levelTwoApproved ? $levelTwo : $levelOne;

        return [
            'approved' => $approved,
            'approved_at' => $approved ? ($settings['two_level_approval'] ? $levelTwo['approved_at'] : $levelOne['approved_at']) : null,
            'approved_by_user_id' => $approved ? $finalApprover['approved_by_user_id'] : null,
            'approved_by_name' => $approved ? $finalApprover['approved_by_name'] : null,
            'approved_by_role' => $approved ? $finalApprover['approved_by_role'] : null,
            'approved_until' => $approvedUntil,
            'level_one' => $levelOne,
            'level_two' => $levelTwo,
        ];
    }

    public function approve(string $period, User $user, string $level = 'level_one'): array
    {
        $period = $this->normalizePeriod($period);
        $level = $this->normalizeApprovalLevelKey($level);
        $settings = $this->settings();
        $state = $this->approvalState();
        $periodState = $this->normalizedPeriodState((array) data_get($state, $period, []));
        $approvedUntil = now()->addHours($period === 'weekly' ? 192 : 36)->toIso8601String();

        if ($settings['distinct_approvers']) {
            $otherLevel = $level === 'level_one' ? 'level_two' : 'level_one';
            $otherApprover = data_get($periodState, $otherLevel . '.approved_by_user_id');
            if ($settings['two_level_approval'] && $otherApprover && (int) $otherApprover === (int) $user->id) {
                throw new \InvalidArgumentException('Os dois níveis do mailing exigem aprovadores distintos.');
            }
        }

        data_set($periodState, $level, [
            'approved' => true,
            'approved_at' => now()->toIso8601String(),
            'approved_by_user_id' => $user->id,
            'approved_by_name' => $user->name,
            'approved_by_role' => $this->userRoleLabel($user),
            'approved_until' => $approvedUntil,
        ]);
        data_set($state, $period, $periodState);

        SystemSetting::set(
            'coverage_executive_mail_approval_state',
            $state,
            'json',
            'coverage_operations',
            'Estado de aprovação do mailing executivo'
        );

        return $this->approvalForPeriod($period);
    }

    public function revoke(string $period): array
    {
        $period = $this->normalizePeriod($period);
        $state = $this->approvalState();
        data_set($state, $period, $this->normalizedPeriodState([]));

        SystemSetting::set(
            'coverage_executive_mail_approval_state',
            $state,
            'json',
            'coverage_operations',
            'Estado de aprovação do mailing executivo'
        );

        return $this->approvalForPeriod($period);
    }

    public function canDispatch(string $period): bool
    {
        if (!$this->requiresApproval()) {
            return true;
        }

        return (bool) ($this->approvalForPeriod($period)['approved'] ?? false);
    }

    public function consumeApproval(string $period): void
    {
        if (!$this->requiresApproval()) {
            return;
        }

        $this->revoke($period);
    }

    public function panelData(): array
    {
        $settings = $this->settings();

        return [
            'settings' => $settings,
            'identity' => $this->identity(),
            'signatures' => $this->signatures(),
            'levels' => [
                'level_one' => $settings['level_one_label'],
                'level_two' => $settings['level_two_label'],
            ],
            'approval' => [
                'daily' => $this->approvalForPeriod('daily'),
                'weekly' => $this->approvalForPeriod('weekly'),
            ],
        ];
    }

    public function periodLabel(string $period): string
    {
        return $this->normalizePeriod($period) === 'weekly' ? 'Semanal' : 'Diário';
    }

    private function normalizePeriod(string $period): string
    {
        return strtolower($period) === 'weekly' ? 'weekly' : 'daily';
    }

    private function normalizedPeriodState(array $state): array
    {
        if (isset($state['level_one']) || isset($state['level_two'])) {
            return [
                'level_one' => $this->normalizeApprovalLevel((array) data_get($state, 'level_one', [])),
                'level_two' => $this->normalizeApprovalLevel((array) data_get($state, 'level_two', [])),
            ];
        }

        $legacy = $this->normalizeApprovalLevel($state);

        return [
            'level_one' => $legacy,
            'level_two' => $this->normalizeApprovalLevel([]),
        ];
    }

    private function normalizeApprovalLevel(array $state): array
    {
        return [
            'approved' => !empty($state['approved']),
            'approved_at' => data_get($state, 'approved_at'),
            'approved_by_user_id' => data_get($state, 'approved_by_user_id'),
            'approved_by_name' => data_get($state, 'approved_by_name'),
            'approved_by_role' => data_get($state, 'approved_by_role'),
            'approved_until' => data_get($state, 'approved_until'),
        ];
    }

    private function isLevelApproved(array $level): bool
    {
        return !empty($level['approved'])
            && !empty($level['approved_until'])
            && Carbon::parse((string) $level['approved_until'])->isFuture();
    }

    private function normalizeApprovalLevelKey(string $level): string
    {
        return strtolower($level) === 'level_two' ? 'level_two' : 'level_one';
    }

    private function normalizeHexColor(string $value, string $fallback): string
    {
        $candidate = trim($value);

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $candidate) === 1) {
            return strtoupper($candidate);
        }

        return $fallback;
    }

    private function initials(string $name): string
    {
        $parts = array_values(array_filter(explode(' ', trim($name))));
        if ($parts === []) {
            return 'MM';
        }

        $letters = collect($parts)
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : 'MM';
    }

    private function userRoleLabel(User $user): string
    {
        $role = $user->role?->value ?? (string) $user->role;

        return match ($role) {
            'advisor' => 'Assessor',
            'secretary' => 'Secretário',
            'mayor' => 'Prefeito',
            default => 'Admin',
        };
    }
}
