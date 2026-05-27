<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('origin_module', 50)
                ->nullable()
                ->after('municipality_id')
                ->comment('chat | federal_programs | content | briefings');

            $table->json('auto_tags')
                ->nullable()
                ->after('title')
                ->comment('Tags tematicas aplicadas automaticamente');

            $table->index('origin_module');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['origin_module']);
            $table->dropColumn(['origin_module', 'auto_tags']);
        });
    }
};
