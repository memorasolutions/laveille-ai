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

function createParentAppointment(): Appointment
{
    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $customer = BookingCustomer::factory()->create();
    $nextMonday = Carbon::now()->next('Monday')->addWeeks(2);

    return Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => $nextMonday->copy()->setTime(10, 0),
        'end_at' => $nextMonday->copy()->setTime(10, 30),
        'status' => 'confirmed',
    ]);
}

it('generateRecurrences crée des occurrences hebdomadaires', function () {
    $parent = createParentAppointment();
    $endDate = $parent->start_at->copy()->addWeeks(4)->format('Y-m-d');

    $childIds = app(RecurrenceService::class)->generateRecurrences($parent, 'weekly', $endDate);

    expect($childIds)->toHaveCount(4);
    foreach ($childIds as $childId) {
        $child = Appointment::find($childId);
        expect($child->recurrence_parent_id)->toBe($parent->id);
        expect($child->service_id)->toBe($parent->service_id);
        expect($child->customer_id)->toBe($parent->customer_id);
    }
});

it('generateRecurrences respecte la limite max de 52 occurrences', function () {
    $parent = createParentAppointment();
    $endDate = $parent->start_at->copy()->addYears(3)->format('Y-m-d');

    $childIds = app(RecurrenceService::class)->generateRecurrences($parent, 'weekly', $endDate);

    expect(count($childIds))->toBeLessThanOrEqual(52);
});

it('cancelSeries annule le parent et les enfants', function () {
    $parent = createParentAppointment();
    $endDate = $parent->start_at->copy()->addWeeks(3)->format('Y-m-d');

    app(RecurrenceService::class)->generateRecurrences($parent, 'weekly', $endDate);

    $count = app(RecurrenceService::class)->cancelSeries($parent);

    expect($count)->toBeGreaterThanOrEqual(1);
    expect($parent->fresh()->status)->toBe('cancelled');
    expect(Appointment::where('recurrence_parent_id', $parent->id)
        ->where('status', '!=', 'cancelled')->count())->toBe(0);
});

it('cancelSeries ne touche pas les rendez-vous complétés', function () {
    $parent = createParentAppointment();
    $endDate = $parent->start_at->copy()->addWeeks(3)->format('Y-m-d');

    $childIds = app(RecurrenceService::class)->generateRecurrences($parent, 'weekly', $endDate);

    // Marquer un enfant comme complété
    if (count($childIds) > 0) {
        Appointment::find($childIds[0])->update(['status' => 'completed']);
    }

    app(RecurrenceService::class)->cancelSeries($parent);

    expect(Appointment::where('recurrence_parent_id', $parent->id)
        ->where('status', 'completed')->count())->toBe(1);
});

it('generateRecurrences daily crée des occurrences quotidiennes', function () {
    $parent = createParentAppointment();
    $endDate = $parent->start_at->copy()->addDays(5)->format('Y-m-d');

    $childIds = app(RecurrenceService::class)->generateRecurrences($parent, 'daily', $endDate);

    expect($childIds)->toHaveCount(5);
});

it('generateRecurrences monthly crée des occurrences mensuelles', function () {
    $parent = createParentAppointment();
    $endDate = $parent->start_at->copy()->addMonths(3)->format('Y-m-d');

    $childIds = app(RecurrenceService::class)->generateRecurrences($parent, 'monthly', $endDate);

    expect($childIds)->toHaveCount(3);
});
