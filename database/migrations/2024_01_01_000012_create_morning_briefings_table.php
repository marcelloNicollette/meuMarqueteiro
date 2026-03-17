<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Briefings matinais gerados automaticamente pelo cron das 6h30.
 * Um por município por dia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('morning_briefings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            // Conteúdo gerado em markdown
            $table->longText('content');

            // Seções estruturadas (para renderização modular)
            $table->json('sections')->nullable()
                ->comment('{agenda, comunicacao, alertas, contexto_politico, pergunta_estrategica}');

            // Entrega
            $table->timestamp('delivered_at')->nullable();
            $table->string('delivery_channel', 20)->default('app')
                ->comment('app | whatsapp');

            // Leitura
            $table->timestamp('read_at')->nullable();

            // Metadados de geração
            $table->string('ai_provider', 20)->nullable();
            $table->string('ai_model', 100)->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->unsignedSmallInteger('rag_sources_count')->nullable();

            $table->timestamps();

            // Garante unicidade: 1 briefing por município por dia
            $table->unique(['municipality_id', 'date']);

            $table->index('date');
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('morning_briefings');
    }
};
