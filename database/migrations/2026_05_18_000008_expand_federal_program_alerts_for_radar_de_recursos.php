<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('federal_program_alerts', function (Blueprint $table) {
            $table->foreignId('resource_source_id')
                ->nullable()
                ->after('municipality_id')
                ->constrained('resource_sources')
                ->nullOnDelete();

            $table->string('short_title')->nullable()->after('program_name');
            $table->string('source_key')->nullable()->after('source_platform');
            $table->string('capture_method', 50)->nullable()->after('source_key');
            $table->string('resource_scope', 50)->nullable()->after('capture_method');
            $table->string('curation_status', 30)->nullable()->after('resource_scope')
                ->comment('pending_review | auto_published | curated | rejected');
            $table->timestamp('published_at')->nullable()->after('curation_status');
            $table->timestamp('closed_at')->nullable()->after('published_at');
            $table->timestamp('archived_at')->nullable()->after('closed_at');
            $table->timestamp('closed_visibility_until')->nullable()->after('archived_at');
            $table->string('estimated_size', 20)->nullable()->after('funding_type')
                ->comment('small | medium | large');
            $table->decimal('counterpart_percentage', 5, 2)->nullable()->after('estimated_size');
            $table->json('documentation_requirements')->nullable()->after('eligibility_criteria');
            $table->json('compatibility_factors')->nullable()->after('match_reason');
            $table->string('viability_level', 20)->nullable()->after('compatibility_factors')
                ->comment('high | medium | low');
            $table->text('viability_reason')->nullable()->after('viability_level');
            $table->json('viability_factors')->nullable()->after('viability_reason');
            $table->json('source_metadata')->nullable()->after('viability_factors');

            $table->index(['status', 'deadline'], 'federal_program_alerts_status_deadline_idx');
            $table->index(['curation_status', 'status'], 'federal_program_alerts_curation_status_idx');
            $table->index(['municipality_id', 'status', 'match_score'], 'federal_program_alerts_municipality_status_match_idx');
        });
    }

    public function down(): void
    {
        Schema::table('federal_program_alerts', function (Blueprint $table) {
            $table->dropIndex('federal_program_alerts_status_deadline_idx');
            $table->dropIndex('federal_program_alerts_curation_status_idx');
            $table->dropIndex('federal_program_alerts_municipality_status_match_idx');

            $table->dropConstrainedForeignId('resource_source_id');

            $table->dropColumn([
                'short_title',
                'source_key',
                'capture_method',
                'resource_scope',
                'curation_status',
                'published_at',
                'closed_at',
                'archived_at',
                'closed_visibility_until',
                'estimated_size',
                'counterpart_percentage',
                'documentation_requirements',
                'compatibility_factors',
                'viability_level',
                'viability_reason',
                'viability_factors',
                'source_metadata',
            ]);
        });
    }
};
