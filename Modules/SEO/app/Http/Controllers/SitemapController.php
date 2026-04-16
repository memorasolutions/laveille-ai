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

        // Articles publiés (avec images)
        Article::where('status', 'published')->whereNotNull('published_at')->get()->each(function ($article) use ($sitemap) {
            $url = Url::create(url('/blog/'.$article->slug))
                ->setLastModificationDate($article->updated_at)
                ->setPriority(0.8)
                ->setChangeFrequency('weekly');

            if ($article->featured_image) {
                $url->addImage(url($article->featured_image));
            }

            $sitemap->add($url);
        });

        // Pages statiques publiées
        StaticPage::where('status', 'published')->get()->each(function ($page) use ($sitemap) {
            $sitemap->add(
                Url::create(route('page.show', $page->slug))
                    ->setLastModificationDate($page->updated_at)
                    ->setPriority(0.6)
                    ->setChangeFrequency('monthly')
            );
        });

        // Outils interactifs (si module Tools actif)
        if (Route::has('tools.index') && class_exists(\Modules\Tools\Models\Tool::class)) {
            $sitemap->add(Url::create(route('tools.index'))->setPriority(0.9)->setChangeFrequency('weekly'));

            if (Route::has('tools.show')) {
                \Modules\Tools\Models\Tool::active()->ordered()->get()->each(function ($tool) use ($sitemap) {
                    $sitemap->add(
                        Url::create(route('tools.show', $tool->slug))
                            ->setLastModificationDate($tool->updated_at)
                            ->setPriority(0.8)
                            ->setChangeFrequency('weekly')
                    );
                });
            }
        }

        // Glossaire (si module Dictionary actif)
        if (Route::has('dictionary.index') && class_exists(\Modules\Dictionary\Models\Term::class)) {
            $sitemap->add(Url::create(route('dictionary.index'))->setPriority(0.8)->setChangeFrequency('weekly'));
            \Modules\Dictionary\Models\Term::published()->get()->each(function ($term) use ($sitemap) {
                $url = Url::create(route('dictionary.show', $term->slug))
                    ->setLastModificationDate($term->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency('monthly');

                if ($term->hero_image) {
                    $url->addImage(url($term->hero_image));
                }

                $sitemap->add($url);
            });
        }

        // Annuaire (si module Directory actif)
        if (Route::has('directory.index') && class_exists(\Modules\Directory\Models\Tool::class)) {
            $sitemap->add(Url::create(route('directory.index'))->setPriority(0.8)->setChangeFrequency('weekly'));
            \Modules\Directory\Models\Tool::published()->get()->each(function ($tool) use ($sitemap) {
                $url = Url::create(route('directory.show', $tool->slug))
                    ->setLastModificationDate($tool->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency('monthly');

                if ($tool->screenshot) {
                    $url->addImage(str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : url($tool->screenshot));
                }

                $sitemap->add($url);
            });
        }

        // Collections publiques (si module Directory actif)
        if (Route::has('collections.index') && class_exists(\Modules\Directory\Models\ToolCollection::class)) {
            $sitemap->add(Url::create(route('collections.index'))->setPriority(0.7)->setChangeFrequency('weekly'));
            \Modules\Directory\Models\ToolCollection::public()->get()->each(function ($collection) use ($sitemap) {
                $sitemap->add(
                    Url::create(route('collections.show', $collection->slug))
                        ->setLastModificationDate($collection->updated_at)
                        ->setPriority(0.6)
                        ->setChangeFrequency('weekly')
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

        // Boutique (si module Shop actif)
        if (Route::has('shop.index') && class_exists(\Modules\Shop\Models\Product::class)) {
            $sitemap->add(Url::create(route('shop.index'))->setPriority(0.7)->setChangeFrequency('weekly'));

            if (Route::has('shop.show')) {
                \Modules\Shop\Models\Product::published()->get()->each(function ($product) use ($sitemap) {
                    $tag = Url::create(route('shop.show', $product))
                        ->setLastModificationDate($product->updated_at)
                        ->setPriority(0.7)
                        ->setChangeFrequency('weekly');

                    if (is_array($product->images) && ! empty($product->images)) {
                        $img = $product->images[0];
                        $tag->addImage(str_starts_with($img, 'http') ? $img : url($img));
                    }

                    $sitemap->add($tag);
                });
            }
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
        if (Route::has('roadmap.boards.index')) {
            $sitemap->add(Url::create(route('roadmap.boards.index'))->setPriority(0.6)->setChangeFrequency('weekly'));
        }

        // News (si module News actif)
        if (Route::has('news.index')) {
            $sitemap->add(Url::create(route('news.index'))->setPriority(0.7)->setChangeFrequency('daily'));
            if (class_exists(\Modules\News\Models\NewsArticle::class)) {
                \Modules\News\Models\NewsArticle::where('is_published', true)->get()->each(function ($article) use ($sitemap) {
                    $url = Url::create(url('/actualites/'.$article->slug))
                        ->setLastModificationDate($article->updated_at)
                        ->setPriority(0.6)
                        ->setChangeFrequency('weekly');

                    if ($article->image_url) {
                        $url->addImage(str_starts_with($article->image_url, 'http') ? $article->image_url : url($article->image_url));
                    }

                    $sitemap->add($url);
                });
            }
        }

        // Pages legales
        foreach (['legal.sales', 'legal.terms', 'legal.cookies', 'legal.privacy'] as $legalRoute) {
            if (Route::has($legalRoute)) {
                $sitemap->add(Url::create(route($legalRoute))->setPriority(0.4)->setChangeFrequency('monthly'));
            }
        }

        // Newsletter archive
        if (Route::has('newsletter.archive')) {
            $sitemap->add(Url::create(route('newsletter.archive'))->setPriority(0.5)->setChangeFrequency('monthly'));
        }

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
