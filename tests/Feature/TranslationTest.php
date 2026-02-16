<?php

declare(strict_types=1);

test('french translations are loaded', function () {
    app()->setLocale('fr');

    expect(__('Welcome'))->toBe('Bienvenue');
    expect(__('Login'))->toBe('Connexion');
    expect(__('Dashboard'))->toBe('Tableau de bord');
});

test('english translations are loaded', function () {
    app()->setLocale('en');

    expect(__('Welcome'))->toBe('Welcome');
    expect(__('Login'))->toBe('Login');
});

test('translatable package is available', function () {
    expect(trait_exists(\Spatie\Translatable\HasTranslations::class))->toBeTrue();
});

test('model states package is available', function () {
    expect(class_exists(\Spatie\ModelStates\State::class))->toBeTrue();
});

test('app locale defaults to fr', function () {
    expect(config('app.locale'))->toBe('fr');
});
