<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de conversas do assistente.
 * Cada conversa pertence a um prefeito e município.
 * As mensagens guardam o conteúdo, as fontes RAG usadas e o feedback.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Conversas ─────────────────────────────────────────
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title')->nullable()
                ->comment('Título gerado automaticamente a partir das primeiras mensagens');

            // Contexto persistente entre sessões
            $table->json('context')->nullable()
                ->comment('Dados de contexto carregados no início de cada sessão');

            // Provider e modelo usados (pode variar por conversa)
            $table->string('ai_provider', 20)->nullable()
                ->comment('openai | anthropic | gemini');
            $table->string('ai_model', 100)->nullable();

            // Métricas de uso
            $table->unsignedInteger('token_count')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['municipality_id', 'last_message_at']);
        });

        // ── Mensagens ─────────────────────────────────────────
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained()
                ->cascadeOnDelete();

            // Papel na conversa
            $table->string('role', 20)
                ->comment('user | assistant | system');

            // Conteúdo
            $table->text('content');

            // Entrada por voz
            $table->string('input_type', 20)->default('text')
                ->comment('text | voice');
            $table->text('voice_transcript')->nullable()
                ->comment('Transcrição original antes de processar');

            // Fontes RAG utilizadas nesta resposta
            $table->json('rag_sources')->nullable()
                ->comment('[{source, category, similarity}, ...]');

            // Métricas
            $table->unsignedInteger('tokens_used')->nullable();

            // Feedback do prefeito
            $table->string('feedback', 20)->nullable()
                ->comment('thumbs_up | thumbs_down');
            $table->text('feedback_note')->nullable();

            // Metadados extras (provider, model, latency_ms, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['conversation_id', 'role']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
