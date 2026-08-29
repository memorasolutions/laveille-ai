<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pivot liant les actualités (news_articles) à UN article de blogue (articles). Jumeau
 * exact de 2026_06_29_000000_create_news_article_tool_table.php (même forme : owner_id +
 * cible_id + source + timestamps + unique) - seul le plafond de 1 diffère, et il n'est PAS
 * imposé ici en SQL (aucune contrainte de comptage) mais applicativement par
 * Modules\News\Console\NewsApplyCommand (même doctrine que le plafond de 10 de
 * related_tool_slugs, lui non plus jamais en base).
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news_article_article')) {
            return;
        }

        Schema::create('news_article_article', function (Blueprint $table) {
            $table->unsignedBigInteger('news_article_id');
            $table->unsignedBigInteger('article_id');
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->unique(['news_article_id', 'article_id']);

            $table->index('news_article_id');
            $table->index('article_id');

            $table->foreign('news_article_id')
                ->references('id')->on('news_articles')
                ->cascadeOnDelete();

            $table->foreign('article_id')
                ->references('id')->on('articles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_article');
    }
};
