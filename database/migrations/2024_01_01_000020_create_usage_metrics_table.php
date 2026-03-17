<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métricas de uso por município — para controle de limites por tier
 * e relatórios de adoção no painel do admin.
 *
 * Snapshots diários consolidados para evitar queries pesadas nas tabelas
 * de mensagens e conteúdos gerados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_metrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            // Conversas e mensagens
            $table->unsignedInteger('conversations_started')->default(0);
            $table->unsignedInteger('messages_sent')->default(0);
            $table->unsignedInteger('messages_received')->default(0);

            // Tokens consumidos por provider
            $table->unsignedBigInteger('tokens_openai')->default(0);
            $table->unsignedBigInteger('tokens_anthropic')->default(0);
            $table->unsignedBigInteger('tokens_gemini')->default(0);

            // Conteúdo gerado
            $table->unsignedInteger('posts_generated')->default(0);
            $table->unsignedInteger('posts_approved')->default(0);
            $table->unsignedInteger('crisis_responses')->default(0);
            $table->unsignedInteger('interview_preps')->default(0);

            // Briefings
            $table->boolean('briefing_generated')->default(false);
            $table->boolean('briefing_read')->default(false);

            // RAG
            $table->unsignedInteger('rag_queries')->default(0);
            $table->unsignedInteger('rag_chunks_retrieved')->default(0);

            // Feedback das respostas
            $table->unsignedSmallInteger('feedback_thumbs_up')->default(0);
            $table->unsignedSmallInteger('feedback_thumbs_down')->default(0);

            // Sessão (minutos ativos estimados)
            $table->unsignedSmallInteger('active_minutes')->default(0);

            $table->timestamps();

            $table->unique(['municipality_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_metrics');
    }
};
