<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('articles', 'tags')) {
            return;
        }

        $articles = DB::table('articles')->whereNotNull('tags')->get();

        foreach ($articles as $article) {
            $tags = json_decode($article->tags, true);

            if (! is_array($tags)) {
                continue;
            }

            foreach ($tags as $tagName) {
                $tagName = trim($tagName);
                if ($tagName === '') {
                    continue;
                }

                $slug = Str::slug($tagName);

                $tag = DB::table('tags')->where('slug', $slug)->first();

                if (! $tag) {
                    $tagId = DB::table('tags')->insertGetId([
                        'name' => $tagName,
                        'slug' => $slug,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $tagId = $tag->id;
                }

                DB::table('article_tag')->insertOrIgnore([
                    'article_id' => $article->id,
                    'tag_id' => $tagId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('article_tag')->truncate();
        DB::table('tags')->truncate();
    }
};
