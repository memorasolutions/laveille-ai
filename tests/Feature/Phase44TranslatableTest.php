<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Pages\Models\StaticPage;
use Modules\SEO\Models\MetaTag;

uses(RefreshDatabase::class);

// --- Article translatable ---

it('article can set and get translations', function () {
    $article = Article::factory()->create();
    $article->setTranslation('title', 'fr', 'Titre en français');
    $article->setTranslation('title', 'en', 'Title in English');
    $article->save();

    expect($article->getTranslation('title', 'en'))->toBe('Title in English')
        ->and($article->getTranslation('title', 'fr'))->toBe('Titre en français');
});

it('article falls back to default locale', function () {
    App::setLocale('en');
    $article = Article::factory()->create(['title' => 'English Only']);

    // Requesting German should fallback to current locale (en)
    expect($article->getTranslation('title', 'de', true))->toBe('English Only');
});

it('article resolveRouteBinding finds by translated slug', function () {
    $article = Article::factory()->create(['slug' => 'my-test-slug']);

    $resolved = (new Article)->resolveRouteBinding('my-test-slug');

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($article->id);
});

// --- Category translatable ---

it('category name is translatable', function () {
    $category = Category::factory()->create();
    $category->setTranslation('name', 'en', 'Technology');
    $category->setTranslation('name', 'fr', 'Technologie');
    $category->save();

    // Reload fresh from DB
    $fresh = Category::find($category->id);

    expect($fresh->getTranslation('name', 'fr'))->toBe('Technologie')
        ->and($fresh->getTranslation('name', 'en'))->toBe('Technology');
});

it('category resolveRouteBinding finds by translated slug', function () {
    $category = Category::factory()->create(['slug' => 'tech-slug']);

    $resolved = (new Category)->resolveRouteBinding('tech-slug');

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($category->id);
});

// --- StaticPage translatable ---

it('static page title and content are translatable', function () {
    $page = StaticPage::factory()->create([
        'title' => 'About Us',
        'content' => 'Our story',
    ]);
    $page->setTranslation('title', 'fr', 'À propos');
    $page->setTranslation('content', 'fr', 'Notre histoire');
    $page->save();

    App::setLocale('fr');
    expect($page->title)->toBe('À propos')
        ->and($page->content)->toBe('Notre histoire');
});

// --- MetaTag translatable ---

it('meta tag fields are translatable', function () {
    $meta = MetaTag::factory()->create([
        'title' => 'SEO Title',
        'description' => 'SEO Description',
        'og_title' => 'OG Title',
    ]);
    $meta->setTranslation('title', 'fr', 'Titre SEO');
    $meta->setTranslation('description', 'fr', 'Description SEO');
    $meta->setTranslation('og_title', 'fr', 'Titre OG');
    $meta->save();

    App::setLocale('fr');
    expect($meta->title)->toBe('Titre SEO')
        ->and($meta->description)->toBe('Description SEO')
        ->and($meta->og_title)->toBe('Titre OG');
});

// --- Factory compatibility ---

it('factory creates translatable model correctly', function () {
    $article = Article::factory()->create();

    // Factory sets plain string, HasTranslations wraps in current locale
    $locale = app()->getLocale();
    expect($article->getTranslation('title', $locale))->toBeString()
        ->and($article->getTranslation('title', $locale))->not->toBeEmpty();
});

// --- Locale switcher ---

it('locale route changes session locale', function () {
    $this->post(route('locale.switch', 'fr'))
        ->assertRedirect();

    expect(session('locale'))->toBe('fr');

    $this->post(route('locale.switch', 'en'))
        ->assertRedirect();

    expect(session('locale'))->toBe('en');
});

it('locale route rejects invalid locale', function () {
    $this->post(route('locale.switch', 'de'))
        ->assertStatus(400);
});

// --- JSON query on translatable columns ---

it('can query translatable columns with JSON syntax', function () {
    $locale = app()->getLocale();
    Article::factory()->create(['title' => 'Searchable Title']);

    $found = Article::where("title->{$locale}", 'Searchable Title')->first();

    expect($found)->not->toBeNull()
        ->and($found->title)->toBe('Searchable Title');
});

it('assertDatabaseHas works with JSON path on translatable', function () {
    $locale = app()->getLocale();
    $page = StaticPage::factory()->create(['title' => 'Contact Page']);

    $this->assertDatabaseHas('static_pages', [
        'id' => $page->id,
        "title->{$locale}" => 'Contact Page',
    ]);
});
