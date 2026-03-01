<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SEO\Models\MetaTag;
use Modules\SEO\Services\SeoService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('meta tag model can be created', function () {
    MetaTag::create([
        'url_pattern' => '/',
        'title' => 'Accueil',
        'description' => 'Page d\'accueil du site',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('seo_meta_tags', ['url_pattern' => '/']);
    expect(MetaTag::where('title->'.app()->getLocale(), 'Accueil')->exists())->toBeTrue();
});

test('meta tag active scope works', function () {
    MetaTag::create(['url_pattern' => '/active', 'title' => 'Active', 'is_active' => true]);
    MetaTag::create(['url_pattern' => '/inactive', 'title' => 'Inactive', 'is_active' => false]);

    expect(MetaTag::active()->count())->toBe(1);
    expect(MetaTag::active()->first()->url_pattern)->toBe('/active');
});

test('meta tag findForUrl matches exact url', function () {
    MetaTag::create(['url_pattern' => '/about', 'title' => 'À propos', 'is_active' => true]);
    MetaTag::create(['url_pattern' => '/contact', 'title' => 'Contact', 'is_active' => true]);

    $tag = MetaTag::findForUrl('/about');

    expect($tag)->not->toBeNull();
    expect($tag->url_pattern)->toBe('/about');
});

test('meta tag findForUrl matches wildcard pattern', function () {
    MetaTag::create(['url_pattern' => '/blog/*', 'title' => 'Article de blog', 'is_active' => true]);

    $tag = MetaTag::findForUrl('/blog/my-post');

    expect($tag)->not->toBeNull();
    expect($tag->url_pattern)->toBe('/blog/*');
});

test('meta tag findForUrl returns null for no match', function () {
    MetaTag::create(['url_pattern' => '/about', 'title' => 'About', 'is_active' => true]);

    expect(MetaTag::findForUrl('/nonexistent'))->toBeNull();
});

test('seo service loads meta tags from database', function () {
    MetaTag::create([
        'url_pattern' => '/about',
        'title' => 'À propos - Mon site',
        'description' => 'Description depuis la DB',
        'is_active' => true,
    ]);

    $seo = new SeoService;
    $seo->loadFromUrl('/about');

    expect($seo->getTitle())->toBe('À propos - Mon site');
    expect($seo->getDescription())->toBe('Description depuis la DB');
});

test('seo service renders meta tags from database', function () {
    MetaTag::create([
        'url_pattern' => '/test-page',
        'title' => 'Test Page Title',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $seo = new SeoService;
    $html = $seo->loadFromUrl('/test-page')->renderMetaTags();

    expect($html)->toContain('<title>Test Page Title</title>');
    expect($html)->toContain('name="description"');
});

test('meta tag has correct default values', function () {
    $tag = MetaTag::create([
        'url_pattern' => '/defaults',
        'is_active' => true,
    ])->fresh();

    expect($tag->twitter_card)->toBe('summary_large_image');
    expect($tag->robots)->toBe('index, follow');
    expect($tag->is_active)->toBeTrue();
});

test('meta tag model uses correct table', function () {
    expect((new MetaTag)->getTable())->toBe('seo_meta_tags');
});
