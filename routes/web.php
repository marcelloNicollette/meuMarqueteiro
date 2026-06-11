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

Route::middleware('auth')
    ->prefix('mayor/chat')
    ->name('mayor.chat.')
    ->group(function () {
        Route::get('/shared/{shareToken}', [ChatController::class, 'showShared'])->name('shared.show');
    });

Route::middleware('auth')
    ->prefix('chat')
    ->name('chat.')
    ->group(function () {
        Route::get('/shared/{shareToken}', [ChatController::class, 'showShared'])->name('shared.show');
    });

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
        Route::post('municipalities/{municipality}/coverage-governance', [Admin\MunicipalityController::class, 'saveCoverageGovernance'])
            ->name('municipalities.coverage-governance');
        Route::post('municipalities/{municipality}/project-bank/refresh', [Admin\MunicipalityController::class, 'refreshProjectBank'])
            ->name('municipalities.project-bank.refresh');

        // Áreas de contato do município
        Route::prefix('municipalities/{municipality}/contact-areas')->name('municipalities.contact-areas.')->group(function () {
            Route::get('/', [Admin\ContactAreaController::class, 'index'])->name('index');
            Route::post('/', [Admin\ContactAreaController::class, 'store'])->name('store');
            Route::put('/{contactArea}', [Admin\ContactAreaController::class, 'update'])->name('update');
            Route::delete('/{contactArea}', [Admin\ContactAreaController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('municipalities/{municipality}/localities')->name('municipalities.localities.')->group(function () {
            Route::get('/', [Admin\MunicipalityLocalityController::class, 'index'])->name('index');
            Route::post('/', [Admin\MunicipalityLocalityController::class, 'store'])->name('store');
            Route::put('/{locality}', [Admin\MunicipalityLocalityController::class, 'update'])->name('update');
            Route::delete('/{locality}', [Admin\MunicipalityLocalityController::class, 'destroy'])->name('destroy');
        });

        // Onboarding
        Route::prefix('municipalities/{municipality}/onboarding')
            ->name('municipalities.onboarding.')
            ->group(function () {
                Route::get('/',                [Admin\OnboardingController::class, 'show'])->name('show');
                Route::post('/documents',      [Admin\OnboardingController::class, 'uploadDocuments'])->name('documents');
                Route::post('/mandato-commitments', [Admin\OnboardingController::class, 'saveMandateCommitments'])->name('mandato-commitments');
                Route::post('/municipality-profile', [Admin\OnboardingController::class, 'saveMunicipalityProfile'])->name('municipality-profile');
                Route::post('/voice-profile',  [Admin\OnboardingController::class, 'saveVoiceProfile'])->name('voice-profile');
                Route::post('/political-map',  [Admin\OnboardingController::class, 'savePoliticalMap'])->name('political-map');
                Route::post('/communication-context', [Admin\OnboardingController::class, 'saveCommunicationContext'])->name('communication-context');
                Route::post('/communication-settings', [Admin\OnboardingController::class, 'saveCommunicationSettings'])->name('communication-settings');
                Route::post('/notification-settings', [Admin\OnboardingController::class, 'saveNotificationSettings'])->name('notification-settings');
                Route::post('/resolve-ai-settings', [Admin\OnboardingController::class, 'saveResolveAiSettings'])->name('resolve-ai-settings');
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
            Route::post('/backfill-sources',             [Admin\FederalProgramsController::class, 'backfillSources'])->name('backfill-sources');
            Route::post('/sources/{source}/config',      [Admin\FederalProgramsController::class, 'updateSourceConfig'])->name('sources.config');
            Route::post('/curation/{entry}/assign',      [Admin\FederalProgramsController::class, 'assignCurationEntry'])->name('curation.assign');
            Route::post('/curation/{entry}/transition',  [Admin\FederalProgramsController::class, 'transitionCurationEntry'])->name('curation.transition');
            Route::post('/executions/reconcile',         [Admin\FederalProgramsController::class, 'reconcileExecutions'])->name('executions.reconcile');
            Route::post('/executions/retry-eligible',    [Admin\FederalProgramsController::class, 'retryEligibleExecutions'])->name('executions.retry-eligible');
            Route::post('/executions/{execution}/retry', [Admin\FederalProgramsController::class, 'retryExecution'])->name('executions.retry');
            Route::post('/{municipality}/sync',          [Admin\FederalProgramsController::class, 'syncMunicipality'])->name('sync');
            Route::get('/{municipality}/sync-status',    [Admin\FederalProgramsController::class, 'syncStatus'])->name('sync-status');
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



        // URLs Monitoradas (RAG) — base de conhecimento via web
        Route::prefix('monitored-urls')->name('monitored-urls.')->group(function () {
            Route::get('/',              [Admin\MonitoredUrlController::class, 'index'])->name('index');
            Route::post('/',             [Admin\MonitoredUrlController::class, 'store'])->name('store');
            Route::post('/{id}/reindex', [Admin\MonitoredUrlController::class, 'reindex'])->name('reindex');
            Route::post('/{id}/toggle',  [Admin\MonitoredUrlController::class, 'toggle'])->name('toggle');
            Route::delete('/{id}',       [Admin\MonitoredUrlController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/preview',  [Admin\MonitoredUrlController::class, 'preview'])->name('preview');
            Route::put('/{id}',          [Admin\MonitoredUrlController::class, 'update'])->name('update');
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
            Route::post('/audio',  [Admin\DiagnosticController::class, 'testAudio'])->name('test-audio');
        });

        // Central executiva de alertas de cobertura
        Route::prefix('coverage-alerts')->name('coverage-alerts.')->group(function () {
            Route::get('/', [Admin\MunicipalityCoverageAlertController::class, 'index'])->name('index');
            Route::get('/export.csv', [Admin\MunicipalityCoverageAlertController::class, 'exportCsv'])->name('export.csv');
            Route::get('/export.xlsx', [Admin\MunicipalityCoverageAlertController::class, 'exportXlsx'])->name('export.xlsx');
            Route::get('/ranking/export.csv', [Admin\MunicipalityCoverageAlertController::class, 'exportExecutiveRankingCsv'])->name('ranking.export.csv');
            Route::get('/ranking/export.xlsx', [Admin\MunicipalityCoverageAlertController::class, 'exportExecutiveRankingXlsx'])->name('ranking.export.xlsx');
            Route::get('/ranking/export.pdf', [Admin\MunicipalityCoverageAlertController::class, 'exportExecutiveRankingPdf'])->name('ranking.export.pdf');
            Route::get('/mailing/{period}/preview', [Admin\MunicipalityCoverageAlertController::class, 'previewMailing'])->name('mailing.preview');
            Route::post('/mailing/{period}/approve', [Admin\MunicipalityCoverageAlertController::class, 'approveMailing'])->name('mailing.approve');
            Route::post('/mailing/{period}/revoke', [Admin\MunicipalityCoverageAlertController::class, 'revokeMailing'])->name('mailing.revoke');
            Route::post('/{alert}/comments', [Admin\MunicipalityCoverageAlertController::class, 'addComment'])->name('comments.store');
            Route::post('/{alert}/acknowledge', [Admin\MunicipalityCoverageAlertController::class, 'acknowledge'])->name('acknowledge');
            Route::post('/{alert}/unacknowledge', [Admin\MunicipalityCoverageAlertController::class, 'unacknowledge'])->name('unacknowledge');
            Route::post('/{alert}/owner', [Admin\MunicipalityCoverageAlertController::class, 'assignOwner'])->name('owner');
            Route::get('/municipalities/{municipality}', [Admin\MunicipalityCoverageAlertController::class, 'municipality'])->name('municipality');
            Route::post('/filters', [Admin\MunicipalityCoverageAlertController::class, 'saveFilter'])->name('filters.save');
            Route::delete('/filters/{filterKey}', [Admin\MunicipalityCoverageAlertController::class, 'deleteFilter'])->name('filters.delete');
            Route::post('/bulk', [Admin\MunicipalityCoverageAlertController::class, 'bulkAction'])->name('bulk');
        });

        // Configurações do sistema
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/',                [Admin\SettingsController::class, 'index'])->name('index');
            Route::post('/ai',             [Admin\SettingsController::class, 'saveAI'])->name('ai');
            Route::post('/test',           [Admin\SettingsController::class, 'testConnection'])->name('test');
            Route::post('/operational',    [Admin\SettingsController::class, 'saveOperational'])->name('operational');
            Route::post('/operational/{activity}/rollback', [Admin\SettingsController::class, 'rollbackOperational'])->name('operational.rollback');
            Route::post('/mail/test',      [Admin\SettingsController::class, 'testMailRuntime'])->name('mail.test');
            Route::get('/integrations',    [Admin\SettingsController::class, 'integrations'])->name('integrations');
            Route::post('/integrations',   [Admin\SettingsController::class, 'saveIntegrations'])->name('integrations.save');
        });
    });

// ─────────────────────────────────────────────────────────────────────────────
// ÁREA DO PREFEITO
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:mayor|secretary|advisor', 'municipality.onboarded'])
    ->prefix('pra-hoje')
    ->name('pra-hoje.')
    ->group(function () {
        Route::get('/', [Mayor\BriefingController::class, 'index'])->name('index');
        Route::get('/{briefing}', [Mayor\BriefingController::class, 'show'])->name('show');
        Route::post('/mark-read/{briefing}', [Mayor\BriefingController::class, 'markRead'])->name('read');
        Route::post('/generate', [Mayor\BriefingController::class, 'generate'])->name('generate');
        Route::post('/preferences', [Mayor\BriefingController::class, 'updatePreferences'])->name('preferences');
        Route::post('/{briefing}/cards/{cardIndex}/conversation', [Mayor\BriefingController::class, 'openCardConversation'])->name('cards.conversation');
    });

Route::middleware(['auth', 'role:mayor|secretary|advisor', 'municipality.onboarded'])
    ->prefix('chat')
    ->name('chat.')
    ->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::post('/new', [ChatController::class, 'create'])->name('create');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::delete('/{conversation}', [ChatController::class, 'destroy'])->name('destroy');
        Route::post('/{conversation}/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::patch('/{conversation}/tags', [ChatController::class, 'updateConversationTags'])->name('tags.update');
        Route::post('/preferences/audio', [ChatController::class, 'updateAudioPreferences'])->name('preferences.audio');
        Route::post('/audio/transcribe', [ChatController::class, 'transcribeAudio'])->name('audio.transcribe');
        Route::get('/messages/{message}/audio', [ChatController::class, 'messageAudio'])->name('audio.message');
        Route::post('/messages/{message}/feedback', [ChatController::class, 'feedback'])->name('feedback');
        Route::post('/messages/{message}/export', [ChatController::class, 'exportMessage'])->name('export');
        Route::post('/messages/{message}/share', [ChatController::class, 'shareMessage'])->name('share');
        Route::post('/shares/{share}/revoke', [ChatController::class, 'revokeShare'])->name('shares.revoke');
    });

Route::middleware(['auth', 'role:mayor|secretary|advisor', 'municipality.onboarded'])
    ->prefix('mayor/project-bank')
    ->name('mayor.project-bank.')
    ->group(function () {
        Route::get('/', [Mayor\ProjectBankController::class, 'index'])->name('index');
        Route::get('/{thesis}', [Mayor\ProjectBankController::class, 'show'])->name('show');
        Route::post('/{thesis}/save', [Mayor\ProjectBankController::class, 'save'])->name('save');
        Route::post('/{thesis}/share', [Mayor\ProjectBankController::class, 'share'])->name('share');
        Route::post('/{thesis}/generate-project', [Mayor\ProjectBankController::class, 'generateProject'])->name('generate-project');
    });

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
            Route::patch('/{conversation}/tags', [ChatController::class, 'updateConversationTags'])->name('tags.update');
            Route::post('/preferences/audio', [ChatController::class, 'updateAudioPreferences'])->name('preferences.audio');
            Route::post('/audio/transcribe', [ChatController::class, 'transcribeAudio'])->name('audio.transcribe');
            Route::get('/messages/{message}/audio', [ChatController::class, 'messageAudio'])->name('audio.message');
            Route::post('/messages/{message}/feedback', [ChatController::class, 'feedback'])->name('feedback');
            Route::post('/messages/{message}/export', [ChatController::class, 'exportMessage'])->name('export');
            Route::post('/messages/{message}/share', [ChatController::class, 'shareMessage'])->name('share');
            Route::post('/shares/{share}/revoke', [ChatController::class, 'revokeShare'])->name('shares.revoke');
        });

        // ── Módulo 2: Projetos ────────────────────────────────────────────
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [Mayor\ProjectController::class, 'index'])->name('index');
            Route::get('/create', [Mayor\ProjectController::class, 'create'])->name('create');
            Route::post('/', [Mayor\ProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [Mayor\ProjectController::class, 'show'])->name('show');
            Route::post('/{project}/collaborators', [Mayor\ProjectController::class, 'inviteCollaborator'])->name('collaborators.invite');
            Route::post('/{project}/collaborators/accept', [Mayor\ProjectController::class, 'acceptCollaboratorInvite'])->name('collaborators.accept');
            Route::delete('/{project}/collaborators/{collaborator}', [Mayor\ProjectController::class, 'removeCollaborator'])->name('collaborators.remove');
            Route::put('/{project}/metadata', [Mayor\ProjectController::class, 'updateMetadata'])->name('metadata.update');
            Route::put('/{project}/sections/{section}', [Mayor\ProjectController::class, 'updateSection'])->name('sections.update');
            Route::post('/{project}/overlap/analyze', [Mayor\ProjectController::class, 'analyzeOverlap'])->name('overlap.analyze');
            Route::post('/{project}/funding/analyze', [Mayor\ProjectController::class, 'analyzeFunding'])->name('funding.analyze');
            Route::post('/{project}/questionnaire/regenerate', [Mayor\ProjectController::class, 'regenerateQuestionnaire'])->name('questionnaire.regenerate');
            Route::post('/{project}/questionnaire/answers', [Mayor\ProjectController::class, 'saveQuestionnaireAnswers'])->name('questionnaire.answers');
            Route::post('/{project}/document/generate', [Mayor\ProjectController::class, 'generateDocument'])->name('document.generate');
            Route::post('/{project}/revisions/open-draft', [Mayor\ProjectController::class, 'openWorkingDraft'])->name('revisions.open-draft');
            Route::post('/{project}/revisions/{revision}/approval-steps/{stepKey}/responsible', [Mayor\ProjectController::class, 'assignRevisionStepResponsible'])->name('revisions.approval-steps.responsible');
            Route::post('/{project}/revisions/{revision}/approval-steps/{stepKey}', [Mayor\ProjectController::class, 'approveRevisionStep'])->name('revisions.approval-steps.approve');
            Route::post('/{project}/revisions/{revision}/approve', [Mayor\ProjectController::class, 'approveRevision'])->name('revisions.approve');
            Route::post('/{project}/revisions/{revision}/publish', [Mayor\ProjectController::class, 'publishRevision'])->name('revisions.publish');
            Route::post('/{project}/revisions/{revision}/restore', [Mayor\ProjectController::class, 'restoreRevision'])->name('revisions.restore');
            Route::get('/{project}/export/word', [Mayor\ProjectController::class, 'exportWord'])->name('export.word');
            Route::get('/{project}/export/word/published', [Mayor\ProjectController::class, 'exportPublishedWord'])->name('export.word.published');
            Route::get('/{project}/export/pdf', [Mayor\ProjectController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/{project}/export/pdf/published', [Mayor\ProjectController::class, 'exportPublishedPdf'])->name('export.pdf.published');
        });

        // ── Módulo 3: Comunicação e Marketing ─────────────────────────────
        Route::prefix('content')->name('content.')->group(function () {
            Route::get('/',                      [Mayor\ContentController::class, 'index'])->name('index');
            Route::post('/generate-post',        [Mayor\ContentController::class, 'generatePost'])->name('generate-post');
            Route::post('/interview-prep',       [Mayor\ContentController::class, 'interviewPrep'])->name('interview-prep');
            Route::post('/crisis-response',      [Mayor\ContentController::class, 'crisisResponse'])->name('crisis-response');
            Route::post('/generate-image',       [Mayor\ContentController::class, 'generateImage'])->name('generate-image');
            Route::post('/operations/{demand}/move', [Mayor\ContentController::class, 'moveOperationDemand'])->name('operations.move');
            Route::post('/templates',            [Mayor\ContentController::class, 'storeTemplate'])->name('templates.store');
            Route::put('/templates/{template}',  [Mayor\ContentController::class, 'updateTemplate'])->name('templates.update');
            Route::delete('/templates/{template}', [Mayor\ContentController::class, 'destroyTemplate'])->name('templates.destroy');
            Route::get('/{content}',             [Mayor\ContentController::class, 'show'])->name('show');
            Route::put('/{content}',             [Mayor\ContentController::class, 'update'])->name('update');
            Route::post('/{content}/refine',     [Mayor\ContentController::class, 'refine'])->name('refine');
            Route::post('/{content}/variations', [Mayor\ContentController::class, 'generateVariations'])->name('variations');
            Route::post('/{content}/crisis-evolve', [Mayor\ContentController::class, 'evolveCrisis'])->name('crisis-evolve');
            Route::post('/{content}/collaborate', [Mayor\ContentController::class, 'collaborate'])->name('collaborate');
            Route::post('/{content}/approve',    [Mayor\ContentController::class, 'approve'])->name('approve');
            Route::post('/{content}/publish',    [Mayor\ContentController::class, 'publish'])->name('publish');
            Route::post('/{content}/schedule',   [Mayor\ContentController::class, 'schedule'])->name('schedule');
            Route::post('/{content}/reorder-schedule', [Mayor\ContentController::class, 'reorderSchedule'])->name('reorder-schedule');
            Route::post('/{content}/archive',    [Mayor\ContentController::class, 'archive'])->name('archive');
            Route::post('/{content}/archive-remove', [Mayor\ContentController::class, 'removeFromArchive'])->name('archive-remove');
        });

        Route::prefix('mentions')->name('mentions.')->group(function () {
            Route::get('/',                       [Mayor\MentionsController::class, 'index'])->name('index');
            Route::post('/refresh',               [Mayor\MentionsController::class, 'refresh'])->name('refresh');
            Route::post('/read',                  [Mayor\MentionsController::class, 'markRead'])->name('read');
            Route::post('/manual',                [Mayor\MentionsController::class, 'storeManualMention'])->name('manual.store');
            Route::post('/{mention}/reclassify',  [Mayor\MentionsController::class, 'reclassify'])->name('reclassify');
            Route::get('/config',                 [Mayor\MentionsController::class, 'config'])->name('config');
            Route::post('/keywords',              [Mayor\MentionsController::class, 'storeKeyword'])->name('keyword.store');
            Route::post('/keywords/{id}/toggle',  [Mayor\MentionsController::class, 'toggleKeyword'])->name('keyword.toggle');
            Route::delete('/keywords/{id}',       [Mayor\MentionsController::class, 'destroyKeyword'])->name('keyword.destroy');
        });

        // ── Módulo 4: Gestão do Mandato ───────────────────────────────────
        Route::prefix('mandato')->name('mandato.')->group(function () {

            // ── Gerenciador de Mandato ──────────────────────────────────
            Route::get('/painel',                    [Mayor\MandatoController::class, 'index'])->name('painel');

            // Eixos temáticos
            Route::get('/eixos',                     [Mayor\MandatoController::class, 'eixos'])->name('eixos');
            Route::post('/eixos',                    [Mayor\MandatoController::class, 'storeEixo'])->name('eixo.store');
            Route::post('/eixos/seed',               [Mayor\MandatoController::class, 'seedDefaultAxes'])->name('eixos.seed');
            Route::put('/eixos/{id}',                [Mayor\MandatoController::class, 'updateEixo'])->name('eixo.update');
            Route::delete('/eixos/{id}',             [Mayor\MandatoController::class, 'destroyEixo'])->name('eixo.destroy');
            Route::get('/eixos/{id}',                [Mayor\MandatoController::class, 'eixo'])->name('eixo');

            // Promessas
            Route::post('/promises',                 [Mayor\MandatoController::class, 'storePromise'])->name('promise.store');
            Route::delete('/promises/{id}',          [Mayor\MandatoController::class, 'destroyPromise'])->name('promise.destroy');
            Route::post('/promises/suggest',         [Mayor\MandatoController::class, 'suggestPromises'])->name('promise.suggest');

            // Ações de governo
            Route::get('/acoes',                     [Mayor\MandatoController::class, 'acoes'])->name('acoes');
            Route::get('/acoes/create',              [Mayor\MandatoController::class, 'createAcao'])->name('acao.create');
            Route::post('/acoes',                    [Mayor\MandatoController::class, 'storeAcao'])->name('acao.store');
            Route::get('/acoes/{id}/edit',           [Mayor\MandatoController::class, 'editAcao'])->name('acao.edit');
            Route::put('/acoes/{id}',                [Mayor\MandatoController::class, 'updateAcao'])->name('acao.update');
            Route::delete('/acoes/{id}',             [Mayor\MandatoController::class, 'destroyAcao'])->name('acao.destroy');

            // Compromissos de campanha
            Route::resource('commitments', Mayor\CommitmentController::class);

            // Radar de programas federais
            Route::get('/federal-programs',           [Mayor\FederalProgramController::class, 'index'])->name('federal-programs');
            Route::match(['GET', 'POST'], '/federal-programs/detail', [Mayor\FederalProgramController::class, 'detail'])->name('federal-programs.detail');
            Route::post('/federal-programs/ask',      [Mayor\FederalProgramController::class, 'askAssistant'])->name('federal-programs.ask.payload');
            Route::post('/federal-programs/save',     [Mayor\FederalProgramController::class, 'toggleSave'])->name('federal-programs.save');
            Route::post('/federal-programs/reopen-notification', [Mayor\FederalProgramController::class, 'toggleReopenNotification'])->name('federal-programs.reopen-notification');
            Route::post('/federal-programs/{program}/ask', [Mayor\FederalProgramController::class, 'askAssistant'])->name('federal-programs.ask');

            // Briefing matinal
            Route::get('/briefings',         [Mayor\BriefingController::class, 'index'])->name('briefings');
            Route::get('/briefings/{briefing}', [Mayor\BriefingController::class, 'show'])->name('briefings.show');
            Route::post('/briefings/mark-read/{briefing}', [Mayor\BriefingController::class, 'markRead'])->name('briefings.read');
            Route::post('/briefings/generate', [Mayor\BriefingController::class, 'generate'])->name('briefings.generate');
            Route::post('/briefings/{briefing}/cards/{cardIndex}/conversation', [Mayor\BriefingController::class, 'openCardConversation'])->name('briefings.cards.conversation');


            // Registro de demandas por voz
            Route::post('/demands/voice',    [Mayor\DemandController::class, 'storeVoice'])->name('demands.voice');
            Route::patch('/demands/{demand}/status', [Mayor\DemandController::class, 'updateStatus'])->name('demands.status');
            Route::patch('/demands/{demand}', [Mayor\DemandController::class, 'update'])->name('demands.update');
            Route::post('/demands/{demand}/comments', [Mayor\DemandController::class, 'addComment'])->name('demands.comments.add');
            Route::post('/demands/{demand}/communication-draft', [Mayor\DemandController::class, 'generateCommunicationDraft'])->name('demands.communication-draft');
            Route::post('/demands/{demand}/strategic-conversation', [Mayor\DemandController::class, 'openStrategicConversation'])->name('demands.strategic-conversation');
            Route::resource('demands',       Mayor\DemandController::class)->only(['index', 'store', 'show']);
        });
    });

Route::middleware(['auth', 'role:secretary|advisor', 'municipality.onboarded'])
    ->prefix('resolve-ai')
    ->name('resolve-ai.')
    ->group(function () {
        Route::get('/demands', [Mayor\DemandController::class, 'index'])->name('demands.index');
        Route::post('/demands', [Mayor\DemandController::class, 'store'])->name('demands.store');
        Route::get('/demands/{demand}', [Mayor\DemandController::class, 'show'])->name('demands.show');
        Route::patch('/demands/{demand}/status', [Mayor\DemandController::class, 'updateStatus'])->name('demands.status');
        Route::patch('/demands/{demand}', [Mayor\DemandController::class, 'update'])->name('demands.update');
        Route::post('/demands/{demand}/comments', [Mayor\DemandController::class, 'addComment'])->name('demands.comments.add');
    });
