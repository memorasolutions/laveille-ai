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
        // Capturé UNE SEULE FOIS avant tout traitement : createShortUrlIfNeeded() effectue
        // un updateQuietly() imbriqué qui resynchronise l'état "original" du modèle (syncOriginal),
        // ce qui ferait retomber isDirty('is_published') à false si on le recalculait après coup -
        // dispatchAutoToolDetection() manquerait alors systématiquement la toute première publication.
        $justPublished = (bool) $article->is_published && $article->isDirty('is_published');

        $this->createShortUrlIfNeeded($article, $justPublished);
        $this->dispatchAutoToolDetection($article, $justPublished);
    }

    private function createShortUrlIfNeeded(NewsArticle $article, bool $justPublished): void
    {
        if (! class_exists(\Modules\ShortUrl\Services\ShortUrlService::class)) {
            return;
        }

        // Uniquement quand is_published passe à true
        if (! $justPublished) {
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

    /**
     * Dispatch la détection automatique d'outils annuaire à la publication (source=auto).
     * Couvre TOUS les articles peu importe category_tag (contrairement à ContentPublished
     * dans NewsArticle::booted(), qui exige un category_tag) pour maximiser la détection.
     * Le bouton manuel "Suggérer les outils détectés" reste disponible en parallèle
     * (source=manual via sync(), jamais affecté par ce chantier).
     */
    private function dispatchAutoToolDetection(NewsArticle $article, bool $justPublished): void
    {
        if (! class_exists(\Modules\News\Jobs\AutoDetectNewsToolsJob::class)) {
            return;
        }

        if (! $justPublished) {
            return;
        }

        \Modules\News\Jobs\AutoDetectNewsToolsJob::dispatch($article);
    }
}
