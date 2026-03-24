<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Mayor;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// AUTENTICAÇÃO
// ─────────────────────────────────────────────────────────────────────────────

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ─────────────────────────────────────────────────────────────────────────────
// ÁREA DO ADMINISTRADOR (Consultor / Back-office)
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])
            ->name('dashboard');

        // Gestão de Municípios (clientes)
        Route::resource('municipalities', Admin\MunicipalityController::class);
        Route::patch('municipalities/{municipality}/toggle', [Admin\MunicipalityController::class, 'toggleActive'])
            ->name('municipalities.toggle');

        // Onboarding
        Route::prefix('municipalities/{municipality}/onboarding')
            ->name('municipalities.onboarding.')
            ->group(function () {
                Route::get('/',                [Admin\OnboardingController::class, 'show'])->name('show');
                Route::post('/documents',      [Admin\OnboardingController::class, 'uploadDocuments'])->name('documents');
                Route::post('/voice-profile',  [Admin\OnboardingController::class, 'saveVoiceProfile'])->name('voice-profile');
                Route::post('/political-map',  [Admin\OnboardingController::class, 'savePoliticalMap'])->name('political-map');
                Route::post('/complete',       [Admin\OnboardingController::class, 'complete'])->name('complete');
                Route::post('/ingest',         [Admin\OnboardingController::class, 'triggerDataIngestion'])->name('ingest');
            });

        // Gestão de usuários (prefeitos)
        Route::resource('users', Admin\UserController::class);
        Route::patch('users/{user}/toggle', [Admin\UserController::class, 'toggleActive'])
            ->name('users.toggle');

        // Monitor de integrações (SICONFI, FNDE, IBGE, etc.)
        Route::prefix('integrations')->name('integrations.')->group(function () {
            Route::get('/',                       [Admin\IntegrationMonitorController::class, 'index'])->name('index');
            Route::post('/sync-all',              [Admin\IntegrationMonitorController::class, 'syncAll'])->name('sync-all');
            Route::post('/{municipality}/sync',   [Admin\IntegrationMonitorController::class, 'sync'])->name('sync');
            Route::post('/{municipality}/sync-now', [Admin\IntegrationMonitorController::class, 'syncNow'])->name('sync-now');
        });

        // Radar de Programas Federais — painel admin + sync
        Route::prefix('federal-programs')->name('federal-programs.')->group(function () {
            Route::get('/',                              [Admin\FederalProgramsController::class, 'index'])->name('index');
            Route::post('/sync-all',                     [Admin\FederalProgramsController::class, 'syncAll'])->name('sync-all');
            Route::post('/{municipality}/sync',          [Admin\FederalProgramsController::class, 'syncMunicipality'])->name('sync');
            Route::get('/{municipality}/programs',       [Admin\FederalProgramsController::class, 'municipalityPrograms'])->name('programs');
            Route::delete('/{municipality}/clear',       [Admin\FederalProgramsController::class, 'clearMunicipality'])->name('clear');
        });

        // Base de conhecimento geral
        Route::prefix('knowledge-base')->name('knowledge-base.')->group(function () {
            Route::get('/',                  [Admin\KnowledgeBaseController::class, 'index'])->name('index');
            Route::post('/upload',           [Admin\KnowledgeBaseController::class, 'upload'])->name('upload');
            Route::get('/{doc}/chunks',      [Admin\KnowledgeBaseController::class, 'chunks'])->name('chunks');
            Route::patch('/{doc}/toggle',    [Admin\KnowledgeBaseController::class, 'toggleActive'])->name('toggle');
            Route::patch('/{doc}/reindex',   [Admin\KnowledgeBaseController::class, 'reindex'])->name('reindex');
            Route::delete('/{doc}',          [Admin\KnowledgeBaseController::class, 'destroy'])->name('destroy');
            Route::post('/cleanup',          [Admin\KnowledgeBaseController::class, 'cleanupOrphanEmbeddings'])->name('cleanup');
        });

        // Relatório de mandato
        Route::post('/municipalities/{municipality}/generate-report', [Admin\ReportController::class, 'generate'])
            ->name('municipalities.report');

        // Logs de uso por cliente
        Route::get('/municipalities/{municipality}/logs', [Admin\UsageLogController::class, 'index'])
            ->name('municipalities.logs');

        // Diagnóstico do sistema
        Route::prefix('diagnostic')->name('diagnostic.')->group(function () {
            Route::get('/',        [Admin\DiagnosticController::class, 'index'])->name('index');
            Route::post('/ai',     [Admin\DiagnosticController::class, 'testAI'])->name('test-ai');
            Route::post('/rag',    [Admin\DiagnosticController::class, 'testRAG'])->name('test-rag');
        });

        // Configurações do sistema
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/',                [Admin\SettingsController::class, 'index'])->name('index');
            Route::post('/ai',             [Admin\SettingsController::class, 'saveAI'])->name('ai');
            Route::post('/test',           [Admin\SettingsController::class, 'testConnection'])->name('test');
            Route::get('/integrations',    [Admin\SettingsController::class, 'integrations'])->name('integrations');
            Route::post('/integrations',   [Admin\SettingsController::class, 'saveIntegrations'])->name('integrations.save');
        });
    });

