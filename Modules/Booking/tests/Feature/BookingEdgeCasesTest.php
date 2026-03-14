<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;
use Modules\Booking\Models\Coupon;
use Modules\Booking\Services\BookingService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('book rejette un créneau déjà pris', function () {
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null, 'sunday' => null,
    ]);

    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $customer = BookingCustomer::factory()->create();
    $nextMonday = now()->next('Monday');

    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => $nextMonday->copy()->setTime(10, 0),
        'end_at' => $nextMonday->copy()->setTime(10, 30),
        'status' => 'confirmed',
    ]);

    expect(fn () => app(BookingService::class)->book([
        'service_id' => $service->id,
        'date' => $nextMonday->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => ['first_name' => 'Test', 'last_name' => 'User', 'email' => 'test@example.com'],
    ]))->toThrow(RuntimeException::class);
});

it('book rejette un samedi', function () {
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null, 'sunday' => null,
    ]);

    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $nextSaturday = now()->next('Saturday');

    expect(fn () => app(BookingService::class)->book([
        'service_id' => $service->id,
        'date' => $nextSaturday->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => ['first_name' => 'Test', 'last_name' => 'User', 'email' => 'sat@example.com'],
    ]))->toThrow(RuntimeException::class);
});

it('book rejette un créneau hors heures ouvrables', function () {
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null, 'sunday' => null,
    ]);

    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $nextMonday = now()->next('Monday');

    expect(fn () => app(BookingService::class)->book([
        'service_id' => $service->id,
        'date' => $nextMonday->format('Y-m-d'),
        'start_time' => '20:00',
        'customer' => ['first_name' => 'Test', 'last_name' => 'User', 'email' => 'late@example.com'],
    ]))->toThrow(RuntimeException::class);
});

it('cancel avec token invalide lance ModelNotFoundException', function () {
    expect(fn () => app(BookingService::class)->cancel('token-inexistant-xyz'))
        ->toThrow(ModelNotFoundException::class);
});

it('reschedule vers créneau occupé lance RuntimeException', function () {
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null, 'sunday' => null,
    ]);

    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $customer = BookingCustomer::factory()->create();
    $nextTuesday = now()->next('Tuesday');

    $existing = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => $nextTuesday->copy()->setTime(14, 0),
        'end_at' => $nextTuesday->copy()->setTime(14, 30),
        'status' => 'confirmed',
    ]);

    $toReschedule = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => $nextTuesday->copy()->setTime(11, 0),
        'end_at' => $nextTuesday->copy()->setTime(11, 30),
        'status' => 'confirmed',
    ]);

    expect(fn () => app(BookingService::class)->reschedule(
        $toReschedule, $nextTuesday->format('Y-m-d'), '14:00'
    ))->toThrow(RuntimeException::class);
});

it('coupon percent calcule le bon discount', function () {
    $coupon = Coupon::create([
        'code' => 'TEST20', 'type' => 'percent', 'value' => 20.00,
        'is_active' => true,
    ]);

    expect($coupon->calculateDiscount(100.00))->toBe(20.00);
});

it('coupon fixed ne dépasse pas le prix', function () {
    $coupon = Coupon::create([
        'code' => 'BIG150', 'type' => 'fixed', 'value' => 150.00,
        'is_active' => true,
    ]);

    expect($coupon->calculateDiscount(100.00))->toBe(100.00);
});

it('coupon expiré n est pas dans scope valid', function () {
    Coupon::create([
        'code' => 'EXPIRED', 'type' => 'percent', 'value' => 10,
        'is_active' => true, 'expires_at' => now()->subDay(),
    ]);
    Coupon::create([
        'code' => 'VALID', 'type' => 'percent', 'value' => 10,
        'is_active' => true, 'expires_at' => now()->addDay(),
    ]);

    expect(Coupon::valid()->count())->toBe(1);
    expect(Coupon::valid()->first()->code)->toBe('VALID');
});

it('book crée le customer si inexistant', function () {
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null, 'sunday' => null,
    ]);

    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $nextWednesday = now()->next('Wednesday');

    app(BookingService::class)->book([
        'service_id' => $service->id,
        'date' => $nextWednesday->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => ['first_name' => 'Nouveau', 'last_name' => 'Client', 'email' => 'nouveau@example.com'],
    ]);

    expect(BookingCustomer::where('email', 'nouveau@example.com')->exists())->toBeTrue();
});

it('book réutilise customer existant par email', function () {
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.working_hours', [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null, 'sunday' => null,
    ]);

    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $existing = BookingCustomer::factory()->create(['email' => 'existant@example.com']);
    $nextThursday = now()->next('Thursday');

    $appointment = app(BookingService::class)->book([
        'service_id' => $service->id,
        'date' => $nextThursday->format('Y-m-d'),
        'start_time' => '11:00',
        'customer' => ['first_name' => 'Autre', 'last_name' => 'Nom', 'email' => 'existant@example.com'],
    ]);

    expect($appointment->customer_id)->toBe($existing->id);
    expect(BookingCustomer::where('email', 'existant@example.com')->count())->toBe(1);
});
