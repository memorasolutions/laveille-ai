<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('admin dashboard is accessible for admin users', function () {
    Role::create(['name' => 'super_admin']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('admin dashboard redirects guests', function () {
    $this->get('/admin')
        ->assertRedirect();
});

test('filament panel is configured', function () {
    $panels = filament()->getPanels();

    expect($panels)->toHaveKey('admin');
});
