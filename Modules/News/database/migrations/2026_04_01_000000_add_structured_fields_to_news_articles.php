<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->tinyInteger('relevance_score')->nullable()->after('summary');
            $table->string('score_justification', 255)->nullable()->after('relevance_score');
            $table->json('structured_summary')->nullable()->after('score_justification');
            $table->string('category_tag', 50)->nullable()->after('structured_summary');
            $table->string('impact_level', 10)->nullable()->after('category_tag');
            $table->string('feed_type', 20)->default('ia')->after('impact_level');
            $table->string('seo_title', 70)->nullable()->after('feed_type');
            $table->string('meta_description', 160)->nullable()->after('seo_title');
            $table->index('relevance_score');
            $table->index('category_tag');
            $table->index('feed_type');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn(['relevance_score', 'score_justification', 'structured_summary', 'category_tag', 'impact_level', 'feed_type', 'seo_title', 'meta_description']);
        });
    }
};
