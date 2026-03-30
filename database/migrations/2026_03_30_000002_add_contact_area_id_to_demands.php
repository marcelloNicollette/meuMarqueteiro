<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->foreignId('contact_area_id')->nullable()->after('responsible_secretary')->constrained('contact_areas')->nullOnDelete();
            $table->index(['municipality_id', 'contact_area_id']);
        });
    }

    public function down(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_area_id');
        });
    }
};

