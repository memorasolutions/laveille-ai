<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('route locale.switch exists', function () {
    expect(Route::has('locale.switch'))->toBeTrue();
});

it('switch to fr sets session locale and redirects', function () {
    $this->post(route('locale.switch', 'fr'))
        ->assertRedirect()
        ->assertStatus(302);

    expect(session('locale'))->toBe('fr');
});

it('switch to en sets session locale and redirects', function () {
    $this->post(route('locale.switch', 'en'))
        ->assertRedirect()
        ->assertStatus(302);

    expect(session('locale'))->toBe('en');
});

it('invalid locale returns 400', function () {
    $this->post(route('locale.switch', 'de'))
        ->assertStatus(400);
});

it('unauthenticated user can switch locale', function () {
    $this->post(route('locale.switch', 'en'))
        ->assertStatus(302);

    expect(session('locale'))->toBe('en');
});

it('switching to en then visiting home returns 200', function () {
    $this->post(route('locale.switch', 'en'));

    $this->get('/')->assertOk();
});

it('locale session persists across requests', function () {
    $this->post(route('locale.switch', 'en'));

    $this->withSession(['locale' => 'en'])
        ->get('/')
        ->assertOk();

    expect(session('locale'))->toBe('en');
});

it('switch to fr after en restores fr locale', function () {
    $this->post(route('locale.switch', 'en'));
    $this->post(route('locale.switch', 'fr'));

    expect(session('locale'))->toBe('fr');
});
