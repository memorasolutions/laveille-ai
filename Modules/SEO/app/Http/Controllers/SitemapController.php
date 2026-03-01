<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Http\Controllers;

use Illuminate\Http\Response;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Pages\Models\StaticPage;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController
{
    public function index(): Response
    {
        $sitemap = Sitemap::create();

        // Pages principales
        $sitemap->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency('daily'));
        $sitemap->add(Url::create(route('faq.show'))->setPriority(0.5));
        $sitemap->add(Url::create(route('contact.show'))->setPriority(0.5));
        $sitemap->add(Url::create(route('about'))->setPriority(0.3));
        $sitemap->add(Url::create(route('legal'))->setPriority(0.2));
        $sitemap->add(Url::create(route('privacy'))->setPriority(0.2));

        // Pricing
        $sitemap->add(Url::create(url('/pricing'))->setPriority(0.9)->setChangeFrequency('weekly'));

        // Articles publiés
        Article::published()->get()->each(function ($article) use ($sitemap) {
            $sitemap->add(
                Url::create(route('blog.show', $article->slug))
                    ->setPriority(0.8)
                    ->setLastModificationDate($article->updated_at)
            );
        });

        // Catégories
        Category::all()->each(function ($category) use ($sitemap) {
            $sitemap->add(Url::create(route('blog.category', $category->slug))->setPriority(0.6));
        });

        // Pages statiques publiées
        StaticPage::where('status', 'published')->get()->each(function ($page) use ($sitemap) {
            $sitemap->add(
                Url::create(route('pages.show', $page->slug))
                    ->setPriority(0.5)
                    ->setLastModificationDate($page->updated_at)
            );
        });

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
