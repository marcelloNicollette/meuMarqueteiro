<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('summary');
            $table->foreignId('approved_by_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->foreignId('published_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('published_by_user_id');
            $table->foreignId('restored_from_revision_id')->nullable()->after('published_at')->constrained('project_document_revisions')->nullOnDelete();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'status']);
            $table->dropConstrainedForeignId('restored_from_revision_id');
            $table->dropConstrainedForeignId('published_by_user_id');
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn(['status', 'approved_at', 'published_at']);
        });
    }
};
