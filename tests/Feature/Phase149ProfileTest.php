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
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create(['password' => bcrypt('Password1!')]);
    $this->user->assignRole('user');
});

// --- Profile API ---

it('returns authenticated user profile', function () {
    Sanctum::actingAs($this->user);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $this->user->id);
});

it('updates user name and bio', function () {
    Sanctum::actingAs($this->user);

    $this->putJson('/api/v1/profile', [
        'name' => 'Nouveau nom',
        'bio' => 'Ma nouvelle bio',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->user->fresh()->name)->toBe('Nouveau nom');
});

it('validates name is required on profile update', function () {
    Sanctum::actingAs($this->user);

    $this->putJson('/api/v1/profile', [
        'bio' => 'Bio sans nom',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('changes password with correct current password', function () {
    Sanctum::actingAs($this->user);

    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'Password1!',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('rejects wrong current password', function () {
    Sanctum::actingAs($this->user);

    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'WrongPassword',
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');
});

it('validates password confirmation required', function () {
    Sanctum::actingAs($this->user);

    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'Password1!',
        'password' => 'NewPassword1!',
    ])
        ->assertUnprocessable();
});

// --- Notifications API ---

it('lists notifications paginated', function () {
    Sanctum::actingAs($this->user);

    $this->user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'data' => ['message' => 'Test notification'],
        'read_at' => null,
    ]);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('marks a notification as read', function () {
    Sanctum::actingAs($this->user);

    $notifId = (string) Str::uuid();
    $this->user->notifications()->create([
        'id' => $notifId,
        'type' => 'App\\Notifications\\TestNotification',
        'data' => ['message' => 'Test'],
        'read_at' => null,
    ]);

    $this->postJson("/api/v1/notifications/{$notifId}/read")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->user->notifications()->find($notifId)->read_at)->not->toBeNull();
});

it('marks all notifications as read', function () {
    Sanctum::actingAs($this->user);

    for ($i = 0; $i < 3; $i++) {
        $this->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['message' => "Notification {$i}"],
            'read_at' => null,
        ]);
    }

    $this->postJson('/api/v1/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('deletes a notification', function () {
    Sanctum::actingAs($this->user);

    $notifId = (string) Str::uuid();
    $this->user->notifications()->create([
        'id' => $notifId,
        'type' => 'App\\Notifications\\TestNotification',
        'data' => ['message' => 'To delete'],
        'read_at' => null,
    ]);

    $this->deleteJson("/api/v1/notifications/{$notifId}")
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('notifications', ['id' => $notifId]);
});

it('returns 404 for nonexistent notification', function () {
    Sanctum::actingAs($this->user);

    $fakeId = (string) Str::uuid();
    $this->deleteJson("/api/v1/notifications/{$fakeId}")
        ->assertNotFound();
});

it('returns 404 when marking nonexistent notification as read', function () {
    Sanctum::actingAs($this->user);

    $fakeId = (string) Str::uuid();
    $this->postJson("/api/v1/notifications/{$fakeId}/read")
        ->assertNotFound();
});

// --- Auth required ---

it('returns 401 for unauthenticated profile access', function () {
    $this->getJson('/api/v1/profile')
        ->assertUnauthorized();
});

it('returns 401 for unauthenticated notifications access', function () {
    $this->getJson('/api/v1/notifications')
        ->assertUnauthorized();
});

it('returns 401 for unauthenticated password change', function () {
    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'test',
        'password' => 'newpass',
        'password_confirmation' => 'newpass',
    ])
        ->assertUnauthorized();
});
