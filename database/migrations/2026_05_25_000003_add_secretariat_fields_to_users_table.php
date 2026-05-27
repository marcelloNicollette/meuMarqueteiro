<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('contact_area_id')
                ->nullable()
                ->after('municipality_id')
                ->constrained('contact_areas')
                ->nullOnDelete();
            $table->boolean('can_register_demands')
                ->default(true)
                ->after('contact_area_id');

            $table->index(['municipality_id', 'contact_area_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['municipality_id', 'contact_area_id']);
            $table->dropConstrainedForeignId('contact_area_id');
            $table->dropColumn('can_register_demands');
        });
    }
};
