<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pivot liant les actualités (news_articles) aux fiches de glossaire (dictionary_terms).
 * Jumelle exacte de news_article_tool (2026_06_29_000000_create_news_article_tool_table.php) :
 * source 'manual' = curation admin (réservée, non utilisée pour l'instant) ; 'auto' = détection
 * automatique par GlossaryLinkifier, déjà capturée par NewsToolSyncAction (ticket #2524).
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news_article_term')) {
            return;
        }

        Schema::create('news_article_term', function (Blueprint $table) {
            $table->unsignedBigInteger('news_article_id');
            $table->unsignedBigInteger('term_id');
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->unique(['news_article_id', 'term_id']);

            $table->index('news_article_id');
            $table->index('term_id');

            $table->foreign('news_article_id')
                ->references('id')->on('news_articles')
                ->cascadeOnDelete();

            $table->foreign('term_id')
                ->references('id')->on('dictionary_terms')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_term');
    }
};
