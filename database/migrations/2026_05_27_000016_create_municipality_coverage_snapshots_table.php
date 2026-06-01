<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_coverage_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('period', 20)->default('daily');
            $table->timestamp('captured_at')->nullable();
            $table->json('summary')->nullable();
            $table->json('comparison')->nullable();
            $table->json('ranking')->nullable();
            $table->timestamps();

            $table->index(['period', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_coverage_snapshots');
    }
};
