<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('kind', 30)->default('post');
            $table->string('channel', 40)->nullable();
            $table->string('format', 40)->nullable();
            $table->string('tone', 40)->nullable();
            $table->text('description')->nullable();
            $table->text('instruction')->nullable();
            $table->json('default_tones')->nullable();
            $table->json('default_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['municipality_id', 'kind', 'is_active']);
            $table->index(['municipality_id', 'channel', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_templates');
    }
};
