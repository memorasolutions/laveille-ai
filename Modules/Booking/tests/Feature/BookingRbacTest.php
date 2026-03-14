<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

$adminRoutes = [
    'admin.booking.dashboard',
    'admin.booking.appointments.index',
    'admin.booking.services.index',
    'admin.booking.settings.edit',
    'admin.booking.coupons.index',
    'admin.booking.packages.index',
    'admin.booking.gift-cards.index',
    'admin.booking.date-overrides.index',
    'admin.booking.analytics',
    'admin.booking.customers.index',
    'admin.booking.webhooks.index',
    'admin.booking.calendar.index',
];

it('guest est redirigé vers login pour chaque route admin booking', function () use ($adminRoutes) {
    foreach ($adminRoutes as $routeName) {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }
});

it('user sans permission reçoit 403 pour chaque route admin booking', function () use ($adminRoutes) {
    $user = \App\Models\User::factory()->create();

    foreach ($adminRoutes as $routeName) {
        $this->actingAs($user)->get(route($routeName))->assertForbidden();
    }
});

it('admin avec manage_booking accède à toutes les routes', function () use ($adminRoutes) {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');

    foreach ($adminRoutes as $routeName) {
        $this->actingAs($admin)->get(route($routeName))->assertOk();
    }
});
