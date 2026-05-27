<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_user_saves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('resource_opportunity_id')
                ->constrained('resource_opportunities')
                ->cascadeOnDelete();

            $table->foreignId('resource_opportunity_cycle_id')
                ->nullable()
                ->constrained('resource_opportunity_cycles')
                ->nullOnDelete();

            $table->string('saved_from', 30)->nullable()
                ->comment('radar | chat | project | admin');
            $table->text('notes')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'resource_opportunity_id'], 'resource_user_saves_unique_user_opportunity');
            $table->index(['municipality_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_user_saves');
    }
};
