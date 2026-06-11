<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\User;
use App\Services\AI\AIProviderService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectDocumentGenerationService
{
    private const SECTION_BATCH_SIZE = 1;
    private const ANSWERS_CONTEXT_LIMIT = 1200;
    private const DOSSIER_CONTEXT_LIMIT = 1800;

    public function __construct(
        private AIProviderService $ai,
        private ProjectStructureService $structure,
        private ProjectSourceThesisContextService $sourceThesisContext,
        private ProjectContextDossierService $contextDossier,
    ) {}

    public function generate(Project $project, ?User $user = null): void
    {
        $project->loadMissing(['municipality', 'sections', 'intakeQuestions']);

        $contents = $this->generateSectionContents($project);
        $source = $contents['__meta']['source'] ?? 'fallback';
        unset($contents['__meta']);

        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $previousGenerationStatus = data_get($metadata, 'generation_status', 'pending');
        $nextVersion = $previousGenerationStatus === 'completed'
            ? ((int) $project->generated_document_version) + 1
            : max(1, (int) $project->generated_document_version);

        foreach ($project->sections as $section) {
            $content = trim((string) ($contents[$section->section_key] ?? ''));
            if ($content === '') {
                $content = $this->fallbackSectionContent($project, $section->section_key, $section->title, $section->description);
            }

            $previousContent = trim((string) ($section->content ?? ''));

            $section->forceFill([
                'content' => $content,
                'needs_review' => false,
                'metadata' => array_merge(is_array($section->metadata) ? $section->metadata : [], [
                    'source' => $source,
                    'generated_at' => now()->toIso8601String(),
                ]),
            ])->save();

            if ($user && $previousContent !== $content) {
                $project->editHistory()->create([
                    'user_id' => $user->id,
                    'project_section_id' => $section->id,
                    'action' => 'project_section_generated',
                    'field_name' => $section->section_key,
                    'previous_content' => $previousContent !== '' ? $previousContent : null,
                    'new_content' => $content,
                    'metadata' => [
                        'section_title' => $section->title,
                        'source' => $source,
                    ],
                ]);
            }
        }

        $metadata['generation_status'] = 'completed';
        $metadata['last_generated_at'] = now()->toIso8601String();
        $metadata['generated_source'] = $source;
        $metadata['generated_sections'] = $project->sections->count();

        $project->forceFill([
            'generated_document_version' => $nextVersion,
            'current_phase' => 'documento_gerado',
            'metadata' => $metadata,
            'last_edited_by_user_id' => $user?->id ?: $project->last_edited_by_user_id,
            'last_edited_at' => now(),
        ])->save();
    }

    private function generateSectionContents(Project $project): array
    {
        try {
            $content = $this->generateWithAI($project);
            if (!empty($content)) {
                return $content;
            }
        } catch (\Throwable $exception) {
            Log::info('projects.document_generation.fallback', [
                'project_id' => $project->id,
                'error' => $exception->getMessage(),
                'batch_size' => self::SECTION_BATCH_SIZE,
            ]);
        }

        return $this->fallbackDocument($project);
    }

    private function generateWithAI(Project $project): array
    {
        $normalized = [];
        $definitions = collect($this->structure->definitions());
        $dossier = $this->contextDossier->build($project);

        foreach ($definitions->chunk(self::SECTION_BATCH_SIZE) as $batchIndex => $definitionChunk) {
            $sections = $this->generateSectionBatchWithAI(
                $project,
                $definitionChunk->values()->all(),
                $dossier,
                $batchIndex + 1
            );

            foreach ($definitionChunk as $definition) {
                $sectionKey = $definition['key'];
                $sectionContent = trim((string) Arr::get($sections, $sectionKey, ''));
                $normalized[$sectionKey] = $sectionContent !== ''
                    ? $sectionContent
                    : $this->fallbackSectionContent($project, $sectionKey, $definition['title'], $definition['description']);
            }
        }

        $normalized['__meta'] = [
            'source' => 'ai',
        ];

        return $normalized;
    }

    private function generateSectionBatchWithAI(Project $project, array $definitions, array $dossier, int $batchNumber): array
    {
        $requestedSectionKeys = collect($definitions)->pluck('key')->values()->all();

        try {
            $response = $this->ai->chat([
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($project, $definitions),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildContextPrompt($project, $definitions, $dossier),
                ],
            ], [
                'temperature' => 0.2,
                'max_tokens' => 900,
                'timeout' => 60,
                'retry_attempts' => 2,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('projects.document_generation.batch_failed', [
                'project_id' => $project->id,
                'batch_number' => $batchNumber,
                'requested_sections' => $requestedSectionKeys,
                'finish_reason' => null,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (in_array($response->finishReason, ['max_tokens', 'length'], true)) {
            Log::warning('projects.document_generation.batch_failed', [
                'project_id' => $project->id,
                'batch_number' => $batchNumber,
                'requested_sections' => $requestedSectionKeys,
                'finish_reason' => $response->finishReason,
                'error' => 'Resposta da IA foi truncada antes de concluir o JSON.',
            ]);

            throw new \RuntimeException('Resposta da IA foi truncada antes de concluir o JSON.');
        }

        $data = $this->extractJson($response->content);
        $sections = is_array($data['sections'] ?? null)
            ? $data['sections']
            : (Arr::isAssoc($data) ? $data : []);

        Log::info('projects.document_generation.batch_succeeded', [
            'project_id' => $project->id,
            'batch_number' => $batchNumber,
            'requested_sections' => $requestedSectionKeys,
            'returned_section_keys' => array_keys(is_array($sections) ? $sections : []),
            'finish_reason' => $response->finishReason,
        ]);

        return is_array($sections) ? $sections : [];
    }

    private function fallbackDocument(Project $project): array
    {
        $sections = [];
        foreach ($this->structure->definitions() as $definition) {
            $sections[$definition['key']] = $this->fallbackSectionContent(
                $project,
                $definition['key'],
                $definition['title'],
                $definition['description']
            );
        }

        $sections['__meta'] = [
            'source' => 'fallback',
        ];

        return $sections;
    }

    private function buildSystemPrompt(Project $project, array $definitions): string
    {
        $jsonSchemaExample = json_encode([
            'sections' => collect($definitions)
                ->mapWithKeys(fn (array $definition) => [$definition['key'] => 'conteudo da secao'])
                ->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $hiddenInstruction = $this->sourceThesisContext->hasSourceThesis($project)
            ? ' Se houver contexto oculto de tese de origem, use esse material para calibrar a narrativa, as prioridades e as fontes de financiamento, sem expor ao usuario que esse contexto veio do Banco de Projetos.'
            : '';

        return "Voce elabora projetos municipais completos. Gere apenas a secao solicitada nesta rodada, em portugues do Brasil. "
            . "Responda apenas JSON valido no formato {$jsonSchemaExample}. "
            . "A resposta deve ser objetiva, direta e suficiente para um documento institucional. "
            . "Escreva entre 1 e 3 paragrafos curtos, sem listas longas, sem repetir o contexto de entrada e sem floreio. "
            . "Quando faltar dado exato, use formulacao tecnica prudente, sem inventar numeros falsos."
            . $hiddenInstruction;
    }

    private function buildContextPrompt(Project $project, array $definitions, array $dossier): string
    {
        $hiddenContext = $this->sourceThesisContext->hiddenPrompt($project);
        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $answers = $project->intakeQuestions
            ->map(function ($question) {
                $answer = filled($question->answer) ? trim((string) $question->answer) : 'Nao informado';
                return "- {$question->question_text}\n  Resposta: {$answer}";
            })
            ->implode("\n");

        return implode("\n\n", [
            "Municipio: " . ($project->municipality?->name ?? 'Nao informado'),
            "Projeto: {$project->title}",
            "Tipo: " . ($project->type_label ?? 'A definir'),
            "Status: {$project->status_label}",
            "Secretaria responsável: " . ($project->responsible_secretariat ?: 'A definir'),
            "Idéia inicial:\n" . $this->limitBlock($project->initial_idea, 500),
            "Seções desta rodada:\n" . $this->buildSectionChecklistPrompt($definitions),
            "Metadados estruturados do projeto:\n" . $this->buildStructuredMetadataPrompt($metadata),
            "Perguntas e respostas:\n" . $this->limitBlock($answers, self::ANSWERS_CONTEXT_LIMIT),
            "Analises internas ja rodadas:\n" . $this->buildAnalysesPrompt($metadata),
            "Contexto ampliado do municipio e do sistema:\n" . $this->limitBlock(
                (string) ($dossier['compiled_context'] ?? 'Sem contexto ampliado'),
                self::DOSSIER_CONTEXT_LIMIT
            ),
            $hiddenContext ?: null,
        ]);
    }

    private function buildSectionChecklistPrompt(array $definitions): string
    {
        return collect($definitions)
            ->map(function (array $definition, int $index) {
                return sprintf(
                    '%d. %s (%s): %s',
                    $index + 1,
                    $definition['title'],
                    $definition['key'],
                    $definition['description']
                );
            })
            ->implode("\n");
    }

    private function limitBlock(string $content, int $maxLength): string
    {
        $content = trim(preg_replace('/\s+/', ' ', $content) ?? '');

        if ($content === '') {
            return 'Nao informado.';
        }

        return Str::limit($content, $maxLength);
    }

    private function buildStructuredMetadataPrompt(array $metadata): string
    {
        $fields = [
            'executive_summary' => 'Resumo executivo',
            'primary_goal' => 'Objetivo principal',
            'target_audience' => 'Publico beneficiado',
            'territorial_scope' => 'Abrangencia territorial',
            'funding_strategy' => 'Estrategia de financiamento',
            'implementation_notes' => 'Notas de implementacao',
            'risk_notes' => 'Riscos e cuidados',
            'priority' => 'Prioridade',
            'expected_beneficiaries' => 'Beneficiarios estimados',
            'estimated_budget' => 'Orcamento estimado',
            'expected_start_date' => 'Previsao de inicio',
            'expected_end_date' => 'Previsao de conclusao',
        ];

        $lines = [];

        foreach ($fields as $field => $label) {
            $value = data_get($metadata, $field);
            if ($value === null || $value === '') {
                continue;
            }

            $lines[] = "- {$label}: {$value}";
        }

        return empty($lines)
            ? 'Nao ha metadados estruturados adicionais preenchidos.'
            : $this->limitBlock(implode("\n", $lines), 700);
    }

    private function buildAnalysesPrompt(array $metadata): string
    {
        $lines = [];

        $overlap = data_get($metadata, 'overlap_analysis');
        if (is_array($overlap) && !empty($overlap)) {
            $lines[] = '- Sobreposicao: status ' . data_get($overlap, 'status', 'nao informado')
                . ' | maior score: ' . data_get($overlap, 'highest_score', 0)
                . ' | matches: ' . data_get($overlap, 'match_count', 0);
        }

        $funding = data_get($metadata, 'funding_analysis');
        if (is_array($funding) && !empty($funding)) {
            $lines[] = '- Financiamento: status ' . data_get($funding, 'status', 'nao informado')
                . ' | maior score: ' . data_get($funding, 'highest_score', 0)
                . ' | matches: ' . data_get($funding, 'match_count', 0);

            foreach (array_slice(data_get($funding, 'matches', []), 0, 4) as $match) {
                $lines[] = sprintf(
                    '  - Programa aderente: %s | tipo: %s | score: %s',
                    $match['title'] ?? 'Nao informado',
                    $match['source_type'] ?? 'nao informado',
                    $match['score'] ?? 0
                );
            }
        }

        return empty($lines)
            ? 'Nenhuma analise complementar registrada ate agora.'
            : $this->limitBlock(implode("\n", $lines), 500);
    }

    private function fallbackSectionContent(Project $project, string $sectionKey, string $sectionTitle, string $sectionDescription): string
    {
        $answers = $this->answersByKey($project);
        $municipality = $project->municipality?->name ?? 'o município';
        $type = $project->type_label ?? 'Projeto municipal';
        $secretariat = $project->responsible_secretariat ?: 'secretaria a definir';
        $beneficiaries = $answers['beneficiarios'] ?? 'moradores e grupos impactados pela iniciativa';
        $problem = $answers['problema_central'] ?? $project->initial_idea;
        $expected = $answers['resultado_esperado'] ?? 'melhorar a entrega de políticas publicas e a qualidade do servico prestado';
        $capacity = $answers['capacidade_execução'] ?? 'estrutura tecnica da prefeitura e articulacao com parceiros locais';
        $sourceThesis = $this->sourceThesisContext->snapshot($project);
        $thesisJustification = trim((string) data_get($sourceThesis, 'justification', ''));
        $thesisImpact = trim((string) data_get($sourceThesis, 'potential_impact', ''));
        $thesisFunding = trim((string) data_get($sourceThesis, 'funding_source', ''));
        $thesisAlignment = trim((string) data_get($sourceThesis, 'government_alignment', ''));

        return match ($sectionKey) {
            'identificação' => "Projeto {$project->title}, classificado como {$type}, estruturado para {$municipality}. A iniciativa sera coordenada por {$secretariat}, sob lideranca do usuario responsável pelo cadastro na plataforma. Esta versão consolida a fase atual do documento com base na idéia inicial e nas respostas do questionario guiado.",
            'resumo_executivo' => "O projeto {$project->title} busca responder a uma demanda prioritaria de {$municipality}, com foco em {$expected}. A proposta organiza objetivos, atividades, cronograma, recursos, fontes de financiamento e mecanismos de acompanhamento, oferecendo uma base tecnica inicial para decisão, captação e execução.",
            'diagnostico_justificativa' => "O diagnostico inicial indica a necessidade de enfrentar o seguinte problema central: {$problem}. "
                . ($thesisJustification !== '' ? "A tese de origem reforca esta leitura ao apontar que {$thesisJustification}. " : '')
                . "A justificativa do projeto decorre da urgencia de organizar uma resposta estruturada, capaz de gerar impacto publico concreto, ampliar eficiencia administrativa e reduzir passivos que hoje limitam o desempenho do município.",
            'objetivos' => "Objetivo geral: estruturar e viabilizar o projeto {$project->title} para entregar {$expected}. Objetivos específicos: qualificar o planejamento tecnico, alinhar atores responsaveis, organizar etapas de implantacao, garantir aderência institucional e criar base consistente para financiamento, monitoramento e execução.",
            'publico_alvo' => "O publico-alvo envolve {$beneficiaries}. "
                . ($thesisImpact !== '' ? "O impacto esperado da tese de origem sugere foco em {$thesisImpact}. " : '')
                . "Tambem sao considerados beneficiarios indiretos as equipes da administracao publica, parceiros institucionais e atores locais que serao impactados pela melhoria gerada pelo projeto. O detalhamento quantitativo deve ser refinado na fase seguinte com dados operacionais do territorio.",
            'atividades' => "As atividades previstas incluem: detalhamento tecnico do escopo, validacao interna com a secretaria responsável, consolidacao de requisitos operacionais, preparacao documental, articulacao com parceiros, organização das etapas de implantacao e definicao do modelo de acompanhamento. Quando aplicavel, o projeto deve incorporar visitas tecnicas, levantamento complementar e validacao juridico-administrativa.",
            'cronograma' => "O cronograma de execução pode ser organizado em fases: 1) refinamento tecnico e consolidacao documental; 2) validacao institucional e orcamentaria; 3) preparacao para captação ou contratacao; 4) implantacao; 5) acompanhamento de entregas e resultados. Os prazos exatos devem ser fechados conforme capacidade da equipe, rito administrativo e disponibilidade financeira.",
            'recursos_necessários' => "Os recursos necessários incluem equipe tecnica para elaboração e acompanhamento, apoio administrativo, insumos ou infraestrutura especifica da proposta, eventual suporte juridico e financeiro, e articulacao intersetorial para dar sustentacao a execução. A capacidade atualmente percebida considera {$capacity}.",
            'orcamento_estimado' => "O orcamento estimado deve considerar custos de planejamento, implantacao, operacao inicial, apoio tecnico e eventuais contratacoes complementares. Nesta versão, recomenda-se estruturar o custo por grandes categorias e detalhar valores em planilha propria na proxima fase, evitando lacunas entre escopo, recursos e cronograma.",
            'fontes_financiamento' => "As fontes de financiamento devem ser buscadas a partir da aderencia do projeto a programas federais e estaduais relacionados ao seu tipo. "
                . ($thesisFunding !== '' ? "A tese de origem já indica a seguinte trilha de recurso como ponto de partida: {$thesisFunding}. " : '')
                . "Para esta proposta, recomenda-se cruzar o documento com oportunidades de convenio, transferencia especial, editais setoriais e linhas de investimento vinculadas a {$type}. A identificação automatica de programas compatíveis entra na fase seguinte do módulo.",
            'parceiros_potenciais' => "Podem atuar como parceiros potenciais secretarias municipais correlatas, orgãos estaduais e federais, consorcios, entidades representativas, associacoes locais e instituicoes tecnicas com aderência ao objeto do projeto. A selecao final deve considerar capacidade operacional, legitimidade institucional e complementariedade de competencias.",
            'indicadores_metas' => "Os indicadores e metas devem medir entrega, alcance e resultado. Recomenda-se adotar pelo menos: indicador de implantacao fisica ou administrativa, indicador de cobertura do publico-alvo, indicador de resultado percebido e marco temporal de conclusao por fase. As metas numericas devem ser calibradas quando houver base tecnica consolidada.",
            'riscos_mitigações' => "Os principais riscos envolvem insuficiencia de dados tecnicos, atraso em validacoes administrativas, restricao orcamentaria, baixa articulacao entre setores e inadequacao entre escopo e financiamento disponível. Como mitigação, recomenda-se validar escopo minimo viavel, organizar governanca interna, antecipar requisitos documentais e revisar a compatibilidade financeira antes da execução.",
            'alinhamento_programa_governo' => "O projeto se alinha ao programa de governo ao responder uma demanda concreta de {$municipality} com potencial de impacto administrativo e social. "
                . ($thesisAlignment !== '' ? "No contexto da tese de origem, esse alinhamento aparece assim: {$thesisAlignment}. " : '')
                . "A aderencia política deve ser reforcada conectando a proposta a compromissos do mandato, prioridades territoriais e entregas que fortaleçam a legitimidade da gestao perante a populacao.",
            'responsaveis_execução' => "A execução deve ser liderada por {$secretariat}, com apoio do gabinete, areas tecnicas correlatas e parceiros institucionais necessários ao objeto do projeto. Recomenda-se definir desde ja um responsável politico, um responsável tecnico e uma rotina minima de acompanhamento para decisão, destravamento e prestacao de contas.",
            default => "{$sectionTitle}: {$sectionDescription}. O projeto {$project->title} deve desenvolver esta secao com base na idéia inicial, nas respostas registradas e na realidade administrativa de {$municipality}.",
        };
    }

    private function answersByKey(Project $project): array
    {
        return $project->intakeQuestions
            ->filter(fn ($question) => filled($question->answer))
            ->mapWithKeys(fn ($question) => [$question->question_key => trim((string) $question->answer)])
            ->all();
    }

    private function extractJson(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new \RuntimeException('Resposta da IA nao trouxe JSON valido.');
        }

        $json = substr($content, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Falha ao decodificar JSON da IA.');
        }

        return $decoded;
    }
}
