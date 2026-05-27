<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mandate_action_progress_logs', function (Blueprint $table) {
            $table->unsignedSmallInteger('from_progress')->nullable()->after('description');
            $table->unsignedSmallInteger('to_progress')->nullable()->after('from_progress');
            $table->string('from_status', 20)->nullable()->after('to_progress');
            $table->string('to_status', 20)->nullable()->after('from_status');
        });
    }

    public function down(): void
    {
        Schema::table('mandate_action_progress_logs', function (Blueprint $table) {
            $table->dropColumn([
                'from_progress',
                'to_progress',
                'from_status',
                'to_status',
            ]);
        });
    }
};
