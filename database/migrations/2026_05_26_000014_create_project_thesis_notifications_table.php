<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_thesis_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_thesis_id')->constrained('project_theses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_thesis_share_id')->nullable()->constrained('project_thesis_shares')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->string('fingerprint', 160);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['project_thesis_id', 'event_type']);
            $table->unique(
                ['user_id', 'event_type', 'fingerprint'],
                'project_thesis_notifications_unique_event'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_thesis_notifications');
    }
};
