<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\SaaS\Models\Plan;
use Modules\SaaS\Services\SubscriptionService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
    $this->plan = Plan::factory()->create(['stripe_price_id' => 'price_test_170']);
});

// --- Routes existence ---

test('invoices route exists', function () {
    expect(Route::has('user.invoices'))->toBeTrue();
});

test('swap plan route exists', function () {
    expect(Route::has('user.subscription.swap'))->toBeTrue();
});

// --- Invoices page ---

test('invoices page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('user.invoices'))
        ->assertOk();
});

test('invoices page redirects guest to login', function () {
    $this->get(route('user.invoices'))
        ->assertRedirect('/login');
});

test('invoices page shows empty state when no invoices', function () {
    $this->actingAs($this->user)
        ->get(route('user.invoices'))
        ->assertSee(__('Aucune facture disponible'));
});

// --- Swap plan ---

test('swap plan requires authentication', function () {
    $this->post(route('user.subscription.swap'))
        ->assertRedirect('/login');
});

test('swap plan validates plan_id required', function () {
    $this->actingAs($this->user)
        ->post(route('user.subscription.swap'), [])
        ->assertSessionHasErrors('plan_id');
});

test('swap plan validates plan_id exists in database', function () {
    $this->actingAs($this->user)
        ->post(route('user.subscription.swap'), ['plan_id' => 99999])
        ->assertSessionHasErrors('plan_id');
});

test('swap plan fails without active subscription', function () {
    $this->actingAs($this->user)
        ->post(route('user.subscription.swap'), ['plan_id' => $this->plan->id])
        ->assertRedirect()
        ->assertSessionHas('error');
});

// --- SubscriptionService unit ---

test('getInvoices returns empty array for user without stripe id', function () {
    $service = app(SubscriptionService::class);
    $invoices = $service->getInvoices($this->user);

    expect($invoices)->toBeArray()->toBeEmpty();
});

test('getStatus returns inactive for user without subscription', function () {
    $service = app(SubscriptionService::class);
    $status = $service->getStatus($this->user);

    expect($status)
        ->toBeArray()
        ->and($status['status'])->toBe('inactive')
        ->and($status['subscribed'])->toBeFalse();
});

test('swap returns null for user without subscription', function () {
    $service = app(SubscriptionService::class);
    $result = $service->swap($this->user, 'price_test_170');

    expect($result)->toBeNull();
});

// --- Translations ---

test('translation key Historique de facturation exists in en', function () {
    app()->setLocale('en');
    expect(__('Historique de facturation'))->toBe('Billing history');
});

test('translation key Aucune facture disponible exists in en', function () {
    app()->setLocale('en');
    expect(__('Aucune facture disponible'))->toBe('No invoices available');
});

test('translation key Changer de plan exists in en', function () {
    app()->setLocale('en');
    expect(__('Changer de plan'))->toBe('Change plan');
});

test('translation key Plan changé avec succès exists in en', function () {
    app()->setLocale('en');
    expect(__('Plan changé avec succès.'))->toBe('Plan changed successfully.');
});

test('translation key Vous devez avoir un abonnement actif exists in en', function () {
    app()->setLocale('en');
    expect(__('Vous devez avoir un abonnement actif pour changer de plan.'))->toBe('You must have an active subscription to change plan.');
});
