<?php

namespace App\Console\Commands;

use App\Models\Municipality;
use App\Services\Projects\ProjectBankLibraryService;
use Illuminate\Console\Command;

class BootstrapProjectBank extends Command
{
    protected $signature = 'project-bank:bootstrap
        {--municipality= : ID especifico do município}
        {--force : Regera as teses mesmo se o banco ja tiver 10 ou mais itens}';

    protected $description = 'Carrega a biblioteca inicial do Banco de Projetos para municípios elegiveis';

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

        $this->info("Carregando biblioteca do Banco de Projetos para {$municipalities->count()} município(s)...");

        foreach ($municipalities as $municipality) {
            try {
                $theses = $service->ensureLibraryForMunicipality($municipality, force: $force, reason: 'bootstrap');
                $this->line(" ✓ {$municipality->name} — {$theses->count()} tese(s)");
            } catch (\Throwable $e) {
                $this->error(" ✗ {$municipality->name} — {$e->getMessage()}");
            }
        }

        $this->info('Carga do Banco de Projetos concluida.');

        return self::SUCCESS;
    }
}
