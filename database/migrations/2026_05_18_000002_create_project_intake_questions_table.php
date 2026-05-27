<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_intake_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('question_key');
            $table->unsignedSmallInteger('question_order');
            $table->text('question_text');
            $table->text('help_text')->nullable();
            $table->string('input_type')->default('textarea');
            $table->string('placeholder')->nullable();
            $table->boolean('is_required')->default(true);
            $table->longText('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'question_key']);
            $table->index(['project_id', 'question_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_intake_questions');
    }
};
