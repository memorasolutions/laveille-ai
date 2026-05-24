<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CrossPromotionService
{
    public function getRecommendationsForArticle(int $articleId, int $limit = 3): array
    {
        return Cache::remember("cross_promo_article_{$articleId}_{$limit}", 3600, function () use ($articleId, $limit) {
            try {
                $current = DB::table('articles')->select('user_id', 'category_id')->where('id', $articleId)->first();
                if (! $current) {
                    return [];
                }

                return DB::table('articles')
                    ->where('id', '!=', $articleId)
                    ->where('user_id', '!=', $current->user_id)
                    ->where('status', 'published')
                    ->when($current->category_id, fn ($q) => $q->where('category_id', $current->category_id))
                    ->orderByDesc('published_at')
                    ->limit($limit)
                    ->get(['id', 'title', 'slug', 'user_id', 'published_at'])
                    ->toArray();
            } catch (\Exception $e) {
                Log::debug('CrossPromo article failed: '.$e->getMessage());
                return [];
            }
        });
    }

    public function getRecommendationsForAuthor(int $authorProfileId, int $limit = 5): array
    {
        return Cache::remember("cross_promo_author_{$authorProfileId}_{$limit}", 3600, function () use ($authorProfileId, $limit) {
            try {
                $author = DB::table('author_profiles')->where('id', $authorProfileId)->first();
                if (! $author) {
                    return [];
                }

                return DB::table('articles')
                    ->where('user_id', '!=', $author->user_id)
                    ->where('status', 'published')
                    ->orderByDesc('published_at')
                    ->limit($limit)
                    ->get(['id', 'title', 'slug', 'user_id', 'published_at'])
                    ->toArray();
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    public function recordRecommendationClick(int $sourceArticleId, int $recommendedArticleId, ?int $clickerUserId = null): void
    {
        try {
            DB::table('cross_promotion_clicks')->insert([
                'source_article_id' => $sourceArticleId,
                'recommended_article_id' => $recommendedArticleId,
                'clicker_user_id' => $clickerUserId,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::debug('CrossPromotionService click log failed: '.$e->getMessage());
        }
    }
}
