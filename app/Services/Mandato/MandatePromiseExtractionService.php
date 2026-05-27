<?php

namespace App\Services\Mandato;

use App\Models\DocumentEmbedding;
use App\Models\Municipality;
use App\Models\MunicipalityDocument;
use App\Services\AI\AIProviderService;
use App\Services\Documents\MunicipalityDocumentTextExtractor;
use App\Services\RAG\RAGService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MandatePromiseExtractionService
{
    public function __construct(
        private readonly AIProviderService $ai,
        private readonly MunicipalityDocumentTextExtractor $extractor,
        private readonly RAGService $rag,
        private readonly MandateAxisCatalogService $axisCatalog,
    ) {}

    public function extractFromGovernmentPlan(Municipality $municipality, MunicipalityDocument $document): array
    {
        $this->axisCatalog->ensureDefaultAxes($municipality);

        $text = $this->extractor->extract($document);
        if (trim($text) === '') {
            throw new \RuntimeException('Não foi possível extrair texto legível do plano de governo enviado.');
        }

        $this->indexSourceDocument($municipality, $document, $text);

        $chunks = collect($this->rag->chunkText($text, 1400, 120))
            ->filter()
            ->take(10)
            ->values();

        $items = $chunks
            ->flatMap(fn (string $chunk) => $this->extractChunk($municipality, $chunk))
            ->map(fn (array $item) => $this->normalizeExtractedItem($municipality, $document, $item))
            ->filter(fn (array $item) => $item['text'] !== '')
            ->unique(fn (array $item) => Str::slug($item['text']))
            ->values()
            ->all();

        return [
            'document_id' => $document->id,
            'document_name' => $document->name,
            'items' => $items,
            'text_excerpt' => Str::limit($text, 8000),
        ];
    }

    private function extractChunk(Municipality $municipality, string $chunk): array
    {
        $axisNames = collect($this->axisCatalog->ensureDefaultAxes($municipality))
            ->pluck('name')
            ->implode(', ');

        $response = $this->ai->chat([
            [
                'role' => 'system',
                'content' => "Você extrai compromissos explícitos de plano de governo municipal.\n"
                    . "Considere apenas promessas, metas ou entregas concretas.\n"
                    . "Use somente estes 9 eixos fixos: {$axisNames}.\n"
                    . "Responda APENAS em JSON no formato {\"commitments\":[{\"description\":\"\",\"axis_name\":\"\",\"keywords\":[\"\"],\"specificity\":\"quantitativo|qualitativo\"}]}.\n"
                    . "Não invente compromisso implícito e não  repita itens equivalentes.",
            ],
            [
                'role' => 'user',
                'content' => "Trecho do plano de governo:\n\n{$chunk}",
            ],
        ], [
            'temperature' => 0.1,
            'max_tokens' => 1800,
            'timeout' => 30,
        ]);

        $payload = $this->decodeJsonPayload($response->content);

        return Arr::wrap($payload['commitments'] ?? []);
    }

    private function normalizeExtractedItem(Municipality $municipality, MunicipalityDocument $document, array $item): array
    {
        $text = trim((string) ($item['description'] ?? ''));
        $keywords = collect(Arr::wrap($item['keywords'] ?? []))
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $specificity = trim((string) ($item['specificity'] ?? 'qualitativo'));
        if (!in_array($specificity, ['quantitativo', 'qualitativo'], true)) {
            $specificity = 'qualitativo';
        }

        return [
            'text' => $text,
            'axis_id' => $this->axisCatalog->axisIdBySuggestedName($municipality, (string) ($item['axis_name'] ?? '')),
            'keywords' => $keywords,
            'keywords_text' => implode(', ', $keywords),
            'specificity' => $specificity,
            'source_document_id' => $document->id,
            'is_active' => true,
        ];
    }

    private function indexSourceDocument(Municipality $municipality, MunicipalityDocument $document, string $text): void
    {
        DocumentEmbedding::query()
            ->where('document_id', $document->id)
            ->where('source', 'programa_governo')
            ->delete();

        $chunks = $this->rag->chunkText($text, 700, 80);
        foreach ($chunks as $index => $chunk) {
            $this->rag->indexChunk(
                content: $chunk,
                layer: 'client_data',
                category: 'polítical',
                source: 'programa_governo',
                chunkIndex: $index,
                metadata: [
                    'document_id' => $document->id,
                    'document_type' => $document->type,
                    'document_name' => $document->name,
                ],
                municipalityId: $municipality->id,
                documentId: $document->id,
            );
        }

        $document->update([
            'indexing_status' => 'done',
            'indexed_at' => now(),
            'chunks_count' => count($chunks),
            'indexing_error' => null,
        ]);
    }

    private function decodeJsonPayload(string $content): array
    {
        $content = trim($content);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['commitments' => []];
    }
}
