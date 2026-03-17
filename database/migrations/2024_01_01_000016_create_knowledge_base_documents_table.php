<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base de Conhecimento Geral — Camada 2 do RAG.
 *
 * Documentos curados pelo time do produto:
 * legislação municipal, benchmarks, programas federais,
 * boas práticas e frameworks de comunicação política.
 *
 * Diferente dos documentos por município, estes são compartilhados
 * entre todos os clientes (municipality_id = NULL nos embeddings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_documents', function (Blueprint $table) {
            $table->id();

            // Identificação
            $table->string('title');
            $table->text('description')->nullable();

            // Classificação temática
            $table->string('category', 60)
                ->comment('legislation | federal_programs | benchmark | best_practice | communication | policy | outros');
            $table->json('tags')->nullable();

            // Vigência (para legislação e programas)
            $table->smallInteger('reference_year')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            // Arquivo
            $table->string('disk', 20)->default('s3');
            $table->string('path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // Conteúdo inline (para textos curtos que não precisam de arquivo)
            $table->longText('content_raw')->nullable();

            // Status de indexação RAG
            $table->string('indexing_status', 20)->default('pending')
                ->comment('pending | processing | done | failed');
            $table->timestamp('indexed_at')->nullable();
            $table->unsignedInteger('chunks_count')->nullable();
            $table->text('indexing_error')->nullable();

            // Quem publicou
            $table->foreignId('published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('indexing_status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_documents');
    }
};
