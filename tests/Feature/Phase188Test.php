<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================
// GOSASS LAYOUT (pages publiques)
// ============================================================

test('landing page has main element', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<main', false);
});

test('landing page has nav with aria-label', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<nav', false)
        ->assertSee('Navigation principale', false);
});

test('landing preloader has aria-hidden', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('cs_preloader', false)
        ->assertSee('aria-hidden="true"', false);
});

// ============================================================
// LOGIN PAGE
// ============================================================

test('login page has h1 heading', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<h1', false);
});

test('login email input has associated label', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('for="login-email"', false)
        ->assertSee('id="login-email"', false);
});

test('login password input has associated label', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('for="login-password"', false);
});

test('login email has autocomplete attribute', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('autocomplete="email"', false);
});

test('login password has autocomplete attribute', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('autocomplete="current-password"', false);
});

test('login password toggle is accessible button', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('toggle-password', false)
        ->assertSee('<button type="button"', false)
        ->assertSee('Afficher le mot de passe', false);
});

test('login social icons have aria-hidden', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('auth/google/redirect', false)
        ->assertSee('Google', false);
});

test('guest layout uses dynamic lang attribute', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('lang="' . str_replace('_', '-', app()->getLocale()) . '"', false);
});

test('guest layout uses main element instead of section', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<main', false);
});

// ============================================================
// ADMIN PAGES
// ============================================================

test('admin breadcrumb uses h1 element', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('<h1 class="fw-semibold', false);
});

test('admin breadcrumb has nav landmark', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Fil d', false);
});

test('admin sidebar has nav landmark', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Menu administration', false);
});

test('admin sidebar buttons have aria-labels', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Fermer le menu', false)
        ->assertSee('Basculer le menu', false)
        ->assertSee('Ouvrir le menu', false);
});
