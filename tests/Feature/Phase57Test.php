<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('page inscription se charge correctement', function () {
    $this->get(route('register'))
        ->assertOk();
});

it('vue register contient l indicateur de force Alpine.js', function () {
    $view = file_get_contents(module_path('Auth', 'resources/views/livewire/register.blade.php'));

    expect($view)->toContain('strength')
        ->toContain('pwd.length')
        ->toContain('Faible')
        ->toContain('Fort');
});

it('vue register affiche les règles de mot de passe', function () {
    $view = file_get_contents(module_path('Auth', 'resources/views/livewire/register.blade.php'));

    expect($view)->toContain('8+ caract')
        ->toContain('Majuscule')
        ->toContain('Chiffre');
});

it('vue register contient la barre de progression', function () {
    $view = file_get_contents(module_path('Auth', 'resources/views/livewire/register.blade.php'));

    expect($view)->toContain('#22c55e')
        ->toContain('#dc2626')
        ->toContain('#f59e0b');
});

it('vue register contient x-model lié au mot de passe', function () {
    $view = file_get_contents(module_path('Auth', 'resources/views/livewire/register.blade.php'));

    expect($view)->toContain('x-model="pwd"');
});
