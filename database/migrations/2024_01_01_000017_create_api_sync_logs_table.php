<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logs de sincronização das APIs públicas.
 * Alimenta o Monitor de Integrações no painel do admin.
 * Permite saber quando cada fonte de dados foi sincronizada e se houve erros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sync_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            // Qual fonte foi sincronizada
            $table->string('source', 30)
                ->comment('siconfi | fnde | ibge | datasus | portal_transparencia | snis');

            // Tipo de dado sincronizado nesta execução
            $table->string('data_type', 60)->nullable()
                ->comment('receita_orcamentaria | fundeb | ideb | etc.');

            // Resultado
            $table->string('status', 20)
                ->comment('success | partial | failed');

            $table->unsignedInteger('records_fetched')->default(0);
            $table->unsignedInteger('records_saved')->default(0);

            // Detalhes do erro (se houver)
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();

            // Performance
            $table->unsignedInteger('duration_ms')->nullable()
                ->comment('Tempo de execução em milissegundos');

            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->index(['municipality_id', 'source']);
            $table->index(['source', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_logs');
    }
};
