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

// --- GroupRegistration model ---

it('GroupRegistration a les relations appointment et customer', function () {
    $service = ServiceModel::factory()->create(['is_group' => true, 'max_participants' => 5]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id]);

    $registration = GroupRegistration::create([
        'appointment_id' => $appointment->id,
        'customer_id' => $customer->id,
        'status' => 'registered',
        'registered_at' => now(),
    ]);

    expect($registration->appointment)->toBeInstanceOf(Appointment::class);
    expect($registration->customer)->toBeInstanceOf(BookingCustomer::class);
});

it('GroupRegistration scopes active et forAppointment fonctionnent', function () {
    $service = ServiceModel::factory()->create(['is_group' => true, 'max_participants' => 10]);
    $customer1 = BookingCustomer::factory()->create();
    $customer2 = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer1->id]);

    GroupRegistration::create(['appointment_id' => $appointment->id, 'customer_id' => $customer1->id, 'status' => 'registered', 'registered_at' => now()]);
    GroupRegistration::create(['appointment_id' => $appointment->id, 'customer_id' => $customer2->id, 'status' => 'cancelled', 'registered_at' => now()]);

    expect(GroupRegistration::active()->count())->toBe(1);
    expect(GroupRegistration::forAppointment($appointment->id)->count())->toBe(2);
    expect(GroupRegistration::active()->forAppointment($appointment->id)->count())->toBe(1);
});

// --- GroupBookingService ---

it('GroupBookingService register crée une inscription pour un service de groupe', function () {
    $service = ServiceModel::factory()->create(['is_group' => true, 'max_participants' => 3]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id]);

    $registration = app(GroupBookingService::class)->register($appointment, $customer);

    expect($registration)->toBeInstanceOf(GroupRegistration::class);
    expect($registration->status)->toBe('registered');
    $this->assertDatabaseHas('booking_group_registrations', [
        'appointment_id' => $appointment->id,
        'customer_id' => $customer->id,
    ]);
});

it('GroupBookingService register refuse un service non-groupe', function () {
    $service = ServiceModel::factory()->create(['is_group' => false, 'max_participants' => 1]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id]);

    expect(fn () => app(GroupBookingService::class)->register($appointment, $customer))
        ->toThrow(\RuntimeException::class);
});

it('GroupBookingService register refuse quand le groupe est plein', function () {
    $service = ServiceModel::factory()->create(['is_group' => true, 'max_participants' => 2]);
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => BookingCustomer::factory()->create()->id]);

    $gbs = app(GroupBookingService::class);
    $gbs->register($appointment, BookingCustomer::factory()->create());
    $gbs->register($appointment, BookingCustomer::factory()->create());

    expect(fn () => $gbs->register($appointment, BookingCustomer::factory()->create()))
        ->toThrow(\RuntimeException::class);
});

it('GroupBookingService spotsRemaining retourne le bon nombre', function () {
    $service = ServiceModel::factory()->create(['is_group' => true, 'max_participants' => 5]);
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => BookingCustomer::factory()->create()->id]);

    $gbs = app(GroupBookingService::class);

    expect($gbs->spotsRemaining($appointment))->toBe(5);

    $gbs->register($appointment, BookingCustomer::factory()->create());
    expect($gbs->spotsRemaining($appointment))->toBe(4);

    $gbs->register($appointment, BookingCustomer::factory()->create());
    expect($gbs->spotsRemaining($appointment))->toBe(3);
});

it('GroupBookingService cancelRegistration met le statut à cancelled', function () {
    $service = ServiceModel::factory()->create(['is_group' => true, 'max_participants' => 5]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id]);

    $gbs = app(GroupBookingService::class);
    $registration = $gbs->register($appointment, $customer);

    $gbs->cancelRegistration($registration);

    expect($registration->fresh()->status)->toBe('cancelled');
    expect($gbs->spotsRemaining($appointment))->toBe(5);
});

// --- Customer stats ---

it('BookingCustomer a les champs total_bookings, total_spent, last_booking_at', function () {
    $customer = BookingCustomer::factory()->create();
    $customer->refresh();

    expect((int) $customer->total_bookings)->toBe(0);
    expect((float) $customer->total_spent)->toBe(0.0);
    expect($customer->last_booking_at)->toBeNull();

    $customer->update([
        'total_bookings' => 5,
        'total_spent' => 250.50,
        'last_booking_at' => now(),
    ]);

    $customer->refresh();
    expect($customer->total_bookings)->toBe(5);
    expect((float) $customer->total_spent)->toBe(250.50);
    expect($customer->last_booking_at)->not->toBeNull();
});
