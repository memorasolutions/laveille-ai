<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('route /manifest.webmanifest retourne 200 avec Content-Type manifest+json', function () {
    $response = $this->get('/manifest.webmanifest');
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/manifest+json');
});

it('manifest JSON contient les champs requis', function () {
    $response = $this->get('/manifest.webmanifest');
    $manifest = json_decode($response->content(), true);

    expect($manifest)->toHaveKeys([
        'name', 'short_name', 'display', 'icons', 'theme_color', 'start_url',
    ]);
});

it('manifest display est standalone', function () {
    $response = $this->get('/manifest.webmanifest');
    $manifest = json_decode($response->content(), true);

    expect($manifest['display'])->toBe('standalone');
});

it('manifest a au moins 2 icônes', function () {
    $response = $this->get('/manifest.webmanifest');
    $manifest = json_decode($response->content(), true);

    expect($manifest['icons'])->toBeArray()->toHaveCount(3);
});

it('route /offline retourne 200', function () {
    $response = $this->get('/offline');
    $response->assertStatus(200);
});

it('page offline contient Hors connexion', function () {
    $response = $this->get('/offline');
    $response->assertSee('Hors connexion');
});

it('config pwa.enabled est true par défaut', function () {
    expect(config('pwa.enabled'))->toBeTrue();
});

it('config pwa.theme_color retourne une valeur', function () {
    expect(config('pwa.theme_color'))->toBeString()->not->toBeEmpty();
});

it('commande pwa:status exécute sans erreur', function () {
    $exitCode = Artisan::call('pwa:status');
    expect($exitCode)->toBe(0);
});

it('fichier sw-source.js existe', function () {
    expect(File::exists(resource_path('js/sw-source.js')))->toBeTrue();
});

it('fichier pwa.js existe', function () {
    expect(File::exists(resource_path('js/pwa.js')))->toBeTrue();
});

it('icône apple touch 180x180 existe', function () {
    expect(File::exists(public_path('icons/apple-touch-icon-180x180.png')))->toBeTrue();
});

it('vite.config.js contient vite-plugin-pwa', function () {
    $content = File::get(base_path('vite.config.js'));
    expect($content)->toContain('vite-plugin-pwa');
});
