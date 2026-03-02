<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

function previewAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

// ── Article Preview ──

test('admin peut voir le preview d\'un article draft', function () {
    $admin = previewAdmin();
    $article = Article::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.blog.articles.preview', $article));

    $response->assertOk();
    $response->assertSee($article->title);
    $response->assertSee('Apercu');
});

test('admin peut voir le preview d\'un article publie', function () {
    $admin = previewAdmin();
    $article = Article::factory()->published()->create();

    $response = $this->actingAs($admin)->get(route('admin.blog.articles.preview', $article));

    $response->assertOk();
});

test('guest est redirige vers login sur preview article', function () {
    $article = Article::factory()->create();

    $response = $this->get(route('admin.blog.articles.preview', $article));

    $response->assertRedirect(route('login'));
});

test('le preview article contient le lien retour vers l\'edition', function () {
    $admin = previewAdmin();
    $article = Article::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.blog.articles.preview', $article));

    $response->assertOk();
    $response->assertSee(route('admin.blog.articles.edit', $article));
});

// ── Page Preview ──

test('admin peut voir le preview d\'une page draft', function () {
    $admin = previewAdmin();
    $page = StaticPage::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.pages.preview', $page));

    $response->assertOk();
    $response->assertSee($page->title);
    $response->assertSee('Apercu');
});

test('admin peut voir le preview d\'une page publiee', function () {
    $admin = previewAdmin();
    $page = StaticPage::factory()->published()->create();

    $response = $this->actingAs($admin)->get(route('admin.pages.preview', $page));

    $response->assertOk();
});

test('guest est redirige vers login sur preview page', function () {
    $page = StaticPage::factory()->create();

    $response = $this->get(route('admin.pages.preview', $page));

    $response->assertRedirect(route('login'));
});

test('le preview page contient le lien retour vers l\'edition', function () {
    $admin = previewAdmin();
    $page = StaticPage::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.pages.preview', $page));

    $response->assertOk();
    $response->assertSee(route('admin.pages.edit', $page));
});
