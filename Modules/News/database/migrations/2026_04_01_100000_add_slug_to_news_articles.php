<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->unique()->after('title');
        });

        // Générer les slugs pour les articles existants
        $articles = DB::table('news_articles')->get(['id', 'title', 'seo_title']);
        foreach ($articles as $article) {
            $baseSlug = Str::slug($article->seo_title ?: $article->title);
            if (empty($baseSlug)) {
                $baseSlug = 'article-'.$article->id;
            }
            $slug = $baseSlug;
            $counter = 2;
            while (DB::table('news_articles')->where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }
            DB::table('news_articles')->where('id', $article->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
