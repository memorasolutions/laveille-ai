<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Team\Models\Team;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));
    $this->user = User::factory()->create();
});

// --- Validation store ---

test('store requiert name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.teams.store'), ['description' => 'Description test'])
        ->assertSessionHasErrors('name');
});

test('store rejette name > 255 chars', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.teams.store'), [
            'name' => Str::random(256),
            'description' => 'Description test',
        ])
        ->assertSessionHasErrors('name');
});

test('store rejette description > 1000 chars', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.teams.store'), [
            'name' => 'Team Test',
            'description' => Str::random(1001),
        ])
        ->assertSessionHasErrors('description');
});

// --- Validation update ---

test('update requiert name', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.teams.update', $team), ['description' => 'Mise à jour'])
        ->assertSessionHasErrors('name');
});

test('update rejette name > 255 chars', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.teams.update', $team), [
            'name' => Str::random(256),
            'description' => 'Mise à jour',
        ])
        ->assertSessionHasErrors('name');
});

// --- Validation invite ---

test('invite requiert email valide', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.teams.invite', $team), [
            'email' => 'email-invalide',
            'role' => 'member',
        ])
        ->assertSessionHasErrors('email');
});

test('invite requiert role valide', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.teams.invite', $team), [
            'email' => 'test@example.com',
            'role' => 'invalid-role',
        ])
        ->assertSessionHasErrors('role');
});

test('invite rejette email déjà membre', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => 'member']);

    $this->actingAs($this->admin)
        ->post(route('admin.teams.invite', $team), [
            'email' => $member->email,
            'role' => 'member',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('email');
});

// --- Permissions ---

test('user sans permission ne peut pas inviter', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->user)
        ->post(route('admin.teams.invite', $team), [
            'email' => 'test@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();
});

// --- Pages ---

test('page create accessible admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.teams.create'))
        ->assertOk();
});

test('page edit accessible admin', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.teams.edit', $team))
        ->assertOk();
});

test('page show affiche les membres', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create(['name' => 'Jean Membre']);
    $team->members()->attach($member, ['role' => 'member']);

    $this->actingAs($this->admin)
        ->get(route('admin.teams.show', $team))
        ->assertOk()
        ->assertSee('Jean Membre');
});

// --- Actions ---

test('delete team redirige et soft delete', function () {
    $team = Team::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.teams.destroy', $team))
        ->assertRedirect();

    $this->assertSoftDeleted('teams', ['id' => $team->id]);
});

test('remove member redirige', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => 'member']);

    $this->actingAs($this->admin)
        ->delete(route('admin.teams.members.remove', [$team, $member]))
        ->assertRedirect();

    $this->assertDatabaseMissing('team_user', [
        'team_id' => $team->id,
        'user_id' => $member->id,
    ]);
});
