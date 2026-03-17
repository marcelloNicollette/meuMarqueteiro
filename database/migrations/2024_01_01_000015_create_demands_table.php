<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandas registradas pelo prefeito em campo (por voz ou texto).
 * Módulo 3 — organizadas por tema, localidade e secretaria responsável.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demands', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('registered_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Origem do registro
            $table->string('input_type', 20)->default('text')
                ->comment('text | voice');
            $table->text('raw_input')
                ->comment('Texto original ou transcrição do áudio');

            // Dados estruturados pela IA
            $table->string('title')->nullable()
                ->comment('Título resumido gerado pela IA');
            $table->text('description')->nullable()
                ->comment('Descrição organizada pela IA');

            // Classificação automática
            $table->string('area', 50)->nullable()
                ->comment('saude | educacao | infraestrutura | etc.');
            $table->string('locality')->nullable()
                ->comment('Bairro, rua ou região identificada');
            $table->string('responsible_secretary')->nullable()
                ->comment('Secretaria responsável sugerida pela IA');

            // Prioridade e urgência
            $table->string('priority', 10)->default('media')
                ->comment('alta | media | baixa');
            $table->boolean('is_urgent')->default(false);

            // Status de acompanhamento
            $table->string('status', 20)->default('pending')
                ->comment('pending | in_progress | resolved | cancelled');
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Localização geográfica (opcional)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index(['municipality_id', 'status']);
            $table->index(['municipality_id', 'area']);
            $table->index('is_urgent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demands');
    }
};
