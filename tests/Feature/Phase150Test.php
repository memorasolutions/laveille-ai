<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use NotificationChannels\WebPush\WebPushChannel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

// --- Infrastructure ---

it('push_subscriptions table exists', function () {
    expect(Schema::hasTable('push_subscriptions'))->toBeTrue();
});

it('webpush config file exists', function () {
    expect(file_exists(config_path('webpush.php')))->toBeTrue();
});

it('service worker contains push event handler', function () {
    $sw = file_get_contents(public_path('service-worker.js'));
    expect($sw)->toContain("addEventListener('push'");
});

it('manifest.webmanifest route exists', function () {
    $this->get('/manifest.webmanifest')->assertStatus(200);
});

it('User model uses HasPushSubscriptions trait', function () {
    $traits = class_uses_recursive(User::class);
    expect($traits)->toContain(\NotificationChannels\WebPush\HasPushSubscriptions::class);
});

it('WebPushNotification class exists', function () {
    expect(class_exists(\Modules\Notifications\Notifications\WebPushNotification::class))->toBeTrue();
});

it('WebPushNotification uses WebPushChannel', function () {
    $notification = new \Modules\Notifications\Notifications\WebPushNotification('Test', 'Body');
    $channels = $notification->via($this->user);
    expect($channels)->toContain(WebPushChannel::class);
});

it('GenerateVapidKeysCommand exists', function () {
    expect(class_exists(\Modules\Notifications\Console\GenerateVapidKeysCommand::class))->toBeTrue();
});

// --- API Push Subscriptions ---

it('stores push subscription with valid data', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
        'keys' => [
            'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQ',
            'auth' => 'tBHItJI5svbpC7',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $this->user->id,
        'subscribable_type' => User::class,
    ]);
});

it('validates endpoint is required', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/push-subscriptions', [
        'keys' => ['p256dh' => 'test', 'auth' => 'test'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
});

it('validates endpoint is a valid URL', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'not-a-url',
        'keys' => ['p256dh' => 'test', 'auth' => 'test'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
});

it('validates keys are required', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/test',
    ])
        ->assertUnprocessable();
});

it('deletes push subscription', function () {
    Sanctum::actingAs($this->user);

    $endpoint = 'https://fcm.googleapis.com/fcm/send/delete-test';
    $this->user->updatePushSubscription($endpoint, 'p256dh-key', 'auth-key');

    $this->deleteJson('/api/v1/push-subscriptions', [
        'endpoint' => $endpoint,
    ])
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('returns 401 for unauthenticated push subscription', function () {
    $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/test',
        'keys' => ['p256dh' => 'test', 'auth' => 'test'],
    ])
        ->assertUnauthorized();
});

it('returns 401 for unauthenticated push deletion', function () {
    $this->deleteJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/test',
    ])
        ->assertUnauthorized();
});

// --- Settings ---

it('push settings exist in seeder', function () {
    $this->seed(\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class);

    $this->assertDatabaseHas('settings', ['key' => 'push.web_push_enabled']);
    $this->assertDatabaseHas('settings', ['key' => 'push.vapid_public_key']);
    $this->assertDatabaseHas('settings', ['key' => 'push.vapid_private_key']);
});

// --- Profile UI ---

it('profile page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('user.profile'))
        ->assertOk();
});
