<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('morning_briefings', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('municipality_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('scope_profile', 20)
                ->nullable()
                ->after('date');

            $table->text('opening_text')
                ->nullable()
                ->after('content');

            $table->json('cards')
                ->nullable()
                ->after('sections');

            $table->timestamp('superseded_at')
                ->nullable()
                ->after('read_at');

            $table->index(['user_id', 'date'], 'morning_briefings_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('morning_briefings', function (Blueprint $table) {
            $table->dropIndex('morning_briefings_user_date_idx');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'scope_profile',
                'opening_text',
                'cards',
                'superseded_at',
            ]);
        });
    }
};
