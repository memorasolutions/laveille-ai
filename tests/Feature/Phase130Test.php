<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
test('commande app:audit existe et s\'exécute', function () {
    $this->artisan('app:audit')->assertExitCode(1);
});

test('commande app:audit affiche le score', function () {
    $this->artisan('app:audit')
        ->expectsOutputToContain('Score :');
});

test('commande app:audit vérifie les extensions PHP', function () {
    $this->artisan('app:audit')
        ->expectsOutputToContain('Extensions PHP');
});

test('commande app:audit vérifie la connexion DB', function () {
    $this->artisan('app:audit')
        ->expectsOutputToContain('Connexion DB');
});

test('commande app:audit vérifie APP_DEBUG', function () {
    $this->artisan('app:audit')
        ->expectsOutputToContain('APP_DEBUG');
});
