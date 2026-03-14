<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;
use Modules\Booking\Services\RecurrenceService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('generateRecurrences crée 4 occurrences weekly sur 4 semaines', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $parent = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => Carbon::parse('2026-06-01 10:00:00'),
        'end_at' => Carbon::parse('2026-06-01 11:00:00'),
        'status' => 'confirmed',
    ]);

    $ids = app(RecurrenceService::class)->generateRecurrences($parent, 'weekly', '2026-06-29');

    expect($ids)->toHaveCount(4);

    $children = Appointment::where('recurrence_parent_id', $parent->id)->orderBy('start_at')->get();
    expect($children)->toHaveCount(4);
    expect($children[0]->start_at->toDateString())->toBe('2026-06-08');
    expect($children[1]->start_at->toDateString())->toBe('2026-06-15');
    expect($children[2]->start_at->toDateString())->toBe('2026-06-22');
    expect($children[3]->start_at->toDateString())->toBe('2026-06-29');
});

it('generateRecurrences respecte la limite de 52 occurrences', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $parent = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => Carbon::parse('2026-01-01 10:00:00'),
        'end_at' => Carbon::parse('2026-01-01 11:00:00'),
    ]);

    $ids = app(RecurrenceService::class)->generateRecurrences($parent, 'daily', '2027-12-31');

    expect(count($ids))->toBeLessThanOrEqual(52);
});

it('cancelSeries annule les enfants non-completed', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $parent = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'confirmed',
    ]);

    $pending = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'recurrence_parent_id' => $parent->id,
        'status' => 'pending',
    ]);
    $completed = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'recurrence_parent_id' => $parent->id,
        'status' => 'completed',
    ]);
    $confirmed = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'recurrence_parent_id' => $parent->id,
        'status' => 'confirmed',
    ]);

    $count = app(RecurrenceService::class)->cancelSeries($parent);

    expect($count)->toBe(3); // parent + pending + confirmed
    expect($pending->fresh()->status)->toBe('cancelled');
    expect($completed->fresh()->status)->toBe('completed');
    expect($confirmed->fresh()->status)->toBe('cancelled');
    expect($parent->fresh()->status)->toBe('cancelled');
});

it('les enfants ont recurrence_parent_id correct', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $parent = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => Carbon::parse('2026-06-01 10:00:00'),
        'end_at' => Carbon::parse('2026-06-01 11:00:00'),
    ]);

    $ids = app(RecurrenceService::class)->generateRecurrences($parent, 'daily', '2026-06-05');

    foreach (Appointment::whereIn('id', $ids)->get() as $child) {
        expect($child->recurrence_parent_id)->toBe($parent->id);
        expect($child->recurrenceParent->id)->toBe($parent->id);
    }
});

it('recurrence_type default est none', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
    ]);

    expect($appointment->fresh()->recurrence_type)->toBe('none');
});
