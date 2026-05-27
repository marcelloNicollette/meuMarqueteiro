<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RadarSyncSnapshotMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $snapshot,
    ) {}

    public function build(): self
    {
        $summary = $this->snapshot['summary'];
        $periodLabel = $this->snapshot['period'] === 'weekly' ? 'Semanal' : 'Diario';

        $mail = $this->subject(
            "Radar de Recursos — Snapshot {$periodLabel} ({$summary['failed']} falhas, {$summary['stale']} stale, {$summary['retried']} retries)"
        )->view('emails.radar.sync-snapshot');

        foreach ($this->snapshot['attachments'] as $attachment) {
            $mail->attachData(
                $attachment['content'],
                $attachment['name'],
                ['mime' => $attachment['mime']]
            );
        }

        return $mail;
    }
}
