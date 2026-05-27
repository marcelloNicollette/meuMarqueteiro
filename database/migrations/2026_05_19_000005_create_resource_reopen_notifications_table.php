<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_reopen_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('resource_opportunity_id')
                ->constrained('resource_opportunities')
                ->cascadeOnDelete();

            $table->foreignId('last_cycle_id')
                ->nullable()
                ->constrained('resource_opportunity_cycles')
                ->nullOnDelete();

            $table->string('channel', 20)->default('push')
                ->comment('push | email | whatsapp | in_app');
            $table->string('status', 20)->default('active')
                ->comment('active | notified | paused | cancelled');
            $table->json('criteria')->nullable()
                ->comment('Filtros opcionais para reabertura por valor, tema ou prazo');

            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'resource_opportunity_id', 'channel'],
                'resource_reopen_notifications_unique_subscription'
            );
            $table->index(['status', 'channel']);
            $table->index(['municipality_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_reopen_notifications');
    }
};
