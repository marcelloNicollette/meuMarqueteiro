<?php

namespace App\Services\Support;

use App\Mail\MunicipalityCoverageSlaBreachMail;
use App\Models\MunicipalityCoverageAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MunicipalityCoverageSlaMailService
{
    public function __construct(
        private readonly MunicipalityCoverageExecutiveService $executive,
        private readonly RuntimeMailConfigService $mail,
    ) {}

    public function dispatch(): int
    {
        $breaches = $this->executive->currentSlaBreaches()
            ->filter(fn (array $entry) => $this->shouldSend($entry['alert']))
            ->values();

        if ($breaches->isEmpty()) {
            return 0;
        }

        $recipients = User::query()
            ->admins()
            ->active()
            ->whereNotNull('email')
            ->pluck('email')
            ->filter(fn (?string $email) => filter_var((string) $email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            return 0;
        }

        $payload = [
            'generated_at' => now(),
            'summary' => [
                'total' => $breaches->count(),
                'high' => $breaches->filter(fn (array $entry) => $entry['alert']->severity === 'high')->count(),
                'municipalities' => $breaches->pluck('alert.municipality_id')->unique()->count(),
                'max_overdue_hours' => (float) ($breaches->max('hours_overdue') ?? 0),
            ],
            'breaches' => $breaches
                ->map(fn (array $entry) => [
                    'alert_id' => $entry['alert']->id,
                    'municipality_name' => $entry['alert']->municipality?->name ?? 'Municipio',
                    'title' => $entry['alert']->title,
                    'message' => (string) $entry['alert']->message,
                    'event_label' => $entry['event_label'],
                    'severity' => $entry['alert']->severity,
                    'target_hours' => $entry['target_hours'],
                    'hours_open' => $entry['hours_open'],
                    'hours_overdue' => $entry['hours_overdue'],
                    'action_url' => $entry['alert']->action_url,
                ])
                ->values()
                ->all(),
        ];

        try {
            $this->mail->send($recipients, new MunicipalityCoverageSlaBreachMail($payload));

            $breaches->each(function (array $entry) {
                /** @var MunicipalityCoverageAlert $alert */
                $alert = $entry['alert'];
                $metadata = is_array($alert->metadata) ? $alert->metadata : [];
                $metadata['sla_email_last_sent_at'] = now()->toIso8601String();
                $metadata['sla_email_sent_count'] = (int) ($metadata['sla_email_sent_count'] ?? 0) + 1;
                $metadata['sla_email_last_overdue_hours'] = $entry['hours_overdue'];
                $alert->update(['metadata' => $metadata]);
            });

            Log::info('Alerta de SLA de cobertura enviado por e-mail.', [
                'recipients' => $recipients,
                'total_breaches' => $payload['summary']['total'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar alerta de SLA de cobertura por e-mail.', [
                'error' => $e->getMessage(),
                'total_breaches' => $payload['summary']['total'],
            ]);

            return 0;
        }

        return $breaches->count();
    }

    private function shouldSend(MunicipalityCoverageAlert $alert): bool
    {
        $lastSentAt = data_get($alert->metadata, 'sla_email_last_sent_at');
        if (!$lastSentAt) {
            return true;
        }

        return Carbon::parse((string) $lastSentAt)->diffInHours(now()) >= 24;
    }
}
