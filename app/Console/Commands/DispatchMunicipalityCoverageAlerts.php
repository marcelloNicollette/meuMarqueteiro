<?php

namespace App\Console\Commands;

use App\Services\Support\MunicipalityCoverageAlertService;
use Illuminate\Console\Command;

class DispatchMunicipalityCoverageAlerts extends Command
{
    protected $signature = 'municipalities:dispatch-coverage-alerts';
    protected $description = 'Dispara alertas automáticos quando Menções, Pra hoje ou Configurações perdem cobertura mínima.';

    public function __construct(
        private readonly MunicipalityCoverageAlertService $alerts,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $processed = $this->alerts->dispatchCoverageAlerts();

        $this->info("Coberturas verificadas: {$processed}");

        return self::SUCCESS;
    }
}
