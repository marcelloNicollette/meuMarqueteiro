<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->json('approval_steps')->nullable()->after('restored_from_revision_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->dropColumn('approval_steps');
        });
    }
};
