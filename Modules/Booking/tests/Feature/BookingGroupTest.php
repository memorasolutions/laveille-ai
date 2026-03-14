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
use Modules\Booking\Models\GroupRegistration;
use Modules\Booking\Services\GroupBookingService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createGroupAppointment(int $maxParticipants = 5): Appointment
{
    $service = ServiceModel::factory()->create([
        'is_group' => true,
        'max_participants' => $maxParticipants,
    ]);

    return Appointment::factory()->create([
        'service_id' => $service->id,
        'status' => 'confirmed',
    ]);
}

it('register crée une inscription de groupe', function () {
    $appointment = createGroupAppointment();
    $customer = BookingCustomer::factory()->create();

    $registration = app(GroupBookingService::class)->register($appointment, $customer);

    expect($registration)->toBeInstanceOf(GroupRegistration::class);
    expect($registration->appointment_id)->toBe($appointment->id);
    expect($registration->customer_id)->toBe($customer->id);
    expect($registration->status)->toBe('registered');
});

it('register refuse un service non-groupe', function () {
    $service = ServiceModel::factory()->create(['is_group' => false]);
    $appointment = Appointment::factory()->create(['service_id' => $service->id]);
    $customer = BookingCustomer::factory()->create();

    expect(fn () => app(GroupBookingService::class)->register($appointment, $customer))
        ->toThrow(RuntimeException::class, 'n\'accepte pas');
});

it('register refuse quand le groupe est complet', function () {
    $appointment = createGroupAppointment(2);

    // Remplir les 2 places
    GroupRegistration::factory()->count(2)->create([
        'appointment_id' => $appointment->id,
        'status' => 'registered',
    ]);

    $customer = BookingCustomer::factory()->create();

    expect(fn () => app(GroupBookingService::class)->register($appointment, $customer))
        ->toThrow(RuntimeException::class, 'complet');
});

it('cancelRegistration change le statut à cancelled', function () {
    $registration = GroupRegistration::factory()->create(['status' => 'registered']);

    app(GroupBookingService::class)->cancelRegistration($registration);

    expect($registration->fresh()->status)->toBe('cancelled');
});

it('spotsRemaining calcule correctement les places restantes', function () {
    $appointment = createGroupAppointment(5);

    GroupRegistration::factory()->count(3)->create([
        'appointment_id' => $appointment->id,
        'status' => 'registered',
    ]);

    expect(app(GroupBookingService::class)->spotsRemaining($appointment))->toBe(2);
});

it('spotsRemaining exclut les inscriptions annulées', function () {
    $appointment = createGroupAppointment(5);

    GroupRegistration::factory()->count(2)->create([
        'appointment_id' => $appointment->id,
        'status' => 'registered',
    ]);
    GroupRegistration::factory()->create([
        'appointment_id' => $appointment->id,
        'status' => 'cancelled',
    ]);

    expect(app(GroupBookingService::class)->spotsRemaining($appointment))->toBe(3);
});

it('register fonctionne après annulation libérant une place', function () {
    $appointment = createGroupAppointment(2);

    $reg1 = GroupRegistration::factory()->create([
        'appointment_id' => $appointment->id,
        'status' => 'registered',
    ]);
    GroupRegistration::factory()->create([
        'appointment_id' => $appointment->id,
        'status' => 'registered',
    ]);

    // Annuler une inscription pour libérer une place
    app(GroupBookingService::class)->cancelRegistration($reg1);

    $customer = BookingCustomer::factory()->create();
    $newReg = app(GroupBookingService::class)->register($appointment, $customer);

    expect($newReg->status)->toBe('registered');
});
