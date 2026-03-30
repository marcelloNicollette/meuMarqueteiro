<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('url');
            $table->string('title')->nullable();           // título definido pelo usuário
            $table->string('page_title')->nullable();      // título extraído da página
            $table->string('category', 60)->default('geral');
            // geral | noticias | transparencia | legislacao | governo | outros

            $table->text('description')->nullable();       // para que serve essa URL

            // Controle de indexação
            $table->string('fetch_status', 20)->default('pending');
            // pending | fetching | indexed | failed
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamp('last_indexed_at')->nullable();
            $table->unsignedInteger('chunks_count')->nullable();
            $table->text('fetch_error')->nullable();

            // Frequência de re-indexação
            $table->string('refresh_frequency', 20)->default('daily');
            // manual | daily | weekly | monthly

            $table->boolean('is_active')->default(true);
            $table->boolean('index_subpages')->default(false); // varrer links internos

            $table->timestamps();

            $table->index('municipality_id');
            $table->index('fetch_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_urls');
    }
};
