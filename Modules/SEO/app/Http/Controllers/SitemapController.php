<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
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

        // Outils (si module Tools actif)
        if (Route::has('tools.index') && class_exists(\Modules\Tools\Models\Tool::class)) {
            $sitemap->add(Url::create(route('tools.index'))->setPriority(0.9)->setChangeFrequency('weekly'));
        }

        // Glossaire (si module Dictionary actif)
        if (Route::has('dictionary.index') && class_exists(\Modules\Dictionary\Models\Term::class)) {
            $sitemap->add(Url::create(route('dictionary.index'))->setPriority(0.8)->setChangeFrequency('weekly'));
            \Modules\Dictionary\Models\Term::published()->get()->each(function ($term) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('dictionary.show', $term->slug))
                        ->setLastModificationDate($term->updated_at)
                        ->setPriority(0.7)
                        ->setChangeFrequency('monthly')
                );
            });
        }

        // Annuaire (si module Directory actif)
        if (Route::has('directory.index') && class_exists(\Modules\Directory\Models\Tool::class)) {
            $sitemap->add(Url::create(route('directory.index'))->setPriority(0.8)->setChangeFrequency('weekly'));
            \Modules\Directory\Models\Tool::published()->get()->each(function ($tool) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('directory.show', $tool->slug))
                        ->setLastModificationDate($tool->updated_at)
                        ->setPriority(0.7)
                        ->setChangeFrequency('monthly')
                );
            });
        }

        // Acronymes éducation (si module Acronyms actif)
        if (Route::has('acronyms.index') && class_exists(\Modules\Acronyms\Models\Acronym::class)) {
            $sitemap->add(Url::create(route('acronyms.index'))->setPriority(0.8)->setChangeFrequency('weekly'));
            \Modules\Acronyms\Models\Acronym::published()->get()->each(function ($acronym) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('acronyms.show', $acronym->getTranslation('slug', app()->getLocale())))
                        ->setLastModificationDate($acronym->updated_at)
                        ->setPriority(0.6)
                        ->setChangeFrequency('monthly')
                );
            });
        }

        // Pages statiques publiques
        if (Route::has('contact')) {
            $sitemap->add(Url::create(route('contact'))->setPriority(0.5)->setChangeFrequency('monthly'));
        }
        if (Route::has('faq.index')) {
            $sitemap->add(Url::create(route('faq.index'))->setPriority(0.7)->setChangeFrequency('monthly'));
        }
        if (Route::has('resources.index')) {
            $sitemap->add(Url::create(route('resources.index'))->setPriority(0.7)->setChangeFrequency('weekly'));
        }
        if (Route::has('directory.roadmap')) {
            $sitemap->add(Url::create(route('directory.roadmap'))->setPriority(0.6)->setChangeFrequency('weekly'));
        }

        // News (si module News actif)
        if (Route::has('news.index')) {
            $sitemap->add(Url::create(route('news.index'))->setPriority(0.7)->setChangeFrequency('daily'));
            if (class_exists(\Modules\News\Models\NewsArticle::class)) {
                \Modules\News\Models\NewsArticle::where('is_published', true)->get()->each(function ($article) use ($sitemap) {
                    $sitemap->add(
                        Url::create(url('/actualites/'.$article->slug))
                            ->setLastModificationDate($article->updated_at)
                            ->setPriority(0.6)
                            ->setChangeFrequency('weekly')
                    );
                });
            }
        }

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
