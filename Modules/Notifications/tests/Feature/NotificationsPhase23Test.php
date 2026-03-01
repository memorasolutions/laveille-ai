<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Notifications\Notifications\PasswordChangedNotification;
use Modules\Notifications\Notifications\SystemAlertNotification;
use Modules\Notifications\Notifications\WelcomeNotification;
use Modules\Notifications\Services\NotificationService;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('password changed notification can be sent via database', function () {
    $user = User::factory()->create();
    $service = app(NotificationService::class);

    $service->sendToUser($user, new PasswordChangedNotification);

    expect($user->notifications()->count())->toBe(1);
    expect($user->notifications->first()->data['type'])->toBe('password_changed');
});

test('password changed notification has correct mail content', function () {
    $user = User::factory()->make();
    $notification = new PasswordChangedNotification;
    $mailMessage = $notification->toMail($user);

    expect($mailMessage)->toBeInstanceOf(MailMessage::class);
    expect($mailMessage->subject)->toBe('Mot de passe modifié');
});

test('system alert notification stores level and message', function () {
    $user = User::factory()->create();
    $service = app(NotificationService::class);
    $message = 'Le disque est presque plein.';

    $service->sendToUser($user, new SystemAlertNotification('warning', $message));

    expect($user->notifications()->count())->toBe(1);
    $data = $user->notifications->first()->data;
    expect($data['type'])->toBe('system_alert');
    expect($data['level'])->toBe('warning');
    expect($data['message'])->toBe($message);
});

test('system alert notification has correct mail for critical level', function () {
    $user = User::factory()->make();
    $notification = new SystemAlertNotification('critical', 'Service externe indisponible.');
    $mailMessage = $notification->toMail($user);

    expect($mailMessage->subject)->toBe('Alerte système - '.config('app.name'));
    expect($mailMessage->greeting)->toBe('Alerte critique');
});

test('welcome notification still works', function () {
    $user = User::factory()->create();
    $service = app(NotificationService::class);

    $service->sendToUser($user, new WelcomeNotification);

    expect($user->notifications()->count())->toBe(1);
    expect($user->notifications->first()->data['type'])->toBe('welcome');
});
