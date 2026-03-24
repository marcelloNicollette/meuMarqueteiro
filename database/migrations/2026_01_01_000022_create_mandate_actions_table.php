<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ações de governo — 17 campos em 5 grupos
        Schema::create('mandate_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mandate_axis_id')->constrained()->cascadeOnDelete();

            // Grupo 1 — Identificação
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('secretaria')->nullable();

            // Grupo 2 — Status e progresso
            $table->string('status', 30)->default('em_andamento');
            // comment: planejado | em_andamento | concluido | suspenso
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('physical_progress')->default(0); // 0-100%

            // Grupo 3 — Recursos e abrangência
            $table->decimal('investment', 15, 2)->nullable();
            $table->string('funding_source')->nullable();
            $table->string('region')->nullable();
            $table->unsignedInteger('beneficiaries')->nullable();

            // Grupo 5 — Evidência e comunicação
            $table->string('proof_url')->nullable();
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            $table->index(['municipality_id', 'mandate_axis_id']);
            $table->index(['municipality_id', 'status']);
        });

        // Tabela pivot: ação <-> promessa (com nível de atendimento)
        Schema::create('mandate_action_promise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandate_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mandate_promise_id')->constrained()->cascadeOnDelete();
            // Grupo 4 — Vínculo com promessas
            $table->unsignedSmallInteger('fulfillment_level')->default(0);
            // comment: 0 | 25 | 50 | 75 | 100
            $table->text('fulfillment_justification')->nullable();
            $table->timestamps();

            $table->unique(['mandate_action_id', 'mandate_promise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandate_action_promise');
        Schema::dropIfExists('mandate_actions');
    }
};
