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

it('la commande booking:send-reminders s\'exécute sans erreur', function () {
    $this->artisan('booking:send-reminders')
        ->assertExitCode(0);
});

it('les no-shows sont marqués pour les rendez-vous confirmés passés', function () {
    $customer = BookingCustomer::factory()->create(['no_show_count' => 0]);
    $service = ServiceModel::factory()->create();

    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'confirmed',
        'start_at' => now()->subHours(3),
        'end_at' => now()->subHours(2),
    ]);

    $this->artisan('booking:send-reminders')->assertExitCode(0);

    expect($appointment->fresh()->status)->toBe('no_show');
    expect($customer->fresh()->no_show_count)->toBe(1);
});

it('les rendez-vous pending ne sont pas marqués no-show', function () {
    $customer = BookingCustomer::factory()->create();
    $service = ServiceModel::factory()->create();

    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'pending',
        'start_at' => now()->subHours(3),
        'end_at' => now()->subHours(2),
    ]);

    $this->artisan('booking:send-reminders')->assertExitCode(0);

    expect($appointment->fresh()->status)->toBe('pending');
});

it('les rendez-vous futurs confirmés ne sont pas marqués no-show', function () {
    $customer = BookingCustomer::factory()->create();
    $service = ServiceModel::factory()->create();

    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'confirmed',
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addHour(),
    ]);

    $this->artisan('booking:send-reminders')->assertExitCode(0);

    expect($appointment->fresh()->status)->toBe('confirmed');
});

it('le no_show_count s\'accumule sur le client', function () {
    $customer = BookingCustomer::factory()->create(['no_show_count' => 2]);
    $service = ServiceModel::factory()->create();

    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'confirmed',
        'start_at' => now()->subHours(3),
        'end_at' => now()->subHours(2),
    ]);

    $this->artisan('booking:send-reminders')->assertExitCode(0);

    expect($customer->fresh()->no_show_count)->toBe(3);
});
