<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

uses(RefreshDatabase::class, MakesGraphQLRequests::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

// ============================================================
// Query depth & complexity
// ============================================================

test('graphql rejects queries exceeding max depth', function () {
    // Build a deeply nested query using comment replies (self-referencing)
    // Schema: Article -> comments -> replies -> replies -> ...
    // Max depth is 10, we build 12+ levels
    $nested = 'id';
    for ($i = 0; $i < 12; $i++) {
        $nested = "replies { {$nested} }";
    }

    $query = "{ articles(first: 1) { data { comments { {$nested} } } } }";

    $response = $this->graphQL($query);
    $errors = $response->json('errors');

    expect($errors)->not->toBeEmpty();
    $messages = collect($errors)->pluck('message')->implode(' ');
    expect(strtolower($messages))->toContain('max query depth');
});

// ============================================================
// Introspection
// ============================================================

test('graphql introspection disabled in production', function () {
    // Force introspection disabled (simulates production config)
    $this->app['config']->set(
        'lighthouse.security.disable_introspection',
        \GraphQL\Validator\Rules\DisableIntrospection::ENABLED
    );

    $response = $this->graphQL('{ __schema { types { name } } }');
    $errors = $response->json('errors');

    expect($errors)->not->toBeEmpty();
    $messages = collect($errors)->pluck('message')->implode(' ');
    expect(strtolower($messages))->toContain('introspection');
});

// ============================================================
// Debug info
// ============================================================

test('graphql returns no debug trace when debug disabled', function () {
    config(['app.debug' => false]);
    $this->app['config']->set(
        'lighthouse.debug',
        \GraphQL\Error\DebugFlag::NONE
    );

    // Send invalid query to trigger an error
    $response = $this->graphQL('{ nonExistentField }');
    $errors = $response->json('errors');

    expect($errors)->not->toBeEmpty();
    foreach ($errors as $error) {
        expect($error)->not->toHaveKey('trace');
        if (isset($error['extensions'])) {
            expect($error['extensions'])->not->toHaveKey('trace');
        }
    }
});

// ============================================================
// Pagination limits
// ============================================================

test('graphql pagination respects max count of 100', function () {
    $response = $this->graphQL('{ articles(first: 200) { data { id } } }');
    $errors = $response->json('errors');

    // Lighthouse should reject first:200 when max_count is 100
    expect($errors)->not->toBeEmpty();
    $messages = collect($errors)->pluck('message')->implode(' ');
    expect(strtolower($messages))->toContain('100');
});

// ============================================================
// Authentication
// ============================================================

test('graphql rejects unauthenticated mutation', function () {
    $response = $this->graphQL('
        mutation {
            createArticle(input: { title: "Test", content: "<p>C</p>", status: "draft" }) { id }
        }
    ');

    $response->assertGraphQLErrorMessage('Unauthenticated.');
});

// ============================================================
// Throttle middleware
// ============================================================

test('graphql route has throttle middleware', function () {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('graphql');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();
    $hasThrottle = collect($middleware)->contains(
        fn (string $m) => str_contains($m, 'throttle') || str_contains($m, 'ThrottleRequests')
    );

    expect($hasThrottle)->toBeTrue();
});
