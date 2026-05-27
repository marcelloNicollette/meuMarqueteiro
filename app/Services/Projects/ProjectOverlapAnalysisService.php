<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectOverlapAnalysisService
{
    public function analyze(Project $project, ?User $user = null): array
    {
        $project->loadMissing(['intakeQuestions', 'sections']);

        $candidates = Project::query()
            ->where('municipality_id', $project->municipality_id)
            ->where('id', '!=', $project->id)
            ->with([
                'intakeQuestions:id,project_id,answer',
                'sections:id,project_id,content',
            ])
            ->get([
                'id',
                'municipality_id',
                'title',
                'initial_idea',
                'project_type',
                'status',
                'responsible_secretariat',
                'updated_at',
            ]);

        $matches = $candidates
            ->map(fn (Project $candidate) => $this->compareProjects($project, $candidate))
            ->filter(fn (array $match) => $match['score'] >= 35)
            ->sortByDesc('score')
            ->values()
            ->take(5)
            ->all();

        $highestScore = (int) collect($matches)->max('score');
        $status = match (true) {
            $highestScore >= 75 => 'review_required',
            $highestScore >= 55 => 'attention',
            default => 'clear',
        };

        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $metadata['overlap_analysis'] = [
            'checked_at' => now()->toIso8601String(),
            'status' => $status,
            'highest_score' => $highestScore,
            'match_count' => count($matches),
            'matches' => $matches,
        ];

        $project->forceFill([
            'metadata' => $metadata,
            'last_edited_by_user_id' => $user?->id ?: $project->last_edited_by_user_id,
            'last_edited_at' => now(),
        ])->save();

        if ($user) {
            $project->editHistory()->create([
                'user_id' => $user->id,
                'action' => 'project_overlap_checked',
                'field_name' => 'overlap_analysis',
                'metadata' => [
                    'status' => $status,
                    'highest_score' => $highestScore,
                    'match_count' => count($matches),
                ],
            ]);
        }

        return $metadata['overlap_analysis'];
    }

    private function compareProjects(Project $current, Project $candidate): array
    {
        $currentTitle = Str::lower($current->title);
        $candidateTitle = Str::lower($candidate->title);

        similar_text($currentTitle, $candidateTitle, $titlePercent);
        $titleSimilarity = max(0, min(1, $titlePercent / 100));

        $currentText = $this->projectCompositeText($current);
        $candidateText = $this->projectCompositeText($candidate);
        $textSimilarity = $this->jaccardSimilarity($currentText, $candidateText);

        $sameType = filled($current->project_type) && $current->project_type === $candidate->project_type ? 1 : 0;
        $sameSecretariat = filled($current->responsible_secretariat)
            && filled($candidate->responsible_secretariat)
            && Str::lower($current->responsible_secretariat) === Str::lower($candidate->responsible_secretariat) ? 1 : 0;

        $score = (int) round(min(
            100,
            ($titleSimilarity * 45)
            + ($textSimilarity * 40)
            + ($sameType * 10)
            + ($sameSecretariat * 5)
        ));

        $reasons = [];
        if ($titleSimilarity >= 0.65) {
            $reasons[] = 'Títulos muito parecidos';
        }
        if ($textSimilarity >= 0.45) {
            $reasons[] = 'Idéia e descrição com alta proximidade';
        }
        if ($sameType) {
            $reasons[] = 'Mesmo tipo de projeto';
        }
        if ($sameSecretariat) {
            $reasons[] = 'Mesma secretaria responsável';
        }

        if (empty($reasons)) {
            $reasons[] = 'Ha sinais parciais de semelhanca que merecem revisão';
        }

        return [
            'project_id' => $candidate->id,
            'title' => $candidate->title,
            'status' => $candidate->status_label,
            'project_type' => $candidate->type_label,
            'score' => $score,
            'reasons' => $reasons,
            'updated_at' => $candidate->updated_at?->toIso8601String(),
        ];
    }

    private function projectCompositeText(Project $project): string
    {
        $answers = $project->intakeQuestions instanceof Collection
            ? $project->intakeQuestions
            : collect();
        $sections = $project->sections instanceof Collection
            ? $project->sections
            : collect();

        return trim(implode(' ', array_filter([
            $project->title,
            $project->initial_idea,
            $answers->pluck('answer')->filter()->implode(' '),
            $sections->pluck('content')->filter()->take(4)->implode(' '),
        ])));
    }

    private function jaccardSimilarity(string $first, string $second): float
    {
        $tokensA = $this->tokenize($first);
        $tokensB = $this->tokenize($second);

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        $intersection = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));

        if (count($union) === 0) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    private function tokenize(string $text): array
    {
        $normalized = Str::lower(Str::ascii($text));
        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/', $normalized) ?: [];

        $stopwords = [
            'de', 'da', 'do', 'das', 'dos', 'e', 'em', 'para', 'por', 'com', 'sem', 'uma', 'um',
            'na', 'no', 'nas', 'nos', 'que', 'a', 'o', 'as', 'os', 'ao', 'ser', 'sera', 'projeto',
            'município', 'municipal', 'prefeitura', 'mais', 'como',
        ];

        return array_values(array_unique(array_filter($parts, function (string $part) use ($stopwords) {
            return strlen($part) >= 4 && !in_array($part, $stopwords, true);
        })));
    }
}
