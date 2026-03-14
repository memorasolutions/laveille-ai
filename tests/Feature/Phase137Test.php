<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Modules\SaaS\Http\Middleware\EnsureSubscribed;
use Modules\SaaS\Notifications\PaymentFailedNotification;
use Modules\SaaS\Notifications\SubscriptionCancelledNotification;
use Modules\SaaS\Notifications\TrialEndingNotification;
use Modules\SaaS\Services\SubscriptionService;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

test('subscription service is registered', function () {
    $service = resolve(SubscriptionService::class);
    expect($service)->toBeInstanceOf(SubscriptionService::class);
});

test('ensure subscribed middleware exists', function () {
    expect(class_exists(EnsureSubscribed::class))->toBeTrue();
});

test('subscription cancel route exists', function () {
    expect(route('user.subscription.cancel'))->not->toBeNull();
});

test('subscription resume route exists', function () {
    expect(route('user.subscription.resume'))->not->toBeNull();
});

test('invoice download route exists', function () {
    expect(route('user.invoices.download', 'inv_123'))->not->toBeNull();
});

test('revenue page accessible by admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.revenue'))
        ->assertOk();
});

test('revenue page shows stats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.revenue'))
        ->assertOk()
        ->assertSee('Actifs');
});

test('payment failed notification can be instantiated', function () {
    $notification = new PaymentFailedNotification('inv_123');
    expect($notification)->toBeInstanceOf(Notification::class);
});

test('subscription cancelled notification can be instantiated', function () {
    $notification = new SubscriptionCancelledNotification(null);
    expect($notification)->toBeInstanceOf(Notification::class);
});

test('trial ending notification can be instantiated', function () {
    $notification = new TrialEndingNotification('2026-03-01');
    expect($notification)->toBeInstanceOf(Notification::class);
});

test('subscription gates are defined from config', function () {
    expect(Gate::has('api_access'))->toBeTrue();
});

test('ensure subscribed middleware redirects non-subscribed user', function () {
    $user = User::factory()->create();

    $middleware = new EnsureSubscribed;
    $request = \Illuminate\Http\Request::create('/test');
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => new \Illuminate\Http\Response('OK'));

    expect($response->getStatusCode())->toBe(302);
});
