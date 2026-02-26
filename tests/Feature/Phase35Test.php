<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makePhase35Admin(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

test('articles table exists', function () {
    expect(\Illuminate\Support\Facades\Schema::hasTable('articles'))->toBeTrue();
});

test('article model has correct fillable', function () {
    $article = new Article;
    expect($article->getFillable())->toContain('title')
        ->and($article->getFillable())->toContain('content')
        ->and($article->getFillable())->toContain('featured_image')
        ->and($article->getFillable())->toContain('status');
});

test('article factory creates valid article', function () {
    $article = Article::factory()->published()->create();
    expect((string) $article->status)->toBe('published')
        ->and($article->published_at)->not->toBeNull()
        ->and($article->slug)->not->toBeEmpty();
});

test('article slug auto-generated from title', function () {
    $article = Article::factory()->create(['title' => 'Mon Article de Test', 'slug' => null]);
    expect($article->slug)->toBe('mon-article-de-test');
});

test('can list articles in backoffice', function () {
    Article::factory()->count(3)->create();
    $this->actingAs(makePhase35Admin())
        ->get('/admin/blog/articles')
        ->assertOk();
});

test('can access create article page', function () {
    $this->actingAs(makePhase35Admin())
        ->get('/admin/blog/articles/create')
        ->assertOk();
});

test('can create article', function () {
    $this->actingAs(makePhase35Admin())
        ->post('/admin/blog/articles', [
            'title' => 'Test Article',
            'content' => '<p>Contenu de test</p>',
            'status' => 'draft',
        ])
        ->assertRedirect('/admin/blog/articles');
    expect(Article::where('title->'.app()->getLocale(), 'Test Article')->exists())->toBeTrue();
});

test('can edit article', function () {
    $article = Article::factory()->create();
    $this->actingAs(makePhase35Admin())
        ->get('/admin/blog/articles/'.$article->slug.'/edit')
        ->assertOk();
});

test('can update article', function () {
    $article = Article::factory()->create(['title' => 'Original']);
    $this->actingAs(makePhase35Admin())
        ->put('/admin/blog/articles/'.$article->slug, [
            'title' => 'Updated',
            'content' => '<p>New content</p>',
            'status' => 'draft',
        ])
        ->assertRedirect('/admin/blog/articles');
    expect($article->fresh()->title)->toBe('Updated');
});

test('can delete article (soft delete)', function () {
    $article = Article::factory()->create();
    $this->actingAs(makePhase35Admin())
        ->delete('/admin/blog/articles/'.$article->slug)
        ->assertRedirect('/admin/blog/articles');
    $this->assertSoftDeleted('articles', ['id' => $article->id]);
});

test('can publish article', function () {
    $article = Article::factory()->draft()->create();
    $this->actingAs(makePhase35Admin())
        ->post('/admin/blog/articles/'.$article->slug.'/publish')
        ->assertRedirect();
    expect((string) $article->fresh()->status)->toBe('published');
});

test('can unpublish article', function () {
    $article = Article::factory()->published()->create();
    $this->actingAs(makePhase35Admin())
        ->post('/admin/blog/articles/'.$article->slug.'/unpublish')
        ->assertRedirect();
    expect((string) $article->fresh()->status)->toBe('draft');
});

test('unauthenticated redirected from blog admin', function () {
    $this->get('/admin/blog/articles')->assertRedirect('/login');
});
