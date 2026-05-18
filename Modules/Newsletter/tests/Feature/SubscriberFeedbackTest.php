<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Newsletter\Models\Subscriber;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('migration adds the 4 unsubscribe feedback columns to newsletter_subscribers', function () {
    expect(Schema::hasColumns('newsletter_subscribers', [
        'unsubscribe_reason',
        'unsubscribe_feedback',
        'paused_until',
        'frequency_preference',
    ]))->toBeTrue();
});

it('scope notPaused excludes future paused subscribers and includes past/null paused', function () {
    $futurePaused = Subscriber::factory()->confirmed()->create([
        'paused_until' => now()->addDays(7),
    ]);

    $pastPaused = Subscriber::factory()->confirmed()->create([
        'paused_until' => now()->subDay(),
    ]);

    $neverPaused = Subscriber::factory()->confirmed()->create([
        'paused_until' => null,
    ]);

    $ids = Subscriber::notPaused()->pluck('id')->all();

    expect($ids)->toContain($pastPaused->id)
        ->and($ids)->toContain($neverPaused->id)
        ->and($ids)->not->toContain($futurePaused->id);
});

it('isPaused returns true when paused_until is in the future', function () {
    $subscriber = Subscriber::factory()->confirmed()->create([
        'paused_until' => now()->addDays(3),
    ]);

    expect($subscriber->isPaused())->toBeTrue();
});

it('isPaused returns false when paused_until is in the past', function () {
    $subscriber = Subscriber::factory()->confirmed()->create([
        'paused_until' => now()->subHour(),
    ]);

    expect($subscriber->isPaused())->toBeFalse();
});

it('isPaused returns false when paused_until is null', function () {
    $subscriber = Subscriber::factory()->confirmed()->create([
        'paused_until' => null,
    ]);

    expect($subscriber->isPaused())->toBeFalse();
});
