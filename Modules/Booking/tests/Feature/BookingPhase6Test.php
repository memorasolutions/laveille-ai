<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->customer = Modules\Booking\Models\BookingCustomer::factory()->create([
        'portal_token' => 'valid-token-123',
    ]);

    $this->service = Modules\Booking\Models\BookingService::factory()->create([
        'is_active' => true,
    ]);
});

it('portal index loads correct view with upcoming and past appointments', function () {
    Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->subDays(1),
        'end_at' => now()->subDays(1)->addMinutes(60),
        'status' => 'confirmed',
    ]);

    $upcoming = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addMinutes(60),
        'status' => 'confirmed',
    ]);

    // Test controller logic directly (view rendering depends on FrontTheme module)
    $controller = new Modules\Booking\Http\Controllers\CustomerPortalController;
    $viewResponse = $controller->index('valid-token-123');

    expect($viewResponse->name())->toBe('booking::public.portal.index');
    expect($viewResponse->getData()['customer']->id)->toBe($this->customer->id);
    expect($viewResponse->getData()['upcoming'])->toHaveCount(1);
    expect($viewResponse->getData()['upcoming']->first()->id)->toBe($upcoming->id);
});

it('portal index returns 404 for invalid token', function () {
    $response = $this->get(route('booking.portal.index', 'invalid-token'));

    $response->assertNotFound();
});

it('portal cancel works when notice period respected', function () {
    config(['booking.min_notice_hours' => 48]);

    $appointment = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addHours(72),
        'end_at' => now()->addHours(73),
        'status' => 'confirmed',
        'cancelled_at' => null,
    ]);

    $response = $this->post(route('booking.portal.cancel', [
        'token' => 'valid-token-123',
        'appointment' => $appointment->id,
    ]), [
        'cancel_reason' => 'Test cancellation',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('booking_appointments', [
        'id' => $appointment->id,
        'status' => 'cancelled',
    ]);
});

it('portal cancel fails when too close', function () {
    config(['booking.min_notice_hours' => 48]);

    $appointment = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addHours(2),
        'end_at' => now()->addHours(3),
        'status' => 'confirmed',
        'cancelled_at' => null,
    ]);

    $response = $this->post(route('booking.portal.cancel', [
        'token' => 'valid-token-123',
        'appointment' => $appointment->id,
    ]), [
        'cancel_reason' => 'Test cancellation',
    ]);

    $response->assertSessionHasErrors();

    $this->assertDatabaseHas('booking_appointments', [
        'id' => $appointment->id,
        'status' => 'confirmed',
    ]);
});

it('portal cancel fails if appointment belongs to different customer', function () {
    config(['booking.min_notice_hours' => 48]);

    $otherCustomer = Modules\Booking\Models\BookingCustomer::factory()->create([
        'portal_token' => 'other-token',
    ]);

    $appointment = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $otherCustomer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addHours(72),
        'end_at' => now()->addHours(73),
        'status' => 'confirmed',
        'cancelled_at' => null,
    ]);

    $response = $this->post(route('booking.portal.cancel', [
        'token' => 'valid-token-123',
        'appointment' => $appointment->id,
    ]), [
        'cancel_reason' => 'Test cancellation',
    ]);

    $response->assertForbidden();
});

it('ical download returns text/calendar content type', function () {
    Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addMinutes(60),
        'status' => 'confirmed',
    ]);

    $response = $this->get(route('booking.portal.ical', 'valid-token-123'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/calendar');
});

it('ical service generates valid VCALENDAR with VEVENT blocks', function () {
    $appointment = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addMinutes(60),
        'status' => 'confirmed',
    ]);

    $content = app(\Modules\Booking\Services\ICalService::class)
        ->generateCalendar(collect([$appointment]), 'Test Calendar');

    expect($content)
        ->toContain('BEGIN:VCALENDAR')
        ->toContain('END:VCALENDAR')
        ->toContain('BEGIN:VEVENT')
        ->toContain('END:VEVENT')
        ->toContain('SUMMARY:'.$appointment->service->name);
});

it('ical service maps status correctly', function () {
    $confirmed = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addMinutes(60),
        'status' => 'confirmed',
    ]);

    $cancelled = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(4),
        'end_at' => now()->addDays(4)->addMinutes(60),
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $pending = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(5),
        'end_at' => now()->addDays(5)->addMinutes(60),
        'status' => 'pending',
    ]);

    $content = app(\Modules\Booking\Services\ICalService::class)
        ->generateCalendar(collect([$confirmed, $cancelled, $pending]), 'Test Calendar');

    expect($content)
        ->toContain('STATUS:CONFIRMED')
        ->toContain('STATUS:CANCELLED')
        ->toContain('STATUS:TENTATIVE');
});
