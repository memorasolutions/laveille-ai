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

test('extractKeyEntities captures capitalized words and known acronyms', function () {
    $entities = DedupService::extractKeyEntities('Microsoft puts an AI legal agent inside Word for contract review');
    expect($entities)->toContain('microsoft')
        ->and($entities)->toContain('word')
        ->and($entities)->toContain('AI');
});

test('jaccardKeywords excludes french and english stopwords', function () {
    $a = 'Microsoft lance un nouvel agent IA dans Word pour les contrats';
    $b = 'Microsoft a lance un agent IA dans Word pour les documents';
    expect(DedupService::jaccardKeywords($a, $b))->toBeGreaterThan(0.5);
});

test('keyEntitiesIntersectionCount finds shared brand entities cross language', function () {
    $a = 'Microsoft puts an AI legal agent inside Word for contract review';
    $b = 'Microsoft veut que les avocats utilisent son nouvel agent IA dans Word';
    expect(DedupService::keyEntitiesIntersectionCount($a, $b))->toBeGreaterThanOrEqual(3);
});

test('isLikelyDuplicate detects Microsoft Word legal agent cross source via entities and similarity', function () {
    $newArticle = [
        'url' => 'https://thedecoder.com/microsoft-word-legal-agent',
        'title' => 'Microsoft puts an AI legal agent inside Word for contract review',
        'published_at' => '2026-05-01 09:15:00',
        'source_language' => 'en',
    ];
    $candidate = [
        'url' => 'https://theverge.com/microsoft-legal-agent-word',
        'title' => 'Microsoft wants lawyers to trust its new AI agent in Word documents',
        'published_at' => '2026-05-01 08:15:00',
        'source_language' => 'en',
    ];
    $result = DedupService::isLikelyDuplicate($newArticle, $candidate);
    expect($result['is_duplicate'])->toBeTrue()
        ->and($result['signals'])->toHaveKey('key_entities_match')
        ->and($result['reason'])->toBe('multi_core');
});

test('isLikelyDuplicate avoids false positive on short generic titles with one shared entity', function () {
    $newArticle = [
        'url' => 'https://a.com/apple-news',
        'title' => 'Apple announces something today',
        'published_at' => '2026-05-01 10:00:00',
        'source_language' => 'en',
    ];
    $candidate = [
        'url' => 'https://b.com/apple-tax',
        'title' => 'Apple sued over App Store fees',
        'published_at' => '2026-05-01 11:00:00',
        'source_language' => 'en',
    ];
    $result = DedupService::isLikelyDuplicate($newArticle, $candidate);
    expect($result['is_duplicate'])->toBeFalse();
});

test('isLikelyDuplicate avoids false positive on different topics same brand', function () {
    $newArticle = [
        'url' => 'https://a.com/google-search',
        'title' => 'Google updates Search ranking algorithm',
        'published_at' => '2026-05-01 10:00:00',
        'source_language' => 'en',
    ];
    $candidate = [
        'url' => 'https://b.com/google-pixel',
        'title' => 'Google announces Pixel 11 launch event',
        'published_at' => '2026-05-01 11:00:00',
        'source_language' => 'en',
    ];
    $result = DedupService::isLikelyDuplicate($newArticle, $candidate);
    expect($result['is_duplicate'])->toBeFalse();
});

test('isLikelyDuplicate respects 24h temporal window', function () {
    $newArticle = [
        'url' => 'https://a.com/x',
        'title' => 'Microsoft launches AI agent in Word for legal review',
        'published_at' => '2026-05-01 10:00:00',
        'source_language' => 'en',
    ];
    $candidate = [
        'url' => 'https://b.com/y',
        'title' => 'Microsoft launches AI agent in Word for legal review',
        'published_at' => '2026-04-25 10:00:00',
        'source_language' => 'en',
    ];
    $result = DedupService::isLikelyDuplicate($newArticle, $candidate);
    expect($result['is_duplicate'])->toBeFalse();
});
