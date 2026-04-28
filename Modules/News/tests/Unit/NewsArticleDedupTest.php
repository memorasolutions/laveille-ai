<?php

declare(strict_types=1);

use Modules\News\Models\NewsArticle;

test('NewsArticle fillable contains canonical_url', function () {
    expect((new NewsArticle())->getFillable())->toContain('canonical_url');
});

test('NewsArticle fillable contains is_potential_duplicate_of', function () {
    expect((new NewsArticle())->getFillable())->toContain('is_potential_duplicate_of');
});

test('NewsArticle fillable contains dedup_score and dedup_reason', function () {
    expect((new NewsArticle())->getFillable())->toContain('dedup_score')->toContain('dedup_reason');
});

test('NewsArticle casts is_potential_duplicate_of as integer', function () {
    expect((new NewsArticle())->getCasts())->toHaveKey('is_potential_duplicate_of', 'integer');
});

test('NewsArticle casts dedup_score as float', function () {
    expect((new NewsArticle())->getCasts())->toHaveKey('dedup_score', 'float');
});

test('NewsArticle has originalArticle BelongsTo and duplicates HasMany methods', function () {
    expect(method_exists(NewsArticle::class, 'originalArticle'))->toBeTrue();
    expect(method_exists(NewsArticle::class, 'duplicates'))->toBeTrue();
});

test('NewsArticle has scopePotentialDuplicates method', function () {
    expect(method_exists(NewsArticle::class, 'scopePotentialDuplicates'))->toBeTrue();
});
