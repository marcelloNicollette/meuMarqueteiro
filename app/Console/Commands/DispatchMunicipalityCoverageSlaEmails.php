<?php

namespace App\Console\Commands;

use App\Services\Support\MunicipalityCoverageSlaMailService;
use Illuminate\Console\Command;

class DispatchMunicipalityCoverageSlaEmails extends Command
{
    protected $signature = 'municipalities:dispatch-coverage-sla-emails';

    protected $description = 'Dispara e-mails automáticos aos admins quando alertas de cobertura estouram o SLA.';

    public function __construct(
        private readonly MunicipalityCoverageSlaMailService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sent = $this->service->dispatch();

        $this->info($sent > 0
            ? $sent . ' alerta(s) fora do SLA enviados por e-mail.'
            : 'Nenhum alerta fora do SLA precisou de envio nesta execução.');

        return self::SUCCESS;
    }
}
