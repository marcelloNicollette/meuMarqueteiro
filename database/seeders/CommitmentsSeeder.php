<?php

namespace Database\Seeders;

use App\Models\GovernmentCommitment;
use App\Models\Municipality;
use Illuminate\Database\Seeder;

class CommitmentsSeeder extends Seeder
{
    public function run(): void
    {
        // Pega o primeiro município ativo
        $municipality = Municipality::where('subscription_active', true)->first();

        if (!$municipality) {
            $this->command->warn('Nenhum município ativo encontrado. Rode MunicipalitiesSeeder primeiro.');
            return;
        }

        // Limpa compromissos existentes do município para evitar duplicatas
        GovernmentCommitment::where('municipality_id', $municipality->id)->delete();

        $this->command->info("Criando compromissos para: {$municipality->name}/{$municipality->state_code}");

        $commitments = [

            // ── SAÚDE ──────────────────────────────────────────────
            [
                'title'                  => 'Construção do novo Posto de Saúde do Bairro Nova Esperança',
                'description'            => 'Construção de UBS de 280m² com consultórios, sala de vacinas, farmácia básica e área de espera coberta. Atenderá aproximadamente 4.200 pessoas da região norte.',
                'area'                   => 'saude',
                'priority'               => 'alta',
                'status'                 => 'em_andamento',
                'progress_percent'       => 45,
                'deadline'               => '2026-08-30',
                'responsible_secretary'  => 'Secretaria Municipal de Saúde',
                'budget_allocated'       => 890000.00,
                'budget_spent'           => 320000.00,
                'budget_source'          => 'federal',
                'notes'                  => 'Recurso via PAB/MS. Licitação concluída, obra em fase de estrutura.',
            ],
            [
                'title'                  => 'Contratação de 4 médicos para ampliação do PSF',
                'description'            => 'Ampliação da cobertura de Saúde da Família com contratação de 4 médicos clínicos gerais para atendimento nas USFs existentes.',
                'area'                   => 'saude',
                'priority'               => 'alta',
                'status'                 => 'entregue',
                'progress_percent'       => 100,
                'deadline'               => '2025-06-01',
                'delivered_at'           => '2025-05-15',
                'responsible_secretary'  => 'Secretaria Municipal de Saúde',
                'budget_allocated'       => 480000.00,
                'budget_spent'           => 480000.00,
                'budget_source'          => 'municipal',
                'notes'                  => 'Concurso público realizado. Todos os aprovados empossados.',
            ],
            [
                'title'                  => 'Implantação do serviço de saúde bucal nas escolas municipais',
                'description'            => 'Programa de prevenção e tratamento odontológico para alunos da rede municipal, com visitas quinzenais de equipe volante.',
                'area'                   => 'saude',
                'priority'               => 'media',
                'status'                 => 'prometido',
                'progress_percent'       => 0,
                'deadline'               => '2026-12-31',
                'responsible_secretary'  => 'Secretaria Municipal de Saúde',
                'budget_allocated'       => 120000.00,
                'budget_source'          => 'municipal',
            ],

            // ── EDUCAÇÃO ───────────────────────────────────────────
            [
                'title'                  => 'Reforma e ampliação da Escola Municipal Prof. João Figueira',
                'description'            => 'Reforma estrutural com troca de cobertura, pintura, instalação de 6 novos ventiladores por sala e construção de quadra poliesportiva coberta.',
                'area'                   => 'educacao',
                'priority'               => 'alta',
                'status'                 => 'em_andamento',
                'progress_percent'       => 70,
                'deadline'               => '2026-02-28',
                'responsible_secretary'  => 'Secretaria Municipal de Educação',
                'budget_allocated'       => 650000.00,
                'budget_spent'           => 455000.00,
                'budget_source'          => 'federal',
                'notes'                  => 'PAR/FNDE. Prazo prorrogado devido a chuvas em janeiro. Obra em fase de acabamento.',
            ],
            [
                'title'                  => 'Programa de merenda escolar 100% orgânica da agricultura familiar',
                'description'            => 'Substituição gradual dos fornecedores convencionais por agricultores familiares do município, cumprindo os 30% exigidos pelo PNAE.',
                'area'                   => 'educacao',
                'priority'               => 'media',
                'status'                 => 'entregue',
                'progress_percent'       => 100,
                'deadline'               => '2025-03-01',
                'responsible_secretary'  => 'Secretaria Municipal de Educação',
                'budget_allocated'       => 0,
                'budget_source'          => 'federal',
                'notes'                  => 'Meta atingida. 34% da merenda agora vem de agricultores familiares cadastrados.',
            ],
            [
                'title'                  => 'Implantação de laboratório de informática em 3 escolas',
                'description'            => 'Instalação de 30 computadores por escola com internet fibra óptica e capacitação de professores para uso pedagógico.',
                'area'                   => 'educacao',
                'priority'               => 'alta',
                'status'                 => 'em_risco',
                'progress_percent'       => 20,
                'deadline'               => '2026-07-15',
                'responsible_secretary'  => 'Secretaria Municipal de Educação',
                'budget_allocated'       => 280000.00,
                'budget_spent'           => 56000.00,
                'budget_source'          => 'estadual',
                'notes'                  => 'ATENÇÃO: Atraso na liberação de convênio estadual. Acompanhar junto à SEEC/BA.',
            ],

            // ── INFRAESTRUTURA ─────────────────────────────────────
            [
                'title'                  => 'Pavimentação de 8km de ruas no Bairro Industrial e adjacências',
                'description'            => 'Pavimentação asfáltica com drenagem pluvial, guias e sarjetas em ruas do Bairro Industrial, São Francisco e Novo Horizonte.',
                'area'                   => 'infraestrutura',
                'priority'               => 'alta',
                'status'                 => 'em_andamento',
                'progress_percent'       => 55,
                'deadline'               => '2026-10-31',
                'responsible_secretary'  => 'Secretaria de Obras e Urbanismo',
                'budget_allocated'       => 2400000.00,
                'budget_spent'           => 1320000.00,
                'budget_source'          => 'federal',
                'notes'                  => 'Emenda parlamentar Dep. Raimundo Costa + Transferegov. 4,4km concluídos.',
            ],
            [
                'title'                  => 'Revitalização da Praça da Matriz',
                'description'            => 'Reforma completa da praça central com novo paisagismo, iluminação em LED, bancos, playground e academia ao ar livre.',
                'area'                   => 'infraestrutura',
                'priority'               => 'media',
                'status'                 => 'entregue',
                'progress_percent'       => 100,
                'deadline'               => '2025-09-07',
                'responsible_secretary'  => 'Secretaria de Obras e Urbanismo',
                'budget_allocated'       => 380000.00,
                'budget_spent'           => 367000.00,
                'budget_source'          => 'municipal',
                'notes'                  => 'Inaugurada em 7/set/2025 com presença de lideranças locais.',
            ],
            [
                'title'                  => 'Construção de 60 casas populares (Minha Casa Minha Vida)',
                'description'            => 'Parceria com Caixa Econômica Federal para atender famílias de baixa renda cadastradas no CadÚnico.',
                'area'                   => 'infraestrutura',
                'priority'               => 'alta',
                'status'                 => 'prometido',
                'progress_percent'       => 8,
                'deadline'               => '2027-12-31',
                'responsible_secretary'  => 'Secretaria de Habitação',
                'budget_allocated'       => 4200000.00,
                'budget_source'          => 'federal',
                'notes'                  => 'Projeto enviado à CEF. Aguardando aprovação de viabilidade técnica.',
            ],

            // ── SOCIAL ─────────────────────────────────────────────
            [
                'title'                  => 'Ampliação do CRAS com equipe multidisciplinar',
                'description'            => 'Contratação de assistente social, psicólogo e terapeuta ocupacional para o Centro de Referência de Assistência Social.',
                'area'                   => 'social',
                'priority'               => 'alta',
                'status'                 => 'entregue',
                'progress_percent'       => 100,
                'deadline'               => '2025-04-01',
                'responsible_secretary'  => 'Secretaria de Assistência Social',
                'budget_allocated'       => 210000.00,
                'budget_spent'           => 210000.00,
                'budget_source'          => 'federal',
                'notes'                  => 'Equipe completa contratada via PSS. CRAS operando com capacidade ampliada.',
            ],
            [
                'title'                  => 'Programa Bolsa Municipal para famílias em extrema vulnerabilidade',
                'description'            => 'Benefício mensal de R$200 para 150 famílias identificadas em extrema pobreza não atendidas pelo programa federal.',
                'area'                   => 'social',
                'priority'               => 'media',
                'status'                 => 'em_andamento',
                'progress_percent'       => 80,
                'deadline'               => '2025-12-01',
                'responsible_secretary'  => 'Secretaria de Assistência Social',
                'budget_allocated'       => 360000.00,
                'budget_spent'           => 288000.00,
                'budget_source'          => 'municipal',
                'notes'                  => '120 famílias já cadastradas e recebendo. Meta de 150 até dezembro.',
            ],

            // ── ECONOMIA ───────────────────────────────────────────
            [
                'title'                  => 'Criação do Programa Municipal de Microcrédito para MEIs',
                'description'            => 'Linha de crédito de até R$10.000 com juros subsidiados para microempreendedores individuais do município.',
                'area'                   => 'economia',
                'priority'               => 'media',
                'status'                 => 'em_risco',
                'progress_percent'       => 30,
                'deadline'               => '2026-06-30',
                'responsible_secretary'  => 'Secretaria de Desenvolvimento Econômico',
                'budget_allocated'       => 500000.00,
                'budget_spent'           => 0,
                'budget_source'          => 'misto',
                'notes'                  => 'ATENÇÃO: Parceria com banco prevista ainda não formalizada. Reunião agendada para março.',
            ],
        ];

        foreach ($commitments as $data) {
            if (!isset($data['delivered_at'])) {
                $data['delivered_at'] = null;
            }
            $data['municipality_id'] = $municipality->id;
            GovernmentCommitment::create($data);
        }

        $this->command->info('✅ ' . count($commitments) . " compromissos criados para {$municipality->name}.");
        $this->command->line('   Acesse: http://localhost:8000/mayor/mandato/commitments');
    }
}
