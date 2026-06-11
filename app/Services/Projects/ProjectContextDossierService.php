<?php

namespace App\Services\Projects;

use App\Models\ContentTemplate;
use App\Models\GeneratedContent;
use App\Models\Municipality;
use App\Models\Project;
use App\Models\User;
use App\Services\AI\AssistantContextService;
use App\Services\RAG\RAGService;
use Illuminate\Support\Str;

class ProjectContextDossierService
{
    public function __construct(
        private AssistantContextService $assistantContext,
        private ProjectSourceThesisContextService $sourceThesisContext,
        private RAGService $rag,
    ) {}

    public function build(Project $project, ?User $user = null): array
    {
        $project->loadMissing(['municipality.mayor', 'owner', 'intakeQuestions', 'sections', 'sourceThesis']);

        $municipality = $project->municipality;
        $contextUser = $this->resolveContextUser($project, $user);
        $operationalContext = ($municipality && $contextUser)
            ? $this->assistantContext->buildOperationalContext($municipality, $contextUser)
            : [
                'strategic_profile' => 'Contexto estrategico do municipio nao disponivel.',
                'demands' => 'Demandas operacionais nao disponiveis.',
                'mandate_execution' => 'Execucao do mandato nao disponivel.',
                'recent_contents' => 'Conteudos recentes nao disponiveis.',
            ];

        $sections = [
            'perfil_municipio' => $this->buildMunicipalityProfile($municipality),
            'contexto_estrategico' => $operationalContext['strategic_profile'] ?? null,
            'demandas_e_mandato' => $this->combineOperationalHighlights($operationalContext),
            'base_de_conteudo' => $this->buildContentBaseContext($municipality),
            'projetos_relacionados' => $this->buildRelatedProjectsContext($project),
            'base_rag' => $this->buildRagContext($project),
            'tese_de_origem' => $this->sourceThesisContext->hiddenPrompt($project),
        ];

        $compiledContext = collect($sections)
            ->filter(fn (?string $content) => filled(trim((string) $content)))
            ->map(function (string $content, string $key) {
                return '## ' . str_replace('_', ' ', Str::headline($key)) . "\n" . trim($content);
            })
            ->implode("\n\n");

        return [
            'compiled_context' => $compiledContext,
            'sections' => $sections,
            'source_summary' => $this->buildSourceSummary($sections),
        ];
    }

    private function resolveContextUser(Project $project, ?User $user = null): ?User
    {
        if ($user && $user->municipality_id === $project->municipality_id) {
            return $user;
        }

        if ($project->owner && $project->owner->municipality_id === $project->municipality_id) {
            return $project->owner;
        }

        return $project->municipality?->mayor;
    }

    private function buildMunicipalityProfile(?Municipality $municipality): string
    {
        if (!$municipality) {
            return 'Municipio nao informado no projeto.';
        }

        $settings = is_array($municipality->settings) ? $municipality->settings : [];
        $lines = [
            "- Municipio: {$municipality->name}/{$municipality->state}",
        ];

        if ($municipality->population) {
            $lines[] = '- Populacao: ' . number_format((int) $municipality->population, 0, ',', '.') . ' habitantes';
        }

        if ($municipality->region) {
            $lines[] = "- Regiao: {$municipality->region}";
        }

        if ($municipality->idhm) {
            $lines[] = '- IDHM: ' . number_format((float) $municipality->idhm, 3, ',', '.');
        }

        if (!empty($settings['economia_principal'])) {
            $lines[] = '- Economia principal: ' . trim((string) $settings['economia_principal']);
        }

        if (!empty($settings['desafios'])) {
            $lines[] = '- Desafios locais: ' . Str::limit(trim((string) $settings['desafios']), 320);
        }

        if (!empty($settings['potenciais'])) {
            $lines[] = '- Potenciais do territorio: ' . Str::limit(trim((string) $settings['potenciais']), 320);
        }

        if (!empty($settings['lista_projetos'])) {
            $lines[] = '- Projetos prioritarios cadastrados: ' . Str::limit(trim((string) $settings['lista_projetos']), 320);
        }

        if (!empty($settings['resumo_programa'])) {
            $lines[] = '- Resumo do programa de governo: ' . Str::limit(trim((string) $settings['resumo_programa']), 320);
        }

        return implode("\n", $lines);
    }

    private function combineOperationalHighlights(array $operationalContext): string
    {
        $blocks = [];

        foreach ([
            'demands' => 'Demandas operacionais',
            'mandate_execution' => 'Execucao do mandato',
            'recent_contents' => 'Conteudos recentes',
        ] as $key => $label) {
            $content = trim((string) ($operationalContext[$key] ?? ''));
            if ($content === '') {
                continue;
            }

            $blocks[] = $label . ":\n" . $content;
        }

        return implode("\n\n", $blocks);
    }

