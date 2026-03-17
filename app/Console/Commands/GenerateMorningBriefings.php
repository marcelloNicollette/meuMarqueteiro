<?php

namespace App\Console\Commands;

use App\Models\Municipality;
use App\Services\AI\MorningBriefingService;
use Illuminate\Console\Command;

class GenerateMorningBriefings extends Command
{
    protected $signature   = 'briefings:generate {--municipality= : ID específico de município}';
    protected $description = 'Gera o briefing matinal para todos os municípios ativos';

    public function handle(MorningBriefingService $service): int
    {
        $query = Municipality::where('subscription_active', true)
            ->where('onboarding_status', 'completed')
            ->with('mayor');

        if ($id = $this->option('municipality')) {
            $query->where('id', $id);
        }

        $municipalities = $query->get();

        $this->info("Gerando briefings para {$municipalities->count()} município(s)...");
        $bar = $this->output->createProgressBar($municipalities->count());

        $errors = [];

        foreach ($municipalities as $municipality) {
            try {
                // Verificar se já foi gerado hoje
                $existing = $municipality->morningBriefings()
                    ->whereDate('date', today())
                    ->exists();

                if ($existing) {
                    $this->line(" ↩ {$municipality->name} — briefing já gerado hoje.");
                    $bar->advance();
                    continue;
                }

                $briefing = $service->generate($municipality);

                $this->line(" ✓ {$municipality->name} — {$briefing->tokens_used} tokens");
            } catch (\Throwable $e) {
                $errors[] = "{$municipality->name}: {$e->getMessage()}";
                $this->error(" ✗ {$municipality->name} — {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (!empty($errors)) {
            $this->warn(count($errors) . " erro(s) durante a geração.");
        } else {
            $this->info('Todos os briefings foram gerados com sucesso!');
        }

        return self::SUCCESS;
    }
}
