<?php

namespace App\Services\Mandato;

use App\Enums\ResourceOpportunityStatus;
use App\Models\FederalProgramAlert;
use App\Models\Municipality;
use App\Services\Radar\HybridRadarReadService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class MandateRadarOpportunitySuggestionService
{
    public function __construct(
        private readonly HybridRadarReadService $radarRead,
    ) {}

    public function buildForPendingPromises(Municipality $municipality, Collection $pendingPromises): array
    {
        if ($pendingPromises->isEmpty()) {
            return [];
        }

        try {
            $programs = $this->radarRead->topMunicipalityPrograms(
                municipality: $municipality,
                limit: 100,
                statuses: ResourceOpportunityStatus::activeForRadar(),
                visibleOnly: false,
            );
        } catch (Throwable) {
            return [];
        }

        if ($programs->isEmpty()) {
            return [];
        }

        return $pendingPromises
            ->mapWithKeys(function (array $promise) use ($programs) {
                $matches = $programs
                    ->map(fn (FederalProgramAlert $program) => $this->scoreProgramForPromise($promise, $program))
                    ->filter(fn (?array $match) => $match !== null)
                    ->sortByDesc('score')
                    ->take(2)
                    ->values()
                    ->all();

                return [(int) $promise['id'] => $matches];
            })
            ->all();
    }

    private function scoreProgramForPromise(array $promise, FederalProgramAlert $program): ?array
    {
        $score = 0;
        $reasons = [];

        $axisTokens = $this->tokens(implode(' ', array_filter([
            $promise['axis_name'] ?? null,
            implode(' ', Arr::wrap($promise['keywords'] ?? [])),
        ])));
        $promiseTokens = $this->tokens(implode(' ', array_filter([
            $promise['text'] ?? null,
            implode(' ', Arr::wrap($promise['keywords'] ?? [])),
            $promise['axis_name'] ?? null,
        ])));
        $programTokens = $this->tokens(implode(' ', array_filter([
            $program->program_name,
            $program->short_title,
            $program->area,
            $program->description,
            $program->match_reason,
            is_array($program->eligibility_criteria) ? implode(' ', $program->eligibility_criteria) : null,
        ])));

        if ($axisTokens->isNotEmpty() && $axisTokens->intersect($programTokens)->isNotEmpty()) {
            $score += 35;
            $reasons[] = 'Area do edital conversa com o eixo do compromisso';
        }

        $sharedTokens = $promiseTokens->intersect($programTokens)->count();
        if ($sharedTokens > 0) {
            $score += min(35, $sharedTokens * 9);
            $reasons[] = 'Descrição do compromisso tem proximidade com a oportunidade';
        }

        if (filled($program->match_score)) {
            $score += min(15, (int) round(((float) $program->match_score) * 15));
        }

        $score += match ((string) $program->status) {
            ResourceOpportunityStatus::Published->value => 10,
            ResourceOpportunityStatus::ClosingSoon->value => 8,
            ResourceOpportunityStatus::Reopened->value => 8,
            ResourceOpportunityStatus::Monitoring->value => 5,
            default => 0,
        };

        $score = min(100, $score);

        if ($score < 45) {
            return null;
        }

        return [
            'title' => $program->program_name,
            'area' => $program->area,
            'score' => $score,
            'status' => $program->status,
            'status_label' => $program->statusLabel(),
            'match_reason' => $program->match_reason,
            'source_url' => $program->source_url,
            'program_id' => $program->id,
            'summary' => $reasons[0] ?? 'Oportunidade ativa com aderência ao compromisso.',
        ];
    }

    private function tokens(string $text): Collection
    {
        $normalized = Str::of($text)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        if ($normalized === '') {
            return collect();
        }

        return collect(explode(' ', $normalized))
            ->map(fn (string $token) => trim($token))
            ->filter(fn (string $token) => strlen($token) >= 4)
            ->reject(fn (string $token) => in_array($token, [
                'para', 'com', 'sem', 'mais', 'menos', 'muito', 'pelo', 'pela',
                'entre', 'sobre', 'como', 'essa', 'esse', 'esta', 'este',
            ], true))
            ->unique()
            ->values();
    }
}
