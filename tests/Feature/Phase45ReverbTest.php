<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Notifications\Events\RealTimeNotification;
use Modules\Notifications\Notifications\SystemAlertNotification;

uses(RefreshDatabase::class);

// --- Config ---

it('reverb config exists with correct default', function () {
    expect(config('reverb.default'))->toBe('reverb')
        ->and(config('reverb.servers.reverb'))->toBeArray()
        ->and(config('reverb.servers.reverb'))->toHaveKeys(['host', 'port']);
});

it('broadcasting config has reverb connection', function () {
    expect(config('broadcasting.connections.reverb'))->toBeArray()
        ->and(config('broadcasting.connections.reverb.driver'))->toBe('reverb');
});

// --- RealTimeNotification Event ---

it('RealTimeNotification implements ShouldBroadcast', function () {
    expect(new RealTimeNotification(User::factory()->create(), 'test'))
        ->toBeInstanceOf(ShouldBroadcast::class);
});

it('RealTimeNotification broadcasts on user private channel', function () {
    $user = User::factory()->create();
    $event = new RealTimeNotification($user, 'Hello');

    $channel = $event->broadcastOn();

    expect($channel)->toBeInstanceOf(PrivateChannel::class)
        ->and((string) $channel)->toContain((string) $user->id);
});

it('RealTimeNotification broadcastWith returns correct data', function () {
    $user = User::factory()->create();
    $event = new RealTimeNotification($user, 'Test message', 'warning');

    $data = $event->broadcastWith();

    expect($data)->toHaveKeys(['message', 'type', 'timestamp'])
        ->and($data['message'])->toBe('Test message')
        ->and($data['type'])->toBe('warning');
});

it('RealTimeNotification broadcastAs returns notification.received', function () {
    $user = User::factory()->create();
    $event = new RealTimeNotification($user, 'test');

    expect($event->broadcastAs())->toBe('notification.received');
});

it('RealTimeNotification defaults to info type', function () {
    $user = User::factory()->create();
    $event = new RealTimeNotification($user, 'test');

    expect($event->type)->toBe('info');
});

// --- SystemAlertNotification ---

it('SystemAlertNotification includes broadcast in via channels', function () {
    $notification = new SystemAlertNotification('info', 'Test alert');
    $user = User::factory()->create();

    $channels = $notification->via($user);

    expect($channels)->toContain('broadcast');
});

it('SystemAlertNotification toBroadcast returns BroadcastMessage', function () {
    $notification = new SystemAlertNotification('critical', 'Server down');
    $user = User::factory()->create();

    $broadcastMessage = $notification->toBroadcast($user);

    expect($broadcastMessage)->toBeInstanceOf(BroadcastMessage::class);
});

// --- Infrastructure files ---

it('supervisor reverb config exists', function () {
    expect(file_exists(config_path('supervisor/reverb.conf')))->toBeTrue();

    $content = file_get_contents(config_path('supervisor/reverb.conf'));
    expect($content)->toContain('reverb:start');
});

it('bootstrap.js contains Echo reverb config', function () {
    $content = file_get_contents(resource_path('js/bootstrap.js'));

    expect($content)->toContain("broadcaster: 'reverb'")
        ->and($content)->toContain('VITE_REVERB_APP_KEY')
        ->and($content)->toContain('laravel-echo');
});

it('env example has reverb variables', function () {
    $content = file_get_contents(base_path('.env.example'));

    expect($content)->toContain('REVERB_APP_ID')
        ->and($content)->toContain('REVERB_APP_KEY')
        ->and($content)->toContain('REVERB_APP_SECRET')
        ->and($content)->toContain('VITE_REVERB_APP_KEY');
});

// --- Channel authorization ---

it('user channel authorizes correct user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/broadcasting/auth?channel_name=private-App.Models.User.'.$user->id)
        ->assertSuccessful();
});
