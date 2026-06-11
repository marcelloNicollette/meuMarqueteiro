<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Demand;
use App\Models\DemandEvent;
use App\Models\FederalProgramAlert;
use App\Models\MandateAction;
use App\Models\MandateAxis;
use App\Models\MandatePromise;
use App\Models\MorningBriefing;
use App\Models\Municipality;
use App\Models\MunicipalityDocument;
use App\Models\Project;
use App\Models\ProjectThesis;
use App\Models\ProjectThesisNotification;
use App\Models\User;
use App\Services\AI\AIProviderService;
use App\Services\AI\AIResponse;
use App\Services\AI\MorningBriefingService;
use App\Services\AI\AssistantContextService;
use App\Services\AI\ChatProactiveAlertService;
use App\Services\Mandato\MandateProjectionService;
use App\Services\Mandato\MandatePromiseExtractionService;
use App\Services\Mandato\MandatePromiseLinkingService;
use App\Services\Projects\ProjectBankLibraryService;
use App\Services\Projects\ProjectContextDossierService;
use App\Services\Projects\ProjectDocumentGenerationService;
use App\Services\Projects\ProjectQuestionFlowService;
use App\Services\Projects\ProjectSourceThesisContextService;
use App\Services\Projects\ProjectStructureService;
use App\Services\Projects\ProjectRevisionService;
use App\Services\Radar\HybridRadarReadService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MandatoSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestingSchema();
        $this->clearTestingData();

        Storage::fake('local');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_can_upload_government_plan_and_store_preview(): void
    {
        $municipality = $this->createMunicipality();
        $admin = $this->createUser(UserRole::Admin);

        $preview = [
            'document_id' => 99,
            'document_name' => 'Plano de governo',
            'items' => [
                [
                    'text' => 'Construir 3 UBS',
                    'axis_id' => null,
                    'keywords' => ['ubs', 'saude'],
                    'keywords_text' => 'ubs, saude',
                    'specificity' => 'quantitativo',
                    'source_document_id' => 99,
                ],
            ],
            'text_excerpt' => 'Trecho do plano',
        ];

        $this->mock(MandatePromiseExtractionService::class, function (MockInterface $mock) use ($municipality, $preview) {
            $mock->shouldReceive('extractFromGovernmentPlan')
                ->once()
                ->withArgs(function ($argMunicipality, $document) use ($municipality) {
                    return $argMunicipality instanceof Municipality
                        && $argMunicipality->is($municipality)
                        && $document instanceof MunicipalityDocument
                        && $document->type === 'programa_governo';
                })
                ->andReturn($preview);
        });

        $this->mock(MandatePromiseLinkingService::class, function (MockInterface $mock) {
            $mock->shouldIgnoreMissing();
        });

        $this->mock(ProjectBankLibraryService::class, function (MockInterface $mock) use ($municipality) {
            $mock->shouldReceive('markRefreshRecommended')
                ->once()
                ->withArgs(fn ($argMunicipality, $reason) => $argMunicipality instanceof Municipality
                    && $argMunicipality->is($municipality)
                    && $reason === 'government_plan_uploaded')
                ->andReturnUsing(fn ($argMunicipality) => $argMunicipality);
        });

        $response = $this->actingAs($admin)->post(
            route('admin.municipalities.onboarding.documents', $municipality),
            [
                'government_plan_file' => UploadedFile::fake()->createWithContent(
                    'plano-governo.txt',
                    'Construir 3 UBS e ampliar vagas nas creches.'
                ),
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document = MunicipalityDocument::query()->first();

        $this->assertNotNull($document);
        $this->assertSame($municipality->id, $document->municipality_id);
        $this->assertSame('programa_governo', $document->type);
        $this->assertSame($admin->id, $document->uploaded_by);

        $municipality->refresh();

        $this->assertSame($document->id, data_get($municipality->settings, 'mandato.plan_document_id'));
        $this->assertSame($preview, data_get($municipality->settings, 'mandato.extraction_preview'));
        $this->assertSame('in_progress', $municipality->onboarding_status);
    }

    public function test_admin_can_save_reviewed_and_manual_commitments_from_onboarding(): void
    {
        $municipality = $this->createMunicipality();
        $admin = $this->createUser(UserRole::Admin);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        $municipality->update([
            'settings' => [
                'mandato' => [
                    'extraction_preview' => [
                        'document_id' => 123,
                        'items' => [['text' => 'Compromisso original']],
                    ],
                ],
            ],
        ]);

        $this->mock(MandatePromiseLinkingService::class, function (MockInterface $mock) use ($municipality) {
            $mock->shouldReceive('ensurePromiseEmbeddings')
                ->once()
                ->withArgs(fn ($argMunicipality, $force) => $argMunicipality instanceof Municipality
                    && $argMunicipality->is($municipality)
                    && $force === true);
        });

        $this->mock(ProjectBankLibraryService::class, function (MockInterface $mock) use ($municipality) {
            $mock->shouldReceive('markRefreshRecommended')
                ->once()
                ->withArgs(fn ($argMunicipality, $reason) => $argMunicipality instanceof Municipality
                    && $argMunicipality->is($municipality)
                    && $reason === 'mandate_commitments_updated')
                ->andReturnUsing(fn ($argMunicipality) => $argMunicipality);
        });

        $response = $this->actingAs($admin)->post(
            route('admin.municipalities.onboarding.mandato-commitments', $municipality),
            [
                'commitments' => [
                    [
                        'enabled' => '1',
                        'text' => 'Construir 3 UBS',
                        'axis_id' => (string) $axis->id,
                        'keywords' => 'ubs, saude',
                        'specificity' => 'quantitativo',
                        'source_document_id' => null,
                    ],
                    [
                        'enabled' => '1',
                        'text' => 'Fortalecer a atencao basica',
                        'axis_id' => (string) $axis->id,
                        'keywords' => 'atencao basica, equipes',
                        'specificity' => 'qualitativo',
                        'source_document_id' => null,
                    ],
                    [
                        'enabled' => '0',
                        'text' => 'Item ignorado',
                        'axis_id' => (string) $axis->id,
                        'keywords' => 'ignorado',
                        'specificity' => 'qualitativo',
                        'source_document_id' => null,
                    ],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $promises = MandatePromise::query()
            ->where('municipality_id', $municipality->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $promises);
        $this->assertSame(['ubs', 'saude'], $promises[0]->keywords);
        $this->assertSame('quantitativo', $promises[0]->specificity);
        $this->assertSame('Fortalecer a atencao basica', $promises[1]->text);

        $municipality->refresh();
        $this->assertNull(data_get($municipality->settings, 'mandato.extraction_preview'));
    }

    public function test_admin_complete_onboarding_bootstraps_project_bank(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'in_progress',
        ]);
        $admin = $this->createUser(UserRole::Admin);

        $this->mock(ProjectBankLibraryService::class, function (MockInterface $mock) use ($municipality) {
            $mock->shouldReceive('ensureLibraryForMunicipality')
                ->once()
                ->withArgs(function ($argMunicipality, $force, $targetCount, $reason) use ($municipality) {
                    return $argMunicipality instanceof Municipality
                        && $argMunicipality->is($municipality)
                        && $force === true
                        && $targetCount === 10
                        && $reason === 'onboarding_complete';
                })
                ->andReturn(collect([
                    new ProjectThesis(['title' => 'Biblioteca inicial']),
                ]));
        });

        $response = $this->actingAs($admin)->post(
            route('admin.municipalities.onboarding.complete', $municipality)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Onboarding concluído e Banco de Projetos inicializado para o município.');

        $municipality->refresh();
        $this->assertSame('completed', $municipality->onboarding_status);
        $this->assertNotNull($municipality->onboarding_completed_at);
    }

    public function test_project_bank_refresh_command_updates_only_municipalities_requiring_curation(): void
    {
        $needsRefresh = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'subscription_active' => true,
        ]);
        $upToDate = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'subscription_active' => true,
        ]);

        $this->mock(ProjectBankLibraryService::class, function (MockInterface $mock) use ($needsRefresh, $upToDate) {
            $mock->shouldReceive('needsPeriodicRefresh')
                ->once()
                ->withArgs(fn ($municipality, $days) => $municipality instanceof Municipality
                    && $municipality->is($needsRefresh)
                    && $days === 7)
                ->andReturn(true);

            $mock->shouldReceive('needsPeriodicRefresh')
                ->once()
                ->withArgs(fn ($municipality, $days) => $municipality instanceof Municipality
                    && $municipality->is($upToDate)
                    && $days === 7)
                ->andReturn(false);

            $mock->shouldReceive('ensureLibraryForMunicipality')
                ->once()
                ->withArgs(function ($municipality, $force, $targetCount, $reason) use ($needsRefresh) {
                    return $municipality instanceof Municipality
                        && $municipality->is($needsRefresh)
                        && $force === true
                        && $targetCount === 10
                        && $reason === 'scheduled_refresh';
                })
                ->andReturn(collect([
                    new ProjectThesis(['title' => 'Biblioteca atualizada']),
                ]));
        });

        $this->artisan('project-bank:refresh-libraries')
            ->expectsOutput("Verificando curadoria do Banco de Projetos para 2 municipio(s)...")
            ->expectsOutput(" ✓ {$needsRefresh->name} — 1 tese(s) curadas")
            ->expectsOutput(" - {$upToDate->name} — sem refresh necessario")
            ->expectsOutput('Curadoria concluida. Atualizados: 1. Sem refresh: 1.')
            ->assertExitCode(0);
    }

    public function test_admin_municipality_show_displays_project_bank_operational_summary(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'settings' => [
                'project_bank' => [
                    'library_size' => 9,
                    'bootstrapped_at' => now()->subDays(3)->toIso8601String(),
                    'last_curated_at' => now()->subHours(6)->toIso8601String(),
                    'needs_refresh' => true,
                    'refresh_recommended_reason' => 'mandate_commitments_updated',
                ],
            ],
        ]);
        $admin = $this->createUser(UserRole::Admin);

        $response = $this->actingAs($admin)->get(route('admin.municipalities.show', $municipality));

        $response->assertOk();
        $response->assertSee('Banco de Projetos');
        $response->assertSee('Reexecutar curadoria');
        $response->assertSee('Refresh recomendado');
        $response->assertSee('mandate_commitments_updated');
    }

    public function test_admin_can_trigger_manual_project_bank_refresh_for_completed_municipality(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'subscription_active' => true,
        ]);
        $admin = $this->createUser(UserRole::Admin);

        $this->mock(ProjectBankLibraryService::class, function (MockInterface $mock) use ($municipality) {
            $mock->shouldReceive('ensureLibraryForMunicipality')
                ->once()
                ->withArgs(function ($argMunicipality, $force, $targetCount, $reason) use ($municipality) {
                    return $argMunicipality instanceof Municipality
                        && $argMunicipality->is($municipality)
                        && $force === true
                        && $targetCount === 10
                        && $reason === 'admin_manual_refresh';
                })
                ->andReturn(collect([
                    new ProjectThesis(['title' => 'Biblioteca recalculada']),
                ]));
        });

        $response = $this->actingAs($admin)->post(route('admin.municipalities.project-bank.refresh', $municipality));

        $response->assertRedirect();
        $response->assertSessionHas('success', "Curadoria do Banco de Projetos reexecutada para {$municipality->name} (1 tese(s)).");
    }

    public function test_mayor_can_request_semantic_promise_suggestions(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        $this->mock(MandatePromiseLinkingService::class, function (MockInterface $mock) use ($municipality, $axis) {
            $mock->shouldReceive('suggestForAction')
                ->once()
                ->withArgs(function ($argMunicipality, $title, $description, $axisId, $limit = 5) use ($municipality, $axis) {
                    return $argMunicipality instanceof Municipality
                        && $argMunicipality->is($municipality)
                        && $title === 'Reforma da UBS Central'
                        && $description === 'Ampliacao da estrutura e melhoria do atendimento.'
                        && $axisId === $axis->id
                        && $limit === 5;
                })
                ->andReturn([
                    [
                        'id' => 10,
                        'text' => 'Reformar e ampliar unidades basicas de saude',
                        'axis_id' => $axis->id,
                        'axis_name' => 'Saude',
                        'similarity' => 0.91,
                        'similarity_percent' => 91,
                        'keywords' => ['ubs', 'reforma'],
                        'specificity' => 'qualitativo',
                    ],
                ]);
        });

        $response = $this->actingAs($mayor)->postJson(
            route('mayor.mandato.promise.suggest'),
            [
                'title' => 'Reforma da UBS Central',
                'description' => 'Ampliacao da estrutura e melhoria do atendimento.',
                'mandate_axis_id' => $axis->id,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('suggestions.0.axis_name', 'Saude')
            ->assertJsonPath('suggestions.0.similarity_percent', 91);
    }

    public function test_mayor_can_create_action_with_automatic_milestone_progress(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);
        $promise = MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Ampliar a cobertura da atencao basica',
            'keywords' => ['saude', 'ubs'],
            'specificity' => 'qualitativo',
            'status' => 'pending',
            'is_active' => true,
        ]);

        $this->mock(MandatePromiseLinkingService::class, function (MockInterface $mock) {
            $mock->shouldIgnoreMissing();
        });

        $response = $this->actingAs($mayor)->post(
            route('mayor.mandato.acao.store'),
            [
                'mandate_axis_id' => $axis->id,
                'title' => 'Obra da UBS Central',
                'description' => 'Ampliacao da estrutura e melhoria do atendimento.',
                'secretaria' => 'Secretaria de Saude',
                'status' => 'em_andamento',
                'physical_progress' => 0,
                'uses_milestones_progress' => '1',
                'promises' => [
                    $promise->id => [
                        'id' => $promise->id,
                        'level' => 50,
                        'justification' => 'Primeira etapa em execução.',
                    ],
                ],
                'milestones' => [
                    [
                        'title' => 'Projeto executivo aprovado',
                        'due_date' => '2026-06-01',
                        'completed' => '1',
                    ],
                    [
                        'title' => 'Início das obras',
                        'due_date' => '2026-07-01',
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('mayor.mandato.painel'));

        $action = MandateAction::query()->first();

        $this->assertNotNull($action);
        $this->assertTrue($action->uses_milestones_progress);
        $this->assertSame(50, $action->physical_progress);
        $this->assertCount(2, $action->milestones);
        $this->assertSame(1, $action->milestones()->whereNotNull('completed_at')->count());
        $this->assertSame(2, $action->progressLogs()->count());

        $promise->refresh();
        $this->assertSame(50, $promise->score);
        $this->assertSame('partial_50', $promise->status);
    }

    public function test_projection_identifies_commitment_gap_and_alert_axis(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'settings' => [
                'mandato' => [
                    'term_end_date' => now()->addMonths(6)->format('Y-m-d'),
                ],
            ],
        ]);
        $axisSaude = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);
        $axisEducacao = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Educacao',
            'icon' => 'E',
            'color' => '#1e3a5f',
            'order' => 2,
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axisSaude->id,
            'text' => 'Entregar a nova UBS',
            'score' => 100,
            'status' => 'fulfilled',
            'is_active' => true,
        ]);

        $projectedPromise = MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axisSaude->id,
            'text' => 'Ampliar a cobertura da atencao basica',
            'score' => 0,
            'status' => 'pending',
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axisEducacao->id,
            'text' => 'Universalizar vagas na pre-escola',
            'score' => 0,
            'status' => 'pending',
            'is_active' => true,
        ]);

        $action = MandateAction::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axisSaude->id,
            'title' => 'Obra da UBS Central',
            'status' => 'em_andamento',
            'physical_progress' => 60,
            'start_date' => now()->subDays(30)->format('Y-m-d'),
        ]);

        $action->promises()->attach($projectedPromise->id, [
            'fulfillment_level' => 100,
        ]);

        $action->progressLogs()->create([
            'event_type' => 'progress_updated',
            'description' => 'Avanco recente',
            'from_progress' => 20,
            'to_progress' => 60,
            'from_status' => 'em_andamento',
            'to_status' => 'em_andamento',
            'occurred_at' => now()->subDays(20),
        ]);

        $projection = app(MandateProjectionService::class)->calculate($municipality);

        $this->assertSame(2, $projection['projected_fulfilled_promises']);
        $this->assertSame(1, $projection['projected_pending_promises']);
        $this->assertTrue($projection['needs_alert']);
        $this->assertSame('Educacao', $projection['axis_alerts'][0]['axis_name']);
        $this->assertSame(1, $projection['axis_alerts'][0]['gap']);
    }

    public function test_mayor_can_open_axis_drilldown_with_promises_and_linked_actions(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        $promise = MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Entregar a UBS Central',
            'score' => 50,
            'status' => 'partial_50',
            'keywords' => ['ubs', 'saude'],
            'specificity' => 'quantitativo',
            'is_active' => true,
        ]);

        $action = MandateAction::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'title' => 'Obra da UBS Central',
            'description' => 'Execução da nova unidade.',
            'secretaria' => 'Secretaria de Saude',
            'status' => 'em_andamento',
            'physical_progress' => 40,
            'start_date' => now()->subDays(12)->format('Y-m-d'),
            'end_date' => now()->addDays(45)->format('Y-m-d'),
        ]);

        $action->promises()->attach($promise->id, [
            'fulfillment_level' => 50,
        ]);

        $response = $this->actingAs($mayor)->get(route('mayor.mandato.eixo', $axis->id));

        $response->assertOk()
            ->assertSee('Compromissos plenos')
            ->assertSee('Compromissos parciais')
            ->assertSee('Compromissos pendentes')
            ->assertSee('Entregar a UBS Central')
            ->assertSee('Obra da UBS Central')
            ->assertSee('Secretaria de Saude');
    }

    public function test_commitments_area_shows_pending_focus_list_grouped_by_axis(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axisSaude = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);
        $axisEducacao = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Educacao',
            'icon' => 'E',
            'color' => '#1e3a5f',
            'order' => 2,
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axisSaude->id,
            'text' => 'Entregar a UBS Central',
            'score' => 0,
            'status' => 'pending',
            'keywords' => ['ubs'],
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axisEducacao->id,
            'text' => 'Ampliar vagas em creches',
            'score' => 0,
            'status' => 'pending',
            'keywords' => ['creches'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($mayor)->get(route('mayor.mandato.painel', [
            'area' => 'commitments',
        ]));

        $response->assertOk()
            ->assertSee('Lista de compromissos pendentes')
            ->assertSee('Saude')
            ->assertSee('Educacao')
            ->assertSee('Entregar a UBS Central')
            ->assertSee('Ampliar vagas em creches')
            ->assertSee('Criar ação vinculada')
            ->assertSee('Verificar ações existentes');
    }

    public function test_commitments_area_suggests_radar_opportunity_for_pending_promise_without_action(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Construir nova UBS no bairro Norte',
            'score' => 0,
            'status' => 'pending',
            'keywords' => ['ubs', 'saude', 'bairro norte'],
            'is_active' => true,
        ]);

        $program = new FederalProgramAlert([
            'municipality_id' => $municipality->id,
            'program_name' => 'Novo PAC Saude',
            'area' => 'Saude',
            'description' => 'Financiamento para UBS e ampliacao da atencao basica.',
            'status' => 'published',
            'match_score' => 0.91,
            'match_reason' => 'Oportunidade aderente a expansao de unidades basicas.',
            'source_url' => 'https://example.com/novo-pac-saude',
        ]);

        $this->mock(HybridRadarReadService::class, function (MockInterface $mock) use ($municipality, $program) {
            $mock->shouldReceive('topMunicipalityPrograms')
                ->once()
                ->withArgs(function ($argMunicipality, $limit, $minMatchScore, $statuses, $visibleOnly) use ($municipality) {
                    return $argMunicipality instanceof Municipality
                        && $argMunicipality->is($municipality)
                        && $limit === 100
                        && $minMatchScore === null
                        && $statuses === ['published', 'closing_soon', 'monitoring', 'reopened']
                        && $visibleOnly === false;
                })
                ->andReturn(collect([$program]));
        });

        $response = $this->actingAs($mayor)->get(route('mayor.mandato.painel', [
            'area' => 'commitments',
        ]));

        $response->assertOk()
            ->assertSee('Sugestao do Radar: Novo PAC Saude')
            ->assertSee('Abrir Radar de Recursos')
            ->assertSee('Aderência');
    }

    public function test_commitments_area_suggests_completed_resolve_ai_evidence_for_open_promise(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Infraestrutura',
            'icon' => 'I',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Melhorar a limpeza urbana dos bairros',
            'score' => 0,
            'status' => 'pending',
            'keywords' => ['limpeza', 'urbana', 'bairros'],
            'is_active' => true,
        ]);

        Demand::create([
            'municipality_id' => $municipality->id,
            'registered_by' => $mayor->id,
            'input_type' => 'text',
            'raw_input' => 'Pedido de capina e retirada de entulho no Jardim America.',
            'title' => 'Capina no Jardim America',
            'completion_note' => 'Capina concluida e entulho recolhido pela equipe de limpeza urbana.',
            'area' => 'Secretaria de Obras',
            'locality' => 'Jardim America',
            'priority' => 'media',
            'status' => 'completed',
            'resolved_at' => now()->subDays(2),
            'confirmed_at' => now()->subDays(2),
        ]);

        $matchingDemand = Demand::create([
            'municipality_id' => $municipality->id,
            'registered_by' => $mayor->id,
            'input_type' => 'text',
            'raw_input' => 'Nova solicitacao de limpeza e capina no Jardim America.',
            'title' => 'Limpeza no Jardim America',
            'completion_note' => 'Limpeza urbana concluida com capina e retirada de residuos.',
            'area' => 'Secretaria de Obras',
            'locality' => 'Jardim America',
            'priority' => 'media',
            'status' => 'completed',
            'resolved_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($mayor)->get(route('mayor.mandato.painel', [
            'area' => 'commitments',
        ]));

        $response->assertOk()
            ->assertSee('Evidencia do Resolve ai: Limpeza no Jardim America')
            ->assertSee('Abrir demanda concluída', false)
            ->assertSee((string) route('resolve-ai.demands.show', $matchingDemand));
    }

    public function test_actions_area_can_open_review_context_for_pending_commitment(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        $promise = MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Entregar a UBS Central',
            'score' => 0,
            'status' => 'pending',
            'is_active' => true,
        ]);

        MandateAction::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'title' => 'Reforma da recepcao da UBS',
            'status' => 'em_andamento',
            'physical_progress' => 20,
        ]);

        $response = $this->actingAs($mayor)->get(route('mayor.mandato.painel', [
            'area' => 'actions',
            'action_axis' => $axis->id,
            'promise_review' => $promise->id,
        ]));

        $response->assertOk()
            ->assertSee('Revisão de vínculo pendente', false)
            ->assertSee('Entregar a UBS Central')
            ->assertSee('Criar nova ação para este compromisso');
    }

    public function test_concluding_mandate_action_creates_communication_suggestion(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        $action = MandateAction::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'title' => 'Entrega da UBS Central',
            'description' => 'Nova unidade entregue com atendimento ampliado.',
            'secretaria' => 'Secretaria de Saude',
            'status' => 'em_andamento',
            'region' => 'Zona Norte',
            'beneficiaries' => 3200,
            'proof_url' => 'https://example.com/ubs-central',
        ]);

        $response = $this->actingAs($mayor)->put(route('mayor.mandato.acao.update', $action->id), [
            'mandate_axis_id' => $axis->id,
            'title' => 'Entrega da UBS Central',
            'description' => 'Nova unidade entregue com atendimento ampliado.',
            'secretaria' => 'Secretaria de Saude',
            'status' => 'concluido',
            'region' => 'Zona Norte',
            'beneficiaries' => 3200,
            'proof_url' => 'https://example.com/ubs-central',
        ]);

        $response->assertRedirect(route('mayor.mandato.painel'));

        $demand = Demand::query()->first();

        $this->assertNotNull($demand);
        $this->assertSame($municipality->id, $demand->municipality_id);
        $this->assertSame('mandato_action_completed', $demand->input_type);
        $this->assertSame('Entrega da UBS Central', $demand->title);
        $this->assertStringContainsString('Eixo tematico: Saude', $demand->raw_input);
        $this->assertStringContainsString('Beneficiários: 3200', $demand->raw_input);
        $this->assertStringContainsString('Evidencia: https://example.com/ubs-central', $demand->raw_input);

        $event = DemandEvent::query()->first();
        $this->assertNotNull($event);
        $this->assertSame('mandate_communication_suggestion_created', $event->event_type);
        $this->assertSame($action->id, data_get($event->metadata, 'mandate_action_id'));
    }

    public function test_mayor_can_link_mandate_action_to_saved_project(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);
        $project = Project::create([
            'municipality_id' => $municipality->id,
            'owner_user_id' => $mayor->id,
            'last_edited_by_user_id' => $mayor->id,
            'title' => 'Nova UBS Central',
            'initial_idea' => 'Estruturar uma nova unidade basica para a regiao central.',
            'project_type' => 'social',
            'status' => 'em_execução',
            'responsible_secretariat' => 'Secretaria de Saude',
            'current_phase' => 'estrutura_inicial',
            'generated_document_version' => 1,
            'last_edited_at' => now(),
        ]);

        $response = $this->actingAs($mayor)->post(route('mayor.mandato.acao.store'), [
            'mandate_axis_id' => $axis->id,
            'project_id' => $project->id,
            'title' => 'Implantar a UBS Central',
            'description' => 'Acompanhamento da obra e ativacao da unidade.',
            'secretaria' => 'Secretaria de Saude',
            'status' => 'em_andamento',
            'physical_progress' => 30,
        ]);

        $response->assertRedirect(route('mayor.mandato.painel'));

        $action = MandateAction::query()->first();

        $this->assertNotNull($action);
        $this->assertSame($project->id, $action->project_id);
    }

    public function test_concluding_project_suggests_review_of_linked_mandate_action_status(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);
        $project = Project::create([
            'municipality_id' => $municipality->id,
            'owner_user_id' => $mayor->id,
            'last_edited_by_user_id' => $mayor->id,
            'title' => 'Nova UBS Central',
            'initial_idea' => 'Estruturar uma nova unidade basica para a regiao central.',
            'project_type' => 'social',
            'status' => 'em_execução',
            'responsible_secretariat' => 'Secretaria de Saude',
            'current_phase' => 'estrutura_inicial',
            'generated_document_version' => 1,
            'last_edited_at' => now(),
        ]);

        MandateAction::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'project_id' => $project->id,
            'title' => 'Implantar a UBS Central',
            'status' => 'em_andamento',
            'physical_progress' => 60,
        ]);

        $this->mock(ProjectRevisionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createRevision')->once();
        });

        $response = $this->actingAs($mayor)->put(route('mayor.projects.metadata.update', $project), [
            'status' => 'concluido',
        ]);

        $response->assertRedirect(route('mayor.projects.show', $project));
        $response->assertSessionHas('warning');
        $response->assertSessionHas('success');

        $project->refresh();
        $this->assertSame('concluido', $project->status);
    }

    public function test_communication_operations_board_displays_mandate_suggestion(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);

        $demand = Demand::create([
            'municipality_id' => $municipality->id,
            'registered_by' => $mayor->id,
            'input_type' => 'mandato_action_completed',
            'raw_input' => "Sugestao automatica do Mandato.\nEixo tematico: Saude\nDescrição: Entrega concluida.",
            'title' => 'Entrega da UBS Central',
            'description' => 'Entrega concluida.',
            'area' => 'Secretaria de Saude',
            'locality' => 'Zona Norte',
            'priority' => 'media',
            'status' => 'registered',
        ]);

        DemandEvent::create([
            'demand_id' => $demand->id,
            'user_id' => $mayor->id,
            'event_type' => 'mandate_communication_suggestion_created',
            'message' => 'Sugestao criada pelo Mandato.',
            'metadata' => [
                'source_module' => 'mandato',
                'mandate_action_id' => 99,
            ],
        ]);

        $response = $this->actingAs($mayor)->get(route('mayor.content.index', [
            'area' => 'operations',
            'operation_type' => 'mandate_delivery',
        ]));

        $response->assertOk()
            ->assertSee('Mandato em conteúdo', false)
            ->assertSee('Entrega da UBS Central')
            ->assertSee('Mandato')
            ->assertSee('Abrir Mandato');
    }

    public function test_pra_hoje_generates_user_briefing_with_real_cards(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'subscription_active' => true,
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);

        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Concluir a nova UPA da regiao norte',
            'status' => 'pending',
            'score' => 0,
            'is_active' => true,
            'order' => 1,
        ]);

        Demand::create([
            'municipality_id' => $municipality->id,
            'registered_by' => $mayor->id,
            'title' => 'Buraco em avenida principal',
            'raw_input' => 'Buraco em avenida principal com risco de acidente.',
            'area' => 'Infraestrutura',
            'locality' => 'Centro',
            'priority' => 'alta',
            'status' => 'overdue',
            'due_at' => now()->subHours(6),
        ]);

        Project::create([
            'municipality_id' => $municipality->id,
            'owner_user_id' => $mayor->id,
            'last_edited_by_user_id' => $mayor->id,
            'title' => 'Reforma da UBS do Centro',
            'initial_idea' => 'Atualizar a estrutura da UBS para ampliar atendimento.',
            'project_type' => 'social',
            'status' => 'em_elaboração',
            'responsible_secretariat' => 'Secretaria de Saude',
            'current_phase' => 'documento_em_revisão',
            'generated_document_version' => 1,
            'metadata' => [
                'expected_end_date' => now()->subDay()->toDateString(),
            ],
            'last_edited_at' => now(),
        ]);

        ProjectThesis::create([
            'municipality_id' => $municipality->id,
            'title' => 'Nova policlinica regional',
            'category' => 'social',
            'justification' => 'A rede especializada tem vazios de atendimento e a demanda reprimida cresceu na microrregiao.',
            'potential_impact' => 'Amplia consultas e exames especializados para pacientes que hoje dependem de deslocamento.',
            'funding_source' => 'Programa federal de qualificacao da rede especializada com janela de submissao prevista.',
            'estimated_size' => 'grande',
            'urgency' => 'alta',
            'execution_complexity' => 'media',
            'reference_municipalities' => 'Municipios vizinhos ja executaram equipamentos equivalentes.',
            'government_alignment' => 'Fortalece a rede de saude especializada prevista no programa de governo.',
            'resource_deadline' => now()->addDays(20)->toDateString(),
        ]);

        $this->mock(AIProviderService::class, function (MockInterface $mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(new AIResponse(
                    content: 'Bom dia. Seu Pra Hoje ja separou o que pede decisão primeiro.',
                    provider: 'fake',
                    model: 'fake-model',
                    tokensUsed: 42,
                    finishReason: 'stop',
                ));
        });

        $this->mock(MandateProjectionService::class, function (MockInterface $mock) use ($axis) {
            $mock->shouldReceive('calculate')
                ->once()
                ->andReturn([
                    'alert_message' => 'No ritmo atual, 2 compromissos não serao entregues ate o fim do mandato.',
                    'axis_alerts' => [
                        [
                            'axis_id' => $axis->id,
                            'axis_name' => 'Saude',
                            'gap' => 2,
                            'total_promises' => 4,
                            'projected_fulfilled' => 2,
                        ],
                    ],
                ]);
        });

        $this->mock(HybridRadarReadService::class, function (MockInterface $mock) use ($municipality) {
            $program = new FederalProgramAlert([
                'id' => 77,
                'municipality_id' => $municipality->id,
                'program_name' => 'Nova UBS para regioes vulneraveis',
                'status' => 'closing_soon',
                'area' => 'saude',
                'match_score' => 0.91,
                'deadline' => now()->addDays(2),
            ]);

            $mock->shouldReceive('topMunicipalityPrograms')
                ->once()
                ->andReturn(collect([$program]));
        });

        $this->mock(\App\Services\WebPushService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendToUser')->once();
        });

        $briefing = app(MorningBriefingService::class)->generateForUser($mayor, force: true);

        $this->assertInstanceOf(MorningBriefing::class, $briefing);
        $this->assertSame($mayor->id, $briefing->user_id);
        $this->assertNotEmpty($briefing->opening_text);
        $this->assertIsArray($briefing->cards);
        $this->assertGreaterThanOrEqual(3, count($briefing->cards));
        $this->assertSame('Resolve ai', $briefing->cards[0]['module_label']);
        $this->assertContains('Mandato', collect($briefing->cards)->pluck('module_label')->all());
        $this->assertContains('Projetos', collect($briefing->cards)->pluck('module_label')->all());
        $this->assertContains('Banco de Projetos', collect($briefing->cards)->pluck('module_label')->all());
        $this->assertContains('Radar de Recursos', collect($briefing->cards)->pluck('module_label')->all());
    }

    public function test_project_assistant_flow_uses_source_thesis_context(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'population' => 18000,
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);

        $thesis = ProjectThesis::create([
            'municipality_id' => $municipality->id,
            'title' => 'Requalificacao da UBS Central',
            'category' => 'saude',
            'justification' => 'A estrutura atual da UBS nao comporta a demanda da atencao primaria e pressiona atendimentos de maior complexidade.',
            'potential_impact' => 'Ampliar a capacidade de atendimento basico e reduzir deslocamentos da populacao.',
            'funding_source' => 'Programa federal de qualificacao da atencao primaria com janela de submissao em 45 dias.',
            'estimated_size' => 'medio',
            'urgency' => 'alta',
            'execution_complexity' => 'media',
            'reference_municipalities' => 'Municipios de porte semelhante no PR reforcaram a rede basica com projetos equivalentes.',
            'government_alignment' => 'A tese reforca o compromisso do governo com saude territorial e melhoria do acesso.',
            'resource_deadline' => now()->addDays(45)->toDateString(),
            'metadata' => [
                'matched_program_name' => 'Qualifica UBS 2026',
            ],
        ]);

        $project = Project::create([
            'municipality_id' => $municipality->id,
            'owner_user_id' => $mayor->id,
            'last_edited_by_user_id' => $mayor->id,
            'source_thesis_id' => $thesis->id,
            'title' => 'Projeto UBS Central',
            'initial_idea' => 'Reorganizar a estrutura da UBS central para ampliar a capacidade de atendimento.',
            'project_type' => null,
            'status' => 'em_elaboração',
            'responsible_secretariat' => 'Secretaria de Saude',
            'current_phase' => 'estrutura_inicial',
            'generated_document_version' => 1,
            'metadata' => [
                'source_thesis_snapshot' => [
                    'id' => $thesis->id,
                    'title' => $thesis->title,
                    'category' => $thesis->category,
                    'justification' => $thesis->justification,
                    'potential_impact' => $thesis->potential_impact,
                    'funding_source' => $thesis->funding_source,
                    'government_alignment' => $thesis->government_alignment,
                    'reference_municipalities' => $thesis->reference_municipalities,
                    'urgency' => $thesis->urgency,
                    'estimated_size' => $thesis->estimated_size,
                    'execution_complexity' => $thesis->execution_complexity,
                    'resource_deadline' => $thesis->resource_deadline?->toDateString(),
                    'metadata' => ['matched_program_name' => 'Qualifica UBS 2026'],
                ],
            ],
            'last_edited_at' => now(),
        ]);

        $project->sections()->createMany(app(ProjectStructureService::class)->buildInitialSections());

        $ai = \Mockery::mock(AIProviderService::class);
        $callCount = 0;
        $ai->shouldReceive('chat')
            ->twice()
            ->andReturnUsing(function (array $messages, array $options) use ($thesis, &$callCount) {
                $callCount++;

                if ($callCount === 1) {
                    $this->assertStringContainsString('nao exponha a existencia da tese', $messages[0]['content']);
                    $this->assertStringContainsString('CONTEXTO OCULTO DE ORIGEM DO PROJETO', $messages[1]['content']);
                    $this->assertStringContainsString($thesis->title, $messages[1]['content']);
                    $this->assertStringContainsString($thesis->funding_source, $messages[1]['content']);
                    $this->assertSame(0.4, $options['temperature']);

                    return new AIResponse(
                        content: json_encode([
                            'questions' => [
                                [
                                    'key' => 'estrategia_execucao',
                                    'question_text' => 'Qual recorte de execucao transforma a proposta em entrega viavel ja na primeira etapa?',
                                    'help_text' => 'Considere a capacidade atual da rede e a fonte de recurso mais aderente.',
                                    'placeholder' => 'Ex.: reforma da recepcao, novas salas de atendimento e adequacao de fluxos.',
                                    'input_type' => 'textarea',
                                ],
                            ],
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        provider: 'mock',
                        model: 'test',
                        tokensUsed: 120,
                    );
                }

                $this->assertStringContainsString('Banco de Projetos', $messages[0]['content']);
                $this->assertStringContainsString('CONTEXTO OCULTO DE ORIGEM DO PROJETO', $messages[1]['content']);
                $this->assertStringContainsString($thesis->justification, $messages[1]['content']);
                $this->assertStringContainsString($thesis->government_alignment, $messages[1]['content']);
                $this->assertSame(0.35, $options['temperature']);

                return new AIResponse(
                    content: json_encode(['sections' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    provider: 'mock',
                    model: 'test',
                    tokensUsed: 180,
                );
            });

        $sourceThesisContext = new ProjectSourceThesisContextService();
        $questionFlow = new ProjectQuestionFlowService($ai, $sourceThesisContext);
        $documentGeneration = new ProjectDocumentGenerationService(
            $ai,
            app(ProjectStructureService::class),
            $sourceThesisContext,
            app(ProjectContextDossierService::class)
        );

        $questionFlow->ensureGenerated($project, $mayor, true);
        $documentGeneration->generate($project, $mayor);

        $project->refresh()->load(['intakeQuestions', 'sections']);

        $this->assertSame('social', $project->project_type);
        $this->assertSame('source_thesis', data_get($project->metadata, 'questionnaire.type_source'));
        $this->assertTrue(data_get($project->metadata, 'questionnaire.source_thesis_context_used'));
        $this->assertSame($thesis->id, data_get($project->metadata, 'questionnaire.source_thesis_id'));
        $this->assertSame('completed', data_get($project->metadata, 'generation_status'));
        $this->assertGreaterThanOrEqual(1, $project->intakeQuestions->count());

        $fundingSection = $project->sections->firstWhere('section_key', 'fontes_financiamento');
        $alignmentSection = $project->sections->firstWhere('section_key', 'alinhamento_programa_governo');

        $this->assertNotNull($fundingSection);
        $this->assertNotNull($alignmentSection);
        $this->assertStringContainsString($thesis->funding_source, $fundingSection->content);
        $this->assertStringContainsString($thesis->government_alignment, $alignmentSection->content);
    }

    public function test_project_bank_share_creates_internal_notification_and_marks_it_read_on_open(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $advisor = $this->createUser(UserRole::Advisor, $municipality);

        $thesis = ProjectThesis::create([
            'municipality_id' => $municipality->id,
            'title' => 'Nova creche municipal',
            'category' => 'educacao',
            'justification' => 'A fila por vagas na primeira infancia segue reprimida.',
            'potential_impact' => 'Ampliar atendimento e apoiar familias em vulnerabilidade.',
            'funding_source' => 'FNDE com edital previsto para o trimestre.',
            'estimated_size' => 'medio',
            'urgency' => 'alta',
            'execution_complexity' => 'media',
            'resource_deadline' => now()->addDays(25)->toDateString(),
        ]);

        $this->mock(\App\Services\WebPushService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendToUser')->once();
        });

        $response = $this->actingAs($mayor)->post(route('mayor.project-bank.share', $thesis), [
            'recipients' => [$advisor->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $notification = ProjectThesisNotification::query()->first();

        $this->assertNotNull($notification);
        $this->assertSame('share_received', $notification->event_type);
        $this->assertSame($advisor->id, $notification->user_id);
        $this->assertSame(route('mayor.project-bank.show', $thesis), $notification->action_url);
        $this->assertNull($notification->read_at);

        $shareId = $notification->project_thesis_share_id;
        $this->assertNotNull($shareId);

        $showResponse = $this->actingAs($advisor)->get(route('mayor.project-bank.show', $thesis));

        $showResponse->assertOk();
        $showResponse->assertSee('Tese compartilhada com voce');

        $notification->refresh();
        $share = DB::table('project_thesis_shares')->where('id', $shareId)->first();

        $this->assertNotNull($notification->read_at);
        $this->assertNotNull($share);
        $this->assertNotNull($share->viewed_at);
    }

    public function test_project_bank_alert_command_creates_internal_notifications_without_duplication(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $secretary = $this->createUser(UserRole::Secretary, $municipality);

        ProjectThesis::create([
            'municipality_id' => $municipality->id,
            'title' => 'Pavimentacao de corredores escolares',
            'category' => 'infraestrutura',
            'justification' => 'Os acessos a escolas em bairros afastados seguem precarios.',
            'potential_impact' => 'Melhorar seguranca e mobilidade em dias de chuva.',
            'funding_source' => 'Programa estadual com submissao em menos de 30 dias.',
            'estimated_size' => 'grande',
            'urgency' => 'alta',
            'execution_complexity' => 'media',
            'resource_deadline' => now()->addDays(12)->toDateString(),
        ]);

        ProjectThesis::create([
            'municipality_id' => $municipality->id,
            'title' => 'Projeto estrutural de lagoa',
            'category' => 'meio_ambiente',
            'justification' => 'Estudo preliminar para medio prazo.',
            'potential_impact' => 'Ordenar expansao urbana futura.',
            'funding_source' => 'Sem janela imediata.',
            'estimated_size' => 'medio',
            'urgency' => 'baixa',
            'execution_complexity' => 'alta',
            'resource_deadline' => now()->addDays(12)->toDateString(),
        ]);

        $this->mock(\App\Services\WebPushService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendToUser')->twice();
        });

        $this->artisan('project-bank:dispatch-alerts')
            ->expectsOutput('Alertas processados: 2')
            ->assertExitCode(0);

        $this->assertSame(2, ProjectThesisNotification::query()->count());
        $this->assertSame(2, ProjectThesisNotification::query()->where('event_type', 'resource_deadline_alert')->count());
        $this->assertSame(
            [$mayor->id, $secretary->id],
            ProjectThesisNotification::query()->orderBy('user_id')->pluck('user_id')->all()
        );

        $this->artisan('project-bank:dispatch-alerts')
            ->expectsOutput('Alertas processados: 0')
            ->assertExitCode(0);

        $this->assertSame(2, ProjectThesisNotification::query()->count());
    }

    public function test_project_bank_index_renders_saved_section_and_live_filter_shell(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);

        $savedThesis = ProjectThesis::create([
            'municipality_id' => $municipality->id,
            'title' => 'Nova UBS no bairro Norte',
            'category' => 'saude',
            'justification' => 'Cobertura insuficiente na atencao primaria.',
            'potential_impact' => 'Ampliar acesso e reduzir filas.',
            'funding_source' => 'Programa federal de atencao primaria.',
            'estimated_size' => 'medio',
            'urgency' => 'alta',
            'execution_complexity' => 'media',
            'resource_deadline' => now()->addDays(20)->toDateString(),
        ]);

        $libraryThesis = ProjectThesis::create([
            'municipality_id' => $municipality->id,
            'title' => 'Pavimentacao da avenida central',
            'category' => 'infraestrutura',
            'justification' => 'Acesso viario degradado no eixo comercial.',
            'potential_impact' => 'Melhorar mobilidade e seguranca.',
            'funding_source' => 'Programa estadual de mobilidade.',
            'estimated_size' => 'grande',
            'urgency' => 'media',
            'execution_complexity' => 'alta',
            'resource_deadline' => now()->addDays(50)->toDateString(),
        ]);

        DB::table('project_thesis_user_states')->insert([
            'project_thesis_id' => $savedThesis->id,
            'user_id' => $mayor->id,
            'is_saved' => true,
            'last_action_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($mayor)->get(route('mayor.project-bank.index', [
            'scope' => 'saved',
            'category' => 'saude',
        ]));

        $response->assertOk();
        $response->assertSee('Salvas por voce');
        $response->assertSee('Biblioteca do municipio');
        $response->assertSee('Filtros inteligentes');
        $response->assertSee($savedThesis->title);
        $response->assertSee($libraryThesis->title);
        $response->assertSee('Limpar filtros');
    }

    public function test_assistant_context_includes_mandate_projection_summary(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'settings' => [
                'mandato' => [
                    'term_end_date' => now()->addMonths(6)->format('Y-m-d'),
                ],
            ],
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);
        $secondaryAxis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Educacao',
            'icon' => 'E',
            'color' => '#1e3a5f',
            'order' => 2,
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Entregar a UBS Central',
            'score' => 0,
            'status' => 'pending',
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $secondaryAxis->id,
            'text' => 'Reformar escolas municipais',
            'score' => 100,
            'status' => 'fulfilled',
            'is_active' => true,
        ]);

        MandateAction::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'title' => 'Entrega de ambulancia',
            'status' => 'concluido',
            'region' => 'Zona Norte',
            'beneficiaries' => 1200,
        ]);

        $context = app(AssistantContextService::class)->buildOperationalContext($municipality, $mayor);

        $this->assertStringContainsString('Projecao do mandato:', $context['mandate_execution']);
        $this->assertStringContainsString('Fim do mandato considerado:', $context['mandate_execution']);
        $this->assertStringContainsString('Compromissos pendentes sem acao vinculada:', $context['mandate_execution']);
        $this->assertStringContainsString('Eixos abaixo da media para foco imediato:', $context['mandate_execution']);
        $this->assertStringContainsString('Acoes concluidas que podem virar argumento de comunicação e posicionamento:', $context['mandate_execution']);
        $this->assertStringContainsString('Entrega de ambulancia', $context['mandate_execution']);
    }

    public function test_chat_proactive_alerts_include_mandate_projection_when_deviation_is_significant(): void
    {
        $municipality = $this->createMunicipality([
            'onboarding_status' => 'completed',
            'settings' => [
                'mandato' => [
                    'term_end_date' => now()->addMonths(4)->format('Y-m-d'),
                ],
            ],
        ]);
        $mayor = $this->createUser(UserRole::Mayor, $municipality);
        $axis = MandateAxis::create([
            'municipality_id' => $municipality->id,
            'name' => 'Saude',
            'icon' => 'S',
            'color' => '#1e3a5f',
            'order' => 1,
            'is_active' => true,
        ]);

        $projectedPromise = MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Entregar a UBS Central',
            'score' => 0,
            'status' => 'pending',
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Ampliar a cobertura da atencao basica',
            'score' => 0,
            'status' => 'pending',
            'is_active' => true,
        ]);

        MandatePromise::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'text' => 'Reduzir fila por consultas especializadas',
            'score' => 0,
            'status' => 'pending',
            'is_active' => true,
        ]);

        $action = MandateAction::create([
            'municipality_id' => $municipality->id,
            'mandate_axis_id' => $axis->id,
            'title' => 'Obra da UBS Central',
            'status' => 'em_andamento',
            'physical_progress' => 50,
            'start_date' => now()->subDays(25)->format('Y-m-d'),
        ]);

        $action->promises()->attach($projectedPromise->id, [
            'fulfillment_level' => 100,
        ]);

        $action->progressLogs()->create([
            'event_type' => 'progress_updated',
            'description' => 'Avanco observado',
            'from_progress' => 10,
            'to_progress' => 50,
            'from_status' => 'em_andamento',
            'to_status' => 'em_andamento',
            'occurred_at' => now()->subDays(15),
        ]);

        $this->mock(HybridRadarReadService::class, function (MockInterface $mock) use ($municipality) {
            $mock->shouldReceive('municipalityRadarPrograms')
                ->once()
                ->with($municipality, false)
                ->andReturn(collect());
        });

        $alerts = app(ChatProactiveAlertService::class)->buildFor($mayor, $municipality);

        $this->assertContains('mandate_projection_risk', collect($alerts)->pluck('key')->all());
    }

    private function createTestingSchema(): void
    {
        if (Schema::hasTable('municipalities')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_primary');
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });

        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('ibge_code', 7)->unique();
            $table->string('name');
            $table->string('state', 100);
            $table->string('state_code', 2);
            $table->string('region', 20)->nullable();
            $table->integer('population')->nullable();
            $table->decimal('gdp', 15, 2)->nullable();
            $table->decimal('idhm', 5, 3)->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->string('onboarding_status', 20)->default('pending');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->string('subscription_tier', 20)->default('essencial');
            $table->boolean('subscription_active')->default(false);
            $table->timestamp('data_last_synced_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('voice_profile')->nullable();
            $table->json('political_map')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('role', 20)->default('mayor');
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->json('preferences')->nullable();
            $table->boolean('can_register_demands')->default(true);
            $table->timestamps();
        });

        Schema::create('demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('input_type', 30)->default('text');
            $table->text('raw_input')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('area')->nullable();
            $table->string('locality')->nullable();
            $table->string('address')->nullable();
            $table->string('responsible_secretary')->nullable();
            $table->unsignedBigInteger('contact_area_id')->nullable();
            $table->string('priority', 20)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->string('status', 20)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('last_progress_at')->nullable();
            $table->timestamp('completion_requested_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->text('reopened_reason')->nullable();
            $table->text('completion_note')->nullable();
            $table->string('completion_attachment_path')->nullable();
            $table->string('completion_attachment_name')->nullable();
            $table->string('completion_attachment_mime')->nullable();
            $table->unsignedBigInteger('completion_attachment_size')->nullable();
            $table->timestamps();
        });

        Schema::create('demand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('demand_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('contact_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('municipality_localities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('name');
            $table->string('type', 30)->nullable();
            $table->string('zone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('origin_module', 50)->nullable();
            $table->string('title')->nullable();
            $table->json('auto_tags')->nullable();
            $table->json('context')->nullable();
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->unsignedInteger('token_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('generated_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('type', 50)->default('post');
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('content_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('kind', 20)->default('post');
            $table->string('channel', 40)->nullable();
            $table->string('format', 40)->nullable();
            $table->string('tone', 40)->nullable();
            $table->text('description')->nullable();
            $table->text('instruction')->nullable();
            $table->json('default_tones')->nullable();
            $table->json('default_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('morning_briefings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->timestamp('read_at')->nullable();
            $table->text('content')->nullable();
            $table->text('opening_text')->nullable();
            $table->json('sections')->nullable();
            $table->json('cards')->nullable();
            $table->string('scope_profile', 20)->nullable();
            $table->string('delivery_channel', 20)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->unsignedInteger('rag_sources_count')->nullable();
            $table->timestamps();
        });

        Schema::create('social_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('author')->nullable();
            $table->text('content')->nullable();
            $table->string('sentiment', 20)->nullable();
            $table->string('source', 50)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->string('group', 50)->default('general');
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('mention_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('keyword');
            $table->string('type', 20)->default('topic');
            $table->boolean('is_active')->default(true);
            $table->boolean('alert_negative')->default(false);
            $table->timestamps();
        });

        Schema::create('municipality_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 50);
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('indexing_status', 20)->default('pending');
            $table->timestamp('indexed_at')->nullable();
            $table->unsignedInteger('chunks_count')->nullable();
            $table->text('indexing_error')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('government_commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('area', 50)->default('outros');
            $table->string('priority', 10)->default('media');
            $table->string('status', 30)->default('prometido');
            $table->unsignedSmallInteger('progress_percent')->default(0);
            $table->date('deadline')->nullable();
            $table->date('delivered_at')->nullable();
            $table->decimal('budget_allocated', 15, 2)->nullable();
            $table->decimal('budget_spent', 15, 2)->nullable();
            $table->string('budget_source')->nullable();
            $table->string('responsible_secretary')->nullable();
            $table->string('responsible_contact')->nullable();
            $table->text('notes')->nullable();
            $table->string('source_document')->nullable();
            $table->timestamps();
        });

        Schema::create('project_theses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->string('title');
            $table->string('category');
            $table->text('justification');
            $table->text('potential_impact');
            $table->text('funding_source');
            $table->string('estimated_size', 20);
            $table->string('urgency', 20);
            $table->string('execution_complexity', 20);
            $table->text('reference_municipalities')->nullable();
            $table->text('government_alignment')->nullable();
            $table->date('resource_deadline')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('project_thesis_user_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_thesis_id')->constrained('project_theses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_saved')->default(false);
            $table->timestamp('last_action_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_thesis_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_thesis_id')->constrained('project_theses')->cascadeOnDelete();
            $table->foreignId('shared_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_thesis_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_thesis_id')->constrained('project_theses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_thesis_share_id')->nullable()->constrained('project_thesis_shares')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->string('fingerprint', 160);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('last_edited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_thesis_id')->nullable()->constrained('project_theses')->nullOnDelete();
            $table->string('title');
            $table->text('initial_idea');
            $table->string('project_type')->nullable();
            $table->string('status')->default('em_elaboração');
            $table->string('responsible_secretariat')->nullable();
            $table->string('current_phase')->default('estrutura_inicial');
            $table->unsignedInteger('generated_document_version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('section_key');
            $table->unsignedSmallInteger('section_order');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('needs_review')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('project_intake_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('question_key');
            $table->unsignedSmallInteger('question_order');
            $table->text('question_text');
            $table->text('help_text')->nullable();
            $table->string('input_type')->default('textarea');
            $table->text('placeholder')->nullable();
            $table->longText('answer')->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('project_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('permission')->default('editor');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_edit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_section_id')->nullable()->constrained('project_sections')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');
            $table->string('field_name')->nullable();
            $table->longText('previous_content')->nullable();
            $table->longText('new_content')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('project_document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('previous_revision_id')->nullable()->constrained('project_document_revisions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('trigger_action');
            $table->string('summary')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('restored_from_revision_id')->nullable()->constrained('project_document_revisions')->nullOnDelete();
            $table->json('approval_steps')->nullable();
            $table->text('approval_reason')->nullable();
            $table->text('publication_reason')->nullable();
            $table->json('snapshot');
            $table->json('comparison_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('mandate_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->string('name');
            $table->string('icon', 10)->nullable();
            $table->string('color', 20)->default('#1e3a5f');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mandate_promises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->foreignId('mandate_axis_id')->constrained('mandate_axes')->cascadeOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('municipality_documents')->nullOnDelete();
            $table->text('text');
            $table->json('keywords')->nullable();
            $table->string('specificity', 30)->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedSmallInteger('score')->default(0);
            $table->string('status', 20)->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mandate_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->foreignId('mandate_axis_id')->constrained('mandate_axes')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('secretaria')->nullable();
            $table->string('status', 20)->default('nao_iniciado');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('physical_progress')->default(0);
            $table->boolean('uses_milestones_progress')->default(false);
            $table->decimal('investment', 15, 2)->nullable();
            $table->string('funding_source')->nullable();
            $table->string('region')->nullable();
            $table->unsignedInteger('beneficiaries')->nullable();
            $table->string('proof_url')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('mandate_action_promise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandate_action_id')->constrained('mandate_actions')->cascadeOnDelete();
            $table->foreignId('mandate_promise_id')->constrained('mandate_promises')->cascadeOnDelete();
            $table->unsignedSmallInteger('fulfillment_level')->default(0);
            $table->text('fulfillment_justification')->nullable();
            $table->timestamps();
        });

        Schema::create('mandate_action_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandate_action_id')->constrained('mandate_actions')->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mandate_action_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandate_action_id')->constrained('mandate_actions')->cascadeOnDelete();
            $table->foreignId('mandate_action_milestone_id')->nullable()->constrained('mandate_action_milestones')->nullOnDelete();
            $table->string('event_type', 40)->default('milestone_completed');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('from_progress')->nullable();
            $table->unsignedSmallInteger('to_progress')->nullable();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    private function clearTestingData(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'mandate_action_progress_logs',
            'mandate_action_milestones',
            'mandate_action_promise',
            'mandate_actions',
            'project_document_revisions',
            'project_edit_histories',
            'project_collaborators',
            'project_sections',
            'project_thesis_notifications',
            'project_thesis_shares',
            'project_thesis_user_states',
            'projects',
            'project_theses',
            'government_commitments',
            'mandate_promises',
            'mandate_axes',
            'conversations',
            'municipality_localities',
            'municipality_documents',
            'generated_contents',
            'contact_areas',
            'social_mentions',
            'system_settings',
            'morning_briefings',
            'demand_comments',
            'demands',
            'activity_log',
            'model_has_roles',
            'roles',
            'users',
            'municipalities',
        ] as $table) {
            DB::table($table)->delete();
        }

        Schema::enableForeignKeyConstraints();
    }

    private function createMunicipality(array $overrides = []): Municipality
    {
        return Municipality::withoutEvents(function () use ($overrides) {
            return Municipality::create(array_merge([
                'ibge_code' => str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
                'name' => 'Municipio Teste',
                'state' => 'Parana',
                'state_code' => 'PR',
                'region' => 'Sul',
                'onboarding_status' => 'in_progress',
                'subscription_tier' => 'essencial',
                'subscription_active' => true,
            ], $overrides));
        });
    }

    private function createUser(UserRole $role, ?Municipality $municipality = null): User
    {
        $this->seedRole($role);

        $user = User::withoutEvents(function () use ($role, $municipality) {
            return User::factory()->create([
                'role' => $role->value,
                'municipality_id' => $municipality?->id,
                'is_active' => true,
                'can_register_demands' => true,
            ]);
        });

        $user->assignRole($role->value);

        return $user;
    }

    private function seedRole(UserRole $role): Role
    {
        return Role::query()->firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }
}
