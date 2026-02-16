<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SEO\Services\SeoService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('seo service is registered as singleton', function () {
    $service1 = app(SeoService::class);
    $service2 = app(SeoService::class);

    expect($service1)->toBeInstanceOf(SeoService::class);
    expect($service1)->toBe($service2);
});

test('seo service can set and get title', function () {
    $seo = app(SeoService::class);

    $seo->setTitle('Mon site');

    expect($seo->getTitle())->toBe('Mon site');
});

test('seo service can set and get description', function () {
    $seo = app(SeoService::class);

    $seo->setDescription('Description du site');

    expect($seo->getDescription())->toBe('Description du site');
});

test('seo service generates meta tags array', function () {
    $seo = new SeoService;

    $seo->setTitle('Test')
        ->setDescription('Desc')
        ->setKeywords('a, b, c');

    $tags = $seo->getMetaTags();

    expect($tags)->toHaveKey('title', 'Test');
    expect($tags)->toHaveKey('description', 'Desc');
    expect($tags)->toHaveKey('keywords', 'a, b, c');
    expect($tags)->toHaveKey('og:title', 'Test');
});

test('seo service renders meta tags html', function () {
    $seo = new SeoService;

    $seo->setTitle('Test Page')
        ->setDescription('A test page');

    $html = $seo->renderMetaTags();

    expect($html)->toContain('<title>Test Page</title>');
    expect($html)->toContain('name="description"');
    expect($html)->toContain('property="og:title"');
});

test('seo service generates robots txt', function () {
    $seo = new SeoService;

    $robots = $seo->generateRobotsTxt(true);

    expect($robots)->toContain('User-agent: *');
    expect($robots)->toContain('Allow: /');
    expect($robots)->toContain('Sitemap:');
});

test('robots txt endpoint returns text', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

test('sitemap xml endpoint returns xml', function () {
    $this->get('/sitemap.xml')
        ->assertOk();
});

test('sitemap package is available', function () {
    expect(class_exists(\Spatie\Sitemap\Sitemap::class))->toBeTrue();
});
