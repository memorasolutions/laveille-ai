<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Modules\Notifications\Console\GenerateVapidKeysCommand;
use Modules\Notifications\Jobs\SendWebPushNotification;
use Modules\Notifications\Notifications\WebPushNotification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('POST push-subscriptions stores subscription for authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        'keys' => ['p256dh' => 'testkey123', 'auth' => 'authkey456'],
    ])->assertOk();
});

test('POST push-subscriptions requires authentication', function () {
    $this->postJson('/api/v1/push-subscriptions')->assertUnauthorized();
});

test('POST push-subscriptions validates required fields', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/push-subscriptions', [])->assertUnprocessable();
});

test('DELETE push-subscriptions removes subscription', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->deleteJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
    ])->assertOk();
});

test('DELETE push-subscriptions requires authentication', function () {
    $this->deleteJson('/api/v1/push-subscriptions')->assertUnauthorized();
});

test('WebPushNotification uses WebPushChannel', function () {
    $notification = new WebPushNotification('Title', 'Body');
    $user = User::factory()->create();

    expect($notification->via($user))->toContain(WebPushChannel::class);
});

test('WebPushNotification toWebPush returns WebPushMessage', function () {
    $notification = new WebPushNotification('Test', 'Body', '/url');
    $user = User::factory()->create();

    expect($notification->toWebPush($user))->toBeInstanceOf(WebPushMessage::class);
});

test('SendWebPushNotification job can be instantiated', function () {
    $job = new SendWebPushNotification('Title', 'Body', '/', 'admin');

    expect($job)->toBeInstanceOf(SendWebPushNotification::class);
    expect($job->title)->toBe('Title');
    expect($job->role)->toBe('admin');
});

test('admin push-notifications index accessible by admin', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/push-notifications')->assertOk();
});

test('admin push-notifications index denied for regular user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/push-notifications')->assertForbidden();
});

test('admin push-notifications store dispatches job', function () {
    Queue::fake();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->post('/admin/push-notifications', [
        'title' => 'Test notification',
        'body' => 'Message de test',
    ])->assertRedirect();

    Queue::assertPushed(SendWebPushNotification::class);
});

test('admin push-notifications store validates required fields', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/admin/push-notifications', [])
        ->assertSessionHasErrors(['title', 'body']);
});

test('GenerateVapidKeysCommand class exists', function () {
    expect(class_exists(GenerateVapidKeysCommand::class))->toBeTrue();
});
