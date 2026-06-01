<?php

namespace App\Services\Support;

use App\Mail\MunicipalityCoverageExecutiveRankingMail;
use Illuminate\Support\Facades\Log;

class MunicipalityCoverageExecutiveMailService
{
    public function __construct(
        private readonly MunicipalityCoverageExecutiveReportService $report,
        private readonly MunicipalityCoverageExecutiveMailGovernanceService $governance,
        private readonly RuntimeMailConfigService $mail,
    ) {}

    public function dispatch(string $period = 'daily', array $extraRecipients = []): bool
    {
        $recipients = collect(array_merge(
            $this->recipientsFromSettings(),
            $extraRecipients
        ))
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            return false;
        }

        $limit = $this->rankingLimit();
        $approvalContext = $this->governance->approvalForPeriod($period);
        $payload = $this->report->buildPayload($period, $limit, $approvalContext);
        $pdfFilename = $this->report->pdfFilename($period);
        $pdfContent = $this->report->pdfBinary($period, $limit, $approvalContext);

        try {
            $this->mail->send(
                $recipients,
                new MunicipalityCoverageExecutiveRankingMail($payload, $pdfContent, $pdfFilename)
            );

            $this->governance->consumeApproval($period);

            Log::info('Mailing gerencial do ranking executivo enviado.', [
                'period' => $period,
                'recipients' => $recipients,
                'ranking_limit' => $limit,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Falha no mailing gerencial do ranking executivo.', [
                'period' => $period,
                'recipients' => $recipients,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enabled(): bool
    {
        return $this->governance->settings()['enabled'];
    }

    public function dailyEnabled(): bool
    {
        return $this->governance->settings()['daily_enabled'];
    }

    public function weeklyEnabled(): bool
    {
        return $this->governance->settings()['weekly_enabled'];
    }

    public function recipientsFromSettings(): array
    {
        return $this->governance->recipients();
    }

    public function rankingLimit(): int
    {
        return $this->governance->rankingLimit();
    }

    public function canDispatch(string $period): bool
    {
        return $this->governance->canDispatch($period);
    }
}
