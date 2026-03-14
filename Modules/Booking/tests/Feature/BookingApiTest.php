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

uses(Tests\TestCase::class, RefreshDatabase::class);

function setupApiConfig(): void
{
    Config::set('booking.min_notice_hours', 0);
    Config::set('booking.buffer_minutes', 15);
    Config::set('booking.timezone', 'America/Toronto');
    Config::set('booking.email.enabled', false);
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

it('POST /api/booking crée un rendez-vous valide', function () {
    setupApiConfig();
    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $nextMonday = Carbon::now()->next('Monday')->addWeeks(5);

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => $nextMonday->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'appointment_id', 'message'])
        ->assertJson(['success' => true]);
});

it('POST /api/booking rejette sans service_id', function () {
    $response = $this->postJson('/api/booking', [
        'date' => '2026-12-01',
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_id']);
});

it('POST /api/booking rejette un email invalide', function () {
    $service = ServiceModel::factory()->create();

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => '2026-12-01',
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'pas-un-email',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['customer.email']);
});

it('POST /api/booking rejette un start_time invalide', function () {
    $service = ServiceModel::factory()->create();

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => '2026-12-01',
        'start_time' => '25:99',
        'customer' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_time']);
});

it('POST /api/booking rejette un service inexistant', function () {
    $response = $this->postJson('/api/booking', [
        'service_id' => 99999,
        'date' => '2026-12-01',
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_id']);
});

it('POST /api/booking rejette un créneau indisponible', function () {
    setupApiConfig();
    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $nextMonday = Carbon::now()->next('Monday')->addWeeks(5);
    $tz = 'America/Toronto';

    // Occuper le créneau
    $customer = BookingCustomer::factory()->create();
    Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => Carbon::parse($nextMonday->format('Y-m-d').' 10:00', $tz),
        'end_at' => Carbon::parse($nextMonday->format('Y-m-d').' 10:30', $tz),
        'status' => 'confirmed',
    ]);

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => $nextMonday->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Autre',
            'last_name' => 'Client',
            'email' => 'autre@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('POST /api/booking rejette un samedi (jour fermé)', function () {
    setupApiConfig();
    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $nextSaturday = Carbon::now()->next('Saturday')->addWeeks(5);

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => $nextSaturday->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'sat@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('POST /api/booking rejette un créneau hors heures', function () {
    setupApiConfig();
    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);
    $nextMonday = Carbon::now()->next('Monday')->addWeeks(5);

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => $nextMonday->format('Y-m-d'),
        'start_time' => '20:00',
        'customer' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'late@example.com',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});
