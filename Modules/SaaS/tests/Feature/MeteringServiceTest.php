<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\UsageRecord;
use Modules\SaaS\Services\MeteringService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = new MeteringService;
});

it('records a usage entry', function () {
    $record = $this->service->record($this->user, 'api_calls', 5, ['endpoint' => '/test']);

    expect($record)->toBeInstanceOf(UsageRecord::class)
        ->and($record->user_id)->toBe($this->user->id)
        ->and($record->metric)->toBe('api_calls')
        ->and($record->quantity)->toBe(5)
        ->and($record->metadata)->toBe(['endpoint' => '/test']);
});

it('gets total usage for a metric', function () {
    $this->service->record($this->user, 'api_calls', 10);
    $this->service->record($this->user, 'api_calls', 20);
    $this->service->record($this->user, 'storage', 50);

    expect($this->service->getUsage($this->user, 'api_calls'))->toBe(30)
        ->and($this->service->getUsage($this->user, 'storage'))->toBe(50);
});

it('gets usage filtered by period', function () {
    $this->service->record($this->user, 'api_calls', 100);

    // Create an old record directly
    UsageRecord::create([
        'user_id' => $this->user->id,
        'metric' => 'api_calls',
        'quantity' => 200,
        'recorded_at' => now()->subMonths(2),
    ]);

    expect($this->service->getUsageByPeriod($this->user, 'api_calls', 'month'))->toBe(100);
});

it('checks limit returns true when under limit', function () {
    $this->service->record($this->user, 'api_calls', 50);

    expect($this->service->checkLimit($this->user, 'api_calls', 100))->toBeTrue();
});

it('checks limit returns false when at or over limit', function () {
    $this->service->record($this->user, 'api_calls', 100);

    expect($this->service->checkLimit($this->user, 'api_calls', 100))->toBeFalse();
});

it('resets monthly by deleting old records', function () {
    $this->service->record($this->user, 'api_calls', 10);

    UsageRecord::create([
        'user_id' => $this->user->id,
        'metric' => 'api_calls',
        'quantity' => 50,
        'recorded_at' => now()->subMonths(2),
    ]);

    $deleted = $this->service->resetMonthly();

    expect($deleted)->toBe(1)
        ->and(UsageRecord::count())->toBe(1);
});

it('returns grouped metrics for a user', function () {
    $this->service->record($this->user, 'api_calls', 10);
    $this->service->record($this->user, 'api_calls', 20);
    $this->service->record($this->user, 'emails', 5);

    $metrics = $this->service->getMetrics($this->user);

    expect($metrics)->toHaveKey('api_calls', 30)
        ->and($metrics)->toHaveKey('emails', 5);
});
