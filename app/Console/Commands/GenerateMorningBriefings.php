<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AI\MorningBriefingService;
use Illuminate\Console\Command;

class GenerateMorningBriefings extends Command
{
    protected $signature = 'briefings:generate
        {--municipality= : ID especifico de município}
        {--user= : ID especifico de usuario}
        {--force : Regenera mesmo se o briefing de hoje ja existir}';
    protected $description = 'Gera o briefing Pra Hoje para usuarios elegiveis';

    public function handle(MorningBriefingService $service): int
    {
        $query = User::query()
            ->municipalOperators()
            ->whereHas('municipality', function ($builder) {
                $builder
                    ->where('subscription_active', true)
                    ->where('onboarding_status', 'completed');
            })
            ->with('municipality');

        if ($id = $this->option('municipality')) {
            $query->where('municipality_id', $id);
        }

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        $this->info("Avaliando {$users->count()} usuario(s) para o Pra Hoje...");
        $bar = $this->output->createProgressBar($users->count());

        $errors = [];
        $force = (bool) $this->option('force');

        foreach ($users as $user) {
            try {
                if (!$force && !$service->shouldGenerateForUser($user, now('America/Sao_Paulo'))) {
                    $this->line(" ↩ {$user->name} — fora da janela ou ja gerado hoje.");
                    $bar->advance();
                    continue;
                }

                $briefing = $service->generateForUser($user, force: $force);
                $cardCount = is_array($briefing->cards) ? count($briefing->cards) : 0;

                $this->line(" ✓ {$user->name} — {$cardCount} card(s)");
            } catch (\Throwable $e) {
                $errors[] = "{$user->name}: {$e->getMessage()}";
                $this->error(" ✗ {$user->name} — {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (!empty($errors)) {
            $this->warn(count($errors) . " erro(s) durante a geração.");
        } else {
            $this->info('Ciclo do Pra Hoje concluido com sucesso.');
        }

        return self::SUCCESS;
    }
}
