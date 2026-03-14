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
use Livewire\Livewire;
use Modules\Backoffice\Livewire\NotificationBell;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function makeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

it('composant NotificationBell se monte', function () {
    $admin = makeAdmin();

    Livewire::actingAs($admin)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 0);
});

it('unreadCount vaut 0 sans notifications', function () {
    $admin = makeAdmin();

    Livewire::actingAs($admin)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 0);
});

it('markAllRead remet unreadCount à zéro', function () {
    $admin = makeAdmin();

    $admin->notifications()->create([
        'id' => Str::uuid(),
        'type' => 'test',
        'data' => json_encode(['message' => 'Test']),
    ]);

    Livewire::actingAs($admin)
        ->test(NotificationBell::class)
        ->call('markAllRead')
        ->assertSet('unreadCount', 0);
});

it('markRead marque une notification comme lue', function () {
    $admin = makeAdmin();

    $notifId = (string) Str::uuid();

    $admin->notifications()->create([
        'id' => $notifId,
        'type' => 'test',
        'data' => json_encode(['message' => 'Test']),
    ]);

    Livewire::actingAs($admin)
        ->test(NotificationBell::class)
        ->call('markRead', $notifId);

    $this->assertNotNull(
        $admin->notifications()->find($notifId)?->read_at
    );
});

it('page dashboard charge sans erreur', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});
