<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Http\Controllers;

use Illuminate\Http\Response;
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
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            \Modules\Blog\Models\Article::published()->get()->each(function ($article) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('blog.show', $article->slug))
                        ->setPriority(0.8)
                        ->setLastModificationDate($article->updated_at)
                );
            });
        }

        // Catégories
        if (class_exists(\Modules\Blog\Models\Category::class)) {
            \Modules\Blog\Models\Category::all()->each(function ($category) use ($sitemap) {
                $sitemap->add(Url::create(route('blog.category', $category->slug))->setPriority(0.6));
            });
        }

        // Pages statiques publiées
        if (class_exists(\Modules\Pages\Models\StaticPage::class)) {
            \Modules\Pages\Models\StaticPage::where('status', 'published')->get()->each(function ($page) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('pages.show', $page->slug))
                        ->setPriority(0.5)
                        ->setLastModificationDate($page->updated_at)
                );
            });
        }

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
