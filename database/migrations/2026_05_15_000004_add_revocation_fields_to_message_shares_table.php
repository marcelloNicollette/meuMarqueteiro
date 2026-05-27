<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_shares', function (Blueprint $table) {
            $table->foreignId('revoked_by_user_id')
                ->nullable()
                ->after('recipient_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('revoked_at')
                ->nullable()
                ->after('viewed_at');

            $table->index(['message_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('message_shares', function (Blueprint $table) {
            $table->dropIndex(['message_id', 'revoked_at']);
            $table->dropConstrainedForeignId('revoked_by_user_id');
            $table->dropColumn('revoked_at');
        });
    }
};
