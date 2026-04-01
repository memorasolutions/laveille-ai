<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;

class RegenerateSlugsCommand extends Command
{
    protected $signature = 'news:regenerate-slugs {--dry-run : Show changes without applying}';

    protected $description = 'Regenerate slugs for news articles from seo_title and create 301 redirects';

    public function handle(): int
    {
        $articles = NewsArticle::whereNotNull('seo_title')
            ->where('seo_title', '!=', '')
            ->get();

        $changed = 0;

        foreach ($articles as $article) {
            $expectedSlug = Str::slug($article->seo_title);
            if ($article->slug === $expectedSlug) {
                continue;
            }

            $oldSlug = $article->slug;
            $generatedSlug = NewsArticle::generateUniqueSlug($article->seo_title, $article->id);

            $this->line("[{$article->id}] {$oldSlug} → {$generatedSlug}");

            if ($this->option('dry-run')) {
                $changed++;

                continue;
            }

            $article->slug = $generatedSlug;
            $article->save();

            // Redirect 301 from old slug
            if (Schema::hasTable('url_redirects')) {
                DB::table('url_redirects')->updateOrInsert(
                    ['from_url' => '/actualites/'.$oldSlug],
                    [
                        'to_url' => '/actualites/'.$generatedSlug,
                        'status_code' => 301,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Update short URL if module exists
            if (class_exists(\Modules\ShortUrl\Models\ShortUrl::class) && $article->short_url_id) {
                \Modules\ShortUrl\Models\ShortUrl::where('id', $article->short_url_id)
                    ->update(['destination_url' => url('/actualites/'.$generatedSlug)]);
            }

            $changed++;
        }

        $mode = $this->option('dry-run') ? 'DRY RUN' : 'APPLIED';
        $this->info("{$mode}: {$changed} slug(s) to update out of {$articles->count()} articles with seo_title.");

        return self::SUCCESS;
    }
}
