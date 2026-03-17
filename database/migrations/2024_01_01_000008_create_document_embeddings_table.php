<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chunks de documentos com embeddings vetoriais — núcleo do sistema RAG.
 *
 * Usa pgvector para busca por similaridade coseno (HNSW index).
 *
 * Isolamento multi-tenant:
 *   - municipality_id = NULL  → base de conhecimento geral (compartilhada)
 *   - municipality_id = X     → dados exclusivos do município X
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_embeddings', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy: NULL = base geral
            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Documento de origem (nullable: dados de APIs não têm documento)
            $table->foreignId('document_id')
                ->nullable()
                ->constrained('municipality_documents')
                ->nullOnDelete();

            // Classificação
            $table->string('layer', 30)
                ->comment('public_data | knowledge_base | client_data');
            $table->string('category', 50)
                ->comment('fiscal | education | health | political | communication | federal_programs | legislation | benchmark');
            $table->string('source', 200)
                ->comment('SICONFI, FNDE, IBGE, programa_governo, etc.');

            // Chunk
            $table->unsignedInteger('chunk_index')->default(0)
                ->comment('Posição do chunk no documento original');
            $table->text('content')
                ->comment('Texto do chunk — o que é buscado e retornado ao LLM');
            $table->unsignedInteger('token_count')->nullable();

            // Metadados flexíveis (período, ano, tags, etc.)
            $table->json('metadata')->nullable();

            // Expiração (para dados que ficam obsoletos)
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Índices relacionais
            $table->index(['municipality_id', 'layer']);
            $table->index(['municipality_id', 'category']);
            $table->index('source');
            $table->index('expires_at');
        });

        // ── Coluna vector (pgvector) ──────────────────────────────
        // Adicionada separadamente pois o Blueprint do Laravel
        // não tem suporte nativo a tipos pgvector.
        // Dimensões: 1536 (OpenAI text-embedding-3-small / text-embedding-ada-002)
        //            768  (Google text-embedding-004)
        // Ajuste VECTOR_DIMENSIONS no .env conforme o provider escolhido.
        $dimensions = (int) config('ai.rag.dimensions', 1536);

        DB::statement("ALTER TABLE document_embeddings ADD COLUMN embedding vector({$dimensions})");

        // ── Índice HNSW para busca aproximada eficiente ───────────
        // HNSW (Hierarchical Navigable Small World) é mais rápido que IVFFlat
        // para bases com menos de 1M de vetores (escala típica deste produto).
        //
        // m               = número de conexões por nó (padrão: 16)
        // ef_construction = precisão na construção do índice (padrão: 64)
        DB::statement("
            CREATE INDEX document_embeddings_embedding_hnsw_idx
            ON document_embeddings
            USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('document_embeddings');
    }
};
