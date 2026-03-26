<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('priority');
            $table->index(['municipality_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->dropIndex(['municipality_id', 'due_date']);
            $table->dropColumn('due_date');
        });
    }
};
