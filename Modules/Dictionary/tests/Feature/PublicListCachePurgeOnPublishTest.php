<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - purge du cache de réponse (Spatie ResponseCache) des pages de LISTE
 * (accueil + /glossaire) quand une fiche du glossaire change d'état de publication ou de
 * contenu.
 *
 * Mesuré le 2026-08-27 : AUCUNE purge n'existait pour ce module avant ce correctif (ni pour la
 * fiche, ni pour les listes) - le cas le plus dépourvu des modules audités ce jour-là. Corrigé
 * par Modules\Dictionary\Observers\TermObserver::purgePublicListCache(), même patron que
 * NewsArticleObserver, réutilisant le même composant App\Support\ResponseCache\PublicCachePurger
 * - jamais un ResponseCache::clear() global.
 */

use Modules\Dictionary\Models\Term;
use Spatie\ResponseCache\CacheItemSelector\CacheItemSelector;
use Spatie\ResponseCache\Facades\ResponseCache;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helper ────────────────────────────────────────────────────────────────────

/**
 * Construction directe (pas de TermFactory dans ce module - même convention que
 * ToolObserverScreenshotDispatchTest dans Modules/Directory).
 */
function dictCachePlcpTerm(string $suffixe, bool $publie = false): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'terme-plcp-'.$suffixe.'-'.uniqid();

    return Term::create([
        'name' => [$locale => 'Terme PLCP '.$suffixe, 'fr' => 'Terme PLCP '.$suffixe],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Définition de test.', 'fr' => 'Définition de test.'],
        'is_published' => $publie,
    ]);
}

// ── Preuve comportementale de bout en bout : la fiche apparaît sur les pages de liste ──
//
// PAS de test HTTP direct sur /glossaire ici (dictionary.index) : sa requête
// (PublicDictionaryController::index()) trie via `orderByRaw("... JSON_UNQUOTE(JSON_EXTRACT(...`,
// syntaxe MySQL que le SQLite :memory: de la suite de tests (phpunit.xml) ne supporte pas
// (« no such function: JSON_UNQUOTE », mesuré le 2026-08-27) - limitation PRÉ-EXISTANTE et
// SANS RAPPORT avec ce correctif (aucun test antérieur ne frappait /glossaire en HTTP). La
// preuve de bout en bout se fait donc sur l'accueil (ci-dessous, requête Eloquent portable) et
// la preuve du MÉCANISME exact sur dictionary.index se fait par les tests mock plus bas, qui
// n'exécutent jamais la vue.

it('rend l\'accueil visible avec le terme juste apres sa publication, meme si l\'accueil etait deja en cache', function () {
    config(['responsecache.enabled' => true]);

    $marker = 'Terme unique accueil PLCP '.uniqid();
    $draft = dictCachePlcpTerm('accueil-'.uniqid());
    $draft->setTranslation('name', 'fr_CA', $marker);
    $draft->setTranslation('name', 'fr', $marker);
    $draft->saveQuietly();

    $avant = $this->get('/');
    $avant->assertOk();
    $avant->assertDontSee($marker, false);

    $draft->update(['is_published' => true]);

    $apres = $this->get('/');
    $apres->assertOk();
    $apres->assertSee($marker, false);
});

// ── Preuve du MÉCANISME (mock, même convention que News::PublicListCachePurgeOnPublishTest) ──

it('purge precisement les URLs accueil et /glossaire a la publication (mock de la chaine Spatie)', function () {
    $draft = dictCachePlcpTerm('mock-publish-'.uniqid());

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('dictionary.index')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $draft->update(['is_published' => true]);
});

it('purge aussi les pages de liste a la depublication (un terme retire doit en disparaitre sans attendre)', function () {
    $term = dictCachePlcpTerm('mock-unpublish-'.uniqid(), publie: true);

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('dictionary.index')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $term->update(['is_published' => false]);
});

it('purge aussi les listes quand un terme deja publie est simplement modifie (contenu affiche sur les listes)', function () {
    // Plus large que le patron News (limité à la bascule is_published) : une fiche PUBLIÉE
    // dont le nom ou la définition change reste affichée sur /glossaire et l'accueil - ces
    // pages doivent donc se rafraîchir aussi sur une simple édition de contenu, pas
    // seulement sur un changement de statut.
    $term = dictCachePlcpTerm('mock-edit-'.uniqid(), publie: true);

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('dictionary.index')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $term->setTranslation('name', 'fr_CA', 'Nom retouché '.uniqid());
    $term->save();
});

it('ne purge rien quand une fiche jamais publiee est simplement modifiee', function () {
    $term = dictCachePlcpTerm('mock-noop-'.uniqid());

    ResponseCache::spy();

    $term->setTranslation('name', 'fr_CA', 'Toujours brouillon, retouché');
    $term->save();

    ResponseCache::shouldNotHaveReceived('selectCachedItems');
});
