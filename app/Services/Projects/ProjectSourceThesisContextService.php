<?php

namespace App\Services\Projects;

use App\Models\Project;
use Illuminate\Support\Str;

class ProjectSourceThesisContextService
{
    public function snapshot(Project $project): ?array
    {
        $project->loadMissing('sourceThesis');

        $snapshot = data_get($project->metadata, 'source_thesis_snapshot');
        $snapshot = is_array($snapshot) ? $snapshot : [];

        if ($project->sourceThesis) {
            $thesis = $project->sourceThesis;

            $snapshot = array_merge($snapshot, [
                'id' => $thesis->id,
                'title' => $thesis->title,
                'category' => $thesis->category,
                'justification' => $thesis->justification,
                'potential_impact' => $thesis->potential_impact,
                'funding_source' => $thesis->funding_source,
                'government_alignment' => $thesis->government_alignment,
                'reference_municipalities' => $thesis->reference_municipalities,
                'urgency' => $thesis->urgency,
                'estimated_size' => $thesis->estimated_size,
                'execution_complexity' => $thesis->execution_complexity,
                'resource_deadline' => optional($thesis->resource_deadline)->toDateString(),
                'metadata' => is_array($thesis->metadata) ? $thesis->metadata : [],
            ]);
        }

        return !empty($snapshot) ? $snapshot : null;
    }

    public function hasSourceThesis(Project $project): bool
    {
        return $this->snapshot($project) !== null;
    }

    public function inferredProjectType(Project $project): ?string
    {
        $category = Str::lower((string) data_get($this->snapshot($project), 'category', ''));

        return match ($category) {
            'infraestrutura', 'mobilidade' => 'infraestrutura',
            'meio ambiente', 'ambiental' => 'ambiental',
            'desenvolvimento econômico', 'desenvolvimento economico', 'agricultura', 'turismo' => 'economico',
            'institucional', 'gestao', 'gestão', 'administracao', 'administração' => 'institucional',
            'saude', 'saúde', 'educacao', 'educação', 'assistencia social', 'assistência social', 'cultura' => 'social',
            default => null,
        };
    }

    public function hiddenPrompt(Project $project): ?string
    {
        $snapshot = $this->snapshot($project);
        if (!$snapshot) {
            return null;
        }

        $lines = [
            'CONTEXTO OCULTO DE ORIGEM DO PROJETO:',
            '- Tese de origem: ' . ($snapshot['title'] ?? 'Nao informada'),
            '- Categoria da tese: ' . ($snapshot['category'] ?? 'Nao informada'),
        ];

        if (!empty($snapshot['urgency'])) {
            $lines[] = '- Urgencia da tese: ' . ucfirst((string) $snapshot['urgency']);
        }
        if (!empty($snapshot['estimated_size'])) {
            $lines[] = '- Porte estimado: ' . ucfirst((string) $snapshot['estimated_size']);
        }
        if (!empty($snapshot['execution_complexity'])) {
            $lines[] = '- Complexidade: ' . ucfirst((string) $snapshot['execution_complexity']);
        }
        if (!empty($snapshot['resource_deadline'])) {
            $lines[] = '- Prazo do recurso associado: ' . (string) $snapshot['resource_deadline'];
        }
        if (!empty($snapshot['justification'])) {
            $lines[] = '- Justificativa da tese: ' . trim((string) $snapshot['justification']);
        }
        if (!empty($snapshot['potential_impact'])) {
            $lines[] = '- Potencial de impacto: ' . trim((string) $snapshot['potential_impact']);
        }
        if (!empty($snapshot['funding_source'])) {
            $lines[] = '- Fonte de recurso sugerida: ' . trim((string) $snapshot['funding_source']);
        }
        if (!empty($snapshot['government_alignment'])) {
            $lines[] = '- Alinhamento com programa de governo: ' . trim((string) $snapshot['government_alignment']);
        }
        if (!empty($snapshot['reference_municipalities'])) {
            $lines[] = '- Referencias municipais: ' . trim((string) $snapshot['reference_municipalities']);
        }

        $matchedProgramName = data_get($snapshot, 'metadata.matched_program_name');
        if (filled($matchedProgramName)) {
            $lines[] = '- Programa aderente encontrado no Radar: ' . $matchedProgramName;
        }

        return implode("\n", $lines);
    }
}
