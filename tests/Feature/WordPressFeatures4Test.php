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

// NOTE (2026-07): le commit 5c98d557 (« fix(blog): add missing Draft→Published state
// transition ») a délibérément ouvert la transition directe Draft→Published (via
// PublishTransition) pour que l'action admin « Publier » (route
// /admin/blog/articles/{slug}/publish, cf. tests/Feature/BlogAdminTest.php) puisse
// publier un brouillon sans passer par pending_review. La route pending_review reste
// un chemin optionnel (workflow éditorial étendu), pas une étape obligatoire. Le test
// ci-dessous vérifiait l'ancien comportement (obligatoire) ; mis à jour pour refléter
// la règle produit actuelle : les deux transitions sont permises depuis draft.
it('draft article can transition to pending_review or be published directly by an admin', function () {
    $article = Article::factory()->create(['status' => 'draft']);

    expect($article->status->canTransitionTo(PendingReviewArticleState::class))->toBeTrue()
        ->and($article->status->canTransitionTo(PublishedArticleState::class))->toBeTrue();
});
