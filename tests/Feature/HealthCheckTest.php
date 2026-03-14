<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('health endpoint returns database status', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'database'])
        ->assertJson(['status' => 'ok', 'database' => 'ok']);
});

test('health endpoint returns cache status', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200)
        ->assertJson(['cache' => 'ok']);
});

test('status endpoint does not expose environment in production', function () {
    $content = file_get_contents(base_path('routes/api/v1.php'));

    expect($content)->toContain('isProduction')
        ->toContain('null');
});

test('status endpoint returns app and version', function () {
    $response = $this->getJson('/api/v1/status');

    $response->assertStatus(200)
        ->assertJsonStructure(['app', 'version', 'timestamp'])
        ->assertJsonPath('version', 'v1');
});
