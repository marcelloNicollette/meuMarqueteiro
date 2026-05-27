<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('federal_program_alerts') && Schema::hasColumn('federal_program_alerts', 'source_url')) {
            DB::statement('ALTER TABLE federal_program_alerts ALTER COLUMN source_url TYPE TEXT');
        }

        if (Schema::hasTable('resource_opportunities') && Schema::hasColumn('resource_opportunities', 'source_url')) {
            DB::statement('ALTER TABLE resource_opportunities ALTER COLUMN source_url TYPE TEXT');
        }

        if (Schema::hasTable('resource_opportunity_cycles')) {
            if (Schema::hasColumn('resource_opportunity_cycles', 'notice_url')) {
                DB::statement('ALTER TABLE resource_opportunity_cycles ALTER COLUMN notice_url TYPE TEXT');
            }

            if (Schema::hasColumn('resource_opportunity_cycles', 'application_url')) {
                DB::statement('ALTER TABLE resource_opportunity_cycles ALTER COLUMN application_url TYPE TEXT');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('resource_opportunity_cycles')) {
            if (Schema::hasColumn('resource_opportunity_cycles', 'application_url')) {
                DB::statement('ALTER TABLE resource_opportunity_cycles ALTER COLUMN application_url TYPE VARCHAR(255)');
            }

            if (Schema::hasColumn('resource_opportunity_cycles', 'notice_url')) {
                DB::statement('ALTER TABLE resource_opportunity_cycles ALTER COLUMN notice_url TYPE VARCHAR(255)');
            }
        }

        if (Schema::hasTable('resource_opportunities') && Schema::hasColumn('resource_opportunities', 'source_url')) {
            DB::statement('ALTER TABLE resource_opportunities ALTER COLUMN source_url TYPE VARCHAR(255)');
        }

        if (Schema::hasTable('federal_program_alerts') && Schema::hasColumn('federal_program_alerts', 'source_url')) {
            DB::statement('ALTER TABLE federal_program_alerts ALTER COLUMN source_url TYPE VARCHAR(255)');
        }
    }
};
