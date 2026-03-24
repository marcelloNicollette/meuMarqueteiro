<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandate_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // ex: Saúde
            $table->string('icon', 10)->nullable();          // emoji ou código
            $table->string('color', 20)->default('#1e3a5f'); // cor do card
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['municipality_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandate_axes');
    }
};
