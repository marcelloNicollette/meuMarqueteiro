<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mandate_actions', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('mandate_axis_id')
                ->constrained('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mandate_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
