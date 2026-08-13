<?php

declare(strict_types=1);

use Modules\News\Services\DedupService;
use Modules\News\Services\RssFetcherService;

// DedupService lit ses listes de mots vides depuis config('news.fusion.*') depuis le
// 2026-08-13 (elles étaient codées en dur) : le conteneur Laravel est donc requis ici.
uses(Tests\TestCase::class);

test('RssFetcherService class exists', function () {
    expect(class_exists(RssFetcherService::class))->toBeTrue();
});

test('DedupService isLikelyDuplicate returns expected array structure', function () {
    $signals = [];
    $result = DedupService::isLikelyDuplicate(
        ['url' => 'https://example.com/a', 'title' => 'Test A', 'published_at' => '2024-06-01T12:00:00Z'],
        ['url' => 'https://example.com/b', 'title' => 'Test B', 'published_at' => '2024-06-01T12:05:00Z'],
        $signals
    );

    expect(array_keys($result))->toBe(['is_duplicate', 'score', 'reason', 'signals']);
});

test('DedupService isLikelyDuplicate detects identical normalized_url + title fuzzy as multi_signal duplicate', function () {
    $now = now()->toIso8601String();
    $signals = [];
    $result = DedupService::isLikelyDuplicate(
        [
            'url' => 'https://example.com/article',
            'title' => 'Breaking News: Major Event Happens',
            'published_at' => $now,
            'source_language' => 'en',
        ],
        [
            'url' => 'https://example.com/article',
            'title' => 'Breaking News: Major Event Happens',
            'published_at' => $now,
            'source_language' => 'en',
        ],
        $signals
    );

    expect($result['is_duplicate'])->toBeTrue();
    // URL normalisée identique : c'est le signal le plus fort, donc le reason courant.
    expect($result['reason'])->toBe('normalized_url_match');
});
