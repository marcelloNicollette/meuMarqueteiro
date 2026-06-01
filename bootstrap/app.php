<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'                 => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'           => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'   => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'municipality.onboarded' => \App\Http\Middleware\EnsureMunicipalityOnboarded::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();
            $roleValue = $user?->role?->value ?? (string) $user?->role;

            return match ($roleValue) {
                'admin' => route('admin.dashboard'),
                'mayor' => route('mayor.chat.index'),
                'secretary', 'advisor' => route('resolve-ai.demands.index'),
                default => '/',
            };
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        app(\App\Console\ScheduleRegistrar::class)->register($schedule);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
