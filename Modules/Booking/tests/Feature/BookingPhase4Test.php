<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->customer = Modules\Booking\Models\BookingCustomer::factory()->create();
    $this->service = Modules\Booking\Models\BookingService::factory()->create([
        'price' => 100.00,
        'is_active' => true,
    ]);
});

it('isPaymentRequired returns false by default', function () {
    config(['booking.stripe.enabled' => false]);

    $paymentService = app(Modules\Booking\Services\PaymentService::class);

    expect($paymentService->isPaymentRequired())->toBeFalse();
});

it('isPaymentRequired returns true when config enabled', function () {
    config(['booking.stripe.enabled' => true]);

    $paymentService = app(Modules\Booking\Services\PaymentService::class);

    expect($paymentService->isPaymentRequired())->toBeTrue();
});

it('calculatePrice returns correct cents without coupon', function () {
    $appointment = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addMinutes(60),
        'status' => 'pending',
    ]);

    $paymentService = app(Modules\Booking\Services\PaymentService::class);

    $method = new ReflectionMethod($paymentService, 'calculatePrice');

    $result = $method->invoke($paymentService, $appointment, null);

    expect($result)->toBe(10000); // 100.00 * 100
});

it('calculatePrice applies coupon percent discount correctly', function () {
    $appointment = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addMinutes(60),
        'status' => 'pending',
    ]);

    $coupon = Modules\Booking\Models\Coupon::create([
        'code' => 'TEST20',
        'type' => 'percent',
        'value' => 20.00,
        'is_active' => true,
    ]);

    $paymentService = app(Modules\Booking\Services\PaymentService::class);
    $method = new ReflectionMethod($paymentService, 'calculatePrice');

    $result = $method->invoke($paymentService, $appointment, $coupon);

    // 100$ - 20% = 80$ = 8000 cents
    expect($result)->toBe(8000);

    $appointment->refresh();
    expect($appointment->coupon_id)->toBe($coupon->id);
    expect((float) $appointment->discount_amount)->toBe(20.00);
});

it('calculatePrice applies coupon fixed discount correctly', function () {
    $appointment = Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'start_at' => now()->addDays(3),
        'end_at' => now()->addDays(3)->addMinutes(60),
        'status' => 'pending',
    ]);

    $coupon = Modules\Booking\Models\Coupon::create([
        'code' => 'FIXED15',
        'type' => 'fixed',
        'value' => 15.00,
        'is_active' => true,
    ]);

    $paymentService = app(Modules\Booking\Services\PaymentService::class);
    $method = new ReflectionMethod($paymentService, 'calculatePrice');

    $result = $method->invoke($paymentService, $appointment, $coupon);

    // 100$ - 15$ = 85$ = 8500 cents
    expect($result)->toBe(8500);
});

it('stripe webhook route returns 400 on invalid signature', function () {
    config(['booking.stripe.webhook_secret' => 'whsec_test_secret']);

    $response = $this->postJson(route('booking.stripe.webhook'), [], [
        'Stripe-Signature' => 'invalid_signature',
    ]);

    $response->assertStatus(400);
});

it('stripe webhook route exists and accepts POST', function () {
    $response = $this->post(route('booking.stripe.webhook'), [], [
        'Stripe-Signature' => 't=123,v1=fake',
    ]);

    // Should return 400 (bad signature) not 404/405
    expect($response->status())->not->toBe(404);
    expect($response->status())->not->toBe(405);
});
