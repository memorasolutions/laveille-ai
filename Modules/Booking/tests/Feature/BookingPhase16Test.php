<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('un admin peut voir la page calendrier', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');

    $response = $this->actingAs($admin)->get(route('admin.booking.calendar.index'));
    $response->assertOk();
    $response->assertSee('Calendrier interactif');
});

it('endpoint events retourne JSON avec appointments', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHour(),
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.booking.calendar.events', [
        'start' => now()->startOfMonth()->toIso8601String(),
        'end' => now()->addMonth()->endOfMonth()->toIso8601String(),
    ]));

    $response->assertOk();
    $response->assertJsonStructure([['id', 'title', 'start', 'end', 'color', 'url']]);
});

it('filtrage par dates retourne uniquement les rendez-vous dans la plage', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();

    $inRange = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => '2026-06-15 10:00:00',
        'end_at' => '2026-06-15 11:00:00',
        'status' => 'confirmed',
    ]);

    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => '2026-08-15 10:00:00',
        'end_at' => '2026-08-15 11:00:00',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.booking.calendar.events', [
        'start' => '2026-06-01T00:00:00Z',
        'end' => '2026-06-30T23:59:59Z',
    ]));

    $response->assertOk()->assertJsonCount(1);
    $response->assertJsonFragment(['id' => $inRange->id]);
});

it('non authentifié est redirigé vers login', function () {
    $this->get(route('admin.booking.calendar.index'))->assertRedirect(route('login'));
});

it('couleurs correspondent aux statuts', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();

    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHour(),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.booking.calendar.events', [
        'start' => now()->startOfMonth()->toIso8601String(),
        'end' => now()->addMonth()->endOfMonth()->toIso8601String(),
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['color' => '#ffc107']);
});
