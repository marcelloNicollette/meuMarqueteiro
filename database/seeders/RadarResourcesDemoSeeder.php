<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Municipality;
use App\Models\ResourceOpportunity;
use App\Models\ResourceOpportunityCycle;
use App\Models\ResourceReopenNotification;
use App\Models\ResourceSource;
use App\Models\ResourceUserSave;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RadarResourcesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ResourceSourcesSeeder::class);

        $mayorRole = Role::query()->firstOrCreate([
            'name' => 'mayor',
            'guard_name' => 'web',
        ]);

        $municipality = Municipality::query()->updateOrCreate(
            ['ibge_code' => '9999991'],
            [
                'name' => 'Cidade Demo Radar',
                'state' => 'Parana',
                'state_code' => 'PR',
                'population' => 48250,
                'idhm' => 0.742,
                'region' => 'Sul',
                'onboarding_status' => 'completed',
                'subscription_tier' => 'estrategico',
                'subscription_active' => true,
                'voice_profile' => [
                    'tone' => 'proximo e objetivo',
                    'style' => 'executivo com linguagem simples',
                ],
            ]
        );

        $mayor = User::query()->updateOrCreate(
            ['email' => 'prefeito.demo.radar@meumarqueteiro.local'],
            [
                'name' => 'Prefeito Demo Radar',
                'password' => Hash::make('Prefeito@2026!'),
                'role' => UserRole::Mayor,
                'municipality_id' => $municipality->id,
                'is_active' => true,
                'preferences' => [
                    'demo_seed' => true,
                ],
            ]
        );
        $mayor->assignRole($mayorRole);

        $sources = ResourceSource::query()
            ->whereIn('key', [
                'portal_transparencia',
                'fnde',
                'bndes',
                'diario_oficial_uniao',
            ])
            ->get()
            ->keyBy('key');

        $records = [
            [
                'source_key' => 'portal_transparencia',
                'canonical_key' => 'demo_radar:saude:ubs_digital',
                'title' => 'Modernizacao da UBS com prontuario digital',
                'short_title' => 'UBS digital',
                'official_title' => 'Programa de modernizacao da atencao primaria com prontuario digital',
                'issuing_body' => 'Ministerio da Saude',
                'thematic_area' => 'saude',
                'resource_type' => 'grant',
                'funding_type' => 'transferencia',
                'resource_scope' => 'federal',
                'summary' => 'Apoio para informatizacao, conectividade e aquisicao de equipamentos em UBS.',
                'description' => 'Oportunidade demo criada para validar o Radar de Recursos com foco em saude digital e reestruturacao da atencao basica.',
                'thematic_tags' => ['saude', 'atencao_basica', 'transformacao_digital'],
                'eligibility_rules' => ['Municipio com equipe e-SUS ativa', 'Plano municipal de saude atualizado'],
                'documentation_requirements' => ['Plano de trabalho', 'Declaracao de capacidade tecnica', 'Comprovacao de conectividade minima'],
                'counterpart_rules' => ['Contrapartida facultativa para itens de ampliacao'],
                'estimated_size' => 'medium',
                'curation_status' => 'curated',
                'latest_status' => 'published',
                'source_url' => 'https://example.test/radar/ubs-digital',
                'compatibility_factors_template' => ['Alta aderência com atencao basica', 'Impacto rapido em atendimento'],
                'viability_factors_template' => ['Baixa contrapartida', 'Execução administrativa simples'],
                'source_metadata' => ['demo_seed' => true, 'demo_group' => 'radar_recursos'],
                'cycle' => [
                    'external_cycle_key' => 'demo-cycle-ubs-digital',
                    'publication_reference' => 'EDT-SAUDE-2026-001',
                    'status' => 'published',
                    'notice_url' => 'https://example.test/radar/ubs-digital/edital',
                    'application_url' => 'https://example.test/radar/ubs-digital/inscricao',
                    'published_at' => now()->subDays(2),
                    'opens_at' => now()->subDay(),
                    'deadline_at' => now()->addDays(28),
                    'total_value' => 1800000,
                    'min_value' => 350000,
                    'counterpart_percentage' => 1.50,
                    'estimated_size' => 'medium',
                    'cycle_metadata' => [
                        'municipality_id' => $municipality->id,
                        'match_score' => 0.93,
                        'match_reason' => 'Alta convergencia com demandas de saude e prontidao da rede municipal.',
                        'viability_level' => 'high',
                        'viability_reason' => 'Baixa contrapartida e boa capacidade de execução imediata.',
                    ],
                ],
                'saved' => true,
                'reopen_notification' => false,
            ],
            [
                'source_key' => 'fnde',
                'canonical_key' => 'demo_radar:educacao:onibus_escolar',
                'title' => 'Renovacao da frota de transporte escolar',
                'short_title' => 'Onibus escolar',
                'official_title' => 'Chamada para renovacao de frota escolar com apoio do FNDE',
                'issuing_body' => 'FNDE',
                'thematic_area' => 'educacao',
                'resource_type' => 'convenio',
                'funding_type' => 'convenio',
                'resource_scope' => 'federal',
                'summary' => 'Edital para renovacao da frota escolar com foco em rotas rurais e seguranca.',
                'description' => 'Oportunidade demo para validar cenarios de prazo curto, detalhamento do edital e criacao de acao a partir do radar.',
                'thematic_tags' => ['educacao', 'transporte_escolar', 'zona_rural'],
                'eligibility_rules' => ['Censo escolar atualizado', 'Mapa de rotas validado'],
                'documentation_requirements' => ['Diagnóstico da frota', 'Mapa das rotas', 'Plano de manutencao'],
                'counterpart_rules' => ['Contrapartida minima de 3%'],
                'estimated_size' => 'large',
                'curation_status' => 'curated',
                'latest_status' => 'closing_soon',
                'source_url' => 'https://example.test/radar/onibus-escolar',
                'compatibility_factors_template' => ['Cobertura rural elevada', 'Necessidade de renovacao da frota'],
                'viability_factors_template' => ['Prazo curto', 'Demanda documental moderada'],
                'source_metadata' => ['demo_seed' => true, 'demo_group' => 'radar_recursos'],
                'cycle' => [
                    'external_cycle_key' => 'demo-cycle-onibus-escolar',
                    'publication_reference' => 'FNDE-TR-2026-008',
                    'status' => 'closing_soon',
                    'notice_url' => 'https://example.test/radar/onibus-escolar/edital',
                    'application_url' => 'https://example.test/radar/onibus-escolar/inscricao',
                    'published_at' => now()->subDays(8),
                    'opens_at' => now()->subDays(7),
                    'deadline_at' => now()->addDays(5),
                    'total_value' => 4200000,
                    'min_value' => 900000,
                    'counterpart_percentage' => 3.00,
                    'estimated_size' => 'large',
                    'cycle_metadata' => [
                        'municipality_id' => $municipality->id,
                        'match_score' => 0.88,
                        'match_reason' => 'Forte aderência com transporte escolar e rotas rurais do município.',
                        'viability_level' => 'medium',
                        'viability_reason' => 'Bom encaixe, mas exige mobilizacao rápida de documentos.',
                    ],
                ],
                'saved' => false,
                'reopen_notification' => false,
            ],
            [
                'source_key' => 'bndes',
                'canonical_key' => 'demo_radar:infra:iluminacao_publica',
                'title' => 'Eficiência energetica para iluminacao publica',
                'short_title' => 'Iluminacao publica',
                'official_title' => 'Linha de apoio para eficiencia energetica e modernizacao da iluminacao publica',
                'issuing_body' => 'BNDES',
                'thematic_area' => 'infraestrutura',
                'resource_type' => 'credit',
                'funding_type' => 'credito',
                'resource_scope' => 'financiamento',
                'summary' => 'Linha para modernizacao de parque luminotecnico, telegestao e reducao de consumo.',
                'description' => 'Oportunidade demo para testar casos de monitoramento e comparacao entre compatibilidade e viabilidade.',
                'thematic_tags' => ['infraestrutura', 'energia', 'cidades_inteligentes'],
                'eligibility_rules' => ['Capacidade de endividamento preservada', 'Projeto basico de modernizacao'],
                'documentation_requirements' => ['Projeto basico', 'Memorial descritivo', 'Demonstrativo de economia estimada'],
                'counterpart_rules' => ['Exige estrutura de garantia contratual'],
                'estimated_size' => 'large',
                'curation_status' => 'curated',
                'latest_status' => 'monitoring',
                'source_url' => 'https://example.test/radar/iluminacao-publica',
                'compatibility_factors_template' => ['Necessidade urbana clara', 'Potencial de economia relevante'],
                'viability_factors_template' => ['Exige garantia e projeto estruturado'],
                'source_metadata' => ['demo_seed' => true, 'demo_group' => 'radar_recursos'],
                'cycle' => [
                    'external_cycle_key' => 'demo-cycle-iluminacao-publica',
                    'publication_reference' => 'BNDES-IP-2026-014',
                    'status' => 'monitoring',
                    'notice_url' => 'https://example.test/radar/iluminacao-publica/edital',
                    'application_url' => 'https://example.test/radar/iluminacao-publica/inscricao',
                    'published_at' => now()->subDays(15),
                    'opens_at' => now()->subDays(12),
                    'deadline_at' => now()->addDays(45),
                    'total_value' => 7500000,
                    'min_value' => 2500000,
                    'counterpart_percentage' => 8.00,
                    'estimated_size' => 'large',
                    'cycle_metadata' => [
                        'municipality_id' => $municipality->id,
                        'match_score' => 0.72,
                        'match_reason' => 'Tema aderente, mas depende de projeto mais robusto e capacidade de financiamento.',
                        'viability_level' => 'medium',
                        'viability_reason' => 'Viavel com estruturacao tecnica e garantias adequadas.',
                    ],
                ],
                'saved' => false,
                'reopen_notification' => false,
            ],
            [
                'source_key' => 'diario_oficial_uniao',
                'canonical_key' => 'demo_radar:social:cozinha_comunitaria',
                'title' => 'Implantacao de cozinha comunitaria',
                'short_title' => 'Cozinha comunitaria',
                'official_title' => 'Edital para implantacao e apoio a cozinhas comunitarias',
                'issuing_body' => 'Ministerio do Desenvolvimento Social',
                'thematic_area' => 'social',
                'resource_type' => 'grant',
                'funding_type' => 'subvencao',
                'resource_scope' => 'transversal',
                'summary' => 'Oportunidade encerrada recentemente, mantida visivel para teste de notificacao de reabertura.',
                'description' => 'Oportunidade demo criada para validar filtros de encerradas, detalhe expandido e reabertura ativa.',
                'thematic_tags' => ['assistencia_social', 'seguranca_alimentar'],
                'eligibility_rules' => ['Diagnóstico territorial de vulnerabilidade', 'Area adequada para implantacao'],
                'documentation_requirements' => ['Plano de execução', 'Croqui do espaco', 'Manifestacao da secretaria responsável'],
                'counterpart_rules' => ['Contrapartida operacional da equipe local'],
                'estimated_size' => 'small',
                'curation_status' => 'curated',
                'latest_status' => 'closed_recently',
                'source_url' => 'https://example.test/radar/cozinha-comunitaria',
                'compatibility_factors_template' => ['Alta urgência social', 'Boa aderência ao diagnostico municipal'],
                'viability_factors_template' => ['Necessita equipe minima e espaco adequado'],
                'source_metadata' => ['demo_seed' => true, 'demo_group' => 'radar_recursos'],
                'cycle' => [
                    'external_cycle_key' => 'demo-cycle-cozinha-comunitaria',
                    'publication_reference' => 'DOU-MDS-2026-021',
                    'status' => 'closed_recently',
                    'notice_url' => 'https://example.test/radar/cozinha-comunitaria/edital',
                    'application_url' => null,
                    'published_at' => now()->subDays(24),
                    'opens_at' => now()->subDays(20),
                    'deadline_at' => now()->subDays(6),
                    'closed_at' => now()->subDays(6),
                    'closed_visibility_until' => now()->addDays(54),
                    'total_value' => 480000,
                    'min_value' => 180000,
                    'counterpart_percentage' => 2.00,
                    'estimated_size' => 'small',
                    'cycle_metadata' => [
                        'municipality_id' => $municipality->id,
                        'match_score' => 0.86,
                        'match_reason' => 'Boa aderência com seguranca alimentar e capacidade de implantacao local.',
                        'viability_level' => 'high',
                        'viability_reason' => 'Encerrada agora, mas com grande potencial em caso de reabertura.',
                    ],
                ],
                'saved' => true,
                'reopen_notification' => true,
            ],
        ];

        $createdCount = 0;

        foreach ($records as $record) {
            $source = $sources->get($record['source_key']);

            if (!$source instanceof ResourceSource) {
                continue;
            }

            $opportunity = ResourceOpportunity::query()->updateOrCreate(
                ['canonical_key' => $record['canonical_key']],
                [
                    'resource_source_id' => $source->id,
                    'source_fingerprint' => $record['canonical_key'],
                    'title' => $record['title'],
                    'short_title' => $record['short_title'],
                    'official_title' => $record['official_title'],
                    'issuing_body' => $record['issuing_body'],
                    'thematic_area' => $record['thematic_area'],
                    'resource_type' => $record['resource_type'],
                    'funding_type' => $record['funding_type'],
                    'resource_scope' => $record['resource_scope'],
                    'summary' => $record['summary'],
                    'description' => $record['description'],
                    'thematic_tags' => $record['thematic_tags'],
                    'eligibility_rules' => $record['eligibility_rules'],
                    'documentation_requirements' => $record['documentation_requirements'],
                    'counterpart_rules' => $record['counterpart_rules'],
                    'estimated_size' => $record['estimated_size'],
                    'curation_status' => $record['curation_status'],
                    'latest_status' => $record['latest_status'],
                    'source_url' => $record['source_url'],
                    'compatibility_factors_template' => $record['compatibility_factors_template'],
                    'viability_factors_template' => $record['viability_factors_template'],
                    'source_metadata' => $record['source_metadata'],
                    'first_seen_at' => now()->subDays(30),
                    'last_seen_at' => now(),
                    'last_published_at' => $record['cycle']['published_at'],
                ]
            );

            $cycle = ResourceOpportunityCycle::query()->updateOrCreate(
                ['external_cycle_key' => $record['cycle']['external_cycle_key']],
                array_merge($record['cycle'], [
                    'resource_opportunity_id' => $opportunity->id,
                    'is_current' => true,
                ])
            );

            if ($record['saved']) {
                ResourceUserSave::query()->updateOrCreate(
                    [
                        'user_id' => $mayor->id,
                        'resource_opportunity_id' => $opportunity->id,
                    ],
                    [
                        'municipality_id' => $municipality->id,
                        'resource_opportunity_cycle_id' => $cycle->id,
                        'saved_from' => 'radar_demo',
                        'preferences' => ['demo_seed' => true],
                        'last_viewed_at' => now(),
                    ]
                );
            } else {
                ResourceUserSave::query()
                    ->where('user_id', $mayor->id)
                    ->where('resource_opportunity_id', $opportunity->id)
                    ->delete();
            }

            if ($record['reopen_notification']) {
                ResourceReopenNotification::query()->updateOrCreate(
                    [
                        'user_id' => $mayor->id,
                        'resource_opportunity_id' => $opportunity->id,
                        'channel' => 'platform',
                    ],
                    [
                        'municipality_id' => $municipality->id,
                        'last_cycle_id' => $cycle->id,
                        'status' => 'active',
                        'criteria' => ['demo_seed' => true, 'notify_on_reopen' => true],
                        'subscribed_at' => now(),
                        'cancelled_at' => null,
                    ]
                );
            } else {
                ResourceReopenNotification::query()
                    ->where('user_id', $mayor->id)
                    ->where('resource_opportunity_id', $opportunity->id)
                    ->delete();
            }

            $createdCount++;
        }

        $this->command?->info('✅ Demo local do Radar de Recursos carregada.');
        $this->command?->table(
            ['Item', 'Valor'],
            [
                ['Municipio demo', $municipality->name . ' / ' . $municipality->state_code],
                ['Prefeito demo', $mayor->email],
                ['Senha', 'Prefeito@2026!'],
                ['Oportunidades demo', (string) $createdCount],
            ]
        );
    }
}
