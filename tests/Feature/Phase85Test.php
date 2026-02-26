<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('profile page loads', function () {
    $this->get(route('user.profile'))
        ->assertStatus(200)
        ->assertSee('Mon profil');
});

it('profile shows user name', function () {
    $this->user->update(['name' => 'Jean Test']);
    $this->get(route('user.profile'))
        ->assertSee('Jean Test');
});

it('profile shows bio when set', function () {
    $this->user->update(['bio' => 'Ma biographie personnelle']);
    $this->get(route('user.profile'))
        ->assertSee('Ma biographie personnelle');
});

it('user can update name and email', function () {
    $this->put(route('user.profile.update'), [
        'name' => 'Nouveau Nom',
        'email' => 'nouveau@example.com',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'name' => 'Nouveau Nom',
        'email' => 'nouveau@example.com',
    ]);
});

it('user can update bio', function () {
    $this->put(route('user.profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'bio' => 'Nouvelle bio de test',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'bio' => 'Nouvelle bio de test',
    ]);
});

it('profile update validates name required', function () {
    $this->put(route('user.profile.update'), [
        'name' => '',
        'email' => $this->user->email,
    ])->assertSessionHasErrors(['name']);
});

it('profile update validates email format', function () {
    $this->put(route('user.profile.update'), [
        'name' => $this->user->name,
        'email' => 'invalide',
    ])->assertSessionHasErrors(['email']);
});

it('profile shows initials when no avatar', function () {
    $this->user->update(['name' => 'Pierre Dupont', 'avatar' => null]);
    $this->get(route('user.profile'))
        ->assertSee('P');
});

it('profile update validates avatar is image', function () {
    $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->put(route('user.profile.update'), [
        'name' => $this->user->name,
        'email' => $this->user->email,
        'avatar' => $file,
    ])->assertSessionHasErrors(['avatar']);
});

it('password update fails with wrong current password', function () {
    $this->put(route('user.password.update'), [
        'current_password' => 'wrongpassword',
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ])->assertSessionHasErrors(['current_password']);
});

it('unauthenticated redirect from profile', function () {
    auth()->logout();
    $this->get(route('user.profile'))
        ->assertRedirect(route('login'));
});
