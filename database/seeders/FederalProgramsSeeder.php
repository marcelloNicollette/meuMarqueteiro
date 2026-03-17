<?php

namespace Database\Seeders;

use App\Models\FederalProgramAlert;
use App\Models\Municipality;
use Illuminate\Database\Seeder;

class FederalProgramsSeeder extends Seeder
{
    public function run(): void
    {
        $municipality = Municipality::where('subscription_active', true)->first();

        if (!$municipality) {
            $this->command->warn('Nenhum município ativo encontrado.');
            return;
        }

        FederalProgramAlert::where('municipality_id', $municipality->id)->delete();

        $this->command->info("Criando programas federais para: {$municipality->name}/{$municipality->state_code}");

        $programs = [

            // ── SAÚDE ──────────────────────────────────────
            [
                'program_name'    => 'Programa de Requalificação das Unidades Básicas de Saúde (REQUALIFICA UBS)',
                'ministry'        => 'Ministério da Saúde',
                'program_code'    => 'REQUALIFICA-UBS-2025',
                'description'     => 'Recurso federal para reforma, ampliação e construção de Unidades Básicas de Saúde. Prioridade para municípios com cobertura ESF abaixo de 70%. Transferência fundo a fundo sem necessidade de convênio.',
                'area'            => 'saude',
                'max_value'       => 500000.00,
                'min_value'       => 100000.00,
                'funding_type'    => 'transferencia',
                'open_date'       => '2025-02-01',
                'deadline'        => '2026-06-30',
                'status'          => 'open',
                'match_score'     => 0.92,
                'match_reason'    => 'Município tem cobertura ESF de 61% e UBS com mais de 15 anos de construção.',
                'source_url'      => 'https://www.gov.br/saude/pt-br/assuntos/saude-de-a-a-z/u/ubs',
                'source_platform' => 'ministerio',
                'ai_matched'      => true,
            ],
            [
                'program_name'    => 'Incentivo Financeiro de Custeio — Saúde Bucal na Atenção Primária',
                'ministry'        => 'Fundo Nacional de Saúde (FNS)',
                'program_code'    => 'FNS-SAUDE-BUCAL-2025',
                'description'     => 'Incentivo mensal por equipe de saúde bucal implantada. Modalidade I: R$2.950/mês; Modalidade II: R$4.700/mês. Município precisa ter CEO ou equipe volante habilitada.',
                'area'            => 'saude',
                'max_value'       => 56400.00,
                'funding_type'    => 'transferencia',
                'open_date'       => '2025-01-01',
                'deadline'        => null,
                'status'          => 'open',
                'match_score'     => 0.88,
                'match_reason'    => 'Município não possui equipe de saúde bucal cadastrada no CNES — alta elegibilidade.',
                'source_url'      => 'https://www.gov.br/saude/pt-br/assuntos/saude-de-a-a-z/s/saude-bucal',
                'source_platform' => 'ministerio',
                'ai_matched'      => true,
            ],

            // ── EDUCAÇÃO ───────────────────────────────────
            [
                'program_name'    => 'PAR — Plano de Ações Articuladas / Obras FNDE 2025',
                'ministry'        => 'Fundo Nacional de Desenvolvimento da Educação (FNDE)',
                'program_code'    => 'FNDE-PAR-2025',
                'description'     => 'Financiamento para construção, reforma e ampliação de escolas da rede municipal. Inclui quadras poliesportivas, banheiros adaptados e salas de recursos multifuncionais. Exige PAR vigente e contrapartida mínima de 1%.',
                'area'            => 'educacao',
                'max_value'       => 1200000.00,
                'min_value'       => 200000.00,
                'funding_type'    => 'convenio',
                'open_date'       => '2025-03-01',
                'deadline'        => '2026-04-30',
                'status'          => 'open',
                'match_score'     => 0.95,
                'match_reason'    => 'Escola Municipal Prof. João Figueira foi identificada no INEP com infraestrutura crítica.',
                'source_url'      => 'https://www.fnde.gov.br/index.php/programas/par',
                'source_platform' => 'fnde',
                'ai_matched'      => true,
            ],
            [
                'program_name'    => 'Programa Nacional de Alimentação Escolar — PNAE 2025',
                'ministry'        => 'Fundo Nacional de Desenvolvimento da Educação (FNDE)',
                'program_code'    => 'PNAE-2025',
                'description'     => 'Repasse per capita para alimentação escolar: R$0,53/dia para ensino fundamental, R$0,64 para EJA, R$1,07 para alunos indígenas e quilombolas. Cumprir 30% de agricultura familiar é obrigatório.',
                'area'            => 'educacao',
                'max_value'       => 320000.00,
                'funding_type'    => 'transferencia',
                'open_date'       => '2025-01-01',
                'deadline'        => null,
                'status'          => 'open',
                'match_score'     => 1.00,
                'match_reason'    => 'Programa automático — todos os municípios com rede escolar recebem. Verificar adimplência no FNDE.',
                'source_url'      => 'https://www.fnde.gov.br/index.php/programas/pnae',
                'source_platform' => 'fnde',
                'ai_matched'      => true,
            ],
            [
                'program_name'    => 'Programa Escola em Tempo Integral — MEC 2025',
                'ministry'        => 'Ministério da Educação (MEC)',
                'program_code'    => 'MEC-ETI-2025',
                'description'     => 'Apoio financeiro para ampliação da jornada escolar para tempo integral. R$2.000/aluno/ano para novos alunos matriculados em tempo integral. Meta nacional: 1 milhão de matrículas novas em 2025.',
                'area'            => 'educacao',
                'max_value'       => 800000.00,
                'funding_type'    => 'transferencia',
                'open_date'       => '2025-02-15',
                'deadline'        => '2026-03-31',
                'status'          => 'closing',
                'match_score'     => 0.79,
                'match_reason'    => 'Município tem 3 escolas aptas. Potencial de 400 novas matrículas em tempo integral.',
                'source_url'      => 'https://www.gov.br/mec/pt-br/assuntos/noticias/2024/escola-em-tempo-integral',
                'source_platform' => 'ministerio',
                'ai_matched'      => true,
            ],

            // ── INFRAESTRUTURA ─────────────────────────────
            [
                'program_name'    => 'Programa de Pavimentação e Qualificação de Vias Urbanas — PAC 2025',
                'ministry'        => 'Ministério das Cidades',
                'program_code'    => 'PAC-PAVIM-2025',
                'description'     => 'Recursos para pavimentação de vias urbanas em municípios com até 100 mil habitantes. Inclui drenagem pluvial, calçadas e acessibilidade. Habilitação via Transferegov com projeto técnico.',
                'area'            => 'infraestrutura',
                'max_value'       => 5000000.00,
                'min_value'       => 500000.00,
                'funding_type'    => 'convenio',
                'open_date'       => '2025-01-15',
                'deadline'        => '2025-12-31',
                'status'          => 'closing',
                'match_score'     => 0.91,
                'match_reason'    => 'Município tem déficit de 18km de vias sem pavimentação identificado no SNIS.',
                'source_url'      => 'https://www.gov.br/cidades/pt-br/assuntos/habitacao/pac',
                'source_platform' => 'transferegov',
                'ai_matched'      => true,
            ],
            [
                'program_name'    => 'Iluminação Pública Eficiente — Troca de Lâmpadas para LED (ANEEL)',
                'ministry'        => 'ANEEL / Programa de Eficiência Energética',
                'program_code'    => 'ANEEL-PEE-LED-2025',
                'description'     => 'Programa de eficiência energética das distribuidoras. Município pode pleitear substituição de luminárias convencionais por LED sem custo direto. Redução média de 60% no consumo de energia pública.',
                'area'            => 'infraestrutura',
                'max_value'       => 1800000.00,
                'funding_type'    => 'transferencia',
                'open_date'       => '2025-01-01',
                'deadline'        => '2026-12-31',
                'status'          => 'open',
                'match_score'     => 0.85,
                'match_reason'    => 'Município ainda utiliza luminárias vapor de sódio. Potencial de redução de R$180k/ano em energia.',
                'source_url'      => 'https://www.aneel.gov.br/programa-de-eficiencia-energetica',
                'source_platform' => 'ministerio',
                'ai_matched'      => true,
            ],

            // ── SANEAMENTO ─────────────────────────────────
            [
                'program_name'    => 'Novo PAC Saneamento — Esgotamento Sanitário 2025',
                'ministry'        => 'Ministério das Cidades / Caixa Econômica Federal',
                'program_code'    => 'PAC-SANEAMENTO-2025',
                'description'     => 'Recursos para sistemas de esgotamento sanitário: redes coletoras, estações de tratamento e ligações domiciliares. Meta do Marco do Saneamento: 90% de cobertura até 2033. Contrapartida de 5%.',
                'area'            => 'saneamento',
                'max_value'       => 8000000.00,
                'min_value'       => 1000000.00,
                'funding_type'    => 'convenio',
                'open_date'       => '2025-02-01',
                'deadline'        => '2026-08-31',
                'status'          => 'open',
                'match_score'     => 0.97,
                'match_reason'    => 'Município tem apenas 32% de cobertura de esgoto (SNIS 2023) — prioridade máxima no Marco do Saneamento.',
                'source_url'      => 'https://www.gov.br/cidades/pt-br/assuntos/saneamento/novo-pac-saneamento',
                'source_platform' => 'caixa',
                'ai_matched'      => true,
            ],

            // ── HABITAÇÃO ──────────────────────────────────
            [
                'program_name'    => 'Minha Casa Minha Vida — Faixa 1 Municípios até 50 mil hab.',
                'ministry'        => 'Ministério das Cidades / Caixa Econômica Federal',
                'program_code'    => 'MCMV-FAIXA1-2025',
                'description'     => 'Construção de unidades habitacionais para famílias com renda bruta mensal até R$2.640. Subsídio de até 95% do valor do imóvel. Município deve apresentar demanda habitacional e terreno.',
                'area'            => 'habitacao',
                'max_value'       => 6000000.00,
                'funding_type'    => 'convenio',
                'open_date'       => '2025-01-01',
                'deadline'        => null,
                'status'          => 'open',
                'match_score'     => 0.88,
                'match_reason'    => 'Cadastro habitacional local com 380 famílias em espera. Município elegível por porte.',
                'source_url'      => 'https://www.gov.br/cidades/pt-br/assuntos/habitacao/minha-casa-minha-vida',
                'source_platform' => 'caixa',
                'ai_matched'      => true,
            ],

            // ── SOCIAL ─────────────────────────────────────
            [
                'program_name'    => 'Programa de Fortalecimento do SUAS — Capacitação e Estruturação',
                'ministry'        => 'Ministério do Desenvolvimento e Assistência Social (MDS)',
                'program_code'    => 'MDS-SUAS-2025',
                'description'     => 'Apoio financeiro para reforma e equipagem de CRAS e CREAS, capacitação de equipes e desenvolvimento de sistemas de gestão da assistência social. Transferência direta via FNAS.',
                'area'            => 'social',
                'max_value'       => 350000.00,
                'funding_type'    => 'transferencia',
                'open_date'       => '2025-03-01',
                'deadline'        => '2025-11-30',
                'status'          => 'closing',
                'match_score'     => 0.82,
                'match_reason'    => 'CRAS municipal com estrutura física precária identificada em vistoria do MDS.',
                'source_url'      => 'https://www.gov.br/mds/pt-br/acoes-e-programas/assistencia-social/suas',
                'source_platform' => 'ministerio',
                'ai_matched'      => true,
            ],

            // ── JÁ CANDIDATADO (exemplo) ────────────────────
            [
                'program_name'    => 'FNDE — Transporte Escolar (PNATE) 2025',
                'ministry'        => 'Fundo Nacional de Desenvolvimento da Educação (FNDE)',
                'program_code'    => 'PNATE-2025',
                'description'     => 'Assistência financeira para custear serviços de transporte escolar dos estudantes da educação básica pública residentes em área rural. R$0,65/dia/aluno + adicional para alunos com deficiência.',
                'area'            => 'educacao',
                'max_value'       => 180000.00,
                'funding_type'    => 'transferencia',
                'open_date'       => '2025-01-01',
                'deadline'        => null,
                'status'          => 'applied',
                'applied_at'      => now()->subDays(45),
                'match_score'     => 1.00,
                'match_reason'    => 'Município rural com 840 alunos transportados — candidatura automática.',
                'source_url'      => 'https://www.fnde.gov.br/index.php/programas/pnate',
                'source_platform' => 'fnde',
                'ai_matched'      => false,
            ],
        ];

        foreach ($programs as $data) {
            $data['municipality_id'] = $municipality->id;
            if (!isset($data['applied_at'])) $data['applied_at'] = null;
            FederalProgramAlert::create($data);
        }

        $this->command->info('✅ ' . count($programs) . " programas federais criados para {$municipality->name}.");
        $this->command->line('   Acesse: http://localhost:8000/mayor/mandato/federal-programs');
    }
}
