<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Http\Controllers;

use Illuminate\Http\Response;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController
{
    public function index(): Response
    {
        $sitemap = Sitemap::create();

        // Page d'accueil
        $sitemap->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency('daily'));

        // Articles publiés
        Article::where('status', 'published')->whereNotNull('published_at')->get()->each(function ($article) use ($sitemap) {
            $sitemap->add(
                Url::create(url('/blog/'.$article->slug))
                    ->setLastModificationDate($article->updated_at)
                    ->setPriority(0.8)
                    ->setChangeFrequency('weekly')
            );
        });

        // Pages statiques publiées
        StaticPage::where('status', 'published')->get()->each(function ($page) use ($sitemap) {
            $sitemap->add(
                Url::create(url('/pages/'.$page->slug))
                    ->setLastModificationDate($page->updated_at)
                    ->setPriority(0.6)
                    ->setChangeFrequency('monthly')
            );
        });

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
