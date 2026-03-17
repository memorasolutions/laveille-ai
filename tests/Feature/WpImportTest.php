<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Tag;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
});

function wxrFixturePath(): string
{
    return base_path('tests/Fixtures/test-wxr.xml');
}

// --- Validation ---

it('fails without --file option', function () {
    $this->artisan('wp:import')
        ->assertExitCode(1);
});

it('fails with non-existent file', function () {
    $this->artisan('wp:import', ['--file' => '/tmp/does-not-exist.xml'])
        ->assertExitCode(1);
});

// --- Dry run ---

it('dry run imports nothing to database', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--dry-run' => true])
        ->assertExitCode(0);

    expect(Article::count())->toBe(0)
        ->and(StaticPage::count())->toBe(0)
        ->and(Category::count())->toBe(0)
        ->and(Tag::count())->toBe(0);
});

// --- Posts ---

it('imports posts with correct status mapping', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--types' => 'posts']);

    $published = Article::where('slug->'.app()->getLocale(), 'published-article')->first();
    $draft = Article::where('slug->'.app()->getLocale(), 'draft-article')->first();
    $pending = Article::where('slug->'.app()->getLocale(), 'pending-article')->first();

    expect($published)->not->toBeNull()
        ->and((string) $published->status)->toBe('published')
        ->and((string) $draft->status)->toBe('draft')
        ->and((string) $pending->status)->toBe('pending_review');
});

it('stores wp_id in article meta for idempotency', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--types' => 'posts']);

    $article = Article::where('slug->'.app()->getLocale(), 'published-article')->first();

    expect($article->meta)->toBeArray()
        ->and($article->meta['wp_id'])->toBe('100');
});

// --- Pages ---

it('imports pages as StaticPage records', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--types' => 'pages']);

    expect(StaticPage::where('slug->'.app()->getLocale(), 'about-us')->exists())->toBeTrue()
        ->and(Article::count())->toBe(0);
});

// --- Categories & Tags ---

it('imports categories', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--types' => 'categories']);

    $locale = app()->getLocale();

    expect(Category::where("slug->{$locale}", 'tech')->exists())->toBeTrue()
        ->and(Category::where("slug->{$locale}", 'news')->exists())->toBeTrue()
        ->and(Category::count())->toBe(2);
});

it('imports tags', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--types' => 'tags']);

    expect(Tag::where('slug', 'laravel')->exists())->toBeTrue()
        ->and(Tag::count())->toBe(1);
});

// --- Selective import ---

it('--types=posts only imports posts', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--types' => 'posts']);

    expect(Article::count())->toBeGreaterThan(0)
        ->and(StaticPage::count())->toBe(0)
        ->and(Category::count())->toBe(0)
        ->and(Tag::count())->toBe(0);
});

// --- Idempotency ---

it('running import twice does not duplicate records', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath()]);

    $counts = [
        'articles' => Article::count(),
        'pages' => StaticPage::count(),
        'categories' => Category::count(),
        'tags' => Tag::count(),
    ];

    $this->artisan('wp:import', ['--file' => wxrFixturePath()]);

    expect(Article::count())->toBe($counts['articles'])
        ->and(StaticPage::count())->toBe($counts['pages'])
        ->and(Category::count())->toBe($counts['categories'])
        ->and(Tag::count())->toBe($counts['tags']);
});

// --- Shortcode stripping ---

it('strips WordPress shortcodes from content', function () {
    $this->artisan('wp:import', ['--file' => wxrFixturePath(), '--types' => 'posts']);

    $article = Article::where('slug->'.app()->getLocale(), 'shortcode-article')->first();

    expect($article->content)->not->toContain('[gallery')
        ->and($article->content)->not->toContain('[caption]')
        ->and($article->content)->toContain('Before')
        ->and($article->content)->toContain('middle')
        ->and($article->content)->toContain('photo')
        ->and($article->content)->toContain('after.');
});
