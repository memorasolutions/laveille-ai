<?php

declare(strict_types=1);

namespace Modules\News\Observers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;

class NewsArticleObserver
{
    public function updated(NewsArticle $article): void
    {
        if (! class_exists(\Modules\ShortUrl\Services\ShortUrlService::class)) {
            return;
        }

        // Uniquement quand is_published passe à true
        if (! $article->is_published || ! $article->isDirty('is_published')) {
            return;
        }

        if ($article->short_url_id) {
            return;
        }

        $domain = \Modules\ShortUrl\Models\ShortUrlDomain::where('is_default', true)->first();
        if (! $domain) {
            return;
        }

        $baseSlug = 'actu-'.mb_substr($article->slug, 0, 20);
        $slug = $baseSlug;
        $counter = 2;

        while (\Modules\ShortUrl\Models\ShortUrl::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        try {
            $service = app(\Modules\ShortUrl\Services\ShortUrlService::class);
            $shortUrl = $service->createShortUrl([
                'original_url' => config('app.url').'/actualites/'.$article->slug,
                'slug' => $slug,
                'title' => $article->seo_title ?? $article->title,
                'og_title' => $article->seo_title ?? $article->title,
                'og_description' => $article->meta_description,
                'og_image' => $article->image_url,
                'redirect_type' => 301,
                'is_active' => true,
                'domain_id' => $domain->id,
            ], null);

            $article->updateQuietly(['short_url_id' => $shortUrl->id]);

            Log::info("Short URL created: {$slug} → article {$article->id}");
        } catch (\Throwable $e) {
            Log::warning("Short URL creation failed for article {$article->id}: ".$e->getMessage());
        }
    }
}
