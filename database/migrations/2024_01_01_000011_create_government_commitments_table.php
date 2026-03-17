<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compromissos de campanha / programa de governo.
 * Módulo 3 — rastreamento de status ao longo do mandato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_commitments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            // Identificação
            $table->string('title');
            $table->text('description')->nullable();

            // Classificação
            $table->string('area', 50)
                ->comment('saude | educacao | infraestrutura | social | seguranca | meio_ambiente | economia | cultura | outros');
            $table->string('priority', 10)->default('media')
                ->comment('alta | media | baixa');

            // Status do ciclo de vida
            $table->string('status', 30)->default('prometido')
                ->comment('prometido | em_andamento | entregue | em_risco | cancelado');

            // Execução
            $table->unsignedSmallInteger('progress_percent')->default(0)
                ->comment('0-100');
            $table->date('deadline')->nullable();
            $table->date('delivered_at')->nullable();

            // Financeiro
            $table->decimal('budget_allocated', 15, 2)->nullable()
                ->comment('Valor orçado em R$');
            $table->decimal('budget_spent', 15, 2)->nullable()
                ->comment('Valor executado em R$');
            $table->string('budget_source')->nullable()
                ->comment('Fonte: municipal, federal, estadual, convênio');

            // Responsabilidade
            $table->string('responsible_secretary')->nullable();
            $table->string('responsible_contact')->nullable();

            // Rastreabilidade
            $table->text('notes')->nullable()
                ->comment('Anotações internas do consultor');
            $table->string('source_document')->nullable()
                ->comment('Nome/ID do documento do programa de governo de origem');

            $table->timestamps();

            $table->index(['municipality_id', 'status']);
            $table->index(['municipality_id', 'area']);
            $table->index(['municipality_id', 'priority']);
            $table->index('deadline');
        });

        // ── Histórico de status (auditoria de mudanças) ───────
        Schema::create('commitment_status_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('government_commitment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('note')->nullable();

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitment_status_history');
        Schema::dropIfExists('government_commitments');
    }
};
