<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mandate_promises', function (Blueprint $table) {
            $table->json('keywords')->nullable()->after('text');
            $table->string('specificity', 30)->nullable()->after('keywords');
            $table->foreignId('source_document_id')
                ->nullable()
                ->after('mandate_axis_id')
                ->constrained('municipality_documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mandate_promises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_document_id');
            $table->dropColumn(['keywords', 'specificity']);
        });
    }
};
