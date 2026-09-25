<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai — page d'avant-première d'un article de blogue planifié
 *
 * Couvre le travail du 2026-09-25 : un article au statut "published" dont published_at est
 * dans le futur sert désormais une page d'avant-première en 200/noindex sur son adresse
 * définitive (PublicPostController::show(), fronttheme::blog.upcoming), jamais un 404 ni un
 * 503, et JAMAIS le contenu complet de l'article. À la date prévue, la même adresse sert
 * l'article complet, indexable.
 *
 * Convention : suit tests/Feature/ArticlesPlanifiesTest.php (même jour, même sujet, autre
 * agent) - RefreshDatabase, config(['responsecache.enabled' => false]) en beforeEach.
 */

use App\Support\ResponseCache\SkipAuthenticatedCacheProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response as HttpResponse;
use Modules\Blog\Models\Article;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Le cache de réponse (cacheResponse:3600 sur blog.show) est déjà désactivé par phpunit.xml
    // (RESPONSE_CACHE_ENABLED=false) - reposé explicitement pour que ce fichier reste vert même
    // exécuté seul avec une config différente (même garde que ArticlesPlanifiesTest.php).
    config(['responsecache.enabled' => false]);

    // Mesuré le 2026-09-25 : cet environnement porte APP_NOINDEX=true (garde-fou global hors
    // production, config/app.php), qui fait passer TOUTES les pages par la branche
    // "noindex, nofollow" du layout (Modules/FrontTheme/resources/views/layouts/master.blade.php,
    // premier @if) - avant même le mécanisme @section('page_noindex') que ce travail utilise. Non
    // maîtrisé, ce réglage rendrait les assertions sur la balise <meta name="robots"> aveugles à
    // la distinction avant-première/publié que ces tests vérifient précisément. Neutralisé ici
    // pour isoler le comportement réel de production (APP_NOINDEX=false) - même principe que le
    // config(['responsecache.enabled' => false]) ci-dessus.
    config(['app.noindex' => false]);
});

// ── Article planifié : avant-première 200/noindex, jamais le contenu ───────────────────────

test('un article planifié répond 200, porte noindex (en-tête ET balise) et affiche « À paraître le »', function () {
    $article = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->addDays(4)->setTime(9, 0),
        'title' => 'Article planifié de test avant-première',
        'slug' => 'article-planifie-avant-premiere-test',
        'excerpt' => 'Extrait public autorisé à paraître dans l\'avant-première.',
        'content' => '<p>PHRASE-SECRETE-DU-CONTENU-9f3d2c</p>',
    ]);

    $response = $this->get('/blog/'.$article->slug);

    $response->assertOk();
    $response->assertHeader('X-Robots-Tag', 'noindex');
    expect($response->getContent())->toContain('name="robots" content="noindex, follow');
    $response->assertSee('À paraître le');
    $response->assertSee('Extrait public autorisé', false);
});

test('la page d\'avant-première ne contient JAMAIS le contenu complet de l\'article', function () {
    $article = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->addWeek(),
        'slug' => 'article-planifie-contenu-cache-test',
        'content' => '<p>PHRASE-SECRETE-DU-CONTENU-9f3d2c</p>',
    ]);

    $response = $this->get('/blog/'.$article->slug);

    $response->assertOk();
    $response->assertDontSee('PHRASE-SECRETE-DU-CONTENU-9f3d2c');
});

// ── Brouillon : reste 404, même avec une date future ────────────────────────────────────────

test('un brouillon (même avec published_at futur) reste 404 - ce n\'est pas un article planifié', function () {
    $article = Article::factory()->create([
        'status' => 'draft',
        'published_at' => now()->addDays(2),
        'slug' => 'brouillon-avec-date-future-test',
    ]);

    $response = $this->get('/blog/'.$article->slug);

    $response->assertNotFound();
});

test('une adresse de blogue sans aucun article correspondant reste 404', function () {
    $response = $this->get('/blog/aucun-article-ne-porte-ce-slug-jamais');

    $response->assertNotFound();
});

// ── Article déjà publié : comportement inchangé (contenu visible, pas de noindex) ──────────

test('un article déjà publié répond 200 sans noindex et avec son contenu complet', function () {
    $article = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->subDay(),
        'slug' => 'article-deja-publie-test',
        'content' => '<p>PHRASE-VISIBLE-DU-CONTENU-7a1c4d</p>',
    ]);

    $response = $this->get('/blog/'.$article->slug);

    $response->assertOk();
    $response->assertHeaderMissing('X-Robots-Tag');
    expect($response->getContent())->not->toContain('noindex');
    $response->assertSee('PHRASE-VISIBLE-DU-CONTENU-7a1c4d', false);
});

// ── Sitemap : exclut l'article planifié, inclut l'article publié ───────────────────────────

test('le sitemap exclut l\'adresse d\'un article planifié mais inclut celle d\'un article publié', function () {
    $planifie = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->addDays(5),
        'slug' => 'planifie-hors-sitemap-test',
    ]);
    $publie = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->subDay(),
        'slug' => 'publie-dans-sitemap-test',
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertDontSee($planifie->getPublicUrl(), false);
    $response->assertSee($publie->getPublicUrl(), false);
});

// ── Cache de réponse : la page d'avant-première ne doit jamais être mise en cache ──────────
//
// Test direct sur SkipAuthenticatedCacheProfile::shouldCacheResponse() (même patron que
// tests/Feature/ResponseCacheTest.php pour shouldCacheRequest()) plutôt qu'un aller-retour HTTP
// complet avec le pilote de cache réel : la classe est la source de vérité unique de cette
// règle (app/Support/ResponseCache/SkipAuthenticatedCacheProfile.php), et le sollicité par
// Spatie ResponseCache EST exactement shouldCacheResponse() sur la Response Symfony produite -
// un test d'intégration HTTP n'exercerait rien de plus que cette méthode elle-même.

test('SkipAuthenticatedCacheProfile ne met JAMAIS en cache une réponse portant X-Robots-Tag: noindex', function () {
    $profile = new SkipAuthenticatedCacheProfile();
    $response = new HttpResponse('<html></html>', 200, ['Content-Type' => 'text/html']);
    $response->headers->set('X-Robots-Tag', 'noindex');

    expect($profile->shouldCacheResponse($response))->toBeFalse();
});

test('SkipAuthenticatedCacheProfile met toujours en cache une réponse HTML normale sans X-Robots-Tag (non-régression)', function () {
    $profile = new SkipAuthenticatedCacheProfile();
    $response = new HttpResponse('<html></html>', 200, ['Content-Type' => 'text/html']);

    expect($profile->shouldCacheResponse($response))->toBeTrue();
});
