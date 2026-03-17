<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos enviados pelo consultor durante o onboarding e ao longo do contrato.
 * Cada documento é indexado no RAG após upload (chunks + embeddings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            // Metadados do arquivo
            $table->string('name')->comment('Nome amigável do documento');
            $table->string('type', 50)->comment('programa_governo | ata_camara | orcamento | comunicacao | outros');
            $table->string('disk', 20)->default('s3');
            $table->string('path')->comment('Caminho no storage');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('original_filename')->nullable();

            // Status de indexação RAG
            $table->string('indexing_status', 20)->default('pending')
                ->comment('pending | processing | done | failed');
            $table->timestamp('indexed_at')->nullable();
            $table->unsignedInteger('chunks_count')->nullable()
                ->comment('Número de chunks gerados no RAG');
            $table->text('indexing_error')->nullable();

            // Quem fez upload
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('type');
            $table->index('indexing_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_documents');
    }
};
