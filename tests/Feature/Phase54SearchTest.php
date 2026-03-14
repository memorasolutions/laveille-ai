<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\Searchable;
use Modules\Blog\Models\Article;
use Modules\Blog\States\DraftArticleState;
use Modules\Blog\States\PublishedArticleState;

uses(RefreshDatabase::class);

test('Article uses Searchable trait', function () {
    $traits = class_uses_recursive(Article::class);
    expect($traits)->toHaveKey(Searchable::class);
});

test('toSearchableArray returns expected keys', function () {
    $article = Article::factory()->create([
        'status' => DraftArticleState::class,
    ]);
    $array = $article->toSearchableArray();
    expect($array)->toHaveKeys(['title', 'content', 'excerpt', 'category', 'tags']);
});

test('shouldBeSearchable is false for draft article', function () {
    $article = Article::factory()->create([
        'status' => DraftArticleState::class,
    ]);
    expect($article->shouldBeSearchable())->toBeFalse();
});

test('shouldBeSearchable is true for published article', function () {
    $article = Article::factory()->create([
        'status' => PublishedArticleState::class,
        'published_at' => now(),
    ]);
    expect($article->shouldBeSearchable())->toBeTrue();
});

test('Search config includes Article model', function () {
    expect(config('search.models'))->toContain(Article::class);
});

test('Article factory creates searchable published article', function () {
    $article = Article::factory()->create([
        'status' => PublishedArticleState::class,
        'published_at' => now(),
    ]);
    expect($article->shouldBeSearchable())->toBeTrue();
    expect($article->toSearchableArray())->toBeArray();
});
