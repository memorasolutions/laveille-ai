<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --- Static PWA files ---

it('manifest.webmanifest route returns valid JSON', function () {
    $response = $this->get('/manifest.webmanifest');
    $response->assertStatus(200);

    $json = json_decode($response->content(), true);
    expect($json)->toBeArray();
});

it('manifest.webmanifest contains required PWA fields', function () {
    $response = $this->get('/manifest.webmanifest');
    $json = json_decode($response->content(), true);

    expect($json)->toHaveKeys(['name', 'short_name', 'start_url', 'display', 'icons'])
        ->and($json['display'])->toBe('standalone')
        ->and(count($json['icons']))->toBeGreaterThanOrEqual(2);
});

it('offline.html exists', function () {
    expect(file_exists(public_path('offline.html')))->toBeTrue();

    $content = file_get_contents(public_path('offline.html'));
    expect($content)->toContain('Hors connexion');
});

it('service-worker.js exists', function () {
    expect(file_exists(public_path('service-worker.js')))->toBeTrue();
});

it('service-worker.js contains cache and fetch logic', function () {
    $content = file_get_contents(public_path('service-worker.js'));

    expect($content)->toContain('CACHE_NAME')
        ->and($content)->toContain("addEventListener('fetch'")
        ->and($content)->toContain('offline.html');
});

it('PWA icons exist', function () {
    expect(file_exists(public_path('icons/icon-192x192.png')))->toBeTrue()
        ->and(file_exists(public_path('icons/icon-512x512.png')))->toBeTrue();
});
