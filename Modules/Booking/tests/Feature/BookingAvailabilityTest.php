<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;
use Modules\Booking\Models\DateOverride;
use Modules\Booking\Services\AvailabilityService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function setupWorkingConfig(): void
{
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.buffer_minutes', 15);
    Config::set('booking.timezone', 'America/Toronto');
    Config::set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null,
        'sunday' => null,
    ]);
}

it('getAvailableSlots retourne des créneaux pour un lundi', function () {
    setupWorkingConfig();
    $nextMonday = Carbon::now()->next('Monday')->addWeeks(5);

    $svc = new AvailabilityService;
    $slots = $svc->getAvailableSlots($nextMonday->format('Y-m-d'), 30);

    expect($slots)->not->toBeEmpty();
    expect($slots[0])->toHaveKeys(['start', 'end']);
    expect($slots[0]['start'])->toBe('09:00');
});

it('getAvailableSlots retourne vide pour un samedi', function () {
    setupWorkingConfig();
    $nextSaturday = Carbon::now()->next('Saturday')->addWeeks(5);

    $svc = new AvailabilityService;
    $slots = $svc->getAvailableSlots($nextSaturday->format('Y-m-d'), 30);

    expect($slots)->toBeEmpty();
});

it('getAvailableSlots exclut les créneaux déjà pris', function () {
    setupWorkingConfig();
    $nextMonday = Carbon::now()->next('Monday')->addWeeks(5);
    $tz = 'America/Toronto';

    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => Carbon::parse($nextMonday->format('Y-m-d').' 09:00', $tz),
        'end_at' => Carbon::parse($nextMonday->format('Y-m-d').' 09:30', $tz),
        'status' => 'confirmed',
    ]);

    $svc = new AvailabilityService;
    $slots = $svc->getAvailableSlots($nextMonday->format('Y-m-d'), 30);

    $startTimes = array_column($slots, 'start');
    expect($startTimes)->not->toContain('09:00');
});

it('isSlotAvailable retourne false si créneau chevauche un RV existant', function () {
    setupWorkingConfig();
    $nextTuesday = Carbon::now()->next('Tuesday')->addWeeks(5);
    $tz = 'America/Toronto';

    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => Carbon::parse($nextTuesday->format('Y-m-d').' 10:00', $tz),
        'end_at' => Carbon::parse($nextTuesday->format('Y-m-d').' 10:30', $tz),
        'status' => 'confirmed',
    ]);

    $svc = new AvailabilityService;
    expect($svc->isSlotAvailable($nextTuesday->format('Y-m-d'), '10:15', 30))->toBeFalse();
});

it('isSlotAvailable retourne false hors heures ouvrables', function () {
    setupWorkingConfig();
    $nextMonday = Carbon::now()->next('Monday')->addWeeks(5);

    $svc = new AvailabilityService;
    expect($svc->isSlotAvailable($nextMonday->format('Y-m-d'), '07:00', 30))->toBeFalse();
    expect($svc->isSlotAvailable($nextMonday->format('Y-m-d'), '18:00', 30))->toBeFalse();
});

it('isSlotAvailable retourne false si date bloquée par DateOverride', function () {
    setupWorkingConfig();
    $nextWednesday = Carbon::now()->next('Wednesday')->addWeeks(5);

    DateOverride::create([
        'date' => $nextWednesday->format('Y-m-d'),
        'override_type' => 'blocked',
        'all_day' => true,
    ]);

    $svc = new AvailabilityService;
    expect($svc->isSlotAvailable($nextWednesday->format('Y-m-d'), '10:00', 30))->toBeFalse();
});

it('getAvailableDates exclut les jours bloqués', function () {
    setupWorkingConfig();
    $nextThursday = Carbon::now()->next('Thursday')->addWeeks(5);

    DateOverride::create([
        'date' => $nextThursday->format('Y-m-d'),
        'override_type' => 'blocked',
        'all_day' => true,
    ]);

    $svc = new AvailabilityService;
    $dates = $svc->getAvailableDates(60);

    expect($dates)->not->toContain($nextThursday->format('Y-m-d'));
});

it('buffer_minutes est respecté entre créneaux', function () {
    setupWorkingConfig();
    Config::set('booking.buffer_minutes', 15);
    $nextFriday = Carbon::now()->next('Friday')->addWeeks(5);

    $svc = new AvailabilityService;
    $slots = $svc->getAvailableSlots($nextFriday->format('Y-m-d'), 30);

    expect($slots[0]['start'])->toBe('09:00');
    expect($slots[0]['end'])->toBe('09:30');
    // Avec buffer 15min : prochain slot à 09:45
    expect($slots[1]['start'])->toBe('09:45');
});
