<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('remind-pending dry-run lists subscribers J+1 without sending', function () {
    Subscriber::factory()->create([
        'created_at' => now()->subDay()->subHours(2),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
    ]);

    Notification::fake();

    $this->artisan('newsletter:remind-pending', ['--dry-run' => true])->assertSuccessful();

    Notification::assertNothingSent();
});

it('remind-pending sends notification to J+1 subscribers', function () {
    Subscriber::factory()->create([
        'created_at' => now()->subDay()->subHours(2),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
    ]);

    Notification::fake();

    $this->artisan('newsletter:remind-pending')->assertSuccessful();

    Notification::assertSentOnDemand(WelcomeNewsletterNotification::class);
});

it('remind-pending skips already confirmed subscribers', function () {
    Subscriber::factory()->create([
        'created_at' => now()->subDay()->subHours(2),
        'confirmed_at' => now(),
        'unsubscribed_at' => null,
    ]);

    Notification::fake();

    $this->artisan('newsletter:remind-pending')->assertSuccessful();

    Notification::assertNothingSent();
});

it('remind-pending skips unsubscribed subscribers', function () {
    Subscriber::factory()->create([
        'created_at' => now()->subDay()->subHours(2),
        'confirmed_at' => null,
        'unsubscribed_at' => now(),
    ]);

    Notification::fake();

    $this->artisan('newsletter:remind-pending')->assertSuccessful();

    Notification::assertNothingSent();
});

it('remind-pending skips subscribers outside 24-48h window', function () {
    Subscriber::factory()->create([
        'created_at' => now(),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
    ]);

    Subscriber::factory()->create([
        'created_at' => now()->subDays(3),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
    ]);

    Notification::fake();

    $this->artisan('newsletter:remind-pending')->assertSuccessful();

    Notification::assertNothingSent();
});

it('purge-unconfirmed dry-run does not modify subscribers', function () {
    $subscriber = Subscriber::factory()->create([
        'created_at' => now()->subDays(8),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
        'bounce_reason' => null,
    ]);

    $this->artisan('newsletter:purge-unconfirmed', ['--dry-run' => true])->assertSuccessful();

    expect($subscriber->fresh()->unsubscribed_at)->toBeNull();
});

it('purge-unconfirmed marks J+7 subscribers as unsubscribed', function () {
    $subscriber = Subscriber::factory()->create([
        'created_at' => now()->subDays(8),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
        'bounce_reason' => null,
    ]);

    $this->artisan('newsletter:purge-unconfirmed')->assertSuccessful();

    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
    expect($subscriber->fresh()->bounce_reason)->toBe('auto_purge_unconfirmed_j7');
});

it('purge-unconfirmed skips subscribers younger than 7 days', function () {
    $subscriber = Subscriber::factory()->create([
        'created_at' => now()->subDays(5),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
        'bounce_reason' => null,
    ]);

    $this->artisan('newsletter:purge-unconfirmed')->assertSuccessful();

    expect($subscriber->fresh()->unsubscribed_at)->toBeNull();
});

it('purge-unconfirmed skips already unsubscribed subscribers', function () {
    $subscriber = Subscriber::factory()->create([
        'created_at' => now()->subDays(8),
        'confirmed_at' => null,
        'unsubscribed_at' => now()->subDay(),
        'bounce_reason' => 'manual_test',
    ]);

    $this->artisan('newsletter:purge-unconfirmed')->assertSuccessful();

    expect($subscriber->fresh()->bounce_reason)->toBe('manual_test');
});

it('purge-unconfirmed never deletes records (Loi 25 compliance)', function () {
    $subscriber = Subscriber::factory()->create([
        'created_at' => now()->subDays(30),
        'confirmed_at' => null,
        'unsubscribed_at' => null,
        'bounce_reason' => null,
    ]);

    $subscriberId = $subscriber->id;

    $this->artisan('newsletter:purge-unconfirmed')->assertSuccessful();

    expect(Subscriber::find($subscriberId))->not->toBeNull();
});
