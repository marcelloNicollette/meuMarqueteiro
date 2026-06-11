<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectIntakeQuestion;
use App\Models\User;
use App\Services\AI\AIProviderService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectQuestionAutofillService
{
    public function __construct(
        private AIProviderService $ai,
        private ProjectContextDossierService $contextDossier,
    ) {}

    public function generateAnswers(Project $project, ?User $user = null): array
    {
        $project->loadMissing(['municipality', 'intakeQuestions']);

        try {
            return $this->generateWithAI($project, $user);
        } catch (\Throwable $exception) {
            Log::info('projects.questionnaire.autofill_fallback', [
                'project_id' => $project->id,
                'error' => $exception->getMessage(),
            ]);

            $context = $this->contextDossier->build($project, $user);

            return [
                'answers' => $this->fallbackAnswers($project),
                'source' => 'fallback',
                'context_summary' => $context['source_summary'] ?? 'dados basicos do projeto',
            ];
        }
    }

    private function generateWithAI(Project $project, ?User $user = null): array
    {
        $context = $this->contextDossier->build($project, $user);
        $questions = $project->intakeQuestions
            ->map(function (ProjectIntakeQuestion $question) {
                return implode("\n", array_filter([
                    "- question_key: {$question->question_key}",
                    "  question_text: {$question->question_text}",
                    $question->help_text ? "  help_text: {$question->help_text}" : null,
                    $question->placeholder ? "  placeholder: {$question->placeholder}" : null,
                ]));
            })
            ->implode("\n");

        $response = $this->ai->chat([
            [
                'role' => 'system',
                'content' => 'Voce preenche questionarios de projetos municipais com base em dados reais do sistema. '
                    . 'Responda TODAS as perguntas do projeto em portugues do Brasil. '
                    . 'Use o contexto do municipio, do mandato, da base de conteudo, dos projetos relacionados e da base documental recuperada. '
                    . 'Se faltar dado exato, entregue uma resposta tecnica inicial, util e prudente, deixando claro quando algo precisa de validacao posterior. '
                    . 'Nao invente numeros, codigos, datas, bairros ou quantitativos especificos. '
                    . 'Retorne apenas JSON valido no formato {"answers":[{"question_key":"...", "answer":"..."}]}.',
            ],
            [
                'role' => 'user',
                'content' => implode("\n\n", array_filter([
                    "Projeto: {$project->title}",
                    'Tipo: ' . ($project->type_label ?? 'A definir'),
                    "Secretaria responsavel: " . ($project->responsible_secretariat ?: 'Nao definida'),
                    "Ideia inicial:\n{$project->initial_idea}",
                    "Perguntas que precisam ser respondidas:\n{$questions}",
                    "Dossie consolidado do municipio e do sistema:\n" . ($context['compiled_context'] ?? 'Sem contexto adicional'),
                ])),
            ],
        ], [
            'temperature' => 0.2,
            'max_tokens' => 2200,
        ]);

        $data = $this->extractJson($response->content);
        $answers = $this->normalizeAnswers($project, $data['answers'] ?? []);

        if (empty($answers)) {
            throw new \RuntimeException('A IA nao retornou respostas aproveitaveis para o questionario.');
        }

        return [
            'answers' => $answers,
            'source' => 'ai',
            'context_summary' => $context['source_summary'] ?? 'dados municipais e base interna',
        ];
    }

    private function normalizeAnswers(Project $project, mixed $rawAnswers): array
    {
        $questions = $project->intakeQuestions->keyBy('question_key');

        $normalized = [];

        if (is_array($rawAnswers) && Arr::isAssoc($rawAnswers)) {
            foreach ($rawAnswers as $questionKey => $answer) {
                $question = $questions->get((string) $questionKey);
                $text = trim((string) $answer);

                if (!$question || $text === '') {
                    continue;
                }

                $normalized[$question->id] = $text;
            }

            return $normalized;
        }

        foreach (Arr::wrap($rawAnswers) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $questionKey = (string) ($item['question_key'] ?? '');
            $answer = trim((string) ($item['answer'] ?? ''));
            $question = $questions->get($questionKey);

            if (!$question || $answer === '') {
                continue;
            }

            $normalized[$question->id] = $answer;
        }

        return $normalized;
    }

    private function fallbackAnswers(Project $project): array
    {
        $answers = [];

        foreach ($project->intakeQuestions as $question) {
            $answers[$question->id] = $this->fallbackAnswerForQuestion($project, $question);
        }

        return $answers;
    }

    private function fallbackAnswerForQuestion(Project $project, ProjectIntakeQuestion $question): string
    {
        $municipality = $project->municipality?->name ?? 'o municipio';
        $secretariat = $project->responsible_secretariat ?: 'a secretaria responsavel';
        $idea = trim((string) $project->initial_idea);
        $ideaSummary = Str::limit($idea !== '' ? $idea : 'a necessidade registrada no projeto', 220);

        return match ($question->question_key) {
            'problema_central' => "O problema central identificado em {$municipality} esta ligado a {$ideaSummary}. "
                . "A leitura inicial sugere que a prefeitura precisa organizar uma resposta estruturada por meio de {$secretariat}, com validacao posterior dos dados operacionais mais detalhados.",
            'beneficiarios' => "Os beneficiarios diretos tendem a ser os moradores, usuarios do servico publico e equipes municipais impactadas por {$ideaSummary}. "
                . 'Na proxima revisao, vale detalhar bairros, perfis prioritarios e volume estimado de atendimento.',
            'resultado_esperado' => "O resultado esperado e transformar {$ideaSummary} em uma entrega publica concreta, com melhoria perceptivel no atendimento, na infraestrutura ou na capacidade de gestao do municipio. "
                . 'Os indicadores exatos ainda devem ser fechados pela equipe tecnica.',
            'capacidade_execução' => "A capacidade inicial de execucao deve combinar articulacao institucional de {$secretariat}, apoio do gabinete e equipe tecnica municipal. "
                . 'Convem confirmar estrutura disponivel, contratos ativos, terreno, equipamentos ou parceiros antes da versao final.',
            default => "Considerando o contexto de {$municipality} e a ideia de {$ideaSummary}, a resposta inicial para este item e orientar o projeto para uma entrega viavel, aderente ao territorio e executavel por {$secretariat}. "
                . 'Os detalhes especificos deste ponto ainda precisam de validacao tecnica complementar.',
        };
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
