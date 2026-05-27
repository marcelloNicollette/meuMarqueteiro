<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('morning_briefings', function (Blueprint $table) {
            $table->dropUnique('morning_briefings_municipality_id_date_unique');
            $table->unique(['user_id', 'date'], 'morning_briefings_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('morning_briefings', function (Blueprint $table) {
            $table->dropUnique('morning_briefings_user_date_unique');
            $table->unique(['municipality_id', 'date'], 'morning_briefings_municipality_id_date_unique');
        });
    }
};
