<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Radar de Programas e Recursos Federais — Módulo 3.
 *
 * Programas identificados automaticamente pelo assistente como compatíveis
 * com o perfil do município. Populados via cron + IA de matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federal_program_alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            // Identificação do programa
            $table->string('program_name');
            $table->string('ministry')->nullable()
                ->comment('Ex: Ministério da Saúde, FNDE, BNDES');
            $table->string('program_code')->nullable()
                ->comment('Código oficial do programa/edital');
            $table->text('description');

            // Classificação
            $table->string('area', 50)->nullable()
                ->comment('saude | educacao | infraestrutura | saneamento | habitacao | social | outros');

            // Financeiro
            $table->decimal('max_value', 15, 2)->nullable()
                ->comment('Valor máximo disponível por município em R$');
            $table->decimal('min_value', 15, 2)->nullable();
            $table->string('funding_type', 30)->nullable()
                ->comment('transferencia | convenio | credito | emenda');

            // Critérios de elegibilidade
            $table->json('eligibility_criteria')->nullable()
                ->comment('Requisitos mínimos para candidatura');

            // Prazos
            $table->date('open_date')->nullable();
            $table->date('deadline')->nullable()
                ->comment('Data limite para candidatura');

            // Status de candidatura
            $table->string('status', 20)->default('open')
                ->comment('open | closing | applied | closed | approved | rejected');
            $table->timestamp('applied_at')->nullable();

            // Matching por IA
            $table->boolean('ai_matched')->default(true)
                ->comment('true = identificado por IA; false = adicionado manualmente');
            $table->decimal('match_score', 4, 2)->nullable()
                ->comment('Score de compatibilidade 0.00-1.00');
            $table->text('match_reason')->nullable()
                ->comment('Explicação da IA sobre a compatibilidade');

            // Fonte
            $table->string('source_url')->nullable();
            $table->string('source_platform')->nullable()
                ->comment('transferegov | bndes | caixa | fnde | ministerio');

            $table->timestamps();

            $table->index(['municipality_id', 'status']);
            $table->index(['municipality_id', 'area']);
            $table->index('deadline');
            $table->index('match_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federal_program_alerts');
    }
};
