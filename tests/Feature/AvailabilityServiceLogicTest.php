<?php

declare(strict_types=1);

/**
 * Tests reflexifs AvailabilityService — logique pure timezone+config.
 *
 * Pattern : config()->set() pour bypass DB + ReflectionMethod pour méthodes protected.
 * Zéro requête Appointment/DateOverride. Test des branches min_notice_hours, max_advance_days, working_hours.
 *
 * @author  MEMORA solutions <info@memora.ca>
 * @project la-veille-de-stef-v2
 * @session S83 #225 Pest étendus
 */

use Modules\Booking\Services\AvailabilityService;

beforeEach(function () {
    config()->set('booking.timezone', 'America/Toronto');
    config()->set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
    ]);
    config()->set('booking.buffer_minutes', 15);
    config()->set('booking.min_notice_hours', 48);
    config()->set('booking.max_advance_days', 60);
    config()->set('booking.slot_duration_minutes', 30);
});

it('reads timezone and config values from constructor', function () {
    $svc = new AvailabilityService;

    $reflection = new ReflectionClass($svc);
    $tzProp = $reflection->getProperty('timezone');
    $tzProp->setAccessible(true);
    $hoursProp = $reflection->getProperty('workingHours');
    $hoursProp->setAccessible(true);
    $bufferProp = $reflection->getProperty('bufferMinutes');
    $bufferProp->setAccessible(true);

    expect($tzProp->getValue($svc))->toBe('America/Toronto');
    expect($hoursProp->getValue($svc))->toHaveKey('monday');
    expect($bufferProp->getValue($svc))->toBe(15);
});

it('rejects slot when start time is below min_notice_hours (early return, no DB)', function () {
    // Fix "now" to a known datetime so min_notice check is deterministic
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-06-15 10:00:00', 'America/Toronto'));
    $svc = new AvailabilityService;

    // Slot 2h after now : 2h < 48h min_notice → false BEFORE DB query
    $result = $svc->isSlotAvailable('2026-06-15', '12:00', 30);

    expect($result)->toBeFalse();

    Carbon\Carbon::setTestNow(); // reset
});

it('respects custom buffer_minutes config from beforeEach', function () {
    config()->set('booking.buffer_minutes', 30); // override beforeEach default 15

    $svc = new AvailabilityService;

    $reflection = new ReflectionClass($svc);
    $bufferProp = $reflection->getProperty('bufferMinutes');
    $bufferProp->setAccessible(true);

    expect($bufferProp->getValue($svc))->toBe(30);
});
