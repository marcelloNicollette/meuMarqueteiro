<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Palavras-chave configuradas por município
        Schema::create('mention_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();

            $table->string('keyword');                    // ex: "Serrinha", "Prefeito João"
            $table->string('type', 30)->default('city');  // city | mayor | secretary | topic | hashtag
            $table->boolean('is_active')->default(true);
            $table->boolean('alert_negative')->default(true); // push em menção negativa
            $table->timestamps();

            $table->index(['municipality_id', 'is_active']);
        });

        // Menções encontradas
        Schema::create('social_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();

            // Origem
            $table->string('source', 30);  // google_news | nitter | rss
            $table->string('platform', 30)->nullable(); // twitter | news | blog | youtube
            $table->string('keyword')->nullable();       // palavra-chave que gerou a menção

            // Conteúdo
            $table->string('title')->nullable();
            $table->text('content')->nullable();         // snippet/texto da menção
            $table->string('url')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('published_at')->nullable();

            // Análise de sentimento (Claude)
            $table->string('sentiment', 20)->default('pending');
            // pending | positive | negative | neutral | analyzing
            $table->tinyInteger('sentiment_score')->nullable(); // -100 a +100
            $table->text('sentiment_reason')->nullable();

            // Controle
            $table->boolean('is_read')->default(false);
            $table->boolean('alert_sent')->default(false);
            $table->string('external_id')->nullable();   // ID externo para evitar duplicatas

            $table->timestamps();

            $table->index(['municipality_id', 'sentiment']);
            $table->index(['municipality_id', 'published_at']);
            $table->index(['municipality_id', 'is_read']);
            $table->unique(['municipality_id', 'external_id'], 'unique_mention');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_mentions');
        Schema::dropIfExists('mention_keywords');
    }
};
