<?php

declare(strict_types=1);

/**
 * Tests du tracking "déjà publié" LinkedIn/Facebook sur les actualités (point rouge admin).
 *
 * Couvre : route de marquage admin-only (isSuperAdmin strict, anti-IDOR), idempotence,
 * validation de la plateforme (liste blanche), et présence/absence du point rouge (+ des
 * données *_shared_at) dans le HTML rendu selon le rôle (admin vs visiteur).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nast pour éviter tout conflit inter-fichiers) ────

function nastSuperAdmin(): User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $email = (string) config('app.superadmin_email');
    if ($email === '') {
        config(['app.superadmin_email' => $email = 'superadmin-nast@laveille.ai']);
    }
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

function nastRegularUser(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

function nastSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source tracking test',
        'url' => 'https://nast-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function nastArticle(int $sourceId, string $suffix = 'A'): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => "Article tracking {$suffix}",
        'guid' => "guid-nast-{$suffix}",
        'url' => "https://exemple.com/nast-{$suffix}",
        'description' => "Description de test pour tracking {$suffix}",
        'slug' => 'article-tracking-' . strtolower($suffix),
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);
}

// ── (a) Refus non-admin ────────────────────────────────────────────────────

it('la route de marquage refuse un visiteur non authentifié (redirigé vers login)', function () {
    $article = nastArticle(nastSource()->id, 'GUEST');

    $response = $this->post(route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'linkedin']));

    $response->assertRedirect();
    expect($article->fresh()->linkedin_shared_at)->toBeNull();
});

it('la route de marquage refuse un utilisateur authentifié non-superadmin (403)', function () {
    $article = nastArticle(nastSource()->id, 'REG');
    $this->actingAs(nastRegularUser());

    $response = $this->post(route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'linkedin']));

    $response->assertStatus(403);
    expect($article->fresh()->linkedin_shared_at)->toBeNull();
});

// ── (b) Marquage indépendant LinkedIn / Facebook ───────────────────────────

it('un superadmin peut marquer linkedin puis facebook indépendamment', function () {
    $article = nastArticle(nastSource()->id, 'MARK');
    $this->actingAs(nastSuperAdmin());

    $r1 = $this->post(route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'linkedin']));
    $r1->assertOk();
    $article->refresh();
    expect($article->linkedin_shared_at)->not->toBeNull();
    expect($article->facebook_shared_at)->toBeNull();

    $r2 = $this->post(route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'facebook']));
    $r2->assertOk();
    $article->refresh();
    expect($article->linkedin_shared_at)->not->toBeNull();
    expect($article->facebook_shared_at)->not->toBeNull();
});

// ── (c) Idempotence ─────────────────────────────────────────────────────────

it('marquer deux fois de suite la même plateforme est idempotent (aucune erreur, timestamp mis à jour)', function () {
    $article = nastArticle(nastSource()->id, 'IDEM');
    $this->actingAs(nastSuperAdmin());

    $this->post(route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'linkedin']))->assertOk();
    $first = $article->fresh()->linkedin_shared_at;

    $this->travel(5)->minutes();

    $this->post(route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'linkedin']))->assertOk();
    $second = $article->fresh()->linkedin_shared_at;

    expect($second->greaterThan($first))->toBeTrue();
});

// ── (d) Point rouge présent pour un admin après marquage ───────────────────

it('le point rouge apparaît dans le HTML rendu pour un admin après marquage', function () {
    $article = nastArticle(nastSource()->id, 'DOTADMIN');
    $article->forceFill(['linkedin_shared_at' => now()])->save();

    $this->actingAs(nastSuperAdmin());
    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(200);
    $response->assertSee('nw-shared-dot', escape: false);
    $response->assertSee('Déjà publié sur LinkedIn', escape: false);
});

// ── (e) Absence totale pour un visiteur non-admin, même si les champs sont remplis ──

it('le point rouge et les données *_shared_at sont absents du HTML pour un visiteur non-admin', function () {
    $article = nastArticle(nastSource()->id, 'DOTGUEST');
    $article->forceFill(['linkedin_shared_at' => now(), 'facebook_shared_at' => now()])->save();

    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(200);
    // NOTE : la classe CSS '.nw-shared-dot' apparaît toujours dans le <style> statique de la
    // page (comme le badge outils analogue, cf. NewsArticleToolFrontTest), et role="img" est
    // utilisé ailleurs sur la page (Octopus, etc.) — ni l'un ni l'autre n'est une fuite de
    // donnée. Le vrai signal serveur-only, propre à cette fonctionnalité : l'écouteur
    // 'admin-share-tracked' (marqueur unique du wrapper x-data) ne doit JAMAIS être rendu pour
    // un visiteur non-admin, quel que soit l'état des colonnes *_shared_at en DB.
    $response->assertDontSee('admin-share-tracked', escape: false);
    $response->assertDontSee('linkedin_shared_at', escape: false);
    $response->assertDontSee('facebook_shared_at', escape: false);
});

it('le point rouge est absent (état initial false) pour un admin quand aucun champ *_shared_at n\'est rempli', function () {
    $article = nastArticle(nastSource()->id, 'DOTNONE');

    $this->actingAs(nastSuperAdmin());
    $response = $this->get(route('news.show', $article->slug));

    $response->assertStatus(200);
    // Le wrapper (span x-data) est bien présent côté serveur pour le gate admin (permet la
    // mise à jour réactive instantanée au clic), mais son état initial linkedin/facebook doit
    // refléter fidèlement l'absence de données en DB (aucun état 'true' au chargement).
    $response->assertSee('role="img"', escape: false);
    $response->assertDontSee('linkedin: true', escape: false);
    $response->assertDontSee('facebook: true', escape: false);
    $response->assertSee('linkedin: false', escape: false);
    $response->assertSee('facebook: false', escape: false);
});

// ── (g) Point rouge sur la liste publique /actualites (composant partagé x-news::admin-shared-dot) ──

it('le point rouge apparaît sur la liste publique des actualités pour un admin après marquage', function () {
    $article = nastArticle(nastSource()->id, 'LISTADMIN');
    $article->forceFill(['linkedin_shared_at' => now()])->save();

    $this->actingAs(nastSuperAdmin());
    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    $response->assertSee('nw-shared-dot', escape: false);
    $response->assertSee('Déjà publié sur LinkedIn', escape: false);
});

it('le point rouge et les données *_shared_at sont absents de la liste publique pour un visiteur non-admin', function () {
    $article = nastArticle(nastSource()->id, 'LISTGUEST');
    $article->forceFill(['linkedin_shared_at' => now(), 'facebook_shared_at' => now()])->save();

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    // Même raisonnement que le test analogue sur la fiche individuelle (voir plus haut) : le
    // signal serveur-only propre à cette fonctionnalité est l'écouteur 'admin-share-tracked',
    // jamais rendu pour un visiteur non-admin, quel que soit l'état des colonnes *_shared_at.
    $response->assertDontSee('admin-share-tracked', escape: false);
    $response->assertDontSee('linkedin_shared_at', escape: false);
    $response->assertDontSee('facebook_shared_at', escape: false);
});

// ── (f) Validation platform invalide ────────────────────────────────────────

it('une plateforme invalide est rejetée avec un 404', function () {
    $article = nastArticle(nastSource()->id, 'BADPLATFORM');
    $this->actingAs(nastSuperAdmin());

    $response = $this->post(route('admin.news.articles.mark-shared', ['article' => $article, 'platform' => 'twitter']));

    $response->assertStatus(404);
    expect($article->fresh()->linkedin_shared_at)->toBeNull();
    expect($article->fresh()->facebook_shared_at)->toBeNull();
});
