<?php

namespace App\Console;

use App\Models\SystemSetting;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleRegistrar
{
    public function register(Schedule $schedule): void
    {
        $schedule->command('briefings:generate')
            ->everyFifteenMinutes()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Falha na geração dos briefings matinais.');
            });

        // ── Radar de Recursos ────────────────────────────────────────────
        $schedule->command('marqueteiro:sync-federal-programs')
            ->weeklyOn(1, '04:00')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Sync do Radar de Recursos concluído com sucesso.');
            })
            ->onFailure(function () {
                \Log::error('Falha na sincronização do Radar de Recursos.');
            });

        $dailyRadarSnapshotTime = (string) SystemSetting::get('radar_sync_snapshot_daily_time', config('radar.sync_snapshot.daily_time', '08:10'));
        $weeklyRadarSnapshotDay = (int) SystemSetting::get('radar_sync_snapshot_weekly_day', config('radar.sync_snapshot.weekly_day', 1));
        $weeklyRadarSnapshotTime = (string) SystemSetting::get('radar_sync_snapshot_weekly_time', config('radar.sync_snapshot.weekly_time', '08:30'));

        $schedule->command('marqueteiro:send-radar-sync-snapshot daily')
            ->dailyAt($dailyRadarSnapshotTime)
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Falha no envio do snapshot diario do Radar.');
            });

        $schedule->command('marqueteiro:send-radar-sync-snapshot weekly')
            ->weeklyOn($weeklyRadarSnapshotDay, $weeklyRadarSnapshotTime)
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Falha no envio do snapshot semanal do Radar.');
            });

        // ── Sincronização de Dados Públicos ──────────────────────────────
        $schedule->command('marqueteiro:ingest')
            ->weeklyOn(1, '03:00')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::error('Falha na ingestão semanal de dados públicos.');
            });

        // ── Monitoramento de Menções ─────────────────────────────────────
        $schedule->command('marqueteiro:monitor-mentions')
            ->everyTwoHours()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no monitoramento de menções.');
            });

        // ── Resolve ai ────────────────────────────────────────────────────
        $schedule->command('resolve-ai:dispatch-alerts')
            ->hourly()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no disparo dos alertas do Resolve ai.');
            });

        $schedule->command('project-bank:dispatch-alerts')
            ->hourly()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no disparo dos alertas do Banco de Projetos.');
            });

        $schedule->command('project-bank:refresh-libraries')
            ->dailyAt('06:20')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha na curadoria periodica do Banco de Projetos.');
            });

        // ── URLs Monitoradas ─────────────────────────────────────────────
        $schedule->command('marqueteiro:index-urls')
            ->dailyAt('05:00')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha na re-indexação de URLs monitoradas.');
            });

        // ── Limpeza de embeddings ────────────────────────────────────────
        $schedule->call(function () {
            \App\Models\DocumentEmbedding::whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->delete();
        })->daily()->at('02:00');

        // ── Audio do chat ────────────────────────────────────────────────
        $schedule->command('marqueteiro:prune-chat-audio')
            ->hourly()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha na limpeza dos temporarios de audio do chat.');
            });

        // ── Telescope (dev) ──────────────────────────────────────────────
        $schedule->command('telescope:prune --hours=48')->daily();
    }
}
