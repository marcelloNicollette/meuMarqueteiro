<?php

namespace App\Services\Mandato;

use App\Models\Demand;
use App\Models\Municipality;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class MandateResolveAiEvidenceSuggestionService
{
    public function buildForOpenPromises(Municipality $municipality, Collection $promises): array
    {
        if ($promises->isEmpty()) {
            return [];
        }

        try {
            $completedDemands = Demand::query()
                ->where('municipality_id', $municipality->id)
                ->whereIn('status', ['completed', 'resolved'])
                ->orderByDesc('resolved_at')
                ->orderByDesc('confirmed_at')
                ->limit(120)
                ->get([
                    'id',
                    'title',
                    'raw_input',
                    'completion_note',
                    'locality',
                    'area',
                    'status',
                    'resolved_at',
                    'confirmed_at',
                ]);
        } catch (Throwable) {
            return [];
        }

        if ($completedDemands->isEmpty()) {
            return [];
        }

        $recurrenceMap = $this->buildRecurrenceMap($completedDemands);

        return $promises
            ->mapWithKeys(function (array $promise) use ($completedDemands, $recurrenceMap) {
                $matches = $completedDemands
                    ->map(fn (Demand $demand) => $this->scoreDemandForPromise($promise, $demand, $recurrenceMap))
                    ->filter(fn (?array $match) => $match !== null)
                    ->sortByDesc('score')
                    ->take(2)
                    ->values()
                    ->all();

                return [(int) $promise['id'] => $matches];
            })
            ->all();
    }

    private function buildRecurrenceMap(Collection $demands): array
    {
        $themeCounts = [];
        $themeLocalityCounts = [];

        foreach ($demands as $demand) {
            $theme = $this->resolveDemandTheme($demand);
            $locality = $this->normalizeLabel((string) ($demand->locality ?: 'sem_localidade'));
            $themeCounts[$theme] = ($themeCounts[$theme] ?? 0) + 1;
            $themeLocalityCounts[$theme . '|' . $locality] = ($themeLocalityCounts[$theme . '|' . $locality] ?? 0) + 1;
        }

        return [
            'theme' => $themeCounts,
            'theme_locality' => $themeLocalityCounts,
        ];
    }

    private function scoreDemandForPromise(array $promise, Demand $demand, array $recurrenceMap): ?array
    {
        $theme = $this->resolveDemandTheme($demand);
        $locality = $this->normalizeLabel((string) ($demand->locality ?: 'sem_localidade'));
        $themeRecurring = (int) ($recurrenceMap['theme'][$theme] ?? 0);
        $themeLocalityRecurring = (int) ($recurrenceMap['theme_locality'][$theme . '|' . $locality] ?? 0);
        $recurrenceTotal = max($themeRecurring, $themeLocalityRecurring);

        if ($recurrenceTotal < 2) {
            return null;
        }

        $score = 0;
        $reasons = [];

        $promiseTokens = $this->tokens(implode(' ', array_filter([
            $promise['text'] ?? null,
            $promise['axis_name'] ?? null,
            implode(' ', Arr::wrap($promise['keywords'] ?? [])),
        ])));
        $demandTokens = $this->tokens(implode(' ', array_filter([
            $demand->title,
            $demand->raw_input,
            $demand->completion_note,
            $theme,
            $demand->area,
        ])));

        $sharedTokens = $promiseTokens->intersect($demandTokens)->count();
        if ($sharedTokens > 0) {
            $score += min(45, $sharedTokens * 11);
            $reasons[] = 'Entrega concluida com proximidade tematica ao compromisso';
        }

        if ($theme !== 'Atendimento geral') {
            $themeTokens = $this->tokens($theme);
            if ($themeTokens->intersect($promiseTokens)->isNotEmpty()) {
                $score += 20;
                $reasons[] = 'Tema recorrente do Resolve ai conversa com o compromisso aberto';
            }
        }

        $score += min(20, $recurrenceTotal * 5);

        $score = min(100, $score);

        if ($score < 45) {
            return null;
        }

        return [
            'demand_id' => $demand->id,
            'title' => $demand->title ?: Str::limit((string) $demand->raw_input, 90),
            'theme' => $theme,
            'locality' => $demand->locality,
            'area' => $demand->area,
            'score' => $score,
            'recurrence_total' => $recurrenceTotal,
            'completion_note' => $demand->completion_note,
            'resolved_at' => optional($demand->resolved_at ?? $demand->confirmed_at)?->format('d/m/Y'),
            'summary' => $reasons[0] ?? 'Entrega concluida e recorrente que pode servir como evidencia para este compromisso.',
        ];
    }

    private function resolveDemandTheme(Demand $demand): string
    {
        $text = $this->normalizeLabel(implode(' ', array_filter([
            $demand->title,
            $demand->raw_input,
            $demand->completion_note,
        ])));

        $themeKeywords = [
            'Iluminacao publica' => ['lampada', 'poste', 'iluminacao', 'escuro', 'luz apagada'],
            'Vias e mobilidade' => ['buraco', 'asfalto', 'calcada', 'transito', 'sinalizacao', 'ponte', 'estrada', 'pavimentacao'],
            'Limpeza urbana' => ['lixo', 'entulho', 'limpeza', 'capina', 'mato alto', 'coleta'],
            'Agua e saneamento' => ['agua', 'esgoto', 'drenagem', 'alagamento', 'vazamento', 'saneamento'],
            'Saude' => ['saude', 'posto', 'ubs', 'medico', 'consulta', 'remedio', 'ambulancia'],
            'Educacao' => ['escola', 'creche', 'professor', 'aluno', 'educacao', 'merenda'],
            'Assistencia social' => ['social', 'familia', 'beneficio', 'assistencia', 'cras', 'cadastro'],
            'Seguranca e ordem' => ['seguranca', 'guarda', 'violencia', 'risco', 'fiscalizacao'],
            'Habitacao e obras' => ['casa', 'moradia', 'habitacao', 'obra', 'reforma', 'construcao'],
            'Esporte e lazer' => ['quadra', 'praca', 'esporte', 'lazer', 'campo', 'parque'],
        ];

        foreach ($themeKeywords as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($text, $this->normalizeLabel($keyword))) {
                    return $theme;
                }
            }
        }

        return 'Atendimento geral';
    }

    private function tokens(string $text): Collection
    {
        $normalized = $this->normalizeLabel($text);

        if ($normalized === '') {
            return collect();
        }

        return collect(explode(' ', $normalized))
            ->map(fn (string $token) => trim($token))
            ->filter(fn (string $token) => strlen($token) >= 4)
            ->reject(fn (string $token) => in_array($token, [
                'para', 'com', 'sem', 'mais', 'menos', 'muito', 'pela', 'pelo',
                'essa', 'esse', 'esta', 'este', 'deve', 'aqui', 'deste',
            ], true))
            ->unique()
            ->values();
    }

    private function normalizeLabel(string $text): string
    {
        return Str::of($text)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]+/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }
}
