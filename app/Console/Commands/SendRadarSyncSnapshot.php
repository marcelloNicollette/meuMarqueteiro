<?php

namespace App\Console\Commands;

use App\Mail\RadarSyncSnapshotMail;
use App\Services\Radar\RadarSyncSnapshotService;
use App\Services\Support\RuntimeMailConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendRadarSyncSnapshot extends Command
{
    protected $signature = 'marqueteiro:send-radar-sync-snapshot
                            {period=daily : daily|weekly}
                            {--force : Envia mesmo se a rotina estiver desabilitada}
                            {--to=* : Destinatarios adicionais para este disparo}';

    protected $description = 'Envia por e-mail o snapshot operacional do sync do Radar de Recursos.';

    public function __construct(
        private readonly RadarSyncSnapshotService $snapshotService,
        private readonly RuntimeMailConfigService $runtimeMail,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $period = strtolower((string) $this->argument('period'));

        if (!in_array($period, ['daily', 'weekly'], true)) {
            $this->error('Periodo invalido. Use daily ou weekly.');

            return self::INVALID;
        }

        if (!$this->option('force')) {
            if (!$this->snapshotService->snapshotEnabled()) {
                $this->warn('Snapshot operacional do Radar esta desabilitado.');

                return self::SUCCESS;
            }

            if ($period === 'daily' && !$this->snapshotService->dailyEnabled()) {
                $this->warn('Snapshot diario do Radar esta desabilitado.');

                return self::SUCCESS;
            }

            if ($period === 'weekly' && !$this->snapshotService->weeklyEnabled()) {
                $this->warn('Snapshot semanal do Radar esta desabilitado.');

                return self::SUCCESS;
            }
        }

        $recipients = collect(array_merge(
            $this->snapshotService->recipientsFromSettings(),
            array_map('trim', (array) $this->option('to'))
        ))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            $this->warn('Nenhum destinatario configurado para o snapshot do Radar.');

            return self::SUCCESS;
        }

        try {
            $snapshot = $this->snapshotService->buildSnapshot($period);
            $this->runtimeMail->send($recipients, new RadarSyncSnapshotMail($snapshot));

            Log::info('Snapshot operacional do Radar enviado por e-mail.', [
                'period' => $period,
                'recipients' => $recipients,
                'summary' => $snapshot['summary'],
            ]);

            $this->info('Snapshot operacional do Radar enviado com sucesso.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar snapshot operacional do Radar.', [
                'period' => $period,
                'recipients' => $recipients,
                'error' => $e->getMessage(),
            ]);

            $this->error('Falha ao enviar snapshot operacional do Radar: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
