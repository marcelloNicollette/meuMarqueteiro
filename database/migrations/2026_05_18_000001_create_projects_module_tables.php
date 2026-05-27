<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('last_edited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('initial_idea');
            $table->string('project_type')->nullable();
            $table->string('status')->default('em_elaboração');
            $table->string('responsible_secretariat')->nullable();
            $table->string('current_phase')->default('estrutura_inicial');
            $table->unsignedInteger('generated_document_version')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();

            $table->index(['municipality_id', 'status']);
            $table->index(['municipality_id', 'project_type']);
        });

        Schema::create('project_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('section_key');
            $table->unsignedSmallInteger('section_order');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('needs_review')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'section_key']);
            $table->index(['project_id', 'section_order']);
        });

        Schema::create('project_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('permission')->default('editor');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_edit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_section_id')->nullable()->constrained('project_sections')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('field_name')->nullable();
            $table->longText('previous_content')->nullable();
            $table->longText('new_content')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_edit_histories');
        Schema::dropIfExists('project_collaborators');
        Schema::dropIfExists('project_sections');
        Schema::dropIfExists('projects');
    }
};
