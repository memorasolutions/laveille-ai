<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Search\Services\SearchService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('search service is registered as singleton', function () {
    $service1 = app(SearchService::class);
    $service2 = app(SearchService::class);

    expect($service1)->toBeInstanceOf(SearchService::class);
    expect($service1)->toBe($service2);
});

test('scout package is available', function () {
    expect(class_exists(\Laravel\Scout\ScoutServiceProvider::class))->toBeTrue();
});

test('user model is searchable', function () {
    expect(method_exists(User::class, 'search'))->toBeTrue();
    expect(method_exists(User::class, 'toSearchableArray'))->toBeTrue();
});

test('user search returns results', function () {
    User::factory()->create(['name' => 'Alice Dupont', 'email' => 'alice@test.com']);
    User::factory()->create(['name' => 'Bob Martin', 'email' => 'bob@test.com']);

    $results = User::search('Alice')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Alice Dupont');
});

test('search service can search across models', function () {
    User::factory()->create(['name' => 'Charlie Test']);
    User::factory()->create(['name' => 'Diana Test']);

    $service = app(SearchService::class);
    $results = $service->search('Charlie', [User::class]);

    expect($results)->toHaveKey(User::class);
    expect($results[User::class])->toHaveCount(1);
});

test('search service returns searchable models from config', function () {
    $service = app(SearchService::class);
    $models = $service->getSearchableModels();

    expect($models)->toBeArray();
    expect($models)->toContain(User::class);
});
