<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->timestamp('due_at')->nullable()->after('due_date');
            $table->timestamp('acknowledged_at')->nullable()->after('resolved_at');
            $table->timestamp('last_progress_at')->nullable()->after('acknowledged_at');
            $table->timestamp('completion_requested_at')->nullable()->after('last_progress_at');
            $table->timestamp('confirmed_at')->nullable()->after('completion_requested_at');
            $table->timestamp('reopened_at')->nullable()->after('confirmed_at');
            $table->string('address')->nullable()->after('locality');
            $table->text('completion_note')->nullable()->after('resolution_note');
            $table->text('reopened_reason')->nullable()->after('completion_note');
            $table->string('completion_attachment_path')->nullable()->after('reopened_reason');
            $table->string('completion_attachment_name')->nullable()->after('completion_attachment_path');
            $table->string('completion_attachment_mime', 120)->nullable()->after('completion_attachment_name');
            $table->unsignedBigInteger('completion_attachment_size')->nullable()->after('completion_attachment_mime');

            $table->index(['municipality_id', 'due_at']);
            $table->index(['municipality_id', 'contact_area_id', 'status'], 'demands_muni_area_status_idx');
        });

        Schema::create('demand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['demand_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_events');

        Schema::table('demands', function (Blueprint $table) {
            $table->dropIndex(['municipality_id', 'due_at']);
            $table->dropIndex('demands_muni_area_status_idx');
            $table->dropColumn([
                'due_at',
                'acknowledged_at',
                'last_progress_at',
                'completion_requested_at',
                'confirmed_at',
                'reopened_at',
                'address',
                'completion_note',
                'reopened_reason',
                'completion_attachment_path',
                'completion_attachment_name',
                'completion_attachment_mime',
                'completion_attachment_size',
            ]);
        });
    }
};
