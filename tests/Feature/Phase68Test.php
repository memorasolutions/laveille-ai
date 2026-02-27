<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    $this->actingAs($this->admin);
});

test('dashboard passe activeUsersCount à la vue', function () {
    User::factory()->count(2)->create(['is_active' => true]);
    User::factory()->count(1)->create(['is_active' => false]);

    $this->get('/admin')
        ->assertOk()
        ->assertViewHas('activeUsersCount');
});

test('activeUsersCount ne compte que les utilisateurs actifs', function () {
    User::factory()->count(3)->create(['is_active' => true]);
    User::factory()->count(2)->create(['is_active' => false]);

    $response = $this->get('/admin')->assertOk();
    // 3 actifs + $this->admin (actif) = 4
    expect($response->viewData('activeUsersCount'))->toBe(4);
});

test('dashboard passe newUsersThisMonth à la vue', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertViewHas('newUsersThisMonth');
});

test('newUsersThisMonth compte les utilisateurs créés ce mois', function () {
    User::factory()->count(2)->create([
        'is_active' => true,
        'created_at' => now(),
    ]);

    $response = $this->get('/admin')->assertOk();
    // 2 nouveaux + $this->admin = 3 minimum
    expect($response->viewData('newUsersThisMonth'))->toBeGreaterThanOrEqual(3);
});

test('dashboard affiche la stat utilisateurs', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('Utilisateurs');
});

test('dashboard affiche le compteur ce mois', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('ce mois');
});
