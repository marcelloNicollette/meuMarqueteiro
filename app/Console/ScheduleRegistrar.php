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

        $dailyCoverageMailTime = (string) SystemSetting::get('coverage_executive_mail_daily_time', SystemSetting::defaults()['coverage_executive_mail_daily_time']);
        $weeklyCoverageMailDay = (int) SystemSetting::get('coverage_executive_mail_weekly_day', SystemSetting::defaults()['coverage_executive_mail_weekly_day']);
        $weeklyCoverageMailTime = (string) SystemSetting::get('coverage_executive_mail_weekly_time', SystemSetting::defaults()['coverage_executive_mail_weekly_time']);

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

        $schedule->command('municipalities:dispatch-coverage-alerts')
            ->hourly()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no disparo dos alertas de cobertura dos municipios.');
            });

        $schedule->command('municipalities:dispatch-coverage-sla-emails')
            ->hourly()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no disparo dos e-mails de SLA da cobertura dos municipios.');
            });

        $schedule->command('municipalities:dispatch-owner-deadline-warnings')
            ->hourly()
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no disparo das notificações de SLA do owner na cobertura dos municipios.');
            });

        $schedule->command('municipalities:snapshot-coverage-alerts daily')
            ->dailyAt('08:45')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha na captura do snapshot diario da central de cobertura.');
            });

        $schedule->command('municipalities:snapshot-coverage-alerts weekly')
            ->weeklyOn(1, '09:00')
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha na captura do snapshot semanal da central de cobertura.');
            });

        $schedule->command('municipalities:send-executive-ranking-mail daily')
            ->dailyAt($dailyCoverageMailTime)
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no mailing diario do ranking executivo de cobertura.');
            });

        $schedule->command('municipalities:send-executive-ranking-mail weekly')
            ->weeklyOn($weeklyCoverageMailDay, $weeklyCoverageMailTime)
            ->timezone('America/Sao_Paulo')
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                \Log::warning('Falha no mailing semanal do ranking executivo de cobertura.');
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
