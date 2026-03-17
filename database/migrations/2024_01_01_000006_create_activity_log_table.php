<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log de auditoria — spatie/laravel-activitylog.
 * Rastreia todas as alterações sensíveis (dados de clientes, onboarding, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');

            // Sujeito da ação (modelo alterado)
            $table->nullableMorphs('subject', 'subject');

            // Evento disparado
            $table->string('event')->nullable();

            // Quem causou a ação
            $table->nullableMorphs('causer', 'causer');

            // Dados antes/depois
            $table->json('properties')->nullable();

            // Rastreamento de lote de ações
            $table->uuid('batch_uuid')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
