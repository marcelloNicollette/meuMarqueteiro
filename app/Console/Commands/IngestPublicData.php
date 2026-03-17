<?php

namespace App\Console\Commands;

use App\Jobs\IngestPublicDataJob;
use App\Models\Municipality;
use App\Services\DataIngestion\DataIngestionOrchestrator;
use Illuminate\Console\Command;

class IngestPublicData extends Command
{
    protected $signature   = 'marqueteiro:ingest
                                {--municipality= : ID ou nome do município (omitir = todos)}
                                {--sync : Executa de forma síncrona (sem fila)}';

    protected $description = 'Busca dados públicos (IBGE, SICONFI, FNDE...) e indexa no RAG';

    public function handle(DataIngestionOrchestrator $orchestrator): int
    {
        $query = Municipality::where('subscription_active', true);

        if ($id = $this->option('municipality')) {
            $query->where('id', $id)->orWhere('name', 'like', "%{$id}%");
        }

        $municipalities = $query->get();

        if ($municipalities->isEmpty()) {
            $this->warn('Nenhum município ativo encontrado.');
            return 1;
        }

        $this->info("Iniciando ingestão para {$municipalities->count()} município(s)...");

        foreach ($municipalities as $m) {
            if ($this->option('sync')) {
                $this->line("  → Processando {$m->name} de forma síncrona...");
                $report = $orchestrator->ingest($m);
                $this->displayReport($report);
            } else {
                IngestPublicDataJob::dispatch($m);
                $this->line("  → Job enfileirado para {$m->name}");
            }
        }

        $this->info('Concluído.');
        return 0;
    }

    private function displayReport(array $report): void
    {
        if ($report['status'] === 'nenhuma_api_ativa') {
            $this->warn("    Nenhuma API ativa para {$report['municipio']}");
            return;
        }

        $this->info("    {$report['municipio']}: {$report['total_indexados']} chunks indexados");

        foreach ($report['chunks'] as $api => $count) {
            $this->line("      {$api}: {$count} blocos coletados");
        }

        foreach ($report['erros'] as $api => $erro) {
            $this->error("      Erro em {$api}: {$erro}");
        }
    }
}
