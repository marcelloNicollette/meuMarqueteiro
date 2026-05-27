<?php

namespace App\Services\Projects;

class ProjectStructureService
{
    public const SECTION_DEFINITIONS = [
        [
            'key' => 'identificação',
            'title' => 'Identificação do Projeto',
            'description' => 'Nome, município, secretaria responsável, usuario criador, data de elaboração e versão do documento.',
        ],
        [
            'key' => 'resumo_executivo',
            'title' => 'Resumo Executivo',
            'description' => 'Síntese do projeto para apresentação rápida a parceiros e tomadores de decisão.',
        ],
        [
            'key' => 'diagnostico_justificativa',
            'title' => 'Diagnóstico e Justificativa',
            'description' => 'Problema ou oportunidade enfrentada pelo município e o racional do projeto.',
        ],
        [
            'key' => 'objetivos',
            'title' => 'Objetivos',
            'description' => 'Objetivo geral e objetivos específicos com clareza e verificabilidade.',
        ],
        [
            'key' => 'publico_alvo',
            'title' => 'Público-Alvo',
            'description' => 'Beneficiários diretos e indiretos, com estimativas quantitativas.',
        ],
        [
            'key' => 'atividades',
            'title' => 'Descrição das Atividades',
            'description' => 'Acoes previstas, organizadas por fase ou eixo tematico.',
        ],
        [
            'key' => 'cronograma',
            'title' => 'Cronograma de Execução',
            'description' => 'Linha do tempo com fases, marcos e prazos estimados.',
        ],
        [
            'key' => 'recursos_necessários',
            'title' => 'Recursos Necessários',
            'description' => 'Recursos humanos, materiais, tecnológicos e financeiros necessários.',
        ],
        [
            'key' => 'orcamento_estimado',
            'title' => 'Orcamento Estimado',
            'description' => 'Categorias de custo, valores unitarios e total estimado.',
        ],
        [
            'key' => 'fontes_financiamento',
            'title' => 'Fontes de Financiamento',
            'description' => 'Programas federais e estaduais compatíveis e suas modalidades.',
        ],
        [
            'key' => 'parceiros_potenciais',
            'title' => 'Parceiros Potenciais',
            'description' => 'Orgãos, instituicoes e parceiros que podem apoiar a execução.',
        ],
        [
            'key' => 'indicadores_metas',
            'title' => 'Indicadores e Metas',
            'description' => 'Indicadores mensuráveis e metas quantitativas para acompanhamento.',
        ],
        [
            'key' => 'riscos_mitigações',
            'title' => 'Riscos e Medidas Mitigadoras',
            'description' => 'Principais riscos financeiros, políticos e operacionais com mitigações.',
        ],
        [
            'key' => 'alinhamento_programa_governo',
            'title' => 'Alinhamento com o Programa de Governo',
            'description' => 'Conexão do projeto com diretrizes e compromissos do mandato.',
        ],
        [
            'key' => 'responsaveis_execução',
            'title' => 'Responsáveis pela Execução',
            'description' => 'Secretaria lider, equipe responsável e parceiros com papeis definidos.',
        ],
    ];

    public function definitions(): array
    {
        return self::SECTION_DEFINITIONS;
    }

    public function buildInitialSections(): array
    {
        return array_map(function (array $section, int $index) {
            return [
                'section_key' => $section['key'],
                'section_order' => $index + 1,
                'title' => $section['title'],
                'description' => $section['description'],
                'content' => null,
                'is_required' => true,
                'needs_review' => true,
                'metadata' => [
                    'source' => 'template',
                ],
            ];
        }, self::SECTION_DEFINITIONS, array_keys(self::SECTION_DEFINITIONS));
    }
}
