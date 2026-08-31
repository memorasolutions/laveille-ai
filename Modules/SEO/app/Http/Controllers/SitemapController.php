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
        foreach (['pillar.ia-pme', 'pillar.ia-education', 'pillar.ia-dev', 'pillar.veille-ia', 'pillar.ia-generative', 'pillar.ia-secteur-public', 'pillar.etat-ia-2026'] as $pillarRoute) {
            if (Route::has($pillarRoute)) {
                $sitemap->add(Url::create(route($pillarRoute))->setPriority(0.9)->setChangeFrequency('weekly'));
            }
        }

        // Sous-articles factuels rattachés aux piliers (faits sourcés, evergreen)
        foreach (['guide.adopter-ia-pme', 'guide.ia-etudier', 'guide.ia-locale', 'guide.ia-generative', 'guide.veille-adoption', 'guide.sp-rediger', 'guide.sp-loi25', 'guide.pme-loi25', 'guide.pme-cas-usage'] as $guideRoute) {
            if (Route::has($guideRoute)) {
                $sitemap->add(Url::create(route($guideRoute))->setPriority(0.7)->setChangeFrequency('monthly'));
            }
        }

        // Articles publiés (avec images)
        // 2026-08-31 (#2092) : url('/blog/'.$article->slug) était un accès brut au slug traduisible
        // (même défaut que le plan de site cassé le 18 juillet 2026) - Article::getPublicUrl() existe
        // déjà depuis le 27 juillet 2026 et protège ce même besoin, il suffisait de l'appeler ici.
        Article::where('status', 'published')->whereNotNull('published_at')->select(['id', 'slug', 'updated_at', 'featured_image'])->get()->each(function ($article) use ($sitemap) {
            $url = Url::create($article->getPublicUrl())
                ->setLastModificationDate($article->updated_at)
                ->setPriority(0.8)
                ->setChangeFrequency('weekly');

            if ($article->featured_image) {
                $url->addImage(url($article->featured_image));
            }

            $sitemap->add($url);
        });

        // Pages statiques publiées
        // 2026-08-31 (#2092) : route('page.show', $page->slug) reproduisait littéralement le
        // patron qui a cassé le plan de site le 18 juillet 2026 (accès brut à un slug traduisible,
        // null dès qu'une page n'a pas de traduction pour la locale courante) - protégé désormais
        // par StaticPage::getPublicUrl() (HasFallbackTranslatedSlug).
        StaticPage::where('status', 'published')->select(['id', 'slug', 'updated_at'])->get()->each(function ($page) use ($sitemap) {
            $sitemap->add(
                Url::create($page->getPublicUrl())
                    ->setLastModificationDate($page->updated_at)
                    ->setPriority(0.6)
                    ->setChangeFrequency('monthly')
            );
        });

        // Outils interactifs (si module Tools actif)
        if (Route::has('tools.index') && class_exists(\Modules\Tools\Models\Tool::class)) {
            $sitemap->add(Url::create(route('tools.index'))->setPriority(0.9)->setChangeFrequency('weekly'));

            if (Route::has('tools.show')) {
                // Round 136 (2026-07-30, passe adversariale) : scopeActive() ne filtre QUE is_active,
                // si bien qu'un outil gaté (construction ou révision) était annoncé aux moteurs avec la
                // même priorité 0.8 hebdomadaire qu'un outil pleinement fonctionnel. Le filtre est posé
                // ICI et non dans scopeActive() : ce scope est partagé avec la liste /outils, qui doit
                // justement continuer d'afficher les outils gatés (avec leur badge) aux superadmins.
                \Modules\Tools\Models\Tool::active()->where('is_under_construction', false)->ordered()->select(['id', 'slug', 'updated_at', 'sort_order'])->get()->each(function ($tool) use ($sitemap) {
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
            // 2026-08-31 (#2092) : même défaut que le plan de site cassé le 18 juillet 2026 (accès
            // brut à un slug traduisible) - protégé par Term::getPublicUrl() (HasFallbackTranslatedSlug).
            \Modules\Dictionary\Models\Term::published()->select(['id', 'slug', 'updated_at', 'hero_image'])->get()->each(function ($term) use ($sitemap) {
                $url = Url::create($term->getPublicUrl())
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
            // 2026-08-06 #1645 : les fiches archivées (contenu crawlé à tort) ne doivent plus être indexées.
            // 2026-08-20 (audit AdSense) : les fiches minces (mêmes critères que le noindex posé dans
            // PublicDirectoryController::show(), constante partagée THIN_SHORT_DESCRIPTION_MAX_LENGTH)
            // sont exclues du sitemap - cohérence noindex/sitemap.
            \Modules\Directory\Models\Tool::published()->notArchived()
                ->with('categories:id')
                ->withCount([
                    'reviews as approved_reviews_count' => fn ($q) => $q->approved(),
                    'resources as approved_resources_count' => fn ($q) => $q->where('is_approved', true),
                    'screenshots as approved_screenshots_count' => fn ($q) => $q->approved(),
                ])
                ->select(['id', 'slug', 'updated_at', 'screenshot', 'lifecycle_status', 'short_description'])
                ->get()
                ->reject(function ($tool) {
                    $shortDescriptionLength = mb_strlen(trim(strip_tags((string) ($tool->short_description ?? ''))));

                    return $tool->categories->isEmpty()
                        && $shortDescriptionLength < \Modules\Directory\Http\Controllers\PublicDirectoryController::THIN_SHORT_DESCRIPTION_MAX_LENGTH
                        && (int) $tool->approved_reviews_count === 0
                        && (int) $tool->approved_resources_count === 0
                        && (int) $tool->approved_screenshots_count === 0;
                })
                ->each(function ($tool) use ($sitemap) {
                    $url = Url::create($tool->getPublicUrl())
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
            \Modules\Directory\Models\ToolCollection::public()->select(['id', 'slug', 'updated_at'])->get()->each(function ($collection) use ($sitemap) {
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
            // 2026-08-31 (#2092) : getTranslation('slug', app()->getLocale()) sans troisième
            // paramètre ni repli reproduisait LITTÉRALEMENT le patron qui a cassé le plan de site
            // le 18 juillet 2026 (config/translatable.php non publié = pas de fallback réel) -
            // protégé par Acronym::getPublicUrl() (HasFallbackTranslatedSlug).
            \Modules\Acronyms\Models\Acronym::published()->select(['id', 'slug', 'updated_at'])->get()->each(function ($acronym) use ($sitemap) {
                $sitemap->add(
                    Url::create($acronym->getPublicUrl())
                        ->setLastModificationDate($acronym->updated_at)
                        ->setPriority(0.6)
                        ->setChangeFrequency('monthly')
                );
            });
        }

        // Boutique (si module Shop actif ET pas en maintenance — sinon /boutique renvoie 503 et pollue le sitemap)
        if (Route::has('shop.index') && class_exists(\Modules\Shop\Models\Product::class) && ! config('shop.maintenance', false)) {
            $sitemap->add(Url::create(route('shop.index'))->setPriority(0.7)->setChangeFrequency('weekly'));

            if (Route::has('shop.show')) {
                \Modules\Shop\Models\Product::published()->select(['id', 'slug', 'updated_at', 'images'])->get()->each(function ($product) use ($sitemap) {
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
        // 2026-08-20 (audit AdSense) : tant qu'aucune proposition publique réelle n'existe, la page
        // reste hors sitemap - même critère que le noindex conditionnel posé dans la vue publique.
        if (Route::has('roadmap.boards.index') && class_exists(\Modules\Roadmap\Models\Board::class)) {
            // Même filtre que PublicBoardController::index() (le tableau glossaire-communautaire
            // n'apparaît pas sur /roadmap, il ne doit donc pas compter dans ce critère).
            $hasPublicIdeas = \Modules\Roadmap\Models\Board::where('is_public', true)
                ->where('slug', '!=', 'glossaire-communautaire')
                ->withCount('ideas')->get()->sum('ideas_count') > 0;
            if ($hasPublicIdeas) {
                $sitemap->add(Url::create(route('roadmap.boards.index'))->setPriority(0.6)->setChangeFrequency('weekly'));
            }
        }

        // Pages auteur EEAT 2026 (#218 S84 — Schema.org Person + sameAs)
        if (Route::has('author.show')) {
            $authors = (array) trans('fronttheme::authors');
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

        // News (si module News actif)
        if (Route::has('news.index')) {
            $sitemap->add(Url::create(route('news.index'))->setPriority(0.7)->setChangeFrequency('daily'));
            if (class_exists(\Modules\News\Models\NewsArticle::class)) {
                // ACTION : chantier AdSense « faible valeur » (2026-08-18) - requête directe sur
                // is_published (jamais NewsArticle::published()), donc l'override de
                // scopePublished() ne la couvre PAS : whereNull('retired_at') explicite requis
                // pour qu'une fiche retirée (réponse 410) sorte du sitemap principal.
                // MCP: SELF (<5 lignes)
                // RAISON: design doc du chantier - toutes les surfaces publiques couvertes.
                \Modules\News\Models\NewsArticle::where('is_published', true)->where('seo_status', 'index')->whereNull('retired_at')->select(['id', 'slug', 'updated_at', 'image_url'])->get()->each(function ($article) use ($sitemap) {
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
