<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE social_mentions ALTER COLUMN url TYPE TEXT');
        DB::statement('ALTER TABLE social_mentions ALTER COLUMN title TYPE TEXT');
    }

    public function down(): void
    {
        // Reverter para VARCHAR(255) pode truncar dados; aplicar apenas se necessário.
        DB::statement('ALTER TABLE social_mentions ALTER COLUMN url TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE social_mentions ALTER COLUMN title TYPE VARCHAR(255)');
    }
};
