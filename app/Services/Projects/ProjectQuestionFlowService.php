<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\User;
use App\Services\AI\AIProviderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectQuestionFlowService
{
    public function __construct(
        private AIProviderService $ai,
        private ProjectSourceThesisContextService $sourceThesisContext,
    ) {}

    public function ensureGenerated(Project $project, ?User $user = null, bool $force = false): void
    {
        if (!$force && $project->intakeQuestions()->exists()) {
            return;
        }

        if ($force) {
            $project->intakeQuestions()->delete();
        }

        [$type, $typeSource, $typeConfidence] = $this->detectProjectType($project);
        $questions = $this->generateQuestions($project, $type);

        $project->intakeQuestions()->createMany($questions);

        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $metadata['questionnaire'] = [
            'generated_at' => now()->toIso8601String(),
            'source' => $questions[0]['metadata']['source'] ?? 'fallback',
            'type_source' => $typeSource,
            'type_confidence' => $typeConfidence,
            'question_count' => count($questions),
            'answered_count' => 0,
            'source_thesis_context_used' => $this->sourceThesisContext->hasSourceThesis($project),
            'source_thesis_id' => data_get($this->sourceThesisContext->snapshot($project), 'id'),
        ];

        $project->forceFill([
            'project_type' => $project->project_type ?: $type,
            'current_phase' => 'questionario',
            'metadata' => $metadata,
            'last_edited_by_user_id' => $user?->id ?: $project->last_edited_by_user_id,
            'last_edited_at' => now(),
        ])->save();

        if ($user) {
            $project->editHistory()->create([
                'user_id' => $user->id,
                'action' => $force ? 'project_questions_regenerated' : 'project_questions_generated',
                'field_name' => 'questionnaire',
                'metadata' => [
                    'project_type' => $project->project_type,
                    'question_count' => count($questions),
                    'source' => $metadata['questionnaire']['source'],
                ],
            ]);
        }
    }

    public function syncAnsweredCount(Project $project): void
    {
        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $questionnaire = is_array($metadata['questionnaire'] ?? null) ? $metadata['questionnaire'] : [];
        $questionnaire['answered_count'] = $project->intakeQuestions()->whereNotNull('answer')->count();
        $questionnaire['last_answered_at'] = now()->toIso8601String();
        $metadata['questionnaire'] = $questionnaire;

        $project->forceFill([
            'metadata' => $metadata,
            'current_phase' => $questionnaire['answered_count'] > 0 ? 'questionario_em_andamento' : 'questionario',
        ])->save();
    }

    private function detectProjectType(Project $project): array
    {
        if (filled($project->project_type)) {
            return [$project->project_type, 'manual', 1.0];
        }

        $sourceThesisType = $this->sourceThesisContext->inferredProjectType($project);
        if ($sourceThesisType) {
            return [$sourceThesisType, 'source_thesis', 0.92];
        }

        $text = Str::lower($project->title . ' ' . $project->initial_idea);
        $keywordMap = [
            'infraestrutura' => ['obra', 'paviment', 'praca', 'praça', 'rua', 'escola', 'posto', 'creche', 'drenagem', 'ilumin', 'quadra', 'reforma', 'constr'],
            'social' => ['familia', 'família', 'juventude', 'idoso', 'assistencia', 'assistência', 'mulher', 'crianca', 'criança', 'capacita', 'saude', 'saúde', 'educa'],
            'ambiental' => ['residuo', 'resíduo', 'coleta', 'reciclag', 'parque', 'nascent', 'meio ambiente', 'sustent', 'arboriza', 'clima', 'esgoto'],
            'economico' => ['renda', 'emprego', 'turismo', 'feira', 'comercio', 'comércio', 'empreendedor', 'desenvolvimento economico', 'agricultura', 'agro'],
            'institucional' => ['sistema', 'digital', 'modernizacao', 'modernização', 'gestao', 'gestão', 'capacitacao interna', 'planejamento', 'governanca', 'governança'],
        ];

        $scores = [];
        foreach ($keywordMap as $type => $keywords) {
            $scores[$type] = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, Str::lower($keyword))) {
                    $scores[$type]++;
                }
            }
        }

        arsort($scores);
        $bestType = array_key_first($scores);
        $bestScore = $scores[$bestType] ?? 0;

        if ($bestType && $bestScore > 0) {
            return [$bestType, 'heuristic', min(0.95, 0.45 + ($bestScore * 0.1))];
        }

        try {
            $hiddenContext = $this->sourceThesisContext->hiddenPrompt($project);
            $response = $this->ai->chat([
                [
                    'role' => 'system',
                    'content' => 'Classifique um projeto municipal em apenas um destes tipos: infraestrutura, social, ambiental, economico ou institucional. '
                        . 'Se houver contexto oculto de tese de origem, use esse contexto internamente para melhorar a classificacao, sem menciona-lo na resposta. '
                        . 'Responda apenas JSON valido com {"project_type":"...", "confidence":0.00}.',
                ],
                [
                    'role' => 'user',
                    'content' => "Título: {$project->title}\nIdéia: {$project->initial_idea}"
                        . ($hiddenContext ? "\n\n{$hiddenContext}" : ''),
                ],
            ], [
                'temperature' => 0.2,
                'max_tokens' => 180,
            ]);

            $data = $this->extractJson($response->content);
            $projectType = $data['project_type'] ?? null;
            $confidence = (float) ($data['confidence'] ?? 0.6);

            if (in_array($projectType, array_keys($this->questionTemplates()), true)) {
                return [$projectType, 'ai', max(0.5, min(1.0, $confidence))];
            }
        } catch (\Throwable $exception) {
            Log::info('projects.question_flow.type_detection_fallback', [
                'project_id' => $project->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return ['institucional', 'fallback', 0.3];
    }

    private function generateQuestions(Project $project, string $type): array
    {
        try {
            $questions = $this->generateQuestionsWithAI($project, $type);
            if (!empty($questions)) {
                return $questions;
            }
        } catch (\Throwable $exception) {
            Log::info('projects.question_flow.questions_fallback', [
                'project_id' => $project->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->fallbackQuestions($project, $type);
    }

    private function generateQuestionsWithAI(Project $project, string $type): array
    {
        $municipalityName = $project->municipality?->name ?? 'Municipio não informado';
        $hiddenContext = $this->sourceThesisContext->hiddenPrompt($project);

        $response = $this->ai->chat([
            [
                'role' => 'system',
                'content' => 'Voce ajuda a montar projetos municipais. Gere no maximo 8 perguntas objetivas e realmente uteis antes da elaboração do documento. '
                    . 'Se houver contexto oculto de tese de origem, use esse contexto para calibrar a priorizacao das perguntas, mas não exponha a existencia da tese ou do Banco de Projetos ao usuario. '
                    . 'As perguntas devem soar naturais, como se fossem parte do fluxo normal de elaboracao do projeto. '
                    . 'Responda apenas JSON valido no formato {"questions":[{"key":"...", "question_text":"...", "help_text":"...", "placeholder":"...", "input_type":"textarea"}]}. Todas as perguntas devem ser em portugues do Brasil.',
            ],
            [
                'role' => 'user',
                'content' => "Municipio: {$municipalityName}\nTipo do projeto: {$type}\nTítulo: {$project->title}\nIdéia inicial: {$project->initial_idea}\nSecretaria responsável: {$project->responsible_secretariat}"
                    . ($hiddenContext ? "\n\n{$hiddenContext}" : ''),
            ],
        ], [
            'temperature' => 0.4,
            'max_tokens' => 900,
        ]);

        $data = $this->extractJson($response->content);
        $questions = $data['questions'] ?? [];

        return $this->normalizeQuestions($questions, 'ai');
    }

    private function fallbackQuestions(Project $project, string $type): array
    {
        $templates = $this->questionTemplates();
        $base = $templates['base'];
        $typeSpecific = $templates[$type] ?? $templates['institucional'];
        $questions = array_slice(array_merge($base, $typeSpecific), 0, 8);

        return $this->normalizeQuestions(
            $this->applySourceThesisFallbackHints($project, $questions),
            'fallback'
        );
    }

    private function applySourceThesisFallbackHints(Project $project, array $questions): array
    {
        $snapshot = $this->sourceThesisContext->snapshot($project);
        if (!$snapshot) {
            return $questions;
        }

        foreach ($questions as &$question) {
            $key = (string) ($question['key'] ?? '');

            if ($key === 'problema_central' && !empty($snapshot['justification'])) {
                $question['help_text'] = 'Considere a dor central que ja aparece no contexto do projeto e detalhe como ela se manifesta no municipio.';
                $question['placeholder'] = Str::limit((string) $snapshot['justification'], 170);
            }

            if ($key === 'resultado_esperado' && !empty($snapshot['potential_impact'])) {
                $question['help_text'] = 'Descreva o resultado final que transforma o potencial identificado em entrega concreta e mensuravel.';
                $question['placeholder'] = Str::limit((string) $snapshot['potential_impact'], 170);
            }

            if ($key === 'capacidade_execução' && !empty($snapshot['funding_source'])) {
                $question['help_text'] = 'Considere a fonte de recurso sugerida e explique o que o municipio ja possui para entrar nessa disputa com boa capacidade de execucao.';
                $question['placeholder'] = Str::limit((string) $snapshot['funding_source'], 170);
            }
        }
        unset($question);

        return $questions;
    }

    private function questionTemplates(): array
    {
        return [
            'base' => [
                [
                    'key' => 'problema_central',
                    'question_text' => 'Qual problema concreto o município quer resolver com este projeto?',
                    'help_text' => 'Descreva a dor atual, a urgência e o impacto de não agir agora.',
                    'placeholder' => 'Ex.: a area sofre com falta de drenagem, alagamentos recorrentes e baixa mobilidade...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'beneficiarios',
                    'question_text' => 'Quem sera beneficiado diretamente e indiretamente pelo projeto?',
                    'help_text' => 'Informe perfis de publico, bairros, comunidades ou secretarias impactadas.',
                    'placeholder' => 'Ex.: moradores de 4 bairros, comerciantes do entorno e equipe da secretaria...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'resultado_esperado',
                    'question_text' => 'Qual resultado concreto a prefeitura espera entregar ao final?',
                    'help_text' => 'Pense em entregas claras, visiveis e mensuráveis.',
                    'placeholder' => 'Ex.: reduzir alagamentos, ampliar atendimento, aumentar ocupacao do espaco...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'capacidade_execução',
                    'question_text' => 'Que estrutura a prefeitura ja possui para executar ou apoiar este projeto?',
                    'help_text' => 'Equipe, equipamentos, contratos, terrenos, base tecnica ou parcerias ja existentes.',
                    'placeholder' => 'Ex.: ja existe terreno regularizado e equipe de engenharia interna...',
                    'input_type' => 'textarea',
                ],
            ],
            'infraestrutura' => [
                [
                    'key' => 'localizacao_obra',
                    'question_text' => 'Onde o projeto sera implantado e qual a situacao atual do local?',
                    'help_text' => 'Indique bairro, endereco, area disponível, titularidade e condicoes atuais.',
                    'placeholder' => 'Ex.: area institucional no bairro X, com necessidade de terraplanagem...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'escopo_fisico',
                    'question_text' => 'Qual e o escopo fisico previsto para a obra ou intervencao?',
                    'help_text' => 'Liste os principais componentes, metragens, ambientes ou itens de infraestrutura.',
                    'placeholder' => 'Ex.: 1 quadra coberta, iluminacao LED, pista de caminhada e playground...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'licencas_projetos',
                    'question_text' => 'Ja existe algum projeto basico, licenca ou estudo tecnico relacionado?',
                    'help_text' => 'Informe o que ja esta pronto e o que ainda precisa ser contratado.',
                    'placeholder' => 'Ex.: ha anteprojeto e memorial, mas falta sondagem e planilha orcamentaria...',
                    'input_type' => 'textarea',
                ],
            ],
            'social' => [
                [
                    'key' => 'publico_prioritario',
                    'question_text' => 'Qual grupo social e prioritario neste projeto e por que ele precisa entrar primeiro?',
                    'help_text' => 'Descreva vulnerabilidades, cobertura atual e criterios de prioridade.',
                    'placeholder' => 'Ex.: mulheres chefes de familia e jovens fora da escola no bairro...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'metodologia_atendimento',
                    'question_text' => 'Como o atendimento ou a intervencao social sera realizado na pratica?',
                    'help_text' => 'Explique etapas, frequencia, equipe envolvida e formato de acompanhamento.',
                    'placeholder' => 'Ex.: oficinas semanais, visitas domiciliares e acompanhamento psicossocial...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'rede_parceira',
                    'question_text' => 'Que rede publica ou comunitaria pode apoiar a execução do projeto?',
                    'help_text' => 'Escolas, CRAS, unidades de saude, associacoes ou OSCs.',
                    'placeholder' => 'Ex.: CRAS, escolas municipais e associacao de moradores...',
                    'input_type' => 'textarea',
                ],
            ],
            'ambiental' => [
                [
                    'key' => 'problema_ambiental',
                    'question_text' => 'Qual passivo ou risco ambiental o projeto pretende enfrentar?',
                    'help_text' => 'Detalhe a situacao atual e os impactos no territorio.',
                    'placeholder' => 'Ex.: descarte irregular, erosao, assoreamento ou baixa arborizacao...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'area_impactada',
                    'question_text' => 'Qual area do município sera diretamente impactada pela acao?',
                    'help_text' => 'Bairro, bacia, parque, nascente ou rota de coleta.',
                    'placeholder' => 'Ex.: entorno do corrego e dois bairros com descarte irregular...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'ganho_ambiental',
                    'question_text' => 'Que melhoria ambiental concreta se espera obter com o projeto?',
                    'help_text' => 'Use ganhos observaveis e, se possivel, mensuráveis.',
                    'placeholder' => 'Ex.: aumento da cobertura vegetal e reducao de residuos em area critica...',
                    'input_type' => 'textarea',
                ],
            ],
            'economico' => [
                [
                    'key' => 'cadeia_economica',
                    'question_text' => 'Qual cadeia economica local o projeto pretende fortalecer?',
                    'help_text' => 'Turismo, agricultura, comercio, industria, economia criativa ou servicos.',
                    'placeholder' => 'Ex.: pequenos produtores, feira municipal e comercio do centro...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'efeito_renda',
                    'question_text' => 'Como o projeto pode gerar renda, emprego ou aumento de atividade economica?',
                    'help_text' => 'Descreva efeitos esperados no curto e medio prazo.',
                    'placeholder' => 'Ex.: ampliar fluxo turistico e ocupacao de pequenos negocios...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'atores_produtivos',
                    'question_text' => 'Quais atores produtivos locais precisam ser engajados desde o início?',
                    'help_text' => 'Associacoes, cooperativas, comerciantes, produtores e entidades de apoio.',
                    'placeholder' => 'Ex.: associacao comercial, sindicato rural e cooperativa local...',
                    'input_type' => 'textarea',
                ],
            ],
            'institucional' => [
                [
                    'key' => 'gargalo_gestao',
                    'question_text' => 'Qual gargalo de gestao ou capacidade institucional precisa ser corrigido?',
                    'help_text' => 'Processos lentos, baixa integracao, falta de sistema, equipe ou governanca.',
                    'placeholder' => 'Ex.: processos manuais e falta de indicadores gerenciais...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'setores_envolvidos',
                    'question_text' => 'Quais secretarias ou setores precisam participar para o projeto funcionar?',
                    'help_text' => 'Liste areas internas e o papel esperado de cada uma.',
                    'placeholder' => 'Ex.: administracao, fazenda, planejamento e TI...',
                    'input_type' => 'textarea',
                ],
                [
                    'key' => 'entregas_governanca',
                    'question_text' => 'Que entregas administrativas ou de governanca precisam sair deste projeto?',
                    'help_text' => 'Indicadores, processos, sistema, fluxos, comites ou instrumentos de controle.',
                    'placeholder' => 'Ex.: painel de metas, fluxo padrao e rotina mensal de acompanhamento...',
                    'input_type' => 'textarea',
                ],
            ],
        ];
    }

    private function normalizeQuestions(array $questions, string $source): array
    {
        $normalized = [];
        foreach (array_slice($questions, 0, 8) as $index => $question) {
            $questionText = trim((string) ($question['question_text'] ?? ''));
            if ($questionText === '') {
                continue;
            }

            $normalized[] = [
                'question_key' => Str::slug((string) ($question['key'] ?? 'pergunta_' . ($index + 1)), '_'),
                'question_order' => $index + 1,
                'question_text' => $questionText,
                'help_text' => filled($question['help_text'] ?? null) ? trim((string) $question['help_text']) : null,
                'input_type' => in_array(($question['input_type'] ?? 'textarea'), ['textarea', 'text'], true)
                    ? $question['input_type']
                    : 'textarea',
                'placeholder' => filled($question['placeholder'] ?? null) ? trim((string) $question['placeholder']) : null,
                'is_required' => true,
                'metadata' => [
                    'source' => $source,
                ],
            ];
        }

        return $normalized;
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
            throw new \RuntimeException('Resposta da IA não trouxe JSON valido.');
        }

        $json = substr($content, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Falha ao decodificar JSON da IA.');
        }

        return $decoded;
    }
}
