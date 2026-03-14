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
use Modules\Blog\Models\Comment;
use Modules\Blog\States\ApprovedCommentState;
use Modules\Blog\States\ArchivedArticleState;
use Modules\Blog\States\DraftArticleState;
use Modules\Blog\States\PendingCommentState;
use Modules\Blog\States\PublishedArticleState;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\States\DraftCampaignState;
use Modules\Newsletter\States\SentCampaignState;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

uses(RefreshDatabase::class);

// ── Article states ───────────────────────────────────────────────────────────

it('article has default draft state', function () {
    $article = Article::factory()->create(['user_id' => User::factory(), 'status' => 'draft']);

    expect($article->status)->toBeInstanceOf(DraftArticleState::class);
});

it('article transitions draft to published', function () {
    $article = Article::factory()->create(['user_id' => User::factory(), 'status' => 'draft', 'published_at' => null]);

    $article->status->transitionTo(PublishedArticleState::class);
    $article->refresh();

    expect($article->status)->toBeInstanceOf(PublishedArticleState::class)
        ->and($article->published_at)->not->toBeNull();
});

it('article transitions published to draft', function () {
    $article = Article::factory()->create(['user_id' => User::factory(), 'status' => 'published', 'published_at' => now()]);

    $article->status->transitionTo(DraftArticleState::class);
    $article->refresh();

    expect($article->status)->toBeInstanceOf(DraftArticleState::class);
});

it('article transitions draft to archived', function () {
    $article = Article::factory()->create(['user_id' => User::factory(), 'status' => 'draft']);

    $article->status->transitionTo(ArchivedArticleState::class);
    $article->refresh();

    expect($article->status)->toBeInstanceOf(ArchivedArticleState::class);
});

it('article cannot transition from archived to published', function () {
    $article = Article::factory()->create(['user_id' => User::factory(), 'status' => 'archived']);

    $article->status->transitionTo(PublishedArticleState::class);
})->throws(CouldNotPerformTransition::class);

it('article published scope works with states', function () {
    Article::factory()->create(['user_id' => User::factory(), 'status' => 'published', 'published_at' => now()->subDay()]);
    Article::factory()->create(['user_id' => User::factory(), 'status' => 'draft']);

    expect(Article::published()->count())->toBe(1);
});

// ── Comment states ───────────────────────────────────────────────────────────

it('comment has default pending state', function () {
    $article = Article::factory()->create(['user_id' => User::factory()]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'status' => 'pending']);

    expect($comment->status)->toBeInstanceOf(PendingCommentState::class);
});

it('comment transitions pending to approved', function () {
    $article = Article::factory()->create(['user_id' => User::factory()]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'status' => 'pending']);

    $comment->status->transitionTo(ApprovedCommentState::class);
    $comment->refresh();

    expect($comment->status)->toBeInstanceOf(ApprovedCommentState::class);
});

it('comment cannot transition from spam to approved', function () {
    $article = Article::factory()->create(['user_id' => User::factory()]);
    $comment = Comment::factory()->create(['article_id' => $article->id, 'status' => 'spam']);

    $comment->status->transitionTo(ApprovedCommentState::class);
})->throws(CouldNotPerformTransition::class);

it('comment approved scope works with states', function () {
    $article = Article::factory()->create(['user_id' => User::factory()]);
    Comment::factory()->create(['article_id' => $article->id, 'status' => 'approved']);
    Comment::factory()->create(['article_id' => $article->id, 'status' => 'pending']);

    expect(Comment::approved()->count())->toBe(1);
});

// ── Campaign states ──────────────────────────────────────────────────────────

it('campaign has default draft state', function () {
    $campaign = Campaign::factory()->create();

    expect($campaign->status)->toBeInstanceOf(DraftCampaignState::class);
});

it('campaign transitions draft to sent', function () {
    $campaign = Campaign::factory()->create(['status' => 'draft']);

    $campaign->status->transitionTo(SentCampaignState::class);
    $campaign->refresh();

    expect($campaign->status)->toBeInstanceOf(SentCampaignState::class);
});

it('campaign cannot transition from sent to draft', function () {
    $campaign = Campaign::factory()->create(['status' => 'sent']);

    $campaign->status->transitionTo(DraftCampaignState::class);
})->throws(CouldNotPerformTransition::class);
