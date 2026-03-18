<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\UsageRecord;
use Modules\SaaS\Services\UsageMeteringService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function meteringService(): UsageMeteringService
{
    return app(UsageMeteringService::class);
}

test('record creates usage record in database', function () {
    $user = User::factory()->create();

    $record = meteringService()->record($user->id, 'api_calls', 5);

    expect($record)->toBeInstanceOf(UsageRecord::class);
    $this->assertDatabaseHas('usage_records', [
        'user_id' => $user->id,
        'metric' => 'api_calls',
        'quantity' => 5,
    ]);
});

test('getCurrentUsage sums current month records', function () {
    $user = User::factory()->create();
    $service = meteringService();

    $service->record($user->id, 'api_calls', 2);
    $service->record($user->id, 'api_calls', 3);

    expect($service->getCurrentUsage($user->id, 'api_calls'))->toBe(5);
});

test('getCurrentUsage ignores other months', function () {
    $user = User::factory()->create();

    // Last month record (manual insert to bypass service cache)
    UsageRecord::create([
        'user_id' => $user->id,
        'metric' => 'api_calls',
        'quantity' => 100,
        'recorded_at' => now()->subMonth(),
    ]);

    // This month via service
    meteringService()->record($user->id, 'api_calls', 3);

    expect(meteringService()->getCurrentUsage($user->id, 'api_calls'))->toBe(3);
});

test('checkLimit returns true when under limit', function () {
    $user = User::factory()->create();
    meteringService()->record($user->id, 'api_calls', 5);

    expect(meteringService()->checkLimit($user->id, 'api_calls', 10))->toBeTrue();
});

test('checkLimit returns false when at or over limit', function () {
    $user = User::factory()->create();
    meteringService()->record($user->id, 'api_calls', 10);

    expect(meteringService()->checkLimit($user->id, 'api_calls', 10))->toBeFalse();
});

test('getRemainingQuota calculates correctly', function () {
    $user = User::factory()->create();
    meteringService()->record($user->id, 'api_calls', 3);

    expect(meteringService()->getRemainingQuota($user->id, 'api_calls', 10))->toBe(7);
    expect(meteringService()->getRemainingQuota($user->id, 'api_calls', 2))->toBe(0);
});

test('getUsageSummary groups by metric', function () {
    $user = User::factory()->create();
    $service = meteringService();

    $service->record($user->id, 'api_calls', 1);
    $service->record($user->id, 'api_calls', 2);
    $service->record($user->id, 'storage', 50);

    $summary = $service->getUsageSummary($user->id);

    expect($summary)->toHaveKey('api_calls', 3)
        ->toHaveKey('storage', 50);
});

test('getUsageByDay returns daily breakdown with zero-filled days', function () {
    $user = User::factory()->create();

    UsageRecord::create([
        'user_id' => $user->id,
        'metric' => 'api_calls',
        'quantity' => 5,
        'recorded_at' => now()->subDays(2),
    ]);
    UsageRecord::create([
        'user_id' => $user->id,
        'metric' => 'api_calls',
        'quantity' => 3,
        'recorded_at' => now(),
    ]);

    $byDay = meteringService()->getUsageByDay($user->id, 'api_calls', 5);

    expect($byDay)->toHaveCount(5);
    expect($byDay[now()->subDays(2)->format('Y-m-d')])->toBe(5);
    expect($byDay[now()->format('Y-m-d')])->toBe(3);
    expect($byDay[now()->subDays(1)->format('Y-m-d')])->toBe(0);
});
