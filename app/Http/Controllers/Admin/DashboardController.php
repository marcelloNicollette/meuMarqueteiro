<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Support\MunicipalityConfigurationStatusService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MunicipalityConfigurationStatusService $configurationStatus,
    ) {}

    public function index(): View
    {
        $stats = [
            'total_municipalities'  => Municipality::count(),
            'active_subscriptions'  => Municipality::where('subscription_active', true)->count(),
            'onboarding_pending'    => Municipality::where('onboarding_status', 'pending')->count(),
            'mayors_total'          => User::where('role', 'mayor')->count(),
            'mayors_active_today'   => User::where('role', 'mayor')
                ->whereDate('last_login_at', today())
                ->count(),
        ];

        $recentMunicipalities = Municipality::with('mayor')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $onboardingInProgress = Municipality::where('onboarding_status', 'in_progress')
            ->get();

        $pendingOnboarding = Municipality::where('onboarding_status', 'pending')->count();
        $activeMunicipalities = Municipality::query()
            ->where('subscription_active', true)
            ->with('mayor')
            ->orderBy('name')
            ->get();
        $configReadiness = $this->configurationStatus->summarizeCollection($activeMunicipalities);
        $configStats = $this->configurationStatus->aggregate($configReadiness);
        $configAlerts = $configReadiness
            ->whereIn('status', ['warning', 'critical'])
            ->sortBy('score')
            ->take(5)
            ->values();

        return view('admin.dashboard', compact(
            'stats',
            'recentMunicipalities',
            'onboardingInProgress',
            'pendingOnboarding',
            'configStats',
            'configAlerts'
        ));
    }
}
