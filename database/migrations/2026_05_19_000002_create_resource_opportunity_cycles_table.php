<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_opportunity_cycles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_opportunity_id')
                ->constrained('resource_opportunities')
                ->cascadeOnDelete();

            $table->foreignId('reopened_from_cycle_id')
                ->nullable()
                ->constrained('resource_opportunity_cycles')
                ->nullOnDelete();

            $table->string('external_cycle_key')->nullable()
                ->comment('Chave externa do edital/chamada/ciclo na fonte');
            $table->string('publication_reference')->nullable()
                ->comment('Numero do edital, portaria, chamada ou referencia publicada');
            $table->string('status', 30)->default('pending_review')
                ->comment('pending_review | published | closing_soon | monitoring | closed_recently | archived | reopened | rejected');
            $table->boolean('is_current')->default(true);

            $table->string('notice_url')->nullable();
            $table->string('application_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('closed_visibility_until')->nullable();

            $table->decimal('total_value', 15, 2)->nullable();
            $table->decimal('min_value', 15, 2)->nullable();
            $table->decimal('counterpart_percentage', 5, 2)->nullable();
            $table->string('estimated_size', 20)->nullable()
                ->comment('small | medium | large');

            $table->json('cycle_metadata')->nullable();
            $table->timestamps();

            $table->index(['resource_opportunity_id', 'is_current']);
            $table->index(['status', 'deadline_at']);
            $table->index('closed_visibility_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_opportunity_cycles');
    }
};
