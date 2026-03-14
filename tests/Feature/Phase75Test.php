<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Plan;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

// 1. 'inline update user name'
test('inline update user name', function () {
    $user = User::factory()->create();
    $this->patch(route('admin.inline.update', ['entity' => 'users', 'id' => $user->id]), [
        'field' => 'name',
        'value' => 'Nouveau Nom',
    ])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nouveau Nom']);
});

// 2. 'inline update user is_active false'
test('inline update user is_active false', function () {
    $user = User::factory()->create(['is_active' => true]);
    $this->patch(route('admin.inline.update', ['entity' => 'users', 'id' => $user->id]), [
        'field' => 'is_active',
        'value' => 0,
    ])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
});

// 3. 'inline field non autorisé'
test('inline field non autorisé', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->patch(route('admin.inline.update', ['entity' => 'users', 'id' => $user->id]), [
        'field' => 'password',
        'value' => 'newpassword123',
    ])
        ->assertStatus(422);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'password' => $originalPassword]);
});

// 4. 'inline entity inconnue'
test('inline entity inconnue', function () {
    $user = User::factory()->create();
    $this->patch(route('admin.inline.update', ['entity' => 'foobar', 'id' => $user->id]), [
        'field' => 'name',
        'value' => 'Nouveau Nom',
    ])
        ->assertStatus(404);
});

// 5. 'inline non authentifié'
test('inline non authentifié', function () {
    $user = User::factory()->create();
    auth()->logout();

    $this->patch(route('admin.inline.update', ['entity' => 'users', 'id' => $user->id]), [
        'field' => 'name',
        'value' => 'Nouveau Nom',
    ])
        ->assertRedirect(route('login'));
});

// 6. 'inline non-admin 403'
test('inline non-admin 403', function () {
    $regularUser = User::factory()->create();
    $this->actingAs($regularUser);

    $user = User::factory()->create();
    $this->patch(route('admin.inline.update', ['entity' => 'users', 'id' => $user->id]), [
        'field' => 'name',
        'value' => 'Nouveau Nom',
    ])
        ->assertStatus(403);
});

// 7. 'inline update plan name'
test('inline update plan name', function () {
    $plan = Plan::factory()->create();
    $this->patch(route('admin.inline.update', ['entity' => 'plans', 'id' => $plan->id]), [
        'field' => 'name',
        'value' => 'Plan Pro',
    ])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Plan Pro']);
});

// 8. 'inline update plan price'
test('inline update plan price', function () {
    $plan = Plan::factory()->create();
    $this->patch(route('admin.inline.update', ['entity' => 'plans', 'id' => $plan->id]), [
        'field' => 'price',
        'value' => 99.99,
    ])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('plans', ['id' => $plan->id, 'price' => 99.99]);
});
