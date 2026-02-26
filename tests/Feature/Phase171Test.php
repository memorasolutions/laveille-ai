<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Modules\SaaS\Services\SaasMetricsService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

// --- SaasMetricsService ---

test('SaasMetricsService is registered as singleton', function () {
    $instance1 = app(SaasMetricsService::class);
    $instance2 = app(SaasMetricsService::class);

    expect($instance1)->toBe($instance2);
});

test('getMrr returns 0 with no subscriptions', function () {
    expect(app(SaasMetricsService::class)->getMrr())->toBe(0.0);
});

test('getArr returns 0 with no subscriptions', function () {
    expect(app(SaasMetricsService::class)->getArr())->toBe(0.0);
});

test('getActiveSubscribersCount returns 0 with no subscriptions', function () {
    expect(app(SaasMetricsService::class)->getActiveSubscribersCount())->toBe(0);
});

test('getTrialSubscribersCount returns 0 with no subscriptions', function () {
    expect(app(SaasMetricsService::class)->getTrialSubscribersCount())->toBe(0);
});

test('getChurnRate returns 0 when no subscribers', function () {
    expect(app(SaasMetricsService::class)->getChurnRate())->toBe(0.0);
});

test('getNewSubscribersThisMonth returns 0 with no subscriptions', function () {
    expect(app(SaasMetricsService::class)->getNewSubscribersThisMonth())->toBe(0);
});

test('getRevenueByPlan returns empty array with no subscriptions', function () {
    expect(app(SaasMetricsService::class)->getRevenueByPlan())->toBeEmpty();
});

test('getAllMetrics returns complete structure', function () {
    $metrics = app(SaasMetricsService::class)->getAllMetrics();

    expect($metrics)->toHaveKeys([
        'mrr', 'arr', 'active', 'trial',
        'cancelled_this_month', 'churn_rate', 'new_this_month',
    ]);
});

// --- Admin metrics endpoint ---

test('revenue metrics route exists', function () {
    expect(Route::has('admin.revenue.metrics'))->toBeTrue();
});

test('admin can access metrics endpoint', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue.metrics'))
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'metrics' => ['mrr', 'arr', 'active', 'trial', 'cancelled_this_month', 'churn_rate', 'new_this_month'],
            'revenue_by_plan',
        ]);
});

test('non-admin cannot access metrics endpoint', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.revenue.metrics'))
        ->assertForbidden();
});

test('guest cannot access metrics endpoint', function () {
    $this->get(route('admin.revenue.metrics'))
        ->assertRedirect('/login');
});

// --- Trial expiry command ---

test('trial expiry command exists and runs', function () {
    $exitCode = Artisan::call('saas:trial-expiry-notify');

    expect($exitCode)->toBe(0);
});

test('trial expiry command is scheduled daily', function () {
    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

    $events = collect($schedule->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'saas:trial-expiry-notify'));

    expect($events)->not->toBeEmpty();
});

// --- Translations ---

test('translation Métriques SaaS exists in en', function () {
    app()->setLocale('en');
    expect(__('Métriques SaaS'))->toBe('SaaS metrics');
});

test('translation Taux de désabonnement exists in en', function () {
    app()->setLocale('en');
    expect(__('Taux de désabonnement'))->toBe('Churn rate');
});

test('translation Revenus mensuels récurrents exists in en', function () {
    app()->setLocale('en');
    expect(__('Revenus mensuels récurrents'))->toBe('Monthly recurring revenue');
});
