<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_curation_queue', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_opportunity_id')
                ->constrained('resource_opportunities')
                ->cascadeOnDelete();

            $table->foreignId('resource_opportunity_cycle_id')
                ->nullable()
                ->constrained('resource_opportunity_cycles')
                ->nullOnDelete();

            $table->foreignId('resource_source_id')
                ->constrained('resource_sources')
                ->cascadeOnDelete();

            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('queue_status', 30)->default('pending')
                ->comment('pending | in_review | enriched | approved | rejected | published');
            $table->string('priority', 20)->default('normal')
                ->comment('low | normal | high | urgent');

            $table->json('source_payload_snapshot')->nullable();
            $table->json('enrichment_payload')->nullable();
            $table->text('decision_notes')->nullable();

            $table->timestamp('entered_queue_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['queue_status', 'priority']);
            $table->index(['assigned_to_user_id', 'queue_status']);
            $table->index(['resource_source_id', 'queue_status']);
            $table->index('sla_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_curation_queue');
    }
};
