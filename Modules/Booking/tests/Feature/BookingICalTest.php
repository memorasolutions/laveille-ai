<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Services\ICalService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('generateCalendar produit un ICS valide', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);
    $appointment->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar(collect([$appointment]), 'Test');

    expect($ics)->toContain('BEGIN:VCALENDAR');
    expect($ics)->toContain('END:VCALENDAR');
    expect($ics)->toContain('BEGIN:VEVENT');
    expect($ics)->toContain('END:VEVENT');
    expect($ics)->toContain('VERSION:2.0');
});

it('generateCalendar contient le bon UID', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);
    $appointment->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar(collect([$appointment]), 'Test');

    expect($ics)->toContain("booking-{$appointment->id}@");
});

it('generateCalendar mappe le status confirmed à CONFIRMED', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);
    $appointment->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar(collect([$appointment]), 'Test');

    expect($ics)->toContain('STATUS:CONFIRMED');
});

it('generateCalendar mappe le status cancelled à CANCELLED', function () {
    $appointment = Appointment::factory()->create(['status' => 'cancelled']);
    $appointment->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar(collect([$appointment]), 'Test');

    expect($ics)->toContain('STATUS:CANCELLED');
});

it('generateCalendar mappe le status pending à TENTATIVE', function () {
    $appointment = Appointment::factory()->create(['status' => 'pending']);
    $appointment->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar(collect([$appointment]), 'Test');

    expect($ics)->toContain('STATUS:TENTATIVE');
});

it('generateCalendar inclut le nom du service comme SUMMARY', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);
    $appointment->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar(collect([$appointment]), 'Test');

    expect($ics)->toContain('SUMMARY:'.$appointment->service->name);
});

it('generateCalendar inclut le nom du calendrier', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);
    $appointment->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar(collect([$appointment]), 'Mon Calendrier');

    expect($ics)->toContain('X-WR-CALNAME:Mon Calendrier');
});

it('generateCalendar gère plusieurs rendez-vous', function () {
    $appointments = Appointment::factory()->count(3)->create(['status' => 'confirmed']);
    $appointments->loadMissing('service');

    $ics = app(ICalService::class)->generateCalendar($appointments, 'Multi');

    expect(substr_count($ics, 'BEGIN:VEVENT'))->toBe(3);
    expect(substr_count($ics, 'END:VEVENT'))->toBe(3);
});
