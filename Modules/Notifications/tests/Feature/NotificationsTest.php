<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
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
