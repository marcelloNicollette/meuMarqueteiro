<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dados fiscais e orçamentários por município.
 * Alimentados pelas APIs: SICONFI, FNDE, Portal da Transparência.
 *
 * Cada registro = snapshot de um tipo de dado em um período.
 * O dado bruto em JSON permite flexibilidade entre diferentes fontes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_data', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            // Origem
            $table->string('source', 30)
                ->comment('siconfi | fnde | portal_transparencia | datasus | ibge | snis');

            // Tipo do dado
            $table->string('type', 60)
                ->comment('receita_orcamentaria | despesa_funcao | rreo | rgf | fundeb | pnae | pnate | ideb | cobertura_esf | saneamento_agua');

            // Período
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('period')->nullable()
                ->comment('Mês (1-12) ou bimestre (1-6) conforme o tipo');

            // Dado bruto da API
            $table->json('data')
                ->comment('Payload completo retornado pela API');

            // Valores extraídos para acesso rápido (sem parsear JSON)
            $table->decimal('value_total', 18, 2)->nullable()
                ->comment('Valor principal do registro em R$');
            $table->decimal('value_per_capita', 10, 2)->nullable();

            $table->timestamp('fetched_at')
                ->comment('Quando foi buscado da API');

            $table->timestamps();

            // Índice composto para evitar duplicatas e buscas eficientes
            $table->unique(['municipality_id', 'source', 'type', 'year', 'period'], 'fiscal_unique_period');
            $table->index(['municipality_id', 'source', 'year']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_data');
    }
};
