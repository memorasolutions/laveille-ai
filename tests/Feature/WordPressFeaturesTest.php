<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

// --- Article features ---

it('article has is_featured boolean cast', function () {
    $article = Article::factory()->create(['is_featured' => false]);
    expect($article->is_featured)->toBeFalse()->toBeBool();

    $article->update(['is_featured' => true]);
    expect($article->fresh()->is_featured)->toBeTrue();
});

it('article has format default standard', function () {
    $article = Article::factory()->create();
    expect($article->fresh()->format)->toBe('standard');
});

it('article scopeFeatured returns only featured', function () {
    Article::factory()->create(['is_featured' => false]);
    Article::factory()->create(['is_featured' => true]);

    expect(Article::featured()->count())->toBe(1);
});

it('article scopeByFormat filters by format', function () {
    Article::factory()->create(['format' => 'standard']);
    Article::factory()->create(['format' => 'video']);
    Article::factory()->create(['format' => 'video']);

    expect(Article::byFormat('video')->count())->toBe(2);
});

it('article isPasswordProtected returns true when password set', function () {
    $article = Article::factory()->create(['content_password' => 'secret']);
    expect($article->isPasswordProtected())->toBeTrue();

    $article2 = Article::factory()->create(['content_password' => null]);
    expect($article2->isPasswordProtected())->toBeFalse();
});

// --- StaticPage features ---

it('static page has content_password', function () {
    $page = StaticPage::factory()->create(['content_password' => 'mypassword']);
    expect($page->content_password)->toBe('mypassword');
    expect($page->isPasswordProtected())->toBeTrue();
});

// --- User Gravatar ---

it('user avatar_url returns gravatar when no avatar', function () {
    $user = User::factory()->create(['avatar' => null]);
    expect($user->avatar_url)->toContain('gravatar.com');
});

it('user avatar_url returns storage path when avatar set', function () {
    $user = User::factory()->create(['avatar' => 'avatars/test.jpg']);
    expect($user->avatar_url)->toContain('storage/avatars/test.jpg');
});

// --- oEmbed endpoint ---

it('oembed endpoint returns article data', function () {
    $article = Article::factory()->create([
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);
    $url = url('/blog/'.$article->slug);

    $this->get('/oembed?url='.urlencode($url))
        ->assertOk()
        ->assertJsonPath('title', $article->title)
        ->assertJsonPath('type', 'rich')
        ->assertJsonPath('version', '1.0');
});

it('oembed endpoint returns 404 for unknown slug', function () {
    $this->get('/oembed?url='.urlencode(url('/blog/nonexistent-slug')))
        ->assertNotFound();
});
