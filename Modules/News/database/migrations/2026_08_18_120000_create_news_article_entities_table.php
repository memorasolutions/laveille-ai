<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: index d'entités nommées par fiche d'actualité (entreprises, modèles, personnes,
 *         lois) - support des articles connexes par entités partagées.
 * MCP: hermes→deepseek-v4-flash (validé par le superviseur)
 * RAISON: arbitrage panel 2026-08-17 (idée neuve claude.ai retenue) - connexes réellement
 *         pertinents sans modération, curation par le cycle /actu2 via la porte bornée.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_article_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_article_id')
                ->constrained('news_articles')
                ->cascadeOnDelete();
            $table->string('entity_slug', 80);
            $table->string('entity_label', 120);
            $table->timestamps();

            $table->unique(['news_article_id', 'entity_slug']);
            $table->index('entity_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_entities');
    }
};
