<?php

declare(strict_types=1);

use Modules\News\Services\DedupService;

test('normalizeUrl strips utm tracking params', function () {
    $input = 'https://example.com/article?id=42&utm_source=twitter&utm_medium=social';
    expect(DedupService::normalizeUrl($input))->toBe('https://example.com/article?id=42');
});

test('normalizeUrl removes www prefix and standard ports', function () {
    $input = 'https://www.example.com:443/path/';
    expect(DedupService::normalizeUrl($input))->toBe('https://example.com/path');
});

test('normalizeUrl preserves non-tracking params sorted', function () {
    $input = 'https://example.com/?b=2&a=1';
    expect(DedupService::normalizeUrl($input))->toBe('https://example.com/?a=1&b=2');
});

test('extractCanonical finds rel canonical link tag', function () {
    $html = '<link rel="canonical" href="https://example.com/canon">';
    expect(DedupService::extractCanonical($html))->toBe('https://example.com/canon');
});

test('extractCanonical falls back to og url meta', function () {
    $html = '<meta property="og:url" content="https://example.com/og">';
    expect(DedupService::extractCanonical($html))->toBe('https://example.com/og');
});

test('titleSimilarity returns 1.0 for identical titles', function () {
    expect(DedupService::titleSimilarity('OpenAI lance GPT-5', 'OpenAI lance GPT-5'))->toBe(1.0);
});

test('titleSimilarity returns less than 0.6 for unrelated titles', function () {
    expect(DedupService::titleSimilarity('OpenAI launches GPT-5', 'Tesla earnings beat'))->toBeLessThan(0.6);
});

test('isLikelyDuplicate detects multi-signal duplicate via canonical and title fuzzy', function () {
    $newArticle = [
        'url' => 'https://a.com/article-1?utm_source=x',
        'canonical_url' => 'https://example.com/article',
        'title' => 'OpenAI lance GPT-5 aujourdhui',
        'published_at' => '2026-04-28 10:00:00',
        'source_language' => 'fr',
    ];
    $candidate = [
        'url' => 'https://b.com/article-different',
        'canonical_url' => 'https://example.com/article',
        'title' => 'OpenAI lance GPT-5 aujourdhui matin',
        'published_at' => '2026-04-28 11:00:00',
        'source_language' => 'fr',
    ];
    $result = DedupService::isLikelyDuplicate($newArticle, $candidate);
    expect($result['is_duplicate'])->toBeTrue()
        ->and($result['signals'])->toHaveKey('canonical_match')
        ->and($result['signals'])->toHaveKey('source_lang_match');
});
