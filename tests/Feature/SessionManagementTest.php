<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('shows sessions section on profile page', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('Sessions actives');
});

it('shows session details when sessions exist in database', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    DB::table('sessions')->insert([
        'id' => 'test-session-display',
        'user_id' => $user->id,
        'ip_address' => '192.168.1.42',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/120',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('192.168.1.42')
        ->assertSee('Chrome')
        ->assertSee('macOS');
});

it('can revoke another session', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);

    DB::table('sessions')->insert([
        'id' => 'fake-session-to-revoke',
        'user_id' => $user->id,
        'ip_address' => '1.2.3.4',
        'user_agent' => 'Mozilla/5.0 Chrome',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->post(route('admin.profile.sessions.revoke', 'fake-session-to-revoke'))
        ->assertRedirect();

    $this->assertDatabaseMissing('sessions', ['id' => 'fake-session-to-revoke']);
});

it('cannot revoke session of another user', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $otherUser = User::factory()->create();

    $this->actingAs($user);

    DB::table('sessions')->insert([
        'id' => 'other-user-session',
        'user_id' => $otherUser->id,
        'ip_address' => '1.2.3.4',
        'user_agent' => 'Mozilla/5.0 Chrome',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->post(route('admin.profile.sessions.revoke', 'other-user-session'))
        ->assertRedirect();

    // Session should still exist (user_id mismatch, delete affected 0 rows)
    $this->assertDatabaseHas('sessions', ['id' => 'other-user-session']);
});

it('revoke others requires password', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->post(route('admin.profile.sessions.revoke-others'), [])
        ->assertSessionHasErrors(['current_password']);
});

it('revoke others with correct password deletes other sessions', function () {
    $user = User::factory()->create(['password' => 'Password1!']);
    $user->assignRole('admin');

    $this->actingAs($user);

    DB::table('sessions')->insert([
        ['id' => 'fake-001', 'user_id' => $user->id, 'ip_address' => '1.2.3.4', 'user_agent' => 'Chrome', 'payload' => '', 'last_activity' => time()],
        ['id' => 'fake-002', 'user_id' => $user->id, 'ip_address' => '5.6.7.8', 'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => time()],
    ]);

    $this->post(route('admin.profile.sessions.revoke-others'), [
        'current_password' => 'Password1!',
    ])->assertRedirect();

    $this->assertDatabaseMissing('sessions', ['id' => 'fake-001']);
    $this->assertDatabaseMissing('sessions', ['id' => 'fake-002']);
});

it('guest cannot access admin profile', function () {
    $this->get(route('admin.profile'))
        ->assertRedirect();
});
