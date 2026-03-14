<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Services\NotificationPreferenceService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guest is redirected from notification preferences', function () {
    $this->get(route('user.notification-preferences'))->assertRedirect();
});

test('authenticated user can view notification preferences', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user.notification-preferences'))
        ->assertOk()
        ->assertSee(__('Préférences de notification'));
});

test('user can update notification preferences', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('user.notification-preferences.update'), [
            'preferences' => [
                'system_alert.mail' => '1',
                'system_alert.database' => '1',
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'notification_type' => 'system_alert',
        'channel' => 'mail',
        'enabled' => true,
    ]);

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'notification_type' => 'payment_failed',
        'channel' => 'mail',
        'enabled' => false,
    ]);
});

test('isEnabled returns true by default for unconfigured type', function () {
    $user = User::factory()->create();

    expect(NotificationPreferenceService::isEnabled($user, 'system_alert', 'mail'))->toBeTrue();
});

test('isEnabled returns false when user disabled a type', function () {
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => 'payment_failed',
        'channel' => 'mail',
        'enabled' => false,
    ]);

    expect(NotificationPreferenceService::isEnabled($user, 'payment_failed', 'mail'))->toBeFalse();
});

test('configurableTypes returns expected structure', function () {
    $types = NotificationPreferenceService::configurableTypes();

    expect($types)->toBeArray()
        ->toHaveKey('system_alert')
        ->toHaveKey('payment_failed');

    expect($types['system_alert'])->toHaveKey('label')
        ->toHaveKey('channels');
});

test('preferences are deleted when user is deleted', function () {
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => 'system_alert',
        'channel' => 'mail',
        'enabled' => true,
    ]);

    $user->delete();

    $this->assertDatabaseMissing('notification_preferences', [
        'user_id' => $user->id,
    ]);
});
