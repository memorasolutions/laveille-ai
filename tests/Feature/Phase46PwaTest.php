<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --- Static PWA files ---

it('manifest.json exists and is valid JSON', function () {
    $path = public_path('manifest.json');
    expect(file_exists($path))->toBeTrue();

    $json = json_decode(file_get_contents($path), true);
    expect($json)->toBeArray();
});

it('manifest.json contains required PWA fields', function () {
    $json = json_decode(file_get_contents(public_path('manifest.json')), true);

    expect($json)->toHaveKeys(['name', 'short_name', 'start_url', 'display', 'icons'])
        ->and($json['display'])->toBe('standalone')
        ->and($json['icons'])->toHaveCount(2);
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

// --- PWA meta tags in layouts ---

it('landing page contains PWA manifest link', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="manifest"', false);
});

it('landing page contains theme-color meta', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('name="theme-color"', false);
});

it('landing page contains service worker registration', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('serviceWorker.register', false);
});
