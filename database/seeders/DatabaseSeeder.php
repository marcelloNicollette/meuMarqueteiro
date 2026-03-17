<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Criar roles no Spatie ─────────────────────────────────────────
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $mayorRole = Role::firstOrCreate(['name' => 'mayor', 'guard_name' => 'web']);

        // ── Admin ─────────────────────────────────────────────────────────
        $admin = User::create([
            'name'      => 'Consultor Admin',
            'email'     => 'admin@meumarqueteiro.com.br',
            'password'  => Hash::make('Admin@2024!'),
            'role'      => UserRole::Admin,
            'is_active' => true,
        ]);
        $admin->assignRole($adminRole);

        // ── Município de exemplo ──────────────────────────────────────────
        /*$municipality = Municipality::create([
            'ibge_code'           => '3550308',
            'name'                => 'Exemplo do Sul',
            'state'               => 'São Paulo',
            'state_code'          => 'SP',
            'population'          => 35000,
            'idhm'                => 0.720,
            'onboarding_status'   => 'completed',
            'subscription_tier'   => 'estrategico',
            'subscription_active' => true,
            'voice_profile' => [
                'tone'       => 'próximo e acessível',
                'style'      => 'informal mas respeitoso',
                'vocabulary' => 'simples, sem tecnicismos',
            ],
        ]);*/

        // ── Prefeito de exemplo ───────────────────────────────────────────
        /*$mayor = User::create([
            'name'            => 'João da Silva',
            'email'           => 'prefeito@exemplodosul.sp.gov.br',
            'password'        => Hash::make('Prefeito@2024!'),
            'role'            => UserRole::Mayor,
            'municipality_id' => $municipality->id,
            'is_active'       => true,
        ]);
        $mayor->assignRole($mayorRole);*/

        // ── Compromissos de exemplo ───────────────────────────────────────
        /*$municipality->governmentCommitments()->createMany([
            [
                'title'                 => 'Pavimentação do bairro Santa Cruz',
                'area'                  => 'infraestrutura',
                'status'                => 'em_andamento',
                'priority'              => 'alta',
                'progress_percent'      => 45,
                'responsible_secretary' => 'Secretaria de Obras',
                'budget_allocated'      => 850000.00,
                'budget_spent'          => 382500.00,
            ],
            [
                'title'                 => 'Reforma da UBS Central',
                'area'                  => 'saude',
                'status'                => 'em_risco',
                'priority'              => 'alta',
                'progress_percent'      => 20,
                'responsible_secretary' => 'Secretaria de Saúde',
                'budget_allocated'      => 420000.00,
                'budget_spent'          => 84000.00,
            ],
            [
                'title'                 => 'Programa de alfabetização de adultos',
                'area'                  => 'educacao',
                'status'                => 'em_andamento',
                'priority'              => 'media',
                'progress_percent'      => 60,
                'responsible_secretary' => 'Secretaria de Educação',
                'budget_allocated'      => 180000.00,
                'budget_spent'          => 108000.00,
            ],
        ]);
        */

        $this->command->info('✅ Seed concluído!');
        $this->command->table(
            ['Tipo', 'E-mail', 'Senha'],
            [
                ['Admin', 'admin@meumarqueteiro.com.br', 'Admin@2024!'],
                //['Prefeito', 'prefeito@exemplodosul.sp.gov.br', 'Prefeito@2024!'],
            ]
        );
    }
}
