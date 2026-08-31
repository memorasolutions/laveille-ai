<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - purge du cache de réponse (Spatie ResponseCache) des pages de LISTE
 * (accueil + /annuaire) quand une fiche outil change d'état de publication ou de contenu.
 *
 * Mesuré le 2026-08-27 : le seul mécanisme existant (DirectoryAdminController::toggleFeatured)
 * ne couvrait que la bascule de mise en avant, et via un ResponseCache::clear() GLOBAL - pas
 * ciblé. La création et la modification d'une fiche via l'admin (store()/update()) ne
 * purgeaient rien : /annuaire (600s) et l'accueil (600s) restaient périmés jusqu'à expiration
 * naturelle. Corrigé par Modules\Directory\Observers\ToolObserver::purgePublicListCache(), même
 * patron que NewsArticleObserver, réutilisant le même composant
 * App\Support\ResponseCache\PublicCachePurger.
 */

use Modules\Directory\Models\Tool;
use Spatie\ResponseCache\CacheItemSelector\CacheItemSelector;
use Spatie\ResponseCache\Facades\ResponseCache;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helper ────────────────────────────────────────────────────────────────────

/** Construction directe (pas de ToolFactory dans ce module - même convention que ToolObserverScreenshotDispatchTest). */
function dirCachePlcpTool(string $suffixe, string $status = 'pending'): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->url = 'https://plcp-tool-'.$suffixe.'.example';
    $tool->pricing = 'free';
    $tool->status = $status;
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil PLCP '.$suffixe);
    $tool->setTranslation('slug', 'fr_CA', 'outil-plcp-'.$suffixe.'-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->save();

    return $tool;
}

// ── Preuve comportementale de bout en bout : la fiche apparaît sur les pages de liste ──

// Mandat #1939 (2026-08-31) : /annuaire (directory.index) est désormais couvert par un vrai test
// HTTP direct - voir Modules/Directory/tests/Feature/PublicDirectoryIndexPageTest.php. La cause
// d'origine (« HAVING clause on a non-aggregate query », mesurée le 2026-08-27 : le bloc « plus
// votés » filtrait via `having('community_votes_count', '>', 0)` sur un alias withCount() sans
// GROUP BY - une dépendance à la permissivité MySQL, pas une fonction manquante) est corrigée
// dans PublicDirectoryController::index() par `has('communityVotes', '>', 0)`, portable, avec le
// même résultat sur MySQL. La preuve ci-dessous (accueil) et les tests mock plus bas restent
// utiles tels quels : ils ciblent le MÉCANISME de purge de cache, pas le rendu de /annuaire.

it('rend l\'accueil visible avec l\'outil juste apres sa publication, meme si l\'accueil etait deja en cache', function () {
    config(['responsecache.enabled' => true]);

    $marker = 'Outil unique accueil PLCP '.uniqid();
    $draft = dirCachePlcpTool('accueil-'.uniqid());
    $draft->setTranslation('name', 'fr_CA', $marker);
    $draft->saveQuietly();

    $avant = $this->get('/');
    $avant->assertOk();
    $avant->assertDontSee($marker, false);

    $draft->update(['status' => 'published']);

    $apres = $this->get('/');
    $apres->assertOk();
    $apres->assertSee($marker, false);
});

// ── Preuve du MÉCANISME (mock, même convention que News::PublicListCachePurgeOnPublishTest) ──

it('purge precisement les URLs accueil et /annuaire a la publication (mock de la chaine Spatie)', function () {
    $draft = dirCachePlcpTool('mock-publish-'.uniqid());

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('directory.index')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $draft->update(['status' => 'published']);
});

it('purge aussi les pages de liste a la depublication (un outil retire doit en disparaitre sans attendre)', function () {
    $tool = dirCachePlcpTool('mock-unpublish-'.uniqid(), status: 'published');

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('directory.index')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $tool->update(['status' => 'pending']);
});

it('purge aussi les listes quand un outil deja publie est simplement modifie (contenu affiche sur les listes)', function () {
    // Plus large que le patron News (limité à la bascule de statut) : une fiche PUBLIÉE dont
    // le nom ou le tarif change reste affichée sur /annuaire et l'accueil - ces pages doivent
    // donc se rafraîchir aussi sur une simple édition de contenu.
    $tool = dirCachePlcpTool('mock-edit-'.uniqid(), status: 'published');

    $selecteur = Mockery::mock(CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->with([route('home'), route('directory.index')])->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $tool->setTranslation('name', 'fr_CA', 'Nom retouché '.uniqid());
    $tool->save();
});

it('ne purge rien quand une fiche jamais publiee est simplement modifiee', function () {
    $tool = dirCachePlcpTool('mock-noop-'.uniqid());

    ResponseCache::spy();

    $tool->setTranslation('name', 'fr_CA', 'Toujours en attente, retouché');
    $tool->save();

    ResponseCache::shouldNotHaveReceived('selectCachedItems');
});
