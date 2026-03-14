<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = \App\Models\User::factory()->create();
    $this->admin->givePermissionTo('manage_booking');
});

it('analytics page is accessible by admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.booking.analytics'));

    $response->assertOk();
});

it('analytics page shows stats for given period', function () {
    $customer = Modules\Booking\Models\BookingCustomer::factory()->create();
    $service = Modules\Booking\Models\BookingService::factory()->create();

    // Create appointments within last 30 days
    Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'amount_paid' => 75.00,
    ]);

    Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'cancelled',
        'cancelled_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.booking.analytics', ['period' => 30]));

    $response->assertOk();
    $response->assertViewHas('stats', function ($stats) {
        return $stats['total_appointments'] === 2
            && $stats['confirmed_count'] === 1
            && $stats['cancelled_count'] === 1
            && $stats['cancellation_rate'] === 50.0;
    });
});

it('analytics page shows top services', function () {
    $customer = Modules\Booking\Models\BookingCustomer::factory()->create();
    $serviceA = Modules\Booking\Models\BookingService::factory()->create(['name' => 'Massage']);
    $serviceB = Modules\Booking\Models\BookingService::factory()->create(['name' => 'Acupuncture']);

    Modules\Booking\Models\Appointment::factory()->count(3)->create([
        'customer_id' => $customer->id,
        'service_id' => $serviceA->id,
    ]);

    Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $customer->id,
        'service_id' => $serviceB->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.booking.analytics'));

    $response->assertOk();
    $response->assertViewHas('topServices');
});

it('csv export returns correct content type', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.booking.analytics.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('rendez-vous-export-');
});

it('csv export contains appointment data', function () {
    $customer = Modules\Booking\Models\BookingCustomer::factory()->create();
    $service = Modules\Booking\Models\BookingService::factory()->create(['name' => 'Ostéopathie']);

    Modules\Booking\Models\Appointment::factory()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'start_at' => now()->subDays(5),
        'end_at' => now()->subDays(5)->addMinutes(60),
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.booking.analytics.export', [
        'from' => now()->subDays(10)->format('Y-m-d'),
        'to' => now()->format('Y-m-d'),
    ]));

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('Ostéopathie');
    expect($content)->toContain('Confirmé');
});

it('analytics requires authentication', function () {
    $response = $this->get(route('admin.booking.analytics'));

    $response->assertRedirect();
});
