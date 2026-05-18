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

/*
|--------------------------------------------------------------------------
| GET /newsletter/unsubscribe/{token} — vue confirmation idempotente
|--------------------------------------------------------------------------
*/

it('GET unsubscribe renders confirmation view and sets unsubscribed_at', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => null]);

    $response = $this->get(route('newsletter.unsubscribe', ['token' => $subscriber->token]));

    $response->assertOk();
    $response->assertViewIs('newsletter::unsubscribe-confirmed');
    $response->assertViewHas('subscriber');
    expect($subscriber->fresh()->unsubscribed_at)->not->toBeNull();
});

it('GET unsubscribe is idempotent — keeps original date for already unsubscribed user', function () {
    $original = now()->subDay()->startOfMinute();
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => $original]);

    $response = $this->get(route('newsletter.unsubscribe', ['token' => $subscriber->token]));

    $response->assertOk();
    $response->assertViewIs('newsletter::unsubscribe-confirmed');
    expect($subscriber->fresh()->unsubscribed_at->equalTo($original))->toBeTrue();
});

it('GET unsubscribe with invalid token redirects home with error flash', function () {
    $response = $this->get(route('newsletter.unsubscribe', ['token' => 'invalid-fake-token-xyz']));

    $response->assertRedirect('/');
    $response->assertSessionHas('error');
});

/*
|--------------------------------------------------------------------------
| POST /newsletter/feedback/{token} — survey
|--------------------------------------------------------------------------
*/

it('POST feedback with valid reason persists reason and feedback', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => now()]);

    $response = $this->postJson(route('newsletter.feedback', ['token' => $subscriber->token]), [
        'reason' => 'too_frequent',
        'feedback' => 'Trop souvent dans ma boîte.',
    ]);

    $response->assertOk();
    $response->assertJson(['ok' => true]);

    $fresh = $subscriber->fresh();
    expect($fresh->unsubscribe_reason)->toBe('too_frequent');
    expect($fresh->unsubscribe_feedback)->toBe('Trop souvent dans ma boîte.');
});

it('POST feedback with invalid reason returns 422', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => now()]);

    $response = $this->postJson(route('newsletter.feedback', ['token' => $subscriber->token]), [
        'reason' => 'bogus_value',
    ]);

    $response->assertStatus(422);
});

it('POST feedback accepts all 5 enumerated reasons', function (string $reason) {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => now()]);

    $response = $this->postJson(route('newsletter.feedback', ['token' => $subscriber->token]), [
        'reason' => $reason,
    ]);

    $response->assertOk();
    expect($subscriber->fresh()->unsubscribe_reason)->toBe($reason);
})->with(['too_frequent', 'not_relevant', 'no_value', 'life_change', 'other']);

/*
|--------------------------------------------------------------------------
| POST /newsletter/pause/{token}
|--------------------------------------------------------------------------
*/

it('POST pause days=30 sets paused_until ~30 days and clears unsubscribed_at', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => now()]);

    $response = $this->postJson(route('newsletter.pause', ['token' => $subscriber->token]), [
        'days' => 30,
    ]);

    $response->assertOk();
    $response->assertJson(['ok' => true]);

    $fresh = $subscriber->fresh();
    expect($fresh->unsubscribed_at)->toBeNull();
    expect($fresh->paused_until)->not->toBeNull();
    // Fenêtre [29.9 ; 30.1] jours pour absorber la latence du test.
    expect($fresh->paused_until->diffInHours(now()))->toBeGreaterThan(29 * 24)->toBeLessThan(31 * 24);
});

it('POST pause rejects invalid days value', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => now()]);

    $response = $this->postJson(route('newsletter.pause', ['token' => $subscriber->token]), [
        'days' => 7,
    ]);

    $response->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| POST /newsletter/frequency/{token}
|--------------------------------------------------------------------------
*/

it('POST frequency monthly saves frequency_preference and re-enables', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => now()]);

    $response = $this->postJson(route('newsletter.frequency', ['token' => $subscriber->token]), [
        'frequency' => 'monthly',
    ]);

    $response->assertOk();
    $response->assertJson(['ok' => true]);

    $fresh = $subscriber->fresh();
    expect($fresh->frequency_preference)->toBe('monthly');
    expect($fresh->unsubscribed_at)->toBeNull();
});

it('POST frequency rejects invalid value', function () {
    $subscriber = Subscriber::factory()->create(['unsubscribed_at' => now()]);

    $response = $this->postJson(route('newsletter.frequency', ['token' => $subscriber->token]), [
        'frequency' => 'daily',
    ]);

    $response->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| POST /newsletter/resubscribe/{token}
|--------------------------------------------------------------------------
*/

it('POST resubscribe clears unsubscribed_at and paused_until', function () {
    $subscriber = Subscriber::factory()->create([
        'unsubscribed_at' => now()->subDay(),
        'paused_until' => now()->addDays(15),
    ]);

    $response = $this->postJson(route('newsletter.resubscribe', ['token' => $subscriber->token]));

    $response->assertOk();
    $response->assertJson(['ok' => true]);

    $fresh = $subscriber->fresh();
    expect($fresh->unsubscribed_at)->toBeNull();
    expect($fresh->paused_until)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Token invalide pour les routes POST → 404 JSON
|--------------------------------------------------------------------------
*/

it('POST routes return 404 JSON for invalid token', function (string $routeName, array $payload) {
    $response = $this->postJson(route($routeName, ['token' => 'fake-bad-token']), $payload);

    $response->assertStatus(404);
    $response->assertJson(['ok' => false]);
})->with([
    'feedback' => ['newsletter.feedback', ['reason' => 'too_frequent']],
    'pause' => ['newsletter.pause', ['days' => 30]],
    'frequency' => ['newsletter.frequency', ['frequency' => 'monthly']],
    'resubscribe' => ['newsletter.resubscribe', []],
]);
