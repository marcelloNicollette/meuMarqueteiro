<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Municípios de exemplo para testes e demonstração.
 * Cobre diferentes portes, regiões e planos.
 */
class MunicipalitiesSeeder extends Seeder
{
    public function run(): void
    {
        $municipios = [
            // Pequeno porte — Nordeste
            [
                'municipio' => [
                    'name'               => 'Serrinha',
                    'ibge_code'          => '2930501',
                    'state'              => 'Bahia',
                    'state_code'         => 'BA',
                    'region'             => 'Nordeste',
                    'population'         => 84690,
                    'subscription_tier'  => 'parceiro',
                    'subscription_active' => true,
                ],
                'prefeito' => [
                    'name'  => 'Prefeito de Serrinha',
                    'email' => 'prefeito@serrinha.ba.gov.br',
                    'pass'  => 'Prefeito@2024!',
                ],
            ],
            // Médio porte — Sul
            [
                'municipio' => [
                    'name'               => 'Lages',
                    'ibge_code'          => '4209300',
                    'state'              => 'Santa Catarina',
                    'state_code'         => 'SC',
                    'region'             => 'Sul',
                    'population'         => 157257,
                    'subscription_tier'  => 'estrategico',
                    'subscription_active' => true,
                ],
                'prefeito' => [
                    'name'  => 'Prefeito de Lages',
                    'email' => 'prefeito@lages.sc.gov.br',
                    'pass'  => 'Prefeito@2024!',
                ],
            ],
            // Grande porte — Sudeste
            [
                'municipio' => [
                    'name'               => 'Ribeirão Preto',
                    'ibge_code'          => '3543402',
                    'state'              => 'São Paulo',
                    'state_code'         => 'SP',
                    'region'             => 'Sudeste',
                    'population'         => 721762,
                    'subscription_tier'  => 'estrategico',
                    'subscription_active' => true,
                ],
                'prefeito' => [
                    'name'  => 'Prefeito de Ribeirão Preto',
                    'email' => 'prefeito@ribeiraopreto.sp.gov.br',
                    'pass'  => 'Prefeito@2024!',
                ],
            ],
            // Pequeno porte — Norte
            [
                'municipio' => [
                    'name'               => 'Altamira',
                    'ibge_code'          => '1500602',
                    'state'              => 'Pará',
                    'state_code'         => 'PA',
                    'region'             => 'Norte',
                    'population'         => 113778,
                    'subscription_tier'  => 'essencial',
                    'subscription_active' => true,
                ],
                'prefeito' => [
                    'name'  => 'Prefeito de Altamira',
                    'email' => 'prefeito@altamira.pa.gov.br',
                    'pass'  => 'Prefeito@2024!',
                ],
            ],
            // Médio porte — Centro-Oeste
            [
                'municipio' => [
                    'name'               => 'Rondonópolis',
                    'ibge_code'          => '5107602',
                    'state'              => 'Mato Grosso',
                    'state_code'         => 'MT',
                    'region'             => 'Centro-Oeste',
                    'population'         => 232520,
                    'subscription_tier'  => 'parceiro',
                    'subscription_active' => true,
                ],
                'prefeito' => [
                    'name'  => 'Prefeito de Rondonópolis',
                    'email' => 'prefeito@rondonopolis.mt.gov.br',
                    'pass'  => 'Prefeito@2024!',
                ],
            ],
        ];

        foreach ($municipios as $item) {
            // Não duplica se já existir
            if (Municipality::where('ibge_code', $item['municipio']['ibge_code'])->exists()) {
                $this->command->info("  Pulando {$item['municipio']['name']} — já existe.");
                continue;
            }

            $municipality = Municipality::create(array_merge($item['municipio'], [
                'onboarding_status' => 'pending',
            ]));

            $user = User::create([
                'name'            => $item['prefeito']['name'],
                'email'           => $item['prefeito']['email'],
                'password'        => Hash::make($item['prefeito']['pass']),
                'role'            => 'mayor',
                'municipality_id' => $municipality->id,
                'is_active'       => true,
            ]);
            $user->assignRole('mayor');

            $this->command->info("  ✓ {$municipality->name} ({$municipality->state_code}) criado.");
        }
    }
}
