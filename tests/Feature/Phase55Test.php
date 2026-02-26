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

it('navbar WowDash contient le bouton dark mode', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    $response->assertSee('data-theme-toggle', false);
});

it('layout WowDash définit le data-theme', function () {
    $layout = file_get_contents(module_path('Backoffice', 'resources/views/themes/wowdash/layouts/admin.blade.php'));

    expect($layout)->toContain('data-theme');
});

it('app.css référence WowDash comme gestionnaire CSS', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('WowDash');
});

it('layout Tailwind lie darkMode au localStorage', function () {
    $layout = file_get_contents(module_path('Backoffice', 'resources/views/layouts/admin.blade.php'));

    expect($layout)->toContain('localStorage.getItem');
    expect($layout)->toContain("'dark': darkMode");
});
