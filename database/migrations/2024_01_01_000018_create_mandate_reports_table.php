<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relatórios mensais de mandato.
 * Gerados automaticamente pelo consultor via back-office
 * e entregues ao prefeito como PDF ou via app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandate_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            // Período de referência
            $table->unsignedSmallInteger('reference_year');
            $table->unsignedTinyInteger('reference_month')
                ->comment('1-12');

            // Título e conteúdo
            $table->string('title');
            $table->longText('content_markdown')
                ->comment('Relatório completo em Markdown');

            // Arquivo PDF gerado
            $table->string('pdf_path')->nullable();
            $table->string('pdf_disk', 20)->default('s3');

            // Resumo estruturado (para exibição rápida no app)
            $table->json('summary')->nullable()
                ->comment('{highlights, commitments_progress, fiscal_snapshot, alerts}');

            // Ciclo de vida
            $table->string('status', 20)->default('draft')
                ->comment('draft | published | delivered');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            // Geração
            $table->string('ai_provider', 20)->nullable();
            $table->unsignedInteger('tokens_used')->nullable();

            $table->foreignId('generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['municipality_id', 'reference_year', 'reference_month']);
            $table->index(['municipality_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandate_reports');
    }
};