    private function buildContentBaseContext(?Municipality $municipality): string
    {
        if (!$municipality) {
            return 'Base de conteudo do municipio nao disponivel.';
        }

        $recentContents = GeneratedContent::query()
            ->where('municipality_id', $municipality->id)
            ->latest('created_at')
            ->limit(4)
            ->get(['title', 'type', 'tags', 'created_at']);

        $templates = ContentTemplate::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->latest('updated_at')
            ->limit(4)
            ->get(['name', 'kind', 'channel', 'tone', 'description']);

        if ($recentContents->isEmpty() && $templates->isEmpty()) {
            return 'Nao ha registros relevantes na base de conteudo do municipio.';
        }

        $lines = [];

        if ($recentContents->isNotEmpty()) {
            $lines[] = 'Registros recentes de conteudo gerado:';
            foreach ($recentContents as $content) {
                $tags = is_array($content->tags) && !empty($content->tags)
                    ? ' | tags: ' . implode(', ', array_slice($content->tags, 0, 3))
                    : '';

                $lines[] = sprintf(
                    '- %s | tipo: %s | criado em: %s%s',
                    $content->title ?: 'Conteudo sem titulo',
                    $content->type ?: 'nao informado',
                    optional($content->created_at)->format('d/m/Y H:i') ?: 'nao informado',
                    $tags
                );
            }
        }

        if ($templates->isNotEmpty()) {
            $lines[] = 'Modelos ativos de conteudo base:';
            foreach ($templates as $template) {
                $description = filled($template->description)
                    ? ' | descricao: ' . Str::limit(trim((string) $template->description), 120)
                    : '';

                $lines[] = sprintf(
                    '- %s | tipo: %s%s%s%s',
                    $template->name ?: 'Modelo sem nome',
                    $template->kind ?: 'nao informado',
                    $template->channel ? " | canal: {$template->channel}" : '',
                    $template->tone ? " | tom: {$template->tone}" : '',
                    $description
                );
            }
        }

        return implode("\n", $lines);
    }

    private function buildRelatedProjectsContext(Project $project): string
    {
        $relatedProjects = Project::query()
            ->where('municipality_id', $project->municipality_id)
            ->where('id', '!=', $project->id)
            ->orderByRaw(
                "CASE WHEN project_type = ? THEN 0 ELSE 1 END, CASE WHEN responsible_secretariat = ? THEN 0 ELSE 1 END, updated_at DESC",
                [$project->project_type, $project->responsible_secretariat]
            )
            ->limit(5)
            ->get([
                'title',
                'project_type',
                'status',
                'responsible_secretariat',
                'updated_at',
            ]);

        if ($relatedProjects->isEmpty()) {
            return 'Nao ha outros projetos municipais cadastrados para comparacao interna.';
        }

        $lines = ['Projetos ja existentes no municipio que podem ajudar na leitura de aderencia:'];

        foreach ($relatedProjects as $relatedProject) {
            $lines[] = sprintf(
                '- %s | tipo: %s | status: %s%s%s',
                $relatedProject->title,
                $relatedProject->type_label,
                $relatedProject->status_label,
                $relatedProject->responsible_secretariat
                    ? " | secretaria: {$relatedProject->responsible_secretariat}"
                    : '',
                $relatedProject->updated_at
                    ? ' | atualizado em: ' . $relatedProject->updated_at->format('d/m/Y')
                    : ''
            );
        }

        return implode("\n", $lines);
    }

    private function buildRagContext(Project $project): string
    {
        if (!$project->municipality) {
            return 'Base documental do municipio indisponivel para recuperacao.';
        }

        try {
            $query = $this->buildRagQuery($project);
            $chunks = $this->rag->retrieve($query, $project->municipality, 4);

            if ($chunks->isEmpty()) {
                return 'Nenhum trecho adicional foi recuperado da base documental do municipio.';
            }

            return $this->rag->buildContext($chunks);
        } catch (\Throwable) {
            return 'Nao foi possivel consultar a base documental indexada do municipio nesta tentativa.';
        }
    }

    private function buildRagQuery(Project $project): string
    {
        $answers = $project->intakeQuestions
            ->pluck('answer')
            ->filter()
            ->implode(' ');

        return trim(implode(' ', array_filter([
            $project->title,
            $project->initial_idea,
            $project->responsible_secretariat,
            $project->project_type,
            Str::limit($answers, 400),
        ])));
    }

    private function buildSourceSummary(array $sections): string
    {
        $labels = [
            'perfil_municipio' => 'cadastro do municipio',
            'contexto_estrategico' => 'onboarding e perfil estrategico',
            'demandas_e_mandato' => 'demandas e execucao do mandato',
            'base_de_conteudo' => 'conteudos e modelos base',
            'projetos_relacionados' => 'projetos ja cadastrados',
            'base_rag' => 'documentos indexados e RAG',
            'tese_de_origem' => 'tese ou contexto de origem',
        ];

        return collect($sections)
            ->filter(fn (?string $content) => filled(trim((string) $content)))
            ->keys()
            ->map(fn (string $key) => $labels[$key] ?? $key)
            ->implode(', ');
    }
}
