<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table pivot liant les actualités (news_articles) aux outils (directory_tools).
 * Source 'manual' = curation admin ; 'auto' réservé pour une future détection.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news_article_tool')) {
            return;
        }

        Schema::create('news_article_tool', function (Blueprint $table) {
            $table->unsignedBigInteger('news_article_id');
            $table->unsignedBigInteger('tool_id');
            $table->string('source', 20)->default('manual');
            $table->timestamps();

            $table->unique(['news_article_id', 'tool_id']);

            $table->index('news_article_id');
            $table->index('tool_id');

            $table->foreign('news_article_id')
                ->references('id')->on('news_articles')
                ->cascadeOnDelete();

            $table->foreign('tool_id')
                ->references('id')->on('directory_tools')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_tool');
    }
};
