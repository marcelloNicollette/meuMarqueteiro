<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandate_promises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mandate_axis_id')->constrained()->cascadeOnDelete();
            $table->text('text');                            // enunciado do compromisso
            $table->unsignedSmallInteger('order')->default(0);
            // Score calculado automaticamente (0, 25, 50, 75, 100)
            $table->unsignedSmallInteger('score')->default(0);
            // Status derivado do score
            $table->string('status', 20)->default('pending');
            // comment: pending | partial_25 | partial_50 | partial_75 | fulfilled
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['municipality_id', 'mandate_axis_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandate_promises');
    }
};
