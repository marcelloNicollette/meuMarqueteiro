<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MunicipalityCoverageExecutiveRankingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $payload,
        public readonly string $pdfContent,
        public readonly string $pdfFilename,
    ) {}

    public function build(): self
    {
        $summary = $this->payload['summary'];
        $periodLabel = match ($this->payload['period'] ?? 'manual') {
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            default => 'Executivo',
        };

        return $this->subject(
            "Cobertura municipal — Ranking {$periodLabel} ({$summary['average_executive_score']}% score medio, {$summary['active_alerts']} alertas ativos)"
        )
            ->view('emails.coverage-alerts.executive-ranking')
            ->attachData($this->pdfContent, $this->pdfFilename, [
                'mime' => 'application/pdf',
            ]);
    }
}
