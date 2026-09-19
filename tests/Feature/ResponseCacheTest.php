<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Support\ResponseCache\SkipAuthenticatedCacheProfile;
use Illuminate\Http\Request;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\SaaS\Models\Plan;
use Modules\Settings\Models\Setting;
use Modules\Tools\Models\Tool;
use Spatie\ResponseCache\Facades\ResponseCache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('article save clears response cache', function () {
    ResponseCache::spy();
    Article::factory()->create(['status' => 'published', 'published_at' => now()]);
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('setting save clears response cache', function () {
    ResponseCache::spy();
    Setting::set('test_cache_key', 'test_value');
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('plan save clears response cache', function () {
    if (! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
        $this->markTestSkipped('Module SaaS désactivé dans ce déploiement (dépend indirectement de Modules\\SaaS\\Models\\Plan).');
    }

    ResponseCache::spy();
    Plan::factory()->create();
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('comment save clears response cache', function () {
    $article = Article::factory()->create(['status' => 'published', 'published_at' => now()]);

    ResponseCache::spy();
    Comment::factory()->create(['article_id' => $article->id]);
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('category save clears response cache', function () {
    ResponseCache::spy();
    Category::factory()->create();
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('response cache middleware is registered', function () {
    $middlewareAliases = app(\Illuminate\Routing\Router::class)->getMiddleware();
    expect($middlewareAliases)->toHaveKey('cacheResponse');
    expect($middlewareAliases)->toHaveKey('doNotCacheResponse');
});

// Mode "maintenance" d'un outil (2026-09-19) : le contournement "aperçu" (visiteur NON connecté)
// ne doit JAMAIS être mis en cache par Spatie ResponseCache - sinon la page RÉELLE de l'outil,
// vue une seule fois par le propriétaire via son jeton d'aperçu, serait figée dans le cache
// serveur et reservie ensuite à TOUS les visiteurs suivants, contournant la maintenance publique
// pour tout le monde. Cf. app/Support/ResponseCache/SkipAuthenticatedCacheProfile.php.

test('SkipAuthenticatedCacheProfile does NOT cache a request carrying the maintenance preview query param', function () {
    $profile = new SkipAuthenticatedCacheProfile();
    $request = Request::create('/outils/constructeur-prompts?'.Tool::MAINTENANCE_PREVIEW_QUERY.'=un-jeton', 'GET');

    expect($profile->shouldCacheRequest($request))->toBeFalse();
});

test('SkipAuthenticatedCacheProfile does NOT cache a request carrying the maintenance preview cookie', function () {
    $profile = new SkipAuthenticatedCacheProfile();
    $request = Request::create('/outils/constructeur-prompts', 'GET');
    $request->cookies->set(Tool::MAINTENANCE_PREVIEW_COOKIE, 'un-jeton');

    expect($profile->shouldCacheRequest($request))->toBeFalse();
});

test('SkipAuthenticatedCacheProfile still caches a plain anonymous GET request (non-regression)', function () {
    $profile = new SkipAuthenticatedCacheProfile();
    $request = Request::create('/outils/constructeur-prompts', 'GET');

    expect($profile->shouldCacheRequest($request))->toBeTrue();
});
