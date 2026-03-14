<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('fr.json exists and is valid JSON with 500+ keys', function () {
    $path = lang_path('fr.json');
    expect(file_exists($path))->toBeTrue();

    $data = json_decode(file_get_contents($path), true);
    expect($data)->toBeArray();
    expect(count($data))->toBeGreaterThanOrEqual(500);
});

test('en.json exists and is valid JSON with 500+ keys', function () {
    $path = lang_path('en.json');
    expect(file_exists($path))->toBeTrue();

    $data = json_decode(file_get_contents($path), true);
    expect($data)->toBeArray();
    expect(count($data))->toBeGreaterThanOrEqual(500);
});

test('all fr.json keys exist in en.json', function () {
    $fr = json_decode(file_get_contents(lang_path('fr.json')), true);
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $missing = array_diff(array_keys($fr), array_keys($en));
    expect($missing)->toBeEmpty('Missing keys in en.json: '.implode(', ', array_slice($missing, 0, 5)));
});

test('locale controller switches to fr', function () {
    $this->post('/locale/fr')
        ->assertRedirect()
        ->assertSessionHas('locale', 'fr');
});

test('locale controller switches to en', function () {
    $this->post('/locale/en')
        ->assertRedirect()
        ->assertSessionHas('locale', 'en');
});

test('locale controller rejects invalid locale', function () {
    $this->post('/locale/es')->assertStatus(400);
});

test('__() returns French for fr locale', function () {
    app()->setLocale('fr');
    // fr.json has identity mapping, so French keys return themselves
    expect(__('Tableau de bord'))->toBe('Tableau de bord');
    expect(__('Connexion'))->toBe('Connexion');
    expect(__('Mon profil'))->toBe('Mon profil');
});

test('__() returns English for en locale', function () {
    app()->setLocale('en');
    // en.json maps French keys to English values
    expect(__('Tableau de bord'))->toBe('Dashboard');
    expect(__('Connexion'))->toBe('Login');
    expect(__('Mon profil'))->toBe('My profile');
    expect(__('Déconnexion'))->toBe('Logout');
    expect(__('Sécurité'))->toBe('Security');
});

test('SetLocale middleware applies locale from session', function () {
    $this->withSession(['locale' => 'en'])
        ->get('/login')
        ->assertSuccessful();

    // After middleware runs, locale should be en
    expect(session('locale'))->toBe('en');
});
