<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();

            // Identificação
            $table->string('ibge_code', 7)->unique()->comment('Código IBGE do município');
            $table->string('name');
            $table->string('state', 100);
            $table->string('state_code', 2);
            $table->string('region', 20)->nullable()->comment('Norte, Nordeste, Centro-Oeste, Sudeste, Sul');

            // Dados socioeconômicos (IBGE)
            $table->integer('population')->nullable();
            $table->decimal('gdp', 15, 2)->nullable()->comment('PIB em R$');
            $table->decimal('idhm', 5, 3)->nullable()->comment('IDH Municipal (0-1)');
            $table->decimal('area_km2', 10, 2)->nullable();

            // Assinatura e onboarding
            $table->string('onboarding_status', 20)->default('pending')
                ->comment('pending | in_progress | completed');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->string('subscription_tier', 20)->default('essencial')
                ->comment('essencial | estrategico | parceiro');
            $table->boolean('subscription_active')->default(false);
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();

            // Dados dinâmicos
            $table->timestamp('data_last_synced_at')->nullable();

            // JSON flexível
            $table->json('settings')->nullable()->comment('Configurações gerais do cliente');
            $table->json('voice_profile')->nullable()->comment('Perfil de voz/comunicação do prefeito');
            $table->json('political_map')->nullable()->comment('Mapa de forças: vereadores, secretários, oposição');

            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index('state_code');
            $table->index('subscription_tier');
            $table->index('subscription_active');
            $table->index('onboarding_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
