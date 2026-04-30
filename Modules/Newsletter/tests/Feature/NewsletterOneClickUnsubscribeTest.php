<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Newsletter\Models\Subscriber;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('one-click POST sets unsubscribed_at and returns 204', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => null]);

    $response = $this->post(route('newsletter.unsubscribe.oneclick', ['token' => $subscriber->token]));

    $response->assertStatus(204);
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('one-click POST invalid token returns 204 silently', function () {
    $response = $this->post(route('newsletter.unsubscribe.oneclick', ['token' => 'invalide-fake-token']));

    $response->assertStatus(204);
});

it('one-click POST is idempotent for already unsubscribed subscriber', function () {
    $original = now()->subDay()->startOfMinute();
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => $original]);

    $response = $this->post(route('newsletter.unsubscribe.oneclick', ['token' => $subscriber->token]));

    $response->assertStatus(204);
    expect($subscriber->fresh()->unsubscribed_at->equalTo($original))->toBeTrue();
});

it('one-click POST is exempt from CSRF', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => null]);

    $response = $this->post(route('newsletter.unsubscribe.oneclick', ['token' => $subscriber->token]));

    $response->assertStatus(204);
});
