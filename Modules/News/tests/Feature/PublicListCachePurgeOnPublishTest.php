<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - purge du cache de réponse (Spatie ResponseCache) des pages de LISTE
 * (accueil + /actualites) quand une fiche change d'état de publication.
 *
 * Défaut mesuré le 2026-08-26/27 : la purge existante (NewsToolSyncAction::invalidatePublicCache)
 * ne couvrait QUE l'URL de la fiche elle-même. Une fiche fraîchement publiée devenait donc
 * accessible par son adresse directe (jamais mise en cache avant publication - abort_if(404) tant
 * qu'elle est brouillon), mais restait invisible sur l'accueil et /actualites jusqu'à
 * l'expiration naturelle du cache de CES pages, déjà visitées et donc déjà en cache.
 *
 * Corrigé par NewsArticleObserver::purgePublicListCache(), appelé pour toute bascule de
 * is_published (publication ET dépublication), via le helper générique
 * App\Support\ResponseCache\PublicCachePurger - jamais un ResponseCache::clear() global (le
 * site sert son rendu 10x plus vite depuis que les pages publiques sont mises en cache,
 * v1.220.0 - un clear() global à chaque publication annulerait ce gain pour tout le trafic).
 */

use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Spatie\ResponseCache\CacheItemSelector\CacheItemSelector;
use Spatie\ResponseCache\Facades\ResponseCache;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function plcpSource(): NewsSource
{
    return NewsSource::create([
        'name'     => 'Source PLCP',
        'url'      => 'https://plcp-source.exemple.com/rss',
        'language' => 'fr',
        'active'   => true,
    ]);
}

/**
 * Fiche BROUILLON minimale (is_published=false) : publishAndPurgeSource() ne fait
 * volontairement aucune validation métier (docblock du modèle), donc aucun champ éditorial
 * n'est requis ici pour reproduire la bascule de publication.
 */
function plcpDraftArticle(string $slug, string $title): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => plcpSource()->id,
        'title'          => $title,
        'guid'           => 'guid-'.$slug,
        'url'            => 'https://exemple.com/'.$slug,
        'description'    => '',
        'summary'        => 'Résumé de test.',
        'slug'           => $slug,
        'pub_date'       => now(),
        'is_published'   => false,
        'seo_status'     => 'index',
    ]);
}

// ── Preuve comportementale de bout en bout : la fiche apparaît sur les pages de liste ──
//
// Sans le correctif (purge de la fiche seule) : la page /actualites a déjà été visitée et mise
// en cache AVANT la publication - la publication ne touche jamais cette URL - donc la seconde
// requête sert encore la version périmée, SANS le titre de la nouvelle fiche.

it('rend la fiche visible sur /actualites juste apres sa publication, meme si la liste etait deja en cache', function () {
    config([
        'responsecache.enabled' => true,
        // Store 'array' : supporte le tagging (contrairement à 'file', utilisé en prod - voir
        // docblock de PublicCachePurger), mais 'cache_tag' reste vide donc non exercé ici ;
        // seule la lifetime importe pour ce test (isolé au process de test, jamais partagé).
    ]);

    $marker = 'Titre unique PLCP '.uniqid();
    $draft = plcpDraftArticle('plcp-liste-'.uniqid(), $marker);

    // 1) Réchauffe le cache de /actualites AVANT la publication : c'est exactement le scénario
    // réel (le visiteur suivant obtient une page déjà en cache, calculée sans la nouvelle fiche).
    $avant = $this->get('/actualites');
    $avant->assertOk();
    $avant->assertDontSee($marker, false);

    // 2) Publication via le MÊME point d'entrée que la bascule rapide admin
    // (AdminNewsController::toggleArticle) et l'écran de composition.
    $draft->publishAndPurgeSource();

    // 3) Sans purge de la page de LISTE, cette seconde requête resservirait la version d'AVANT
    // la publication (rappel réchauffé en 1) - le test échoue avant le correctif, passe après.
    $apres = $this->get('/actualites');
    $apres->assertOk();
    $apres->assertSee($marker, false);
});

it('rend l\'accueil visible avec la fiche juste apres sa publication, meme si l\'accueil etait deja en cache', function () {
    config(['responsecache.enabled' => true]);

    $marker = 'Titre unique accueil PLCP '.uniqid();
    $draft = plcpDraftArticle('plcp-accueil-'.uniqid(), $marker);

    $avant = $this->get('/');
    $avant->assertOk();
    $avant->assertDontSee($marker, false);

    $draft->publishAndPurgeSource();

    $apres = $this->get('/');
    $apres->assertOk();
    $apres->assertSee($marker, false);
});

// ── Preuve du MÉCANISME (mock, même convention que ComposedSummaryApplyTest) ───────────────
//
// Prouve la chaîne COMPLETE (forUrls -> usingSuffix -> forget) sur les URLs home + news.index,
// pas seulement qu'un appel générique a eu lieu.

it('purge precisement les URLs accueil et /actualites a la publication (mock de la chaine Spatie)', function () {
    $draft = plcpDraftArticle('plcp-mock-publish-'.uniqid(), 'Fiche mock publication');

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('news.index'), route('news.verifications')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $draft->publishAndPurgeSource();
});

it('purge aussi les pages de liste a la depublication (une fiche retiree doit en disparaitre sans attendre)', function () {
    $article = plcpDraftArticle('plcp-mock-unpublish-'.uniqid(), 'Fiche mock depublication');
    $article->publishAndPurgeSource();

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('news.index'), route('news.verifications')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $article->update(['is_published' => false]);
});

it('ne purge rien quand une modification ne touche pas is_published', function () {
    $article = plcpDraftArticle('plcp-mock-noop-'.uniqid(), 'Fiche mock sans bascule');
    $article->publishAndPurgeSource();

    ResponseCache::spy();

    $article->update(['title' => 'Titre retouché, sans toucher is_published']);

    ResponseCache::shouldNotHaveReceived('selectCachedItems');
});
