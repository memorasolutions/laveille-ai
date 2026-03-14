<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Modules\Blog\Models\Article;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\DigestNotification;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

test('newsletter:digest command is registered', function () {
    expect(Artisan::all())->toHaveKey('newsletter:digest');
});

test('digest skips when disabled in settings', function () {
    $this->artisan('newsletter:digest')
        ->expectsOutputToContain('disabled')
        ->assertExitCode(0);
});

test('digest sends with --force even when disabled', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();
    Article::factory()->create(['status' => 'published', 'published_at' => now()]);

    Notification::fake();

    $this->artisan('newsletter:digest', ['--force' => true])
        ->assertExitCode(0);

    Notification::assertSentTo($subscriber, DigestNotification::class);
});

test('digest skips when no recent articles', function () {
    Setting::set('newsletter.digest_enabled', true);
    Subscriber::factory()->confirmed()->create();

    $this->artisan('newsletter:digest')
        ->expectsOutputToContain('No new articles')
        ->assertExitCode(0);
});

test('digest skips when no active subscribers', function () {
    Setting::set('newsletter.digest_enabled', true);
    Article::factory()->create(['status' => 'published', 'published_at' => now()]);

    $this->artisan('newsletter:digest')
        ->expectsOutputToContain('No active subscribers')
        ->assertExitCode(0);
});

test('digest sends to active subscribers only', function () {
    Setting::set('newsletter.digest_enabled', true);
    $active1 = Subscriber::factory()->confirmed()->create();
    $active2 = Subscriber::factory()->confirmed()->create();
    $unsubscribed = Subscriber::factory()->create(['unsubscribed_at' => now()]);
    Article::factory()->create(['status' => 'published', 'published_at' => now()]);

    Notification::fake();

    $this->artisan('newsletter:digest')
        ->assertExitCode(0);

    Notification::assertSentTo($active1, DigestNotification::class);
    Notification::assertSentTo($active2, DigestNotification::class);
    Notification::assertNotSentTo($unsubscribed, DigestNotification::class);
});

test('digest is scheduled', function () {
    $events = \Illuminate\Support\Facades\Schedule::events();

    $found = collect($events)->contains(function ($event) {
        return str_contains($event->command ?? '', 'newsletter:digest');
    });

    expect($found)->toBeTrue();
});
