<?php

declare(strict_types=1);

use Database\Seeders\CookieCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CookieCategorySeeder::class);
});

it('cookie consent banner shown on homepage', function () {
    $this->get('/')->assertOk()->assertSee('utilisons des cookies');
});

it('accept route sets cookie with all categories enabled', function () {
    $response = $this->post(route('cookie.accept'));
    $response->assertRedirect();
    $cookie = $response->getCookie('cookie_consent');
    $decoded = json_decode($cookie->getValue(), true);
    expect($decoded['essential'])->toBeTrue();
    expect($decoded['analytics'])->toBeTrue();
    expect($decoded['marketing'])->toBeTrue();
    expect($decoded['functional'])->toBeTrue();
});

it('decline route sets cookie with only essential', function () {
    $response = $this->post(route('cookie.decline'));
    $response->assertRedirect();
    $cookie = $response->getCookie('cookie_consent');
    $decoded = json_decode($cookie->getValue(), true);
    expect($decoded['essential'])->toBeTrue();
    expect($decoded['analytics'])->toBeFalse();
    expect($decoded['marketing'])->toBeFalse();
    expect($decoded['functional'])->toBeFalse();
});

it('cookie accept route exists', function () {
    expect(Route::has('cookie.accept'))->toBeTrue();
});

it('cookie decline route exists', function () {
    expect(Route::has('cookie.decline'))->toBeTrue();
});

it('banner hidden when cookie already set', function () {
    $this->withCookies(['cookie_consent' => 'all'])
        ->get('/')
        ->assertOk()
        ->assertDontSee('utilisons des cookies');
});

it('accept redirects with status 302', function () {
    $this->post(route('cookie.accept'))->assertStatus(302);
});

it('banner contains csrf token field', function () {
    $this->get('/')->assertOk()->assertSee('_token', false);
});

it('cookie customize route exists', function () {
    expect(Route::has('cookie.customize'))->toBeTrue();
});

it('customize route sets granular cookie preferences', function () {
    $response = $this->post(route('cookie.customize'), [
        'analytics' => '1',
        'marketing' => '0',
    ]);
    $response->assertRedirect();
    $cookie = $response->getCookie('cookie_consent');
    $decoded = json_decode($cookie->getValue(), true);
    expect($decoded['essential'])->toBeTrue();
    expect($decoded['analytics'])->toBeTrue();
    expect($decoded['marketing'])->toBeFalse();
});

it('customize route with no checkboxes sets all to false', function () {
    $response = $this->post(route('cookie.customize'), []);
    $response->assertRedirect();
    $cookie = $response->getCookie('cookie_consent');
    $decoded = json_decode($cookie->getValue(), true);
    expect($decoded['essential'])->toBeTrue();
    expect($decoded['analytics'])->toBeFalse();
    expect($decoded['marketing'])->toBeFalse();
});

it('banner shows Personnaliser button', function () {
    $this->get('/')->assertOk()->assertSee('Personnaliser');
});
