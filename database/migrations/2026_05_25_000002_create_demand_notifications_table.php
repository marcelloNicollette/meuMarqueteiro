<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->string('event_type', 60);
            $table->string('channel', 30);
            $table->string('recipient_type', 30)->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('destination')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['demand_id', 'created_at']);
            $table->index(['event_type', 'channel']);
            $table->index(['recipient_type', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_notifications');
    }
};
