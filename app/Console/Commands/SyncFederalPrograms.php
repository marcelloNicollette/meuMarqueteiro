<?php

namespace App\Console\Commands;

use App\Models\Municipality;
use App\Services\FederalPrograms\FederalProgramSyncService;
use Illuminate\Console\Command;

class SyncFederalPrograms extends Command
{
    protected $signature = 'marqueteiro:sync-federal-programs
                                {--municipality= : ID ou nome do município (omitir = todos)}
                                {--force         : Re-analisa programas já existentes}
                                {--dry-run       : Mostra o que seria sincronizado sem salvar}';

    protected $description = 'Sincroniza oportunidades do Radar de Recursos via Portal da Transparência e avalia compatibilidade';

    public function __construct(private FederalProgramSyncService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Municipality::query();

        if ($id = $this->option('municipality')) {
            // Com --municipality=ID: busca pelo ID ou nome, ignora status da assinatura
            // (útil para testes e uso admin)
            $query->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('name', 'like', "%{$id}%");
            });
        } else {
            // Sem filtro: só processa municípios com assinatura ativa
            $query->where('subscription_active', true);
        }

        $municipalities = $query->get();

        if ($municipalities->isEmpty()) {
            $this->warn('Nenhum município encontrado com os critérios informados.');
            $this->line('  Dica: use --municipality=ID para forçar um município específico.');
            return 1;
        }

        $this->info("🔍 Iniciando sync do Radar de Recursos para {$municipalities->count()} município(s)...");
        $this->newLine();

        $totalSalvos = 0;

        foreach ($municipalities as $municipality) {
            $this->line("  <fg=cyan>→ {$municipality->name} / {$municipality->state_code}</> (IBGE: {$municipality->ibge_code})");

            try {
                $result = $this->service->sync(
                    municipality: $municipality,
                    force: $this->option('force'),
                    dryRun: $this->option('dry-run'),
                    output: $this->output,
                );

                $this->line("    <fg=green>✓</> {$result['novos']} novos  |  {$result['atualizados']} atualizados  |  {$result['descartados']} sem elegibilidade");
                $sourceSummary = collect($result['source_runs'] ?? [])
                    ->map(fn (array $sourceRun) => ($sourceRun['source_name'] ?? $sourceRun['source_key'] ?? 'fonte') . '=' . (int) ($sourceRun['records_fetched'] ?? 0))
                    ->implode(' | ');
                $this->line("    Fontes: " . ($sourceSummary !== '' ? $sourceSummary : 'nenhuma'));
                $totalSalvos += $result['novos'] + $result['atualizados'];
            } catch (\Exception $e) {
                $this->error("    Erro em {$municipality->name}: " . $e->getMessage());
                \Log::error("sync-federal-programs [{$municipality->id}]: " . $e->getMessage());
            }

            $this->newLine();
        }

        $this->info("✅ Concluído. Total: {$totalSalvos} programa(s) criados/atualizados.");
        return 0;
    }
}
