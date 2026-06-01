<?php

namespace App\Console\Commands;

use App\Services\Support\MunicipalityCoverageExecutiveMailService;
use Illuminate\Console\Command;

class SendMunicipalityCoverageExecutiveRankingMail extends Command
{
    protected $signature = 'municipalities:send-executive-ranking-mail
                            {period=daily : daily|weekly}
                            {--force : Envia mesmo se a rotina estiver desabilitada}
                            {--to=* : Destinatarios adicionais para este disparo}';

    protected $description = 'Envia o mailing gerencial periódico do ranking executivo de cobertura municipal.';

    public function __construct(
        private readonly MunicipalityCoverageExecutiveMailService $service,
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
            if (!$this->service->enabled()) {
                $this->warn('Mailing executivo de cobertura esta desabilitado.');

                return self::SUCCESS;
            }

            if ($period === 'daily' && !$this->service->dailyEnabled()) {
                $this->warn('Mailing diario de cobertura esta desabilitado.');

                return self::SUCCESS;
            }

            if ($period === 'weekly' && !$this->service->weeklyEnabled()) {
                $this->warn('Mailing semanal de cobertura esta desabilitado.');

                return self::SUCCESS;
            }

            if (!$this->service->canDispatch($period)) {
                $this->warn('Mailing executivo aguardando aprovação manual para este período.');

                return self::SUCCESS;
            }
        }

        $sent = $this->service->dispatch($period, (array) $this->option('to'));

        $this->info($sent
            ? 'Mailing gerencial do ranking executivo enviado com sucesso.'
            : 'Nenhum destinatario valido configurado para o mailing executivo.');

        return self::SUCCESS;
    }
}
