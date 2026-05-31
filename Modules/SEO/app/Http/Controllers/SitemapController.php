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

        // Pages piliers SEO (S134 audit) — hubs thématiques evergreen
        foreach (['pillar.ia-pme', 'pillar.ia-education', 'pillar.ia-dev', 'pillar.veille-ia', 'pillar.ia-generative'] as $pillarRoute) {
            if (Route::has($pillarRoute)) {
                $sitemap->add(Url::create(route($pillarRoute))->setPriority(0.9)->setChangeFrequency('weekly'));
            }
        }

        // Sous-articles factuels rattachés aux piliers (faits sourcés, evergreen)
        foreach (['guide.adopter-ia-pme', 'guide.ia-etudier', 'guide.ia-locale', 'guide.ia-generative', 'guide.veille-adoption'] as $guideRoute) {
            if (Route::has($guideRoute)) {
                $sitemap->add(Url::create(route($guideRoute))->setPriority(0.7)->setChangeFrequency('monthly'));
            }
        }

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
            if (Route::has('directory.education-pricing')) {
                $sitemap->add(Url::create(route('directory.education-pricing'))->setPriority(0.8)->setChangeFrequency('weekly'));
            }
            \Modules\Directory\Models\Tool::published()->get()->each(function ($tool) use ($sitemap) {
                $url = Url::create(route('directory.show', $tool->slug))
                    ->setLastModificationDate($tool->updated_at)
                    ->setPriority(0.7)
                    ->setChangeFrequency('monthly');

                // S134 SEO : ne lister QUE les images self-hosted (les screenshots externes causent des warnings sitemap-image GSC)
                if ($tool->screenshot && ! (str_starts_with($tool->screenshot, 'http') && ! str_contains($tool->screenshot, 'laveille.ai'))) {
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

        // Pages auteur EEAT 2026 (#218 S84 — Schema.org Person + sameAs)
        if (Route::has('author.show')) {
            $authors = (array) trans('fronttheme::authors');
            if (is_array($authors)) {
                foreach ($authors as $slug => $data) {
                    if (is_string($slug) && is_array($data)) {
                        $sitemap->add(
                            Url::create(route('author.show', $slug))
                                ->setPriority(0.7)
                                ->setChangeFrequency('monthly')
                        );
                    }
                }
            }
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

                    // S134 SEO : ne lister QUE les images self-hosted (les image_url externes des sources news causent des warnings sitemap-image GSC)
                    if ($article->image_url && ! (str_starts_with($article->image_url, 'http') && ! str_contains($article->image_url, 'laveille.ai'))) {
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

        // Pages éditoriales et techniques S90 #43 (EEAT + transparence + API)
        foreach ([
            'methodologie' => ['priority' => 0.9, 'freq' => 'monthly'],     // Charte éditoriale (signal EEAT fort)
            'api.docs'     => ['priority' => 0.6, 'freq' => 'monthly'],     // Doc API publique
            'stats.public' => ['priority' => 0.5, 'freq' => 'weekly'],      // Compteurs vivants
        ] as $routeName => $cfg) {
            if (Route::has($routeName)) {
                $sitemap->add(Url::create(route($routeName))->setPriority($cfg['priority'])->setChangeFrequency($cfg['freq']));
            }
        }

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