// ─────────────────────────────────────────────────────────────────────────────
// ÁREA DO PREFEITO
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:mayor', 'municipality.onboarded'])
    ->prefix('mayor')
    ->name('mayor.')
    ->group(function () {

        // Dashboard do prefeito
        Route::get('/dashboard', [Mayor\DashboardController::class, 'index'])
            ->name('dashboard');

        // Situacao do mandato
        Route::get('/situacao', [Mayor\SituacaoController::class, 'index'])
            ->name('situacao');

        // Web Push Notifications
        Route::prefix('push')->name('push.')->group(function () {
            Route::get('/vapid-key',      [Mayor\PushController::class, 'vapidPublicKey'])->name('vapid-key');
            Route::post('/subscribe',     [Mayor\PushController::class, 'subscribe'])->name('subscribe');
            Route::post('/unsubscribe',   [Mayor\PushController::class, 'unsubscribe'])->name('unsubscribe');
            Route::post('/test',          [Mayor\PushController::class, 'test'])->name('test');
        });

        // ── Módulo 1: Assistente Conversacional ───────────────────────────
        Route::prefix('chat')->name('chat.')->group(function () {
            Route::get('/',                    [ChatController::class, 'index'])->name('index');
            Route::post('/new',                [ChatController::class, 'create'])->name('create');
            Route::get('/{conversation}',      [ChatController::class, 'show'])->name('show');
            Route::delete('/{conversation}',   [ChatController::class, 'destroy'])->name('destroy');
            Route::post('/{conversation}/send', [ChatController::class, 'sendMessage'])->name('send');
            Route::post('/messages/{message}/feedback', [ChatController::class, 'feedback'])->name('feedback');
        });

        // ── Módulo 2: Comunicação e Marketing ─────────────────────────────
        Route::prefix('content')->name('content.')->group(function () {
            Route::get('/',                      [Mayor\ContentController::class, 'index'])->name('index');
            Route::post('/generate-post',        [Mayor\ContentController::class, 'generatePost'])->name('generate-post');
            Route::post('/interview-prep',       [Mayor\ContentController::class, 'interviewPrep'])->name('interview-prep');
            Route::post('/crisis-response',      [Mayor\ContentController::class, 'crisisResponse'])->name('crisis-response');
            Route::post('/generate-image',       [Mayor\ContentController::class, 'generateImage'])->name('generate-image');
            Route::get('/{content}',             [Mayor\ContentController::class, 'show'])->name('show');
            Route::put('/{content}',             [Mayor\ContentController::class, 'update'])->name('update');
            Route::post('/{content}/publish',    [Mayor\ContentController::class, 'publish'])->name('publish');
        });

        // ── Módulo 3: Gestão do Mandato ───────────────────────────────────
        Route::prefix('mandato')->name('mandato.')->group(function () {
            // Compromissos de campanha
            Route::resource('commitments', Mayor\CommitmentController::class);

            // Radar de programas federais
            Route::get('/federal-programs',           [Mayor\FederalProgramController::class, 'index'])->name('federal-programs');
            Route::post('/federal-programs/{program}/ask', [Mayor\FederalProgramController::class, 'askAssistant'])->name('federal-programs.ask');

            // Briefing matinal
            Route::get('/briefings',         [Mayor\BriefingController::class, 'index'])->name('briefings');
            Route::get('/briefings/{briefing}', [Mayor\BriefingController::class, 'show'])->name('briefings.show');
            Route::post('/briefings/mark-read/{briefing}', [Mayor\BriefingController::class, 'markRead'])->name('briefings.read');
            Route::post('/briefings/generate', [Mayor\BriefingController::class, 'generate'])->name('briefings.generate');

            // Registro de demandas por voz
            Route::post('/demands/voice',    [Mayor\DemandController::class, 'storeVoice'])->name('demands.voice');
            Route::resource('demands',       Mayor\DemandController::class);
        });
    });
