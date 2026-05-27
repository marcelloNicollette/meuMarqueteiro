<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum ResourceOpportunityStatus: string
{
    case PendingReview = 'pending_review';
    case Published = 'published';
    case ClosingSoon = 'closing_soon';
    case Monitoring = 'monitoring';
    case ClosedRecently = 'closed_recently';
    case Archived = 'archived';
    case Reopened = 'reopened';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pendente de validacao',
            self::Published => 'Publicado',
            self::ClosingSoon => 'Encerrando em breve',
            self::Monitoring => 'Em monitoramento',
            self::ClosedRecently => 'Encerrado recentemente',
            self::Archived => 'Arquivado',
            self::Reopened => 'Reaberto',
            self::Rejected => 'Rejeitado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PendingReview => 'warning',
            self::Published => 'success',
            self::ClosingSoon => 'attention',
            self::Monitoring => 'info',
            self::ClosedRecently => 'muted',
            self::Archived => 'muted',
            self::Reopened => 'success',
            self::Rejected => 'danger',
        };
    }

    public static function userVisible(): array
    {
        return [
            self::Published->value,
            self::ClosingSoon->value,
            self::Monitoring->value,
            self::ClosedRecently->value,
            self::Reopened->value,
        ];
    }

    public static function activeForRadar(): array
    {
        return [
            self::Published->value,
            self::ClosingSoon->value,
            self::Monitoring->value,
            self::Reopened->value,
        ];
    }

    public static function actionableForProjects(): array
    {
        return [
            self::Published->value,
            self::ClosingSoon->value,
            self::Monitoring->value,
            self::Reopened->value,
        ];
    }

    public static function normalize(
        ?string $status,
        mixed $deadline = null,
        ?CarbonInterface $now = null,
    ): string {
        $now ??= now();

        $canonical = match ($status) {
            self::PendingReview->value,
            self::Published->value,
            self::ClosingSoon->value,
            self::Monitoring->value,
            self::ClosedRecently->value,
            self::Archived->value,
            self::Reopened->value,
            self::Rejected->value => $status,
            'open', 'applied', 'approved' => self::Published->value,
            'closing' => self::ClosingSoon->value,
            'closed' => self::ClosedRecently->value,
            'historical', 'low_priority' => self::Monitoring->value,
            'rejected_legacy' => self::Rejected->value,
            default => self::Monitoring->value,
        };

        if ($deadline instanceof CarbonInterface) {
            $daysLeft = $now->diffInDays($deadline, false);

            if ($daysLeft < 0) {
                return abs($daysLeft) <= 60
                    ? self::ClosedRecently->value
                    : self::Archived->value;
            }

            if ($daysLeft <= 30 && in_array($canonical, [
                self::Published->value,
                self::Monitoring->value,
                self::Reopened->value,
            ], true)) {
                return self::ClosingSoon->value;
            }
        }

        return $canonical;
    }

    public static function tryFromNormalized(?string $status, mixed $deadline = null): self
    {
        return self::from(self::normalize($status, $deadline));
    }

    public static function labelFor(?string $status, mixed $deadline = null): string
    {
        return self::tryFromNormalized($status, $deadline)->label();
    }
}
