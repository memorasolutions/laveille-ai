<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 74 (2026-07-27) : passe adversariale fraîche, périmètre transitif complet du graphe de
// constructeur-prompts.blade.php + Article::getPublicUrl(). 2 manques réels confirmés
// INDÉPENDAMMENT (jamais sur la seule parole du sous-agent) :
//
// 1. P0 site-wide (pas seulement constructeur-prompts) : Modules/FrontTheme/resources/views/
//    partials/header.blade.php (rendu sur CHAQUE page publique via le layout) faisait
//    route('blog.show', $latestArticle->slug) sans vérifier que le slug existe pour la locale
//    courante. Reproduit via tinker : Article::latest('published_at')->first()->getTranslations
//    ('slug') === ['fr_CA' => '...'] (aucune clé 'en') → app()->setLocale('en') →
//    $article->slug === '' → route('blog.show', '') lève UrlGenerationException (500).
//    Confirmé indépendamment sur home.blade.php (11 occurrences du même pattern, hero1-4,
//    highlight, recent, sponsored). Fixé via Article::getPublicUrl() (repli manuel locale
//    courante -> 'fr_CA' -> 1re traduction disponible, MÊME pattern que Tool::getPublicUrl(),
//    P0 2026-07-19, Modules/Directory - voir ToolPublicUrlLocaleFallbackTest.php) + migration des
//    12 sites d'appel (header.blade.php + 11 dans home.blade.php) vers $article->getPublicUrl().
//
// Étape 9 (2026-08-02, réécriture complète du Constructeur de prompts) : les 3 tests ci-dessous
// (Article::getPublicUrl, home page, header partial via constructeur-prompts) sont INFRASTRUCTURE
// pure - zéro dépendance au markup de l'ancien assistant (9 $defaultTaskCards, personas/verbs
// bruts). Conservés tels quels. Les tests qui verrouillaient l'i18n des ANCIENNES 9 cartes
// d'objectif ($defaultTaskCards) et le verrou négatif sur personas/verbs injectés en JS
// (window.promptBuilderConfig, disparu avec la réécriture) ont été retirés : la nouvelle page
// n'a plus ce concept - les 9 gabarits sont rendus côté Blade via __() directement (voir
// PromptBuilderRewriteTest.php pour la couverture i18n de la nouvelle interface).

it('Article::getPublicUrl() ne plante pas quand le slug n\'existe que pour une autre locale (round 74)', function () {
    // Locale FR_CA à la création : Article::boot() auto-génère le slug pour la locale COURANTE
    // uniquement (creating() : if empty($model->slug) -> Str::slug($model->title)) - on ne bascule
    // en EN qu'APRÈS la création, pour reproduire fidèlement "aucune traduction 'en' du tout".
    config(['app.locale' => 'fr_CA']);
    $article = Article::factory()->published()->create();

    config(['app.locale' => 'en']);

    expect($article->getTranslation('slug', 'en', false))->toBe('');
    expect($article->getPublicUrl())->toContain($article->getTranslation('slug', 'fr_CA', false));
});

it('home page does not 500 in EN locale when the latest article has no EN slug translation (round 74)', function () {
    $article = Article::factory()->published()->create();
    $article->setTranslation('slug', 'fr_CA', 'article-home-fallback-round74');
    $article->save();

    $response = $this->withSession(['locale' => 'en'])->get('/');

    $response->assertOk();
});

it('constructeur-prompts page does not 500 in EN locale via the shared header partial (round 74)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $article = Article::factory()->published()->create();
    $article->setTranslation('slug', 'fr_CA', 'article-cp-fallback-round74');
    $article->save();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts');

    $response->assertOk();
});
