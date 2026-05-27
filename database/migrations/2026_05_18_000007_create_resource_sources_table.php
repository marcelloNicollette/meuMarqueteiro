<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('resource_scope', 50)->nullable()
                ->comment('federal | estadual | multilateral | financiamento | transversal');
            $table->string('capture_method', 50)->nullable()
                ->comment('api_official | scraping | diary_monitor | human_curation');
            $table->string('refresh_frequency', 30)->nullable()
                ->comment('daily | weekly | monthly | quarterly | cycle_based');
            $table->string('source_url')->nullable();
            $table->text('access_guide')->nullable();
            $table->json('index_fields')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_sources');
    }
};
