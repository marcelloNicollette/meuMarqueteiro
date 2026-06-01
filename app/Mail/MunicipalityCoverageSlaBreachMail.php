<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MunicipalityCoverageSlaBreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function build(): self
    {
        $summary = $this->payload['summary'];

        return $this->subject(
            'Cobertura municipal — SLA estourado (' . $summary['total'] . ' alerta(s), ' . $summary['municipalities'] . ' município(s))'
        )->view('emails.coverage-alerts.sla-breach');
    }
}
