<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - NewsToolsQuickEditModal (Livewire) : popup rapide « Outils liés »
 * depuis la liste /actualites (icône engrenage).
 *
 * Prouve :
 *  - SÉCURITÉ (OWASP A01) : open() refuse un utilisateur sans view_admin_panel ;
 *  - le wrapper affiche bien l'ArticleToolsEditor de l'actualité CHOISIE (pas
 *    une autre) - test anti-mélange d'actualités ;
 *  - la liste publique /actualites affiche le bouton engrenage pour un admin
 *    et le masque pour un visiteur/utilisateur non-admin.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\News\Livewire\NewsToolsQuickEditModal;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function ntqmSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source ntqm',
        'url' => 'https://ntqm-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function ntqmArticle(int $sourceId, string $suffix = 'A'): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => "Article ntqm {$suffix}",
        'guid' => "guid-ntqm-{$suffix}",
        'url' => "https://exemple.com/ntqm-{$suffix}",
        'description' => "Description de test ntqm {$suffix}",
        'slug' => "article-ntqm-" . strtolower($suffix),
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);
}

function ntqmAdmin(): User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function ntqmUser(): User
{
    return User::factory()->create(['email_verified_at' => now()]);
}

// ── SÉCURITÉ ──────────────────────────────────────────────────────────────────

it('open refuse un visiteur non authentifié (403)', function () {
    $source = ntqmSource();
    $article = ntqmArticle($source->id, 'SEC1');

    Livewire::test(NewsToolsQuickEditModal::class)
        ->call('open', $article->id)
        ->assertForbidden();
});

it('open refuse un utilisateur sans permission view_admin_panel (403)', function () {
    $user = ntqmUser();
    $source = ntqmSource();
    $article = ntqmArticle($source->id, 'SEC2');

    $this->actingAs($user);

    Livewire::test(NewsToolsQuickEditModal::class)
        ->call('open', $article->id)
        ->assertForbidden();
});

// ── AIGUILLAGE : la bonne actualité, jamais un mélange ───────────────────────

it('open définit articleId sur l\'actualité choisie par l\'admin', function () {
    $admin = ntqmAdmin();
    $source = ntqmSource();
    $articleA = ntqmArticle($source->id, 'PICKA');
    $articleB = ntqmArticle($source->id, 'PICKB');

    $this->actingAs($admin);

    Livewire::test(NewsToolsQuickEditModal::class)
        ->call('open', $articleA->id)
        ->assertSet('articleId', $articleA->id)
        ->call('open', $articleB->id)
        ->assertSet('articleId', $articleB->id);
});

it('le rendu affiche bien l\'éditeur de l\'actualité active (pas un placeholder vide)', function () {
    $admin = ntqmAdmin();
    $source = ntqmSource();
    $article = ntqmArticle($source->id, 'RENDER');

    $this->actingAs($admin);

    Livewire::test(NewsToolsQuickEditModal::class)
        ->call('open', $article->id)
        ->assertSee($article->title);
});

it('sans article sélectionné, affiche le message d\'invite plutôt qu\'une erreur', function () {
    $admin = ntqmAdmin();

    $this->actingAs($admin);

    Livewire::test(NewsToolsQuickEditModal::class)
        ->assertSee(__('Sélectionnez une actualité pour gérer ses outils.'));
});

// ── VISIBILITÉ du bouton engrenage sur la liste publique ─────────────────────

it('liste des actualités : le bouton engrenage est visible pour un admin', function () {
    $admin = ntqmAdmin();
    $source = ntqmSource();
    ntqmArticle($source->id, 'GEARADMIN');

    $this->actingAs($admin);

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    // La classe CSS "nw-gear-btn" figure aussi dans le <style> inconditionnel du
    // partial : on vérifie le rendu RÉEL (aria/title du bouton), pas la classe.
    $response->assertSee(__('Gérer les outils liés'), false);
    $response->assertSee("Livewire.dispatch('open-news-tools-editor'", false);
});

it('liste des actualités : le bouton engrenage est absent pour un visiteur non authentifié', function () {
    $source = ntqmSource();
    ntqmArticle($source->id, 'GEARGUEST');

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    $response->assertDontSee(__('Gérer les outils liés'), false);
});

it('liste des actualités : le bouton engrenage est absent pour un utilisateur sans view_admin_panel', function () {
    $user = ntqmUser();
    $source = ntqmSource();
    ntqmArticle($source->id, 'GEARUSER');

    $this->actingAs($user);

    $response = $this->get(route('news.index'));

    $response->assertStatus(200);
    $response->assertDontSee(__('Gérer les outils liés'), false);
});
