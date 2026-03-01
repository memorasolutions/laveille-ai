<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('page admin se charge en mode clair', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

it('navbar Backend contient le bouton dark mode', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    $response->assertSee('data-bs-theme', false);
});

it('layout Backend définit le data-bs-theme via color-modes.js', function () {
    $colorModes = file_get_contents(base_path('resources/js/nobleui/color-modes.js'));

    expect($colorModes)->toContain('data-bs-theme');
});

it('layout Tailwind lie darkMode au localStorage', function () {
    $layout = file_get_contents(module_path('Backoffice', 'resources/views/layouts/admin.blade.php'));

    expect($layout)->toContain('localStorage.getItem');
    expect($layout)->toContain("'dark': darkMode");
});
