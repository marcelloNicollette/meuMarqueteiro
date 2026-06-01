<?php

namespace App\Console\Commands;

use App\Services\Support\MunicipalityCoverageExecutiveService;
use Illuminate\Console\Command;

class SnapshotMunicipalityCoverageAlerts extends Command
{
    protected $signature = 'municipalities:snapshot-coverage-alerts {period=daily : daily|weekly}';

    protected $description = 'Captura um snapshot periódico da central executiva de alertas para histórico gerencial.';

    public function __construct(
        private readonly MunicipalityCoverageExecutiveService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $period = strtolower((string) $this->argument('period'));
        if (!in_array($period, ['daily', 'weekly'], true)) {
            $this->error('Período inválido. Use daily ou weekly.');

            return self::INVALID;
        }

        $snapshot = $this->service->captureSnapshot($period);
        if (!$snapshot) {
            $this->warn('Tabela de snapshots ainda não existe. Rode php artisan migrate antes de capturar snapshots.');

            return self::SUCCESS;
        }

        $this->info('Snapshot ' . $period . ' capturado com sucesso.');

        return self::SUCCESS;
    }
}
