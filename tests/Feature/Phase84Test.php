<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('user articles index loads', function () {
    $this->get(route('user.articles.index'))
        ->assertStatus(200)
        ->assertSee('Mes articles');
});

it('user articles index shows own articles', function () {
    Article::factory()->create(['user_id' => $this->user->id, 'title' => 'Article personnel']);

    $this->get(route('user.articles.index'))
        ->assertStatus(200)
        ->assertSee('Article personnel');
});

it('user articles index does not show other users articles', function () {
    $other = User::factory()->create();
    Article::factory()->create(['user_id' => $other->id, 'title' => 'Article d\'un autre']);

    $this->get(route('user.articles.index'))
        ->assertStatus(200)
        ->assertDontSee('Article d\'un autre');
});

it('create article page loads', function () {
    $this->get(route('user.articles.create'))
        ->assertStatus(200)
        ->assertSee('Nouvel article');
});

it('user can store a new article', function () {
    $this->post(route('user.articles.store'), [
        'title' => 'Mon premier article',
        'content' => 'Contenu de test suffisamment long.',
        'status' => 'draft',
    ])->assertRedirect(route('user.articles.index'));

    expect(Article::where('title->'.app()->getLocale(), 'Mon premier article')
        ->where('user_id', $this->user->id)
        ->where('status', 'draft')
        ->exists())->toBeTrue();
});

it('store validates required fields', function () {
    $this->post(route('user.articles.store'), [])
        ->assertSessionHasErrors(['title', 'content', 'status']);
});

it('user can edit own article', function () {
    $article = Article::factory()->create(['user_id' => $this->user->id]);

    $this->get(route('user.articles.edit', $article))
        ->assertStatus(200)
        ->assertSee('Modifier');
});

it('user cannot edit other users article', function () {
    $other = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $other->id]);

    $this->get(route('user.articles.edit', $article))
        ->assertStatus(403);
});

it('user can update own article', function () {
    $article = Article::factory()->create(['user_id' => $this->user->id]);

    $this->put(route('user.articles.update', $article), [
        'title' => 'Titre modifié',
        'content' => 'Contenu mis à jour.',
        'status' => 'published',
    ])->assertRedirect(route('user.articles.index'));

    expect(Article::where('id', $article->id)
        ->where('title->'.app()->getLocale(), 'Titre modifié')
        ->exists())->toBeTrue();
});

it('user cannot update other users article', function () {
    $other = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $other->id]);

    $this->put(route('user.articles.update', $article), [
        'title' => 'Titre piraté',
        'content' => 'Contenu piraté.',
        'status' => 'published',
    ])->assertStatus(403);
});

it('user can delete own article', function () {
    $article = Article::factory()->create(['user_id' => $this->user->id]);

    $this->delete(route('user.articles.destroy', $article))
        ->assertRedirect(route('user.articles.index'));

    $this->assertSoftDeleted('articles', ['id' => $article->id]);
});

it('user cannot delete other users article', function () {
    $other = User::factory()->create();
    $article = Article::factory()->create(['user_id' => $other->id]);

    $this->delete(route('user.articles.destroy', $article))
        ->assertStatus(403);
});

it('unauthenticated user is redirected from articles', function () {
    auth()->logout();
    $this->get(route('user.articles.index'))
        ->assertRedirect(route('login'));
});

it('articles nav link is present in user layout', function () {
    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('Mes articles');
});

it('user can store article with tags via CSV', function () {
    $this->post(route('user.articles.store'), [
        'title' => 'Article avec tags',
        'content' => 'Contenu avec des tags.',
        'status' => 'draft',
        'tags_input' => 'laravel, php, tutorial',
    ])->assertRedirect(route('user.articles.index'));

    $article = Article::where('title->'.app()->getLocale(), 'Article avec tags')->first();
    expect($article->tags)->toContain('laravel');
});
