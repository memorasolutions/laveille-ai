<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_active' => true,
        'password' => Hash::make('Password1!'),
    ]);

    $this->adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin->assignRole($this->adminRole);

    $this->actingAs($this->admin);
});

// === USERS CRUD ===

test('liste utilisateurs retourne 200', function () {
    $this->get(route('admin.users.index'))->assertOk();
});

test('page créer utilisateur retourne 200', function () {
    $this->get(route('admin.users.create'))->assertOk();
});

test('store crée un utilisateur', function () {
    $this->post(route('admin.users.store'), [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('store valide le nom obligatoire', function () {
    $this->post(route('admin.users.store'), [
        'email' => 'noname@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertSessionHasErrors('name');
});

test('page show utilisateur retourne 200', function () {
    $user = User::factory()->create();
    $this->get(route('admin.users.show', $user))->assertOk();
});

test('page edit utilisateur retourne 200', function () {
    $user = User::factory()->create();
    $this->get(route('admin.users.edit', $user))->assertOk();
});

test('update modifie le nom', function () {
    $user = User::factory()->create(['name' => 'Ancien Nom']);

    $this->put(route('admin.users.update', $user), [
        'name' => 'Nouveau Nom',
        'email' => $user->email,
    ])->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nouveau Nom']);
});

test('destroy supprime utilisateur tiers', function () {
    $user = User::factory()->create();

    $this->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

// === ROLES CRUD ===

test('liste rôles retourne 200', function () {
    $this->get(route('admin.roles.index'))->assertOk();
});

test('page créer rôle retourne 200', function () {
    $this->get(route('admin.roles.create'))->assertOk();
});

test('store crée un rôle moderator', function () {
    $this->post(route('admin.roles.store'), ['name' => 'moderator'])
        ->assertRedirect(route('admin.roles.index'));

    $this->assertDatabaseHas('roles', ['name' => 'moderator']);
});

test('page show rôle retourne 200', function () {
    $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
    $this->get(route('admin.roles.show', $role))->assertOk();
});

test('destroy empêche suppression rôle admin', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->delete(route('admin.roles.destroy', $adminRole))
        ->assertRedirect();

    $this->assertDatabaseHas('roles', ['name' => 'admin']);
});

// === SETTINGS CRUD ===

test('liste paramètres retourne 200', function () {
    $this->get(route('admin.settings.index'))->assertOk();
});

test('page créer paramètre retourne 200', function () {
    $this->get(route('admin.settings.create'))->assertOk();
});

test('store crée un paramètre', function () {
    $this->post(route('admin.settings.store'), [
        'key' => 'site_name',
        'value' => 'MonSite',
        'group' => 'general',
    ])->assertRedirect(route('admin.settings.index'));

    $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'MonSite']);
});

test('update modifie valeur paramètre', function () {
    $setting = Setting::create(['key' => 'site_title', 'value' => 'Ancien', 'group' => 'general']);

    $this->put(route('admin.settings.update', $setting), [
        'key' => 'site_title',
        'value' => 'Nouveau',
        'group' => 'general',
    ])->assertRedirect();

    $this->assertDatabaseHas('settings', ['id' => $setting->id, 'value' => 'Nouveau']);
});

// === PROFILE ===

test('page profil retourne 200', function () {
    $this->get('/admin/profile')->assertOk();
});

test('page tokens API retourne 200', function () {
    $this->get(route('admin.profile.tokens.index'))->assertOk();
});
