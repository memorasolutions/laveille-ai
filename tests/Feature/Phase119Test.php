<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Article::factory()->create(['status' => 'published', 'slug' => 'mon-article', 'title' => 'Mon Article', 'content' => 'Contenu', 'published_at' => now()]);
    StaticPage::factory()->create(['status' => 'published', 'slug' => 'ma-page', 'title' => 'Ma Page', 'content' => 'Contenu']);
});

it('sitemap.xml returns 200', function () {
    $this->get('/sitemap.xml')->assertOk();
});

it('sitemap contains the homepage URL', function () {
    $this->get('/sitemap.xml')->assertSee('/');
});

it('sitemap contains the published article URL', function () {
    $this->get('/sitemap.xml')->assertSee('mon-article');
});

it('sitemap contains the published static page URL', function () {
    $this->get('/sitemap.xml')->assertSee('ma-page');
});

it('sitemap does not contain draft page URL', function () {
    StaticPage::factory()->create(['status' => 'draft', 'slug' => 'draft-page', 'title' => 'Draft', 'content' => 'Contenu']);
    $this->get('/sitemap.xml')->assertDontSee('draft-page');
});

it('robots.txt returns 200', function () {
    $this->get('/robots.txt')->assertOk();
});

it('robots.txt contains sitemap url', function () {
    $this->get('/robots.txt')->assertSee('sitemap.xml');
});
