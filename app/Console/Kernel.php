<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Agendamento de tarefas automáticas do sistema.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── Briefing Matinal ─────────────────────────────────────────────
        // Gerado todo dia às 6h30 (BRT) para todos os municípios ativos
        $hour   = config('ai.morning_briefing.hour', 6);
        $minute = config('ai.morning_briefing.minute', 30);

        $schedule->command('briefings:generate')
            ->dailyAt("{$hour}:{$minute}")
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Falha na geração dos briefings matinais.');
            });

        // ── Radar de Programas Federais ───────────────────────────────────
        // Transferegov + Portal da Transparência + análise Claude
        // Semanal às segundas, 04h (após ingestão de dados)
        $schedule->command('marqueteiro:sync-federal-programs')
            ->weeklyOn(1, '04:00')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Sync de programas federais concluído com sucesso.');
            })
            ->onFailure(function () {
                \Log::error('Falha na sincronização de programas federais.');
            });

        // ── Sincronização de Dados Públicos ───────────────────────────────
        // Ingestão completa: semanal às segundas, 3h
        $schedule->command('marqueteiro:ingest')
            ->weeklyOn(1, '03:00')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Falha na ingestão semanal de dados públicos.');
            });

        // ── Limpeza ───────────────────────────────────────────────────────
        // Remover embeddings expirados
        $schedule->call(function () {
            \App\Models\DocumentEmbedding::whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->delete();
        })->daily()->at('02:00');

        // Snapshots do Telescope (dev)
        $schedule->command('telescope:prune --hours=48')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
