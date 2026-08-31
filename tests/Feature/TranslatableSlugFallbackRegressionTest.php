<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Preuve de non-régression (#2092, 2026-08-31). Reproduit exactement la panne du 18 juillet 2026 :
 * une fiche dont le champ 'slug' (Spatie Translatable) n'a de traduction que sous 'fr', jamais sous
 * 'fr_CA' - la locale de production - fait tomber toute boucle qui construit une adresse par accès
 * brut (route(..., $model->slug) ou getTranslation('slug', app()->getLocale()) sans repli). Vérifié
 * empiriquement (2026-08-31) : getTranslation() renvoie alors '' (chaîne vide, PAS null - Spatie
 * Translatable\Translatable::$allowNullForTranslation vaut false par défaut et config/translatable.php
 * n'est pas publié dans ce projet) et route('...', '') lève UrlGenerationException
 * ("Missing required parameter"), exactement comme observé en production le 18 juillet 2026.
 *
 * Deux temps par surface protégée : la caractérisation du défaut d'origine (le patron brut lève
 * bien l'exception - preuve que le scénario reproduit vraiment l'incident), puis la preuve que le
 * correctif (Modules\Core\Traits\HasFallbackTranslatedSlug, exposé via getPublicUrl() ou
 * resolveTranslatedSlug()) ne lève jamais et que chaque page réelle reste disponible.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Modules\Acronyms\Models\Acronym;
use Modules\Blog\Models\Article;
use Modules\Dictionary\Models\Term;
use Modules\Journal\Models\Journal;
use Modules\Journal\Services\JournalBlockService;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Locale de production reproduite en test (même convention que
    // Modules/SEO/tests/Feature/SitemapThinExclusionTest.php).
    config(['app.locale' => 'fr_CA']);
    app()->setLocale('fr_CA');
});

// -- Fixtures "cassées" : slug SEULEMENT sous 'fr', jamais 'fr_CA' - reproduit l'incident exact. --

function brokenStaticPage(string $slug): StaticPage
{
    // StaticPage::boot() comble tout slug vide à la création (empty($model->slug) -> Str::slug($title))
    // pour la locale COURANTE - créer directement avec seulement 'fr' serait donc auto-guéri avant
    // même l'incident. On crée d'abord normalement (satisfait le garde-fou + l'unicité), PUIS on
    // écrase la traduction du slug pour ne garder QUE 'fr' via une mise à jour (le hook ne
    // s'exécute qu'à la création, jamais à la mise à jour) - reproduit l'incident exact.
    $page = new StaticPage();
    $page->setTranslation('title', 'fr_CA', 'Page cassée '.$slug);
    $page->setTranslation('slug', 'fr_CA', 'temp-'.$slug);
    $page->status = 'published';
    $page->save();

    // setTranslations() FUSIONNE (boucle setTranslation par clé), elle ne remplace jamais le tableau
    // complet - la traduction 'fr_CA' serait restée si on l'avait utilisée ici. forgetTranslation()
    // retire précisément la clé 'fr_CA', pour ne garder que 'fr' - reproduit l'incident exact.
    $page->forgetTranslation('slug', 'fr_CA');
    $page->setTranslation('slug', 'fr', $slug);
    $page->save();

    return $page->refresh();
}

function brokenTerm(string $slug): Term
{
    $term = new Term();
    $term->setTranslation('name', 'fr_CA', 'Terme cassé '.$slug);
    $term->setTranslation('definition', 'fr_CA', 'Définition de test '.$slug.'.');
    $term->setTranslation('slug', 'fr', $slug);
    $term->is_published = true;
    $term->save();

    return $term->refresh();
}

function brokenAcronym(string $slug): Acronym
{
    $acronym = new Acronym();
    $acronym->setTranslation('acronym', 'fr_CA', strtoupper(substr($slug, 0, 6)));
    $acronym->setTranslation('full_name', 'fr_CA', 'Signification de test '.$slug);
    $acronym->setTranslation('slug', 'fr', $slug);
    $acronym->is_published = true;
    $acronym->save();

    return $acronym->refresh();
}

function brokenArticle(string $slug): Article
{
    $article = new Article();
    $article->user_id = User::factory()->create()->id;
    $article->setTranslation('title', 'fr_CA', 'Article cassé '.$slug);
    $article->setTranslation('content', 'fr_CA', '<p>Contenu de test '.$slug.'.</p>');
    $article->setTranslation('excerpt', 'fr_CA', 'Extrait de test '.$slug.'.');
    $article->setTranslation('slug', 'fr', $slug);
    $article->published_at = now();
    $article->save();

    return $article->refresh();
}

// -- 1. Caractérisation du défaut d'origine : le patron brut lève bien l'exception. --

test('avant correctif : le patron brut levait UrlGenerationException pour une fiche sans traduction fr_CA (#2092)', function () {
    $page = brokenStaticPage('page-cassee-avant-2092');
    $term = brokenTerm('terme-casse-avant-2092');
    $acronym = brokenAcronym('acronyme-casse-avant-2092');

    expect(fn () => route('page.show', $page->slug))->toThrow(UrlGenerationException::class);
    expect(fn () => route('dictionary.show', $term->slug))->toThrow(UrlGenerationException::class);
    expect(fn () => route('acronyms.show', $acronym->getTranslation('slug', app()->getLocale())))
        ->toThrow(UrlGenerationException::class);
});

// -- 2. Après correctif : getPublicUrl()/resolveTranslatedSlug() résolvent toujours, sans jamais lever. --

