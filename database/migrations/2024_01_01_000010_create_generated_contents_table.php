<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conteúdo gerado pelo Módulo 2 — Comunicação e Marketing Político.
 * Posts, discursos, comunicados, respostas a crise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Conversa que gerou este conteúdo (opcional)
            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Classificação
            $table->string('type', 50)
                ->comment('post_instagram | post_facebook | post_whatsapp | discurso | comunicado | resposta_crise | preparacao_entrevista');
            $table->string('channel', 30)->nullable()
                ->comment('instagram | facebook | whatsapp | discurso | todos');
            $table->string('tone', 30)->nullable()
                ->comment('celebratorio | tecnico | empatico | informativo');

            // Conteúdo
            $table->string('title')->nullable()
                ->comment('Título interno para identificação');
            $table->longText('content')
                ->comment('Variação principal (escolhida ou primeira gerada)');
            $table->json('variations')->nullable()
                ->comment('[{tone, content}, ...] — variações alternativas geradas');

            // Publicação
            $table->timestamp('published_at')->nullable();
            $table->string('published_url')->nullable();

            // Ciclo de vida
            $table->string('status', 20)->default('draft')
                ->comment('draft | approved | published | archived');

            // Tags e metadados
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable()
                ->comment('{theme, provider, model, tokens_used}');

            $table->timestamps();

            $table->index(['municipality_id', 'status']);
            $table->index(['municipality_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_contents');
    }
};
