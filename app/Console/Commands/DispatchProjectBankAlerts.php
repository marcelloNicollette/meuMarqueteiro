<?php

namespace App\Console\Commands;

use App\Services\Projects\ProjectBankNotificationService;
use Illuminate\Console\Command;

class DispatchProjectBankAlerts extends Command
{
    protected $signature = 'project-bank:dispatch-alerts';
    protected $description = 'Dispara alertas proativos do Banco de Projetos para teses urgentes com prazo proximo.';

    public function __construct(
        private readonly ProjectBankNotificationService $notifications,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $processed = $this->notifications->dispatchUrgencyAlerts();

        $this->info("Alertas processados: {$processed}");

        return self::SUCCESS;
    }
}
