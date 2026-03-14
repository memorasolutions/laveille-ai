<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\SaaS\Models\Plan;
use Modules\Settings\Models\Setting;
use Spatie\ResponseCache\Facades\ResponseCache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('article save clears response cache', function () {
    ResponseCache::spy();
    Article::factory()->create(['status' => 'published', 'published_at' => now()]);
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('setting save clears response cache', function () {
    ResponseCache::spy();
    Setting::set('test_cache_key', 'test_value');
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('plan save clears response cache', function () {
    ResponseCache::spy();
    Plan::factory()->create();
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('comment save clears response cache', function () {
    $article = Article::factory()->create(['status' => 'published', 'published_at' => now()]);

    ResponseCache::spy();
    Comment::factory()->create(['article_id' => $article->id]);
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('category save clears response cache', function () {
    ResponseCache::spy();
    Category::factory()->create();
    ResponseCache::shouldHaveReceived('clear')->atLeast()->once();
});

test('response cache middleware is registered', function () {
    $middlewareAliases = app(\Illuminate\Routing\Router::class)->getMiddleware();
    expect($middlewareAliases)->toHaveKey('cacheResponse');
    expect($middlewareAliases)->toHaveKey('doNotCacheResponse');
});
