<?php
declare(strict_types=1);

use Modules\Directory\Services\ToolDiscoveryService;

test('isUrlExcluded blocks Hacker News', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://news.ycombinator.com/item?id=12345'))->toBeTrue();
});

test('isUrlExcluded blocks YouTube videos', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://www.youtube.com/watch?v=DGoI6T3SGfY'))->toBeTrue();
});

test('isUrlExcluded blocks youtu.be short links', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://youtu.be/abc123'))->toBeTrue();
});

test('isUrlExcluded blocks github.io personal projects', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://graomelo.github.io/'))->toBeTrue();
});

test('isUrlExcluded blocks framer.website landings', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://onaai.framer.website'))->toBeTrue();
});

test('isUrlExcluded blocks blog paths', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://example.com/blog/post-title'))->toBeTrue();
});

test('isUrlExcluded blocks article paths', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://example.com/article/something'))->toBeTrue();
});

test('isUrlExcluded blocks research papers', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://research.google/blog/test/'))->toBeTrue();
});

test('isUrlExcluded blocks medium articles', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://medium.com/@user/article'))->toBeTrue();
});

test('isUrlExcluded blocks substack newsletters', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://author.substack.com/p/post'))->toBeTrue();
});

test('isUrlExcluded blocks reddit threads', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://www.reddit.com/r/programming'))->toBeTrue();
});

test('isUrlExcluded allows legitimate SaaS domains', function () {
    expect(ToolDiscoveryService::isUrlExcluded('https://www.taskshell.app/'))->toBeFalse();
    expect(ToolDiscoveryService::isUrlExcluded('https://midjourney.com'))->toBeFalse();
    expect(ToolDiscoveryService::isUrlExcluded('https://chat.openai.com'))->toBeFalse();
});

test('isUrlExcluded rejects malformed URL', function () {
    expect(ToolDiscoveryService::isUrlExcluded('not-a-url'))->toBeTrue();
});
