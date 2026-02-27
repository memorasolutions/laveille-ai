<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\NotificationBell;
use Modules\Notifications\Events\RealTimeNotification;
use Modules\Notifications\Notifications\SystemAlertNotification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('NotificationBell Livewire renders', function () {
    $this->actingAs($this->admin);

    Livewire::test(NotificationBell::class)
        ->assertSuccessful();
});

test('NotificationBell shows unread count zero', function () {
    $this->actingAs($this->admin);

    Livewire::test(NotificationBell::class)
        ->assertSet('unreadCount', 0);
});

test('NotificationBell shows unread count after notification', function () {
    $this->admin->notify(new SystemAlertNotification('info', 'Test'));

    $this->actingAs($this->admin);

    Livewire::test(NotificationBell::class)
        ->assertSet('unreadCount', 1);
});

test('markRead works', function () {
    $this->admin->notify(new SystemAlertNotification('info', 'Test'));
    $notifId = $this->admin->unreadNotifications()->first()->id;

    $this->actingAs($this->admin);

    Livewire::test(NotificationBell::class)
        ->call('markRead', $notifId)
        ->assertSet('unreadCount', 0);
});

test('markAllRead works', function () {
    $this->admin->notify(new SystemAlertNotification('info', 'Test 1'));
    $this->admin->notify(new SystemAlertNotification('warning', 'Test 2'));

    $this->actingAs($this->admin);

    Livewire::test(NotificationBell::class)
        ->assertSet('unreadCount', 2)
        ->call('markAllRead')
        ->assertSet('unreadCount', 0);
});

test('RealTimeNotification implements ShouldBroadcast', function () {
    $event = new RealTimeNotification($this->admin, 'Test');

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});

test('RealTimeNotification broadcasts on correct private channel', function () {
    $event = new RealTimeNotification($this->admin, 'Test');
    $channel = $event->broadcastOn();

    expect($channel)->toBeInstanceOf(PrivateChannel::class);
    expect($channel->name)->toContain((string) $this->admin->id);
});

test('broadcastAs returns notification.received', function () {
    $event = new RealTimeNotification($this->admin, 'Test');

    expect($event->broadcastAs())->toBe('notification.received');
});

test('broadcastWith returns correct data', function () {
    $event = new RealTimeNotification($this->admin, 'Test message', 'warning');
    $data = $event->broadcastWith();

    expect($data)->toHaveKeys(['message', 'type', 'timestamp'])
        ->and($data['message'])->toBe('Test message')
        ->and($data['type'])->toBe('warning');
});

test('NotificationBell has getListeners method', function () {
    $this->actingAs($this->admin);
    $component = new NotificationBell;
    $listeners = $component->getListeners();

    expect($listeners)->toBeArray()
        ->and(array_values($listeners))->toContain('onNotificationReceived');
});

test('NotificationBell has onNotificationReceived method', function () {
    expect(method_exists(NotificationBell::class, 'onNotificationReceived'))->toBeTrue();
});

test('admin notifications page accessible', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.notifications.index'))
        ->assertOk();
});

test('admin notification broadcast route works', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.notifications.broadcast'), [
            'level' => 'info',
            'message' => 'Test broadcast',
        ])
        ->assertRedirect();
});

test('notifications route exists', function () {
    expect(Route::has('admin.notifications.index'))->toBeTrue();
});

test('toast partial included in admin layout', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSee('toast-container', false);
});
