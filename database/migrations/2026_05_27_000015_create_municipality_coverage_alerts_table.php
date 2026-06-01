<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_coverage_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained('municipalities')->cascadeOnDelete();
            $table->string('event_type', 80);
            $table->string('severity', 20)->default('medium');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->string('fingerprint', 160);
            $table->string('status', 20)->default('active');
            $table->timestamp('first_detected_at')->nullable();
            $table->timestamp('last_detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['municipality_id', 'status']);
            $table->index(['event_type', 'status']);
            $table->index(['fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_coverage_alerts');
    }
};
