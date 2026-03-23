<?php

namespace App\Services\RAG;

use App\Models\DocumentEmbedding;
use App\Models\Municipality;
use App\Services\AI\AIProviderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Serviço RAG (Retrieval-Augmented Generation).
 *
 * Fluxo:
 *  1. embed(query)        — gera vetor da pergunta
 *  2. retrieve(vector)    — busca chunks similares via pgvector
 *  3. buildContext(chunks) — formata contexto para o LLM
 *  4. O AIService usa o contexto no prompt do assistente
 */
class RAGService
{
    public function __construct(
        private AIProviderService $ai,
    ) {}

    /**
     * Recupera os chunks mais relevantes para uma query,
     * filtrados pelo município (isolamento multi-tenant).
     *
     * @return Collection<DocumentEmbedding>
     */
    public function retrieve(string $query, Municipality $municipality, int $limit = 10): Collection
    {
        // 1. Gerar embedding da query
        $queryVector = $this->ai->embed($query);
        $vectorStr   = '[' . implode(',', $queryVector) . ']';

        // 2. Busca por similaridade coseno via pgvector
        //    Retorna chunks do município + da base de conhecimento geral (municipality_id = null)
        $threshold = config('ai.rag.similarity_threshold', 0.75);

        $results = DB::select(
            "
            SELECT
                id,
                municipality_id,
                layer,
                category,
                source,
                content,
                metadata,
                1 - (embedding <=> :vector::vector) AS similarity
            FROM document_embeddings
            WHERE
                (municipality_id = :mun_id OR municipality_id IS NULL)
                AND (expires_at IS NULL OR expires_at > NOW())
                AND 1 - (embedding <=> :vector2::vector) > :threshold
            ORDER BY embedding <=> :vector3::vector
            LIMIT :limit
            ",
            [
                'vector'     => $vectorStr,
                'vector2'    => $vectorStr,
                'vector3'    => $vectorStr,
                'mun_id'     => $municipality->id,
                'threshold'  => $threshold,
                'limit'      => $limit,
            ]
        );

        if (!empty($results)) {
            return collect($results);
        }

        $fallbackThreshold = (float) config('ai.rag.fallback_similarity_threshold', 0.35);

        $fallback = DB::select(
            "
            SELECT
                id,
                municipality_id,
                layer,
                category,
                source,
                content,
                metadata,
                1 - (embedding <=> :vector::vector) AS similarity
            FROM document_embeddings
            WHERE
                (municipality_id = :mun_id OR municipality_id IS NULL)
                AND (expires_at IS NULL OR expires_at > NOW())
                AND 1 - (embedding <=> :vector2::vector) > :threshold
            ORDER BY embedding <=> :vector3::vector
            LIMIT :limit
            ",
            [
                'vector'     => $vectorStr,
                'vector2'    => $vectorStr,
                'vector3'    => $vectorStr,
                'mun_id'     => $municipality->id,
                'threshold'  => $fallbackThreshold,
                'limit'      => $limit,
            ]
        );

        return collect($fallback);
    }

    /**
     * Formata os chunks recuperados em um bloco de contexto
     * para inserir no system prompt do assistente.
     */
    public function buildContext(Collection $chunks): string
    {
        if ($chunks->isEmpty()) {
            return '';
        }

        $context = "### Informações do município recuperadas:\n\n";

        foreach ($chunks as $chunk) {
            $meta     = is_array($chunk->metadata) ? $chunk->metadata : json_decode($chunk->metadata ?? '{}', true);
            $category = $chunk->category ?? 'geral';
            $source   = $chunk->source ?? 'desconhecido';
            $layer    = $chunk->layer ?? 'public_data';

            $context .= "---\n";
            $context .= "**Fonte:** {$source} | **Categoria:** {$category} | **Camada:** {$layer}\n";

            if (!empty($meta['period'])) {
                $context .= "**Período:** {$meta['period']}\n";
            }

            $context .= "\n{$chunk->content}\n\n";
        }

        return $context;
    }

    /**
     * Indexa um chunk de texto como embedding no banco.
     */
    public function indexChunk(
        string  $content,
        string  $layer,
        string  $category,
        string  $source,
        int     $chunkIndex,
        array   $metadata = [],
        ?int    $municipalityId = null,
        ?int    $documentId = null,
    ): DocumentEmbedding {
        $vector    = $this->ai->embed($content);
        $vectorStr = '[' . implode(',', $vector) . ']';

        return DocumentEmbedding::create([
            'municipality_id' => $municipalityId,
            'document_id'     => $documentId,
            'layer'           => $layer,
            'category'        => $category,
            'source'          => $source,
            'chunk_index'     => $chunkIndex,
            'content'         => $content,
            'embedding'       => DB::raw("'{$vectorStr}'::vector"),
            'metadata'        => $metadata,
            'token_count'     => str_word_count($content),
        ]);
    }

    /**
     * Divide um texto longo em chunks sobrepostos para indexação.
     *
     * @return array<string>
     */
    public function chunkText(string $text, int $chunkSize = 800, int $overlap = 100): array
    {
        $words  = explode(' ', $text);
        $chunks = [];
        $i      = 0;

        while ($i < count($words)) {
            $chunk    = implode(' ', array_slice($words, $i, $chunkSize));
            $chunks[] = trim($chunk);
            $i       += ($chunkSize - $overlap);
        }

        return array_filter($chunks);
    }
}
