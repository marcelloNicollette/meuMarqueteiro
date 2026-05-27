<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mandate_actions', function (Blueprint $table) {
            $table->boolean('uses_milestones_progress')
                ->default(false)
                ->after('physical_progress');
        });

        Schema::create('mandate_action_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandate_action_id')
                ->constrained('mandate_actions')
                ->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['mandate_action_id', 'order']);
        });

        Schema::create('mandate_action_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandate_action_id')
                ->constrained('mandate_actions')
                ->cascadeOnDelete();
            $table->foreignId('mandate_action_milestone_id')
                ->nullable()
                ->constrained('mandate_action_milestones')
                ->nullOnDelete();
            $table->string('event_type', 40)->default('milestone_completed');
            $table->text('description')->nullable();
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['mandate_action_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandate_action_progress_logs');
        Schema::dropIfExists('mandate_action_milestones');

        Schema::table('mandate_actions', function (Blueprint $table) {
            $table->dropColumn('uses_milestones_progress');
        });
    }
};
