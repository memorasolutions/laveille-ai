<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\ViewComposers;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Settings\Facades\Settings;
use Nwidart\Modules\Facades\Module;

class FrontendDataComposer
{
    public function compose(View $view): void
    {
        $latestArticles = collect();
        $latestArticle = null;
        $categories = collect();
        $recentArticles = collect();
        $popularTags = collect();
        $tags = collect();

        if (Module::has('Blog') && Module::find('Blog')?->isEnabled()) {

            $articleClass = 'Modules\\Blog\\Models\\Article';
            if (class_exists($articleClass)) {
                $cacheDuration = (int) Settings::get('cache.frontend_composer_duration', 600);
                $latestArticles = Cache::remember('front_latest_articles', $cacheDuration, function () use ($articleClass) {
                    return $articleClass::published()
                        ->with(['user', 'blogCategory'])
                        ->latest('published_at')
                        ->take((int) Settings::get('fronttheme.sidebar_latest_articles_limit', 5))
                        ->get();
                });

                $latestArticle = $latestArticles->first();

                $recentArticles = $latestArticles->take((int) Settings::get('fronttheme.sidebar_recent_articles_limit', 4));
            }

            $categoryClass = 'Modules\\Blog\\Models\\Category';
            if (class_exists($categoryClass)) {
                $categories = Cache::remember('front_categories', 600, function () use ($categoryClass) {
                    return $categoryClass::withCount('articles')->get();
                });
            }

            $tagClass = 'Modules\\Blog\\Models\\Tag';
            if (class_exists($tagClass)) {
                $tags = Cache::remember('front_tags', 600, function () use ($tagClass) {
                    return $tagClass::all();
                });

                $popularTags = $tags->take((int) Settings::get('fronttheme.sidebar_popular_tags_limit', 10));
            }
        }

        // Dernier article d'actualité (module News)
        $latestNewsArticle = null;
        $newsArticleClass = 'Modules\\News\\Models\\NewsArticle';
        if (Module::has('News') && Module::find('News')?->isEnabled() && class_exists($newsArticleClass)) {
            $latestNewsArticle = Cache::remember('front_latest_news_article', 300, function () use ($newsArticleClass) {
                return $newsArticleClass::where('is_published', true)
                    ->latest('pub_date')
                    ->first(['id', 'title', 'seo_title', 'slug']);
            });
        }

        $view->with(compact(
            'latestArticles',
            'latestArticle',
            'latestNewsArticle',
            'categories',
            'recentArticles',
            'popularTags',
            'tags',
        ));
    }
}
