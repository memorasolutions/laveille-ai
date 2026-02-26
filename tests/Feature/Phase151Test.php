<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Category;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;
use Modules\Search\Services\SearchService;

uses(RefreshDatabase::class);

// --- Trait Searchable sur les 3 nouveaux modèles ---

it('has plan model as searchable', function () {
    expect(class_uses_recursive(Plan::class))
        ->toContain(\Laravel\Scout\Searchable::class);
});

it('has category model as searchable', function () {
    expect(class_uses_recursive(Category::class))
        ->toContain(\Laravel\Scout\Searchable::class);
});

it('has static page model as searchable', function () {
    expect(class_uses_recursive(StaticPage::class))
        ->toContain(\Laravel\Scout\Searchable::class);
});

// --- shouldBeSearchable ---

it('marks active plan as searchable', function () {
    $plan = Plan::factory()->create(['is_active' => true]);
    expect($plan->shouldBeSearchable())->toBeTrue();
});

it('excludes inactive plans from search', function () {
    $plan = Plan::factory()->create(['is_active' => false]);
    expect($plan->shouldBeSearchable())->toBeFalse();
});

it('marks published static page as searchable', function () {
    $page = StaticPage::factory()->create(['status' => 'published']);
    expect($page->shouldBeSearchable())->toBeTrue();
});

it('excludes draft pages from search', function () {
    $page = StaticPage::factory()->create(['status' => 'draft']);
    expect($page->shouldBeSearchable())->toBeFalse();
});

// --- toSearchableArray ---

it('plan returns correct searchable array', function () {
    $plan = Plan::factory()->create(['name' => 'Premium', 'description' => 'Best plan']);
    $array = $plan->toSearchableArray();
    expect($array)->toHaveKeys(['name', 'description']);
    expect($array['name'])->toBe('Premium');
});

it('category returns correct searchable array', function () {
    $category = Category::factory()->create(['name' => 'Laravel', 'description' => 'PHP framework']);
    $array = $category->toSearchableArray();
    expect($array)->toHaveKeys(['name', 'description']);
});

it('static page returns correct searchable array', function () {
    $page = StaticPage::factory()->create(['title' => 'About Us', 'content' => 'Welcome']);
    $array = $page->toSearchableArray();
    expect($array)->toHaveKeys(['title', 'content']);
});

// --- Config ---

it('search config includes all models', function () {
    $models = config('search.models');
    expect($models)->toHaveCount(6);
    expect($models)->toContain(\App\Models\User::class);
    expect($models)->toContain(\Modules\Blog\Models\Article::class);
    expect($models)->toContain(\Modules\SaaS\Models\Plan::class);
    expect($models)->toContain(\Modules\Blog\Models\Category::class);
    expect($models)->toContain(\Modules\Pages\Models\StaticPage::class);
    expect($models)->toContain(\Modules\Settings\Models\Setting::class);
});

// --- SearchService ---

it('search service returns searchable models from config', function () {
    $service = app(SearchService::class);
    $models = $service->getSearchableModels();
    expect($models)->toHaveCount(6);
});

// --- API Search endpoints ---

it('search api is publicly accessible', function () {
    $response = $this->getJson('/api/v1/search?q=test');
    $response->assertOk();
});

it('search api validates query parameter', function () {
    $response = $this->getJson('/api/v1/search');
    $response->assertStatus(422);
});

it('search api validates minimum query length', function () {
    $response = $this->getJson('/api/v1/search?q=a');
    $response->assertStatus(422);
});

it('search api returns results for plans', function () {
    Plan::factory()->create(['name' => 'UniqueTestPlan', 'is_active' => true]);

    $response = $this->getJson('/api/v1/search?q=UniqueTestPlan');
    $response->assertOk();
    $response->assertJsonFragment(['success' => true]);
});

it('search api filters by model', function () {
    Plan::factory()->create(['name' => 'FilterTestPlan', 'is_active' => true]);

    $response = $this->getJson('/api/v1/search?q=FilterTestPlan&model=Plan');
    $response->assertOk();
    $response->assertJsonFragment(['success' => true]);
});

it('search api rejects invalid model filter', function () {
    $response = $this->getJson('/api/v1/search?q=test&model=InvalidModel');
    $response->assertStatus(422);
});
