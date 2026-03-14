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

it('un admin peut voir le tableau de bord booking', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');

    $response = $this->actingAs($admin)->get(route('admin.booking.dashboard'));
    $response->assertOk();
    $response->assertSee('Tableau de bord');
    $response->assertSee('Aujourd', false);
});

it('le dashboard affiche les KPI corrects', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create(['price' => 50.00]);
    $customer = BookingCustomer::factory()->create();
    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => now()->setTime(14, 0),
        'end_at' => now()->setTime(15, 0),
        'status' => 'confirmed',
    ]);

    // RV pending_approval
    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => now()->addDays(2)->setTime(10, 0),
        'end_at' => now()->addDays(2)->setTime(11, 0),
        'status' => 'pending_approval',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.booking.dashboard'));
    $response->assertOk();
    $response->assertSee('50,00');
});

it('le dashboard affiche les prochains rendez-vous', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create(['name' => 'Massage détente']);
    $customer = BookingCustomer::factory()->create(['first_name' => 'Julie', 'last_name' => 'Lavoie']);

    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(11, 0),
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.booking.dashboard'));
    $response->assertOk();
    $response->assertSee('Massage détente');
    $response->assertSee('Julie Lavoie');
});

it('non authentifié est redirigé vers login', function () {
    $this->get(route('admin.booking.dashboard'))->assertRedirect(route('login'));
});
