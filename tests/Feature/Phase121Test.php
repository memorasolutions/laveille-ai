<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->regularAdmin = User::factory()->create();
    $this->regularAdmin->assignRole('admin');

    $this->targetUser = User::factory()->create();
    $this->targetUser->assignRole('user');
});

it('super_admin can impersonate regular user', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.users.impersonate', $this->targetUser))
        ->assertRedirect(route('user.dashboard'));

    expect(session('impersonating_original_id'))->toBe($this->superAdmin->id);
});

it('non-super_admin admin cannot impersonate', function () {
    $this->actingAs($this->regularAdmin)
        ->post(route('admin.users.impersonate', $this->targetUser))
        ->assertForbidden();
});

it('regular user cannot impersonate', function () {
    $this->actingAs($this->targetUser)
        ->post(route('admin.users.impersonate', User::factory()->create()))
        ->assertForbidden();
});

it('cannot impersonate another super_admin', function () {
    $superAdmin2 = User::factory()->create();
    $superAdmin2->assignRole('super_admin');

    $this->actingAs($this->superAdmin)
        ->post(route('admin.users.impersonate', $superAdmin2))
        ->assertRedirect()
        ->assertSessionHasErrors('error');
});

it('stop impersonating returns to original admin', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.users.impersonate', $this->targetUser));

    $this->post(route('admin.impersonate.stop'))
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->id())->toBe($this->superAdmin->id);
});

it('stop impersonating without active session returns 403', function () {
    $this->actingAs($this->targetUser)
        ->post(route('admin.impersonate.stop'))
        ->assertForbidden();
});

it('impersonation banner visible when impersonating', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('admin.users.impersonate', $this->targetUser));

    $this->get(route('user.dashboard'))
        ->assertOk()
        ->assertSee('Impersonnification en cours');
});

it('unauthenticated cannot access impersonate route', function () {
    $this->post(route('admin.users.impersonate', $this->targetUser))
        ->assertRedirect(route('login'));
});
