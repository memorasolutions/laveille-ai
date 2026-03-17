<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\States\PendingReviewArticleState;
use Modules\Blog\States\PublishedArticleState;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

// --- Preview tokens ---

it('article gets preview_token on creation', function () {
    $article = Article::factory()->create();

    expect($article->preview_token)->not->toBeNull()
        ->and(strlen($article->preview_token))->toBe(64);
});

it('static page gets preview_token on creation', function () {
    $page = StaticPage::factory()->create(['status' => 'published']);

    expect($page->preview_token)->not->toBeNull()
        ->and(strlen($page->preview_token))->toBe(64);
});

it('preview URL returns article content', function () {
    $article = Article::factory()->create(['title' => 'Mon article test']);

    $this->get(route('preview.show', $article->preview_token))
        ->assertOk()
        ->assertSee('Mon article test');
});

it('preview URL returns 404 for invalid token', function () {
    $this->get(route('preview.show', str_repeat('x', 64)))
        ->assertNotFound();
});

// --- Editorial workflow ---

it('article can transition to pending_review', function () {
    $article = Article::factory()->create(['status' => 'draft']);
    $article->status->transitionTo(PendingReviewArticleState::class);

    expect((string) $article->fresh()->status)->toBe('pending_review');
});

it('draft article cannot directly transition to published', function () {
    $article = Article::factory()->create(['status' => 'draft']);

    expect($article->status->canTransitionTo(PendingReviewArticleState::class))->toBeTrue()
        ->and($article->status->canTransitionTo(PublishedArticleState::class))->toBeFalse();
});
