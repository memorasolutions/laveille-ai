<?php

declare(strict_types=1);

/**
 * Tests front-end Phase 3 : onglet Actualités sur fiche outil + bloc Outils sur actualité.
 *
 * Stratégie :
 *  - Onglet Directory : tests sur la vue Blade (lecture fichier, comme les tests Directory
 *    existants) pour contourner l'incompatibilité FIELD() MySQL/SQLite + un test de
 *    relation modèle vérifiant le comptage conditionnel.
 *  - Page actualité : tests HTTP sur news.show (pas de FIELD() dans ce contrôleur).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function natFrontAdmin(): User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

// ── Helpers locaux ────────────────────────────────────────────────────────────

function natFrontSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source front test',
        'url' => 'https://nat-front.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function natFrontArticle(int $sourceId, string $suffix = 'F', bool $published = true): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => "Article front {$suffix}",
        'guid' => "guid-nat-front-{$suffix}",
        'url' => "https://exemple.com/nat-front-{$suffix}",
        'description' => "Description de test pour front {$suffix}",
        'slug' => "article-front-" . strtolower($suffix),
        'pub_date' => now()->subDay(),
        'is_published' => $published,
        'seo_status' => 'index',
    ]);
}

function natFrontTool(string $suffix = 'F'): Tool
{
    $name = "Outil front {$suffix}";
    $slug = "outil-front-" . strtolower($suffix);

    // Tableau associatif (PAS json_encode) pour que Spatie appelle setTranslations() correctement.
    return Tool::withoutEvents(fn () => Tool::create([
        'name' => ['fr_CA' => $name, 'en' => $name],
        'slug' => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

// ── Tests de la vue Directory show (lecture fichier, compatible SQLite) ───────

it('la vue Directory show contient l\'onglet news dans la whitelist Alpine', function () {
    $content = file_get_contents(base_path('Modules/Directory/resources/views/public/show.blade.php'));
    expect($content)->toContain("'news'");
    expect($content)->toContain("'news'].includes(window.location.hash");
});

it('la vue Directory show contient le bouton onglet Actualités conditionnel', function () {
    $content = file_get_contents(base_path('Modules/Directory/resources/views/public/show.blade.php'));
    expect($content)->toContain("tab==='news'");
    expect($content)->toContain("\$toolNewsArticles->isNotEmpty()");
    expect($content)->toContain("Actualités");
});

it('la vue Directory show contient le panneau news avec l\'include du partial article-card', function () {
    $content = file_get_contents(base_path('Modules/Directory/resources/views/public/show.blade.php'));
    expect($content)->toContain("x-show=\"tab==='news'\"");
    // Le markup des cartes est dans le partial réutilisable (DRY) — vérifier l'include.
    expect($content)->toContain("news::public.partials.article-card");
});

it('le partial article-card contient bien le lien news.show et pub_date', function () {
    $content = file_get_contents(base_path('Modules/News/resources/views/public/partials/article-card.blade.php'));
    expect($content)->toContain('news.show');
    expect($content)->toContain('pub_date');
    expect($content)->toContain('nw-card');
});

// ── Tests de relation (modèle) : logique de comptage ─────────────────────────

it('un outil sans actualités liées renvoie 0 actualités publiées', function () {
    $tool = natFrontTool('ZERO');
    expect($tool->newsArticles()->published()->count())->toBe(0);
});

it('un outil avec une actualité non publiée n\'a pas d\'actualités publiées', function () {
    $source = natFrontSource();
    $tool = natFrontTool('UNPUB');
    $article = natFrontArticle($source->id, 'UNPUB', false);

    $tool->newsArticles()->attach($article->id, ['source' => 'manual']);

    expect($tool->newsArticles()->published()->count())->toBe(0);
});

it('un outil avec des actualités publiées les renvoie dans la relation', function () {
    $source = natFrontSource();
    $tool = natFrontTool('PUB');
    $articleA = natFrontArticle($source->id, 'PUBA');
    $articleB = natFrontArticle($source->id, 'PUBB');

    $tool->newsArticles()->attach([$articleA->id => ['source' => 'manual'], $articleB->id => ['source' => 'manual']]);

    $linked = $tool->newsArticles()->published()->latest('pub_date')->limit(12)->get();
    expect($linked)->toHaveCount(2);
    expect($linked->pluck('id'))->toContain($articleA->id)->toContain($articleB->id);
});

// ── Tests HTTP sur la page actualité (News show, compatible SQLite) ───────────

it('page actualité : le bloc Outils mentionnés apparaît quand des outils sont liés', function () {
    $source = natFrontSource();
    $article = natFrontArticle($source->id, 'OBL');
    $tool = natFrontTool('OBL');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(200);
    $response->assertSee(__('Outils mentionnés'));
    $response->assertSee('Outil front OBL');
});

it('page actualité : le bloc Outils mentionnés est absent quand aucun outil n\'est lié', function () {
    $source = natFrontSource();
    $article = natFrontArticle($source->id, 'NOBL');

    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(200);
    $response->assertDontSee(__('Outils mentionnés'));
});

// ── Liste /actualites : badge « outils liés » sur la miniature (2026-07-04) ──

it('liste des actualités : le badge outil apparaît sur la miniature pour un admin quand des outils sont liés', function () {
    $source = natFrontSource();
    $article = natFrontArticle($source->id, 'BADGE');
    $tool = natFrontTool('BADGE');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $this->actingAs(natFrontAdmin());
    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    // Vérifie le badge RENDU (aria-label), pas juste le nom de classe CSS qui
    // apparaît toujours une fois dans le <style> @once, même sans aucun badge.
    $response->assertSee(__('Outils liés à cette actualité'));
});

it('liste des actualités : le badge outil est absent de la miniature quand aucun outil n\'est lié (admin)', function () {
    $source = natFrontSource();
    natFrontArticle($source->id, 'NOBADGE');

    $this->actingAs(natFrontAdmin());
    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    $response->assertDontSee(__('Outils liés à cette actualité'));
});

it('liste des actualités : le badge outil est admin-only (masqué pour un visiteur non authentifié même avec des outils liés)', function () {
    $source = natFrontSource();
    $article = natFrontArticle($source->id, 'BADGEGUEST');
    $tool = natFrontTool('BADGEGUEST');

    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    $response->assertDontSee(__('Outils liés à cette actualité'));
});

it('la relation tools est eager-loadée sur la liste des actualités (anti N+1)', function () {
    $source = natFrontSource();
    $articleA = natFrontArticle($source->id, 'EAGERA');
    $articleB = natFrontArticle($source->id, 'EAGERB');
    $tool = natFrontTool('EAGER');
    $articleA->tools()->attach($tool->id, ['source' => 'manual']);
    $articleB->tools()->attach($tool->id, ['source' => 'manual']);

    DB::enableQueryLog();
    $this->get(route('news.index'));
    $toolQueries = collect(DB::getQueryLog())->pluck('query')->filter(
        fn (string $q) => str_contains($q, 'directory_tools') && str_contains($q, 'news_article_tool')
    );
    DB::disableQueryLog();

    // Signature anti-N+1 : UNE requête JOIN unique qui couvre TOUS les articles de
    // la page via IN (...), jamais une requête répétée par article (N+1).
    expect($toolQueries)->toHaveCount(1);
    expect($toolQueries->first())->toContain('in (');
});
