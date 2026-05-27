<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_sources', function (Blueprint $table) {
            $table->string('pipeline_group', 40)->nullable()->after('capture_method')
                ->comment('group_a_api | group_b_scraping | group_c_diary_monitor | group_d_human_curation');
            $table->string('operational_status', 30)->nullable()->after('refresh_frequency')
                ->comment('live | mapped | pipeline_next | curation_only');
            $table->boolean('requires_human_curation')->default(false)->after('is_active');
            $table->boolean('supports_municipality_sync')->default(false)->after('requires_human_curation');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('supports_municipality_sync');
            $table->json('operational_tags')->nullable()->after('index_fields');
            $table->json('source_metadata')->nullable()->after('operational_tags');

            $table->index(['pipeline_group', 'is_active'], 'resource_sources_pipeline_active_idx');
            $table->index(['operational_status', 'is_active'], 'resource_sources_status_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('resource_sources', function (Blueprint $table) {
            $table->dropIndex('resource_sources_pipeline_active_idx');
            $table->dropIndex('resource_sources_status_active_idx');
            $table->dropColumn([
                'pipeline_group',
                'operational_status',
                'requires_human_curation',
                'supports_municipality_sync',
                'sort_order',
                'operational_tags',
                'source_metadata',
            ]);
        });
    }
};
