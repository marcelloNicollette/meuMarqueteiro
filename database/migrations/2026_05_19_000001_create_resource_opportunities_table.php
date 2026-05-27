<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_opportunities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_source_id')
                ->constrained('resource_sources')
                ->cascadeOnDelete();

            $table->string('canonical_key')->nullable()->unique()
                ->comment('Chave canônica estável da oportunidade');
            $table->string('source_fingerprint')->nullable()->unique()
                ->comment('Hash ou fingerprint para deduplicação entre fontes/capturas');

            $table->string('title');
            $table->string('short_title')->nullable();
            $table->string('official_title')->nullable();
            $table->string('issuing_body')->nullable()
                ->comment('Órgão, ministério, banco ou entidade emissora');

            $table->string('thematic_area', 50)->nullable()
                ->comment('saude | educacao | infraestrutura | saneamento | habitacao | social | outros');
            $table->string('resource_type', 30)->nullable()
                ->comment('grant | transfer | convenio | credit | blended | emenda | technical_support');
            $table->string('funding_type', 30)->nullable()
                ->comment('transferencia | convenio | credito | emenda | subvencao');
            $table->string('resource_scope', 50)->nullable()
                ->comment('federal | estadual | multilateral | financiamento | transversal');

            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->json('thematic_tags')->nullable();
            $table->json('eligibility_rules')->nullable();
            $table->json('documentation_requirements')->nullable();
            $table->json('counterpart_rules')->nullable();

            $table->string('estimated_size', 20)->nullable()
                ->comment('small | medium | large');
            $table->string('curation_status', 30)->default('pending_review')
                ->comment('pending_review | auto_published | curated | rejected');
            $table->string('latest_status', 30)->nullable()
                ->comment('pending_review | published | closing_soon | monitoring | closed_recently | archived | reopened | rejected');

            $table->string('source_url')->nullable();
            $table->json('compatibility_factors_template')->nullable();
            $table->json('viability_factors_template')->nullable();
            $table->json('source_metadata')->nullable();

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_published_at')->nullable();
            $table->timestamps();

            $table->index(['resource_source_id', 'curation_status']);
            $table->index(['resource_scope', 'latest_status']);
            $table->index(['thematic_area', 'funding_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_opportunities');
    }
};
