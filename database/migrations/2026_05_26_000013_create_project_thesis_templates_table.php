<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_thesis_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category', 80);
            $table->string('estimated_size', 20);
            $table->string('default_urgency', 20);
            $table->string('execution_complexity', 20);
            $table->text('base_justification_template');
            $table->text('base_impact_template');
            $table->text('base_funding_template');
            $table->text('reference_municipalities_template');
            $table->text('government_alignment_template')->nullable();
            $table->json('keywords')->nullable();
            $table->json('profile_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['category', 'is_active']);
        });

        Schema::table('project_theses', function (Blueprint $table) {
            $table->foreignId('project_thesis_template_id')
                ->nullable()
                ->after('municipality_id')
                ->constrained('project_thesis_templates')
                ->nullOnDelete();

            $table->unique(
                ['municipality_id', 'project_thesis_template_id'],
                'project_theses_municipality_template_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('project_theses', function (Blueprint $table) {
            $table->dropUnique('project_theses_municipality_template_unique');
            $table->dropConstrainedForeignId('project_thesis_template_id');
        });

        Schema::dropIfExists('project_thesis_templates');
    }
};
