<?php

namespace App\Console\Commands;

use App\Models\Municipality;
use App\Services\DataIngestion\SICONFIService;
use App\Services\DataIngestion\FNDEService;
use App\Services\DataIngestion\IBGEService;
use Illuminate\Console\Command;

class SyncPublicData extends Command
{
    protected $signature   = 'data:sync {--source=all : siconfi|fnde|ibge|all} {--municipality= : ID específico}';
    protected $description = 'Sincroniza dados públicos municipais (SICONFI, FNDE, IBGE)';

    public function handle(
        SICONFIService $siconfi,
        FNDEService    $fnde,
        IBGEService    $ibge,
    ): int {
        $source = $this->option('source');

        $municipalities = Municipality::where('subscription_active', true)
            ->when($this->option('municipality'), fn($q, $id) => $q->where('id', $id))
            ->get();

        $this->info("Sincronizando dados para {$municipalities->count()} município(s) — fonte: {$source}");

        foreach ($municipalities as $municipality) {
            $this->line("\n📍 {$municipality->name} — {$municipality->ibge_code}");

            try {
                if (in_array($source, ['siconfi', 'all'])) {
                    $siconfi->sync($municipality);
                    $this->line("  ✓ SICONFI");
                }

                if (in_array($source, ['fnde', 'all'])) {
                    $fnde->sync($municipality);
                    $this->line("  ✓ FNDE");
                }

                if (in_array($source, ['ibge', 'all'])) {
                    $ibge->sync($municipality);
                    $this->line("  ✓ IBGE");
                }

                $municipality->update(['data_last_synced_at' => now()]);

            } catch (\Throwable $e) {
                $this->error("  ✗ Erro: {$e->getMessage()}");
                report($e);
            }
        }

        $this->newLine();
        $this->info('Sincronização concluída!');

        return self::SUCCESS;
    }
}
