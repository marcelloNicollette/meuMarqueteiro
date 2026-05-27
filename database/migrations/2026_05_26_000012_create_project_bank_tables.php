<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_theses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 80);
            $table->text('justification');
            $table->text('potential_impact');
            $table->text('funding_source');
            $table->string('estimated_size', 20);
            $table->string('urgency', 20);
            $table->string('execution_complexity', 20);
            $table->text('reference_municipalities')->nullable();
            $table->text('government_alignment')->nullable();
            $table->date('resource_deadline')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['municipality_id', 'urgency']);
            $table->index(['municipality_id', 'category']);
        });

        Schema::create('project_thesis_user_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_thesis_id')->constrained('project_theses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_saved')->default(false);
            $table->timestamp('last_action_at')->nullable();
            $table->timestamps();

            $table->unique(['project_thesis_id', 'user_id'], 'project_thesis_user_unique');
        });

        Schema::create('project_thesis_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_thesis_id')->constrained('project_theses')->cascadeOnDelete();
            $table->foreignId('shared_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->index(['shared_with_user_id', 'created_at']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('source_thesis_id')
                ->nullable()
                ->after('last_edited_by_user_id')
                ->constrained('project_theses')
                ->nullOnDelete();

            $table->index(['municipality_id', 'source_thesis_id']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['municipality_id', 'source_thesis_id']);
            $table->dropConstrainedForeignId('source_thesis_id');
        });

        Schema::dropIfExists('project_thesis_shares');
        Schema::dropIfExists('project_thesis_user_states');
        Schema::dropIfExists('project_theses');
    }
};
