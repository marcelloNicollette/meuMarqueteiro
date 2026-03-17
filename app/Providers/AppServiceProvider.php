<?php

namespace App\Providers;

use App\Http\Middleware\EnsureMunicipalityOnboarded;
use App\Listeners\UpdateLastLogin;
use App\Services\AI\AIProviderService;
use App\Services\AI\AssistantService;
use App\Services\AI\MorningBriefingService;
use App\Services\Communication\ContentGenerationService;
use App\Services\FederalPrograms\ClaudeMatchingService;
use App\Services\FederalPrograms\FederalProgramSyncService;
use App\Services\FederalPrograms\TransparenciaClient;
use App\Services\FederalPrograms\TransferegovClient;
use App\Services\RAG\RAGService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIProviderService::class);

        $this->app->singleton(
            RAGService::class,
            fn($app) =>
            new RAGService($app->make(AIProviderService::class))
        );

        $this->app->singleton(
            AssistantService::class,
            fn($app) =>
            new AssistantService(
                $app->make(AIProviderService::class),
                $app->make(RAGService::class),
            )
        );

        $this->app->singleton(
            MorningBriefingService::class,
            fn($app) =>
            new MorningBriefingService(
                $app->make(RAGService::class),
            )
        );

        $this->app->singleton(
            ContentGenerationService::class,
            fn($app) =>
            new ContentGenerationService($app->make(AIProviderService::class))
        );

        // ── Radar de Programas Federais ─────────────────────────────────
        $this->app->singleton(TransferegovClient::class);
        $this->app->singleton(TransparenciaClient::class);
        $this->app->singleton(ClaudeMatchingService::class);
        $this->app->singleton(
            FederalProgramSyncService::class,
            fn($app) =>
            new FederalProgramSyncService(
                $app->make(TransferegovClient::class),
                $app->make(TransparenciaClient::class),
                $app->make(ClaudeMatchingService::class),
            )
        );
    }

    public function boot(): void
    {
        // Middleware aliases registrados no bootstrap/app.php (Laravel 12)

        // Atualiza last_login_at e last_login_ip a cada login
        Event::listen(Login::class, UpdateLastLogin::class);
    }
}
