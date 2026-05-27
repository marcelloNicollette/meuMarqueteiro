<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_shares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('conversation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('message_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('recipient_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->uuid('share_token')->unique();
            $table->text('excerpt');
            $table->text('context_excerpt')->nullable();
            $table->string('message_role', 20);
            $table->text('note')->nullable();
            $table->timestamp('viewed_at')->nullable();

            $table->timestamps();

            $table->index(['owner_user_id', 'recipient_user_id']);
            $table->index(['conversation_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_shares');
    }
};
