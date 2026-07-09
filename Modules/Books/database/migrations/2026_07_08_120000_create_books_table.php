<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('slug')->unique();
            $table->string('series_slug')->nullable();
            $table->unsignedInteger('series_position')->nullable();
            $table->string('genre')->nullable();
            $table->string('target_audience')->nullable();
            $table->text('author_bio_short')->nullable();
            $table->text('one_sentence_answer')->nullable(); // AEO : phrase-réponse ≤ 40 mots pour AI Overviews + LLM citation
            $table->json('benefits')->nullable(); // array de bénéfices/points forts
            $table->text('excerpt')->nullable();
            $table->json('toc_summary')->nullable(); // sommaire/table des matières résumée
            $table->json('faq')->nullable(); // array of {question, answer} pour FAQPage Schema.org
            $table->string('isbn_paperback')->nullable();
            $table->string('asin_kindle')->nullable();
            $table->decimal('price_paperback', 8, 2)->nullable();
            $table->decimal('price_kindle', 8, 2)->nullable();
            $table->string('amazon_url_paperback')->nullable();
            $table->string('amazon_url_kindle')->nullable();
            $table->string('cover_image')->nullable();
            $table->date('date_published')->nullable();
            $table->boolean('is_under_construction')->default(true);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
