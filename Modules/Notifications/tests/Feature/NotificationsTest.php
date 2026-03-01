<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Modules\Notifications\Contracts\SmsDriverInterface;
use Modules\Notifications\Drivers\NullSmsDriver;
use Modules\Notifications\Services\NotificationService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('notification service is registered as singleton', function () {
    $service1 = app(NotificationService::class);
    $service2 = app(NotificationService::class);

    expect($service1)->toBeInstanceOf(NotificationService::class);
    expect($service1)->toBe($service2);
});

test('notification service can send to user', function () {
    $user = User::factory()->create();
    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['database'];
        }

        public function toArray($notifiable): array
        {
            return ['message' => 'Test notification'];
        }
    };

    $service = app(NotificationService::class);
    $service->sendToUser($user, $notification);

    expect($user->notifications()->count())->toBe(1);
    expect($user->notifications->first()->data['message'])->toBe('Test notification');
});

test('notification service can get unread notifications', function () {
    $user = User::factory()->create();
    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['database'];
        }

        public function toArray($notifiable): array
        {
            return ['message' => 'Unread test'];
        }
    };

    $service = app(NotificationService::class);
    $service->sendToUser($user, $notification);

    $unread = $service->getUnread($user);

    expect($unread)->toHaveCount(1);
});

test('notification service can mark notifications as read', function () {
    $user = User::factory()->create();
    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['database'];
        }

        public function toArray($notifiable): array
        {
            return ['message' => 'To be read'];
        }
    };

    $service = app(NotificationService::class);
    $service->sendToUser($user, $notification);

    expect($service->getUnread($user))->toHaveCount(1);

    $service->markAsRead($user);

    expect($service->getUnread($user->fresh()))->toHaveCount(0);
});

test('notification service can get all notifications', function () {
    $user = User::factory()->create();
    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['database'];
        }

        public function toArray($notifiable): array
        {
            return ['message' => 'All test'];
        }
    };

    $service = app(NotificationService::class);
    $service->sendToUser($user, $notification);
    $service->markAsRead($user);
    $service->sendToUser($user, $notification);

    $all = $service->getAll($user->fresh());

    expect($all)->toHaveCount(2);
});

// --- SMS Driver Tests ---

test('SmsDriverInterface exists', function () {
    expect(interface_exists(SmsDriverInterface::class))->toBeTrue();
});

test('NullSmsDriver implements SmsDriverInterface', function () {
    $driver = new NullSmsDriver;

    expect($driver)->toBeInstanceOf(SmsDriverInterface::class);
});

test('NullSmsDriver send returns true', function () {
    $driver = new NullSmsDriver;

    expect($driver->send('+15141234567', 'Test SMS'))->toBeTrue();
});

test('NullSmsDriver sendBulk returns results', function () {
    $driver = new NullSmsDriver;
    $recipients = ['+15141111111', '+15142222222'];

    $results = $driver->sendBulk($recipients, 'Bulk test');

    expect($results)->toHaveCount(2);
    expect($results['+15141111111'])->toBeTrue();
});

test('NullSmsDriver is not configured', function () {
    $driver = new NullSmsDriver;

    expect($driver->isConfigured())->toBeFalse();
    expect($driver->getBalance())->toBeNull();
});

test('SmsDriverInterface is bound in container', function () {
    $driver = app(SmsDriverInterface::class);

    expect($driver)->toBeInstanceOf(NullSmsDriver::class);
});
