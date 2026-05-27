<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\User;
use App\Services\AI\AIProviderService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ProjectDocumentGenerationService
{
    public function __construct(
        private AIProviderService $ai,
        private ProjectStructureService $structure,
        private ProjectSourceThesisContextService $sourceThesisContext,
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
            ]);
        }

        return $this->fallbackDocument($project);
    }

    private function generateWithAI(Project $project): array
    {
        $response = $this->ai->chat([
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt($project),
            ],
            [
                'role' => 'user',
                'content' => $this->buildContextPrompt($project),
            ],
        ], [
            'temperature' => 0.35,
            'max_tokens' => 2200,
        ]);

        $data = $this->extractJson($response->content);
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $normalized = [];

        foreach ($this->structure->definitions() as $definition) {
            $sectionKey = $definition['key'];
            $sectionContent = trim((string) Arr::get($sections, $sectionKey, ''));
            $normalized[$sectionKey] = $sectionContent !== ''
                ? $sectionContent
                : $this->fallbackSectionContent($project, $sectionKey, $definition['title'], $definition['description']);
        }

        $normalized['__meta'] = [
            'source' => 'ai',
        ];

        return $normalized;
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

    private function buildSystemPrompt(Project $project): string
    {
        $sectionKeys = collect($this->structure->definitions())
            ->pluck('key')
            ->implode(', ');

        $hiddenInstruction = $this->sourceThesisContext->hasSourceThesis($project)
            ? ' Se houver contexto oculto de tese de origem, use esse material para calibrar a narrativa, as prioridades e as fontes de financiamento, sem expor ao usuario que esse contexto veio do Banco de Projetos.'
            : '';

        return "Voce elabora projetos municipais completos. Gere um documento com 15 seções obrigatórias, sem lacunas, em portugues do Brasil. "
            . "Responda apenas JSON valido no formato {\"sections\":{\"{$sectionKeys}\":\"conteudo\"}}. "
            . "Cada secao deve trazer texto util, objetivo e consistente com o contexto. Quando faltar dado exato, redija uma formulacao tecnica prudente, sem inventar numeros falsos."
            . $hiddenInstruction;
    }

    private function buildContextPrompt(Project $project): string
    {
        $hiddenContext = $this->sourceThesisContext->hiddenPrompt($project);
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
            "Idéia inicial:\n{$project->initial_idea}",
            "Perguntas e respostas:\n{$answers}",
            $hiddenContext ?: null,
        ]);
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
