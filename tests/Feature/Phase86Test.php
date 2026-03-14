<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function makeNotification(User $user, bool $read = false): DatabaseNotification
{
    return DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['message' => 'Notification de test'],
        'read_at' => $read ? now() : null,
        'created_at' => now(),
    ]);
}

it('notifications page loads', function () {
    $this->get(route('user.notifications'))
        ->assertStatus(200)
        ->assertSee('Notifications');
});

it('notifications page shows empty state', function () {
    $this->get(route('user.notifications'))
        ->assertStatus(200)
        ->assertSee('Aucune notification');
});

it('notifications page shows unread count', function () {
    makeNotification($this->user);

    $this->get(route('user.notifications'))
        ->assertStatus(200)
        ->assertSee('1');
});

it('notifications page shows notification message', function () {
    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => ['message' => 'Votre mot de passe a changé'],
        'read_at' => null,
        'created_at' => now(),
    ]);

    $this->get(route('user.notifications'))
        ->assertSee('Votre mot de passe a changé');
});

it('user can mark all notifications as read', function () {
    makeNotification($this->user);
    makeNotification($this->user);

    $this->post(route('user.notifications.markAllRead'))
        ->assertRedirect();

    expect($this->user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('user can mark single notification as read', function () {
    $notif = makeNotification($this->user);

    $this->post(route('user.notifications.markRead', $notif->id))
        ->assertRedirect();

    $this->assertDatabaseHas('notifications', [
        'id' => $notif->id,
    ]);
    expect($notif->fresh()->read_at)->not->toBeNull();
});

it('user can delete single notification', function () {
    $notif = makeNotification($this->user);

    $this->delete(route('user.notifications.destroy', $notif->id))
        ->assertRedirect();

    $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
});

it('user can delete all notifications', function () {
    makeNotification($this->user);
    makeNotification($this->user);
    makeNotification($this->user);

    $this->delete(route('user.notifications.destroyAll'))
        ->assertRedirect(route('user.notifications'));

    $this->assertDatabaseCount('notifications', 0);
});

it('user cannot access other users notifications', function () {
    $other = User::factory()->create();
    $notif = makeNotification($other);

    $this->delete(route('user.notifications.destroy', $notif->id))
        ->assertStatus(404);
});

it('unauthenticated redirect from notifications', function () {
    auth()->logout();
    $this->get(route('user.notifications'))
        ->assertRedirect(route('login'));
});

it('notification bell badge shows in nav when unread exist', function () {
    makeNotification($this->user);

    $this->get(route('user.dashboard'))
        ->assertStatus(200)
        ->assertSee('bg-danger');
});
