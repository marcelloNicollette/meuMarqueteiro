<?php

namespace App\Console\Commands;

use App\Models\Municipality;
use App\Services\Projects\ProjectBankLibraryService;
use Illuminate\Console\Command;

class RefreshProjectBankLibraries extends Command
{
    protected $signature = 'project-bank:refresh-libraries
        {--municipality= : ID especifico do municipio}
        {--stale-days=7 : Dias maximos sem curadoria antes de forcar refresh}
        {--force : Regera para todos os municipios elegiveis, ignorando o criterio de staleness}';

    protected $description = 'Atualiza periodicamente a biblioteca do Banco de Projetos para municipios onboarded.';

    public function handle(ProjectBankLibraryService $service): int
    {
        $query = Municipality::query()
            ->where('subscription_active', true)
            ->where('onboarding_status', 'completed');

        if ($id = $this->option('municipality')) {
            $query->where('id', $id);
        }

        $municipalities = $query->get();
        $force = (bool) $this->option('force');
        $staleDays = max((int) $this->option('stale-days'), 1);
        $processed = 0;
        $skipped = 0;

        $this->info("Verificando curadoria do Banco de Projetos para {$municipalities->count()} municipio(s)...");

        foreach ($municipalities as $municipality) {
            $shouldRefresh = $force || $service->needsPeriodicRefresh($municipality, $staleDays);

            if (!$shouldRefresh) {
                $skipped++;
                $this->line(" - {$municipality->name} — sem refresh necessario");
                continue;
            }

            try {
                $theses = $service->ensureLibraryForMunicipality(
                    $municipality,
                    force: true,
                    reason: $force ? 'forced_refresh' : 'scheduled_refresh'
                );

                $processed++;
                $this->line(" ✓ {$municipality->name} — {$theses->count()} tese(s) curadas");
            } catch (\Throwable $e) {
                $this->error(" ✗ {$municipality->name} — {$e->getMessage()}");
            }
        }

        $this->info("Curadoria concluida. Atualizados: {$processed}. Sem refresh: {$skipped}.");

        return self::SUCCESS;
    }
}
