<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create('conversation_memories', function (Blueprint $table) use ($driver) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_message_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            $table->foreignId('assistant_message_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            $table->string('memory_type', 40)->default('turn')
                ->comment('turn | summary | preference | risk | commitment | opportunity');

            $table->string('source', 100)->default('chat')
                ->comment('chat | briefing | import | manual');

            $table->text('content');
            $table->json('metadata')->nullable();
            $table->unsignedInteger('token_count')->nullable();
            $table->decimal('importance_score', 5, 2)->default(0.50);
            $table->timestamp('last_used_at')->nullable();

            if ($driver !== 'pgsql') {
                $table->longText('embedding')->nullable();
            }

            $table->timestamps();

            $table->index(['user_id', 'conversation_id']);
            $table->index(['municipality_id', 'memory_type']);
            $table->index(['user_id', 'last_used_at']);
            $table->unique('assistant_message_id');
        });

        if ($driver !== 'pgsql') {
            return;
        }

        $dimensions = (int) config('ai.rag.dimensions', 1024);

        DB::statement("ALTER TABLE conversation_memories ADD COLUMN embedding vector({$dimensions})");

        DB::statement("
            CREATE INDEX conversation_memories_embedding_hnsw_idx
            ON conversation_memories
            USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_memories');
    }
};
