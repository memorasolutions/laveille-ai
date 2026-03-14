<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\RecurringAppointment;
use Modules\Booking\Models\WaitlistEntry;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createDeps(): array
{
    $service = BookingService::create([
        'name' => 'Massage', 'slug' => 'massage-'.uniqid(),
        'duration_minutes' => 60, 'price' => 100, 'is_active' => true,
    ]);
    $customer = BookingCustomer::create([
        'first_name' => 'Jean', 'last_name' => 'T',
        'email' => 'j'.uniqid().'@test.com',
    ]);

    return [$service, $customer];
}

// --- Waitlist ---

it('WaitlistEntry scope waiting filtre correctement', function () {
    [$service, $customer] = createDeps();

    WaitlistEntry::create(['service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'waiting']);
    WaitlistEntry::create(['service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'booked']);

    expect(WaitlistEntry::waiting()->count())->toBe(1);
});

it('WaitlistEntry scope forService filtre par service', function () {
    [$service1, $customer] = createDeps();
    $service2 = BookingService::create([
        'name' => 'Facial', 'slug' => 'facial-'.uniqid(),
        'duration_minutes' => 30, 'price' => 50, 'is_active' => true,
    ]);

    WaitlistEntry::create(['service_id' => $service1->id, 'customer_id' => $customer->id, 'status' => 'waiting']);
    WaitlistEntry::create(['service_id' => $service2->id, 'customer_id' => $customer->id, 'status' => 'waiting']);

    expect(WaitlistEntry::forService($service1->id)->count())->toBe(1);
});

it('WaitlistEntry scope forDate filtre par date', function () {
    [$service, $customer] = createDeps();
    $tomorrow = now()->addDay()->toDateString();

    WaitlistEntry::create([
        'service_id' => $service->id, 'customer_id' => $customer->id,
        'status' => 'waiting', 'preferred_date' => $tomorrow,
    ]);

    expect(WaitlistEntry::forDate($tomorrow)->count())->toBe(1);
    expect(WaitlistEntry::forDate(now()->subDay()->toDateString())->count())->toBe(0);
});

it('WaitlistEntry a les relations customer et service', function () {
    [$service, $customer] = createDeps();

    $entry = WaitlistEntry::create([
        'service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'waiting',
    ]);

    expect($entry->customer)->toBeInstanceOf(BookingCustomer::class);
    expect($entry->service)->toBeInstanceOf(BookingService::class);
});

// --- Recurring ---

it('RecurringAppointment scope active filtre correctement', function () {
    [$service, $customer] = createDeps();

    RecurringAppointment::create([
        'service_id' => $service->id, 'customer_id' => $customer->id,
        'frequency' => 'weekly', 'day_of_week' => 1, 'preferred_time' => '10:00',
        'starts_at' => now(), 'is_active' => true,
    ]);
    RecurringAppointment::create([
        'service_id' => $service->id, 'customer_id' => $customer->id,
        'frequency' => 'weekly', 'day_of_week' => 2, 'preferred_time' => '14:00',
        'starts_at' => now(), 'is_active' => false,
    ]);

    expect(RecurringAppointment::active()->count())->toBe(1);
});

it('RecurringAppointment scope forCustomer filtre par client', function () {
    [$service, $customer1] = createDeps();
    $customer2 = BookingCustomer::create([
        'first_name' => 'Marie', 'last_name' => 'D', 'email' => 'm'.uniqid().'@test.com',
    ]);

    RecurringAppointment::create([
        'service_id' => $service->id, 'customer_id' => $customer1->id,
        'frequency' => 'weekly', 'day_of_week' => 1, 'preferred_time' => '10:00',
        'starts_at' => now(), 'is_active' => true,
    ]);
    RecurringAppointment::create([
        'service_id' => $service->id, 'customer_id' => $customer2->id,
        'frequency' => 'weekly', 'day_of_week' => 2, 'preferred_time' => '14:00',
        'starts_at' => now(), 'is_active' => true,
    ]);

    expect(RecurringAppointment::forCustomer($customer1->id)->count())->toBe(1);
});

it('RecurringAppointment nextOccurrence retourne la prochaine date weekly', function () {
    [$service, $customer] = createDeps();

    $recurring = RecurringAppointment::create([
        'service_id' => $service->id, 'customer_id' => $customer->id,
        'frequency' => 'weekly', 'day_of_week' => 1, 'preferred_time' => '10:00',
        'starts_at' => now(), 'is_active' => true,
    ]);

    $next = $recurring->nextOccurrence();

    expect($next)->not->toBeNull();
    expect($next->dayOfWeek)->toBe(1); // Lundi
});

it('RecurringAppointment nextOccurrence retourne null si inactif', function () {
    [$service, $customer] = createDeps();

    $recurring = RecurringAppointment::create([
        'service_id' => $service->id, 'customer_id' => $customer->id,
        'frequency' => 'weekly', 'day_of_week' => 1, 'preferred_time' => '10:00',
        'starts_at' => now(), 'is_active' => false,
    ]);

    expect($recurring->nextOccurrence())->toBeNull();
});

it('RecurringAppointment nextOccurrence retourne null si terminé', function () {
    [$service, $customer] = createDeps();

    $recurring = RecurringAppointment::create([
        'service_id' => $service->id, 'customer_id' => $customer->id,
        'frequency' => 'weekly', 'day_of_week' => 1, 'preferred_time' => '10:00',
        'starts_at' => now()->subWeek(), 'ends_at' => now()->subDay(),
        'is_active' => true,
    ]);

    expect($recurring->nextOccurrence())->toBeNull();
});
