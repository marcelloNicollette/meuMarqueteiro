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
        app(ScheduleRegistrar::class)->register($schedule);
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