test('après correctif : getPublicUrl() replie vers la traduction fr disponible, sans exception (#2092)', function () {
    $page = brokenStaticPage('page-cassee-apres-2092');
    $term = brokenTerm('terme-casse-apres-2092');
    $acronym = brokenAcronym('acronyme-casse-apres-2092');
    $article = brokenArticle('article-casse-apres-2092');

    expect($page->getPublicUrl())->toContain('page-cassee-apres-2092');
    expect($term->getPublicUrl())->toContain('terme-casse-apres-2092');
    expect($acronym->getPublicUrl())->toContain('acronyme-casse-apres-2092');
    expect($article->getPublicUrl())->toContain('article-casse-apres-2092');

    // resolveTranslatedSlug() : utilisé pour les routes AUTRES que la fiche canonique (suggestions,
    // avis, visite...) - même repli, résultat brut (pas une URL complète).
    expect($term->resolveTranslatedSlug())->toBe('terme-casse-apres-2092');
    expect($acronym->resolveTranslatedSlug())->toBe('acronyme-casse-apres-2092');
    expect($page->resolveTranslatedSlug())->toBe('page-cassee-apres-2092');
});

// -- 3. Preuve de bout en bout : les pages RÉELLES ne tombent plus. --

test('item A - le plan de site reste disponible malgré une page/un terme/un acronyme/un article sans traduction fr_CA (#2092)', function () {
    $page = brokenStaticPage('sitemap-page-2092');
    $term = brokenTerm('sitemap-terme-2092');
    $acronym = brokenAcronym('sitemap-acronyme-2092');
    $article = brokenArticle('sitemap-article-2092');
    $article->status = 'published';
    $article->save();

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertSee($page->getPublicUrl(), false);
    $response->assertSee($term->getPublicUrl(), false);
    $response->assertSee($acronym->getPublicUrl(), false);
    $response->assertSee($article->getPublicUrl(), false);
});

test('item B - la page d\'accueil reste disponible malgré un terme et un acronyme sans traduction fr_CA (#2092)', function () {
    brokenTerm('accueil-terme-2092');
    brokenAcronym('accueil-acronyme-2092');

    $response = $this->get(route('home'));

    $response->assertOk();
});

test('item C - la recherche interne reste disponible malgré un terme et un acronyme sans traduction fr_CA (#2092)', function () {
    $term = brokenTerm('recherche-terme-2092');
    $acronym = brokenAcronym('recherche-acronyme-2092');

    $responseTerm = $this->get(route('search.index', ['q' => 'Terme cassé recherche-terme-2092']));
    $responseTerm->assertOk();

    $responseAcronym = $this->get(route('search.index', ['q' => $acronym->getTranslation('full_name', 'fr_CA', false)]));
    $responseAcronym->assertOk();
});

test('item D - le flux RSS du blogue reste disponible malgré un article sans traduction fr_CA (#2092)', function () {
    $article = brokenArticle('flux-article-2092');

    // La route blog.feed est désactivée dans ce déploiement (Modules/Blog/routes/web.php, route
    // commentée, hors périmètre de #2092) - la vue elle-même référence route('blog.feed') pour son
    // propre <atom:link> (ligne non touchée par le correctif). On l'enregistre localement, le temps
    // du test, pour rendre la vue CORRIGÉE (Modules/Blog/resources/views/feed/rss.blade.php) sans
    // dépendre de l'état (dés)activé de la route en production, hors périmètre de ce correctif.
    if (! \Illuminate\Support\Facades\Route::has('blog.feed')) {
        \Illuminate\Support\Facades\Route::get('/test-2092-blog-feed', fn () => null)->name('blog.feed');
        // Le nom ajouté après coup n'est pas indexé tant que le cache de noms de routes n'est pas
        // rafraîchi (vérifié empiriquement 2026-08-31) - sinon route('blog.feed') échoue toujours.
        app('router')->getRoutes()->refreshNameLookups();
    }

    $html = view('blog::feed.rss', ['articles' => Article::where('id', $article->id)->get()])->render();

    expect($html)->toContain($article->getPublicUrl());
});

test('item E - l\'infolettre hebdomadaire se rend malgré un article vedette sans traduction fr_CA (#2092)', function () {
    $article = brokenArticle('infolettre-article-2092');

    $html = view('newsletter::emails.digest-weekly', [
        'subject' => 'Test #2092',
        'highlight' => null,
        'topNews' => collect(),
        'toolOfWeek' => null,
        'featuredArticle' => $article,
        'didYouKnow' => null,
        'aiTerm' => null,
        'interactiveTool' => null,
        'weeklyPrompt' => null,
        'wellnessChallenge' => null,
        'editorial' => null,
        'unsubscribeUrl' => '#test-2092',
        'weekNumber' => 1,
    ])->render();

    expect($html)->toContain($article->getPublicUrl());
});

test('item F - le bloc journal "glossaire" reste disponible malgré un terme sans traduction fr_CA (#2092)', function () {
    $term = brokenTerm('journal-terme-2092');
    $user = User::factory()->create();
    $journal = Journal::create([
        'user_id' => $user->id,
        'title' => 'Journal test #2092',
        'slug' => 'journal-test-2092-'.uniqid(),
        'journal_date' => now()->toDateString(),
        'template' => 'classique',
        'is_published' => false,
    ]);

    $block = app(JournalBlockService::class)->addFromSource($journal, 'glossary', $term->id);

    expect($block->payload['url'])->toBe($term->getPublicUrl());
});
