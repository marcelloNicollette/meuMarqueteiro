<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->text('approval_reason')->nullable()->after('approval_steps');
            $table->text('publication_reason')->nullable()->after('approval_reason');
            $table->string('publication_signature_name')->nullable()->after('publication_reason');
            $table->string('publication_signature_role', 120)->nullable()->after('publication_signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_document_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'approval_reason',
                'publication_reason',
                'publication_signature_name',
                'publication_signature_role',
            ]);
        });
    }
};
