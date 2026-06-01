<?php

namespace App\Console\Commands;

use App\Services\Support\MunicipalityCoverageAlertService;
use Illuminate\Console\Command;

class DispatchMunicipalityCoverageOwnerWarnings extends Command
{
    protected $signature = 'municipalities:dispatch-owner-deadline-warnings';

    protected $description = 'Dispara notificações de vencimento iminente ou atraso do SLA do owner nos alertas de cobertura.';

    public function __construct(
        private readonly MunicipalityCoverageAlertService $alerts,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $processed = $this->alerts->dispatchOwnerDeadlineWarnings();

        $this->info("Notificações de owner processadas: {$processed}");

        return self::SUCCESS;
    }
}
