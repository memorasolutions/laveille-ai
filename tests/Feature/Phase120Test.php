<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('GET / retourne 200', function () {
    $this->get('/')->assertStatus(200);
});

it('GET /blog retourne 200', function () {
    $this->get('/blog')->assertStatus(200);
});

it('GET /contact retourne 200', function () {
    $this->get('/contact')->assertStatus(200);
});

it('GET /faq retourne 200', function () {
    $this->get('/faq')->assertStatus(200);
});

it('GET /login retourne 200', function () {
    $this->get('/login')->assertStatus(200);
});

it('GET /register retourne 200', function () {
    $this->get('/register')->assertStatus(200);
});

it('GET /about retourne 200', function () {
    $this->get('/about')->assertStatus(200);
});

it('GET /legal retourne 200', function () {
    $this->get('/legal')->assertStatus(200);
});

it('GET /privacy retourne 200', function () {
    $this->get('/privacy')->assertStatus(200);
});

it('config fronttheme.active retourne gosass', function () {
    expect(config('fronttheme.active'))->toBe('gosass');
});

it('fronttheme_layout() retourne le bon chemin de vue', function () {
    expect(fronttheme_layout())->toBe('fronttheme::themes.gosass.layouts.app');
});
