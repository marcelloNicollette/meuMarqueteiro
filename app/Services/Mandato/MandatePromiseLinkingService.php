<?php

namespace App\Services\Mandato;

use App\Models\DocumentEmbedding;
use App\Models\MandatePromise;
use App\Models\Municipality;
use App\Services\AI\AIProviderService;
use App\Services\RAG\RAGService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MandatePromiseLinkingService
{
    public function __construct(
        private readonly AIProviderService $ai,
        private readonly RAGService $rag,
    ) {}

    public function ensurePromiseEmbeddings(Municipality $municipality, bool $force = false): void
    {
        $promiseQuery = MandatePromise::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->with('axis');

        $promises = $promiseQuery->get();
        $existingCount = DocumentEmbedding::query()
            ->where('municipality_id', $municipality->id)
            ->where('source', 'mandato_promise')
            ->count();

        if (!$force && $existingCount > 0 && $existingCount === $promises->count()) {
            return;
        }

        DocumentEmbedding::query()
            ->where('municipality_id', $municipality->id)
            ->where('source', 'mandato_promise')
            ->delete();

        $promises->each(function (MandatePromise $promise) use ($municipality) {
                $keywords = collect($promise->keywords ?? [])->filter()->implode(', ');
                $content = trim(implode(' ', array_filter([
                    $promise->axis?->name,
                    $promise->text,
                    $keywords !== '' ? 'Palavras-chave: ' . $keywords . '.' : null,
                    $promise->specificity ? 'Especificidade: ' . $promise->specificity . '.' : null,
                ])));

                $this->rag->indexChunk(
                    content: $content,
                    layer: 'client_data',
                    category: 'polítical',
                    source: 'mandato_promise',
                    chunkIndex: 0,
                    metadata: [
                        'promise_id' => $promise->id,
                        'axis_id' => $promise->mandate_axis_id,
                        'keywords' => $promise->keywords ?? [],
                        'specificity' => $promise->specificity,
                    ],
                    municipalityId: $municipality->id,
                );
        });
    }

    public function suggestForAction(
        Municipality $municipality,
        string $title,
        ?string $description = null,
        ?int $axisId = null,
        int $limit = 5,
    ): array {
        $query = trim($title . ' ' . ($description ?? ''));
        if ($query === '') {
            return [];
        }

        $this->ensurePromiseEmbeddings($municipality);

        return $this->rag->isVectorSearchAvailable()
            ? $this->vectorSuggestions($municipality, $query, $axisId, $limit)
            : $this->fallbackSuggestions($municipality, $query, $axisId, $limit);
    }

    private function vectorSuggestions(Municipality $municipality, string $query, ?int $axisId, int $limit): array
    {
        $vector = $this->ai->embed($query);
        $vectorStr = '[' . implode(',', $vector) . ']';
        $axisIdString = $axisId ? (string) $axisId : '';

        $rows = DB::select(
            "
            SELECT
                metadata->>'promise_id' AS promise_id,
                1 - (embedding <=> :vector::vector) AS similarity,
                CASE WHEN :axis_id <> '' AND metadata->>'axis_id' = :axis_id2 THEN 0.08 ELSE 0 END AS axis_bonus
            FROM document_embeddings
            WHERE municipality_id = :municipality_id
              AND source = 'mandato_promise'
              AND layer = 'client_data'
              AND category = 'polítical'
            ORDER BY ((1 - (embedding <=> :vector2::vector)) + CASE WHEN :axis_id3 <> '' AND metadata->>'axis_id' = :axis_id4 THEN 0.08 ELSE 0 END) DESC
            LIMIT :limit
            ",
            [
                'vector' => $vectorStr,
                'vector2' => $vectorStr,
                'municipality_id' => $municipality->id,
                'axis_id' => $axisIdString,
                'axis_id2' => $axisIdString,
                'axis_id3' => $axisIdString,
                'axis_id4' => $axisIdString,
                'limit' => $limit,
            ]
        );

        return $this->hydrateSuggestions($rows);
    }

    private function fallbackSuggestions(Municipality $municipality, string $query, ?int $axisId, int $limit): array
    {
        $queryLower = mb_strtolower($query);

        $rows = MandatePromise::query()
            ->where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->with('axis')
            ->get()
            ->map(function (MandatePromise $promise) use ($queryLower, $axisId) {
                similar_text($queryLower, mb_strtolower($promise->text), $percent);
                $score = $percent / 100;
                if ($axisId && (int) $promise->mandate_axis_id === (int) $axisId) {
                    $score += 0.08;
                }

                return (object) [
                    'promise_id' => (string) $promise->id,
                    'similarity' => $score,
                    'axis_bonus' => $axisId && (int) $promise->mandate_axis_id === (int) $axisId ? 0.08 : 0,
                ];
            })
            ->sortByDesc(fn ($row) => $row->similarity + $row->axis_bonus)
            ->take($limit)
            ->values()
            ->all();

        return $this->hydrateSuggestions($rows);
    }

    private function hydrateSuggestions(array $rows): array
    {
        $promiseIds = collect($rows)
            ->pluck('promise_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($promiseIds->isEmpty()) {
            return [];
        }

        $promises = MandatePromise::query()
            ->whereIn('id', $promiseIds)
            ->with('axis')
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->map(function ($row) use ($promises) {
                $promise = $promises->get((int) $row->promise_id);
                if (!$promise) {
                    return null;
                }

                return [
                    'id' => $promise->id,
                    'text' => $promise->text,
                    'axis_id' => $promise->mandate_axis_id,
                    'axis_name' => $promise->axis?->name,
                    'similarity' => round((float) $row->similarity, 4),
                    'similarity_percent' => max(0, min(100, (int) round(((float) $row->similarity) * 100))),
                    'keywords' => $promise->keywords ?? [],
                    'specificity' => $promise->specificity,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
