<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;

uses(Tests\TestCase::class, RefreshDatabase::class);

// --- Widget ---

it('la route widget est accessible et retourne les headers CORS', function () {
    ServiceModel::factory()->create();
    $response = $this->get(route('booking.widget'));

    $response->assertStatus(200);
    $response->assertHeader('Access-Control-Allow-Origin', '*');
    $response->assertSee('Prendre rendez-vous');
});

it('le widget affiche les services actifs', function () {
    ServiceModel::factory()->create(['name' => 'Massage détente', 'is_active' => true]);
    ServiceModel::factory()->create(['name' => 'XYZ_SERVICE_DESACTIVE_123', 'is_active' => false]);

    $response = $this->get(route('booking.widget'));

    $response->assertSee('Massage détente');
    $response->assertDontSee('XYZ_SERVICE_DESACTIVE_123');
});

it('le widget filtre par service_id', function () {
    $s1 = ServiceModel::factory()->create(['name' => 'Alpha unique service']);
    ServiceModel::factory()->create(['name' => 'Beta unique service']);

    $response = $this->get(route('booking.widget', ['service_id' => $s1->id]));

    $response->assertSee('Alpha unique service');
    $response->assertDontSee('Beta unique service');
});

it('le widget accepte un paramètre couleur', function () {
    ServiceModel::factory()->create();
    $response = $this->get(route('booking.widget', ['color' => '#ff5500']));

    $response->assertSee('#ff5500');
});

// --- Public API ---

it('l\'API publique crée un rendez-vous', function () {
    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);

    // Mock l'AvailabilityService pour que le slot soit disponible
    $mock = \Mockery::mock(\Modules\Booking\Services\AvailabilityService::class);
    $mock->shouldReceive('isSlotAvailable')->andReturn(true);
    $this->app->instance(\Modules\Booking\Services\AvailabilityService::class, $mock);

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => now()->addDays(7)->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com',
            'phone' => '+15141234567',
        ],
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['success', 'appointment_id', 'message']);
    $this->assertDatabaseHas('booking_customers', ['email' => 'jean@test.com']);
});

it('l\'API publique refuse un créneau indisponible', function () {
    $service = ServiceModel::factory()->create(['duration_minutes' => 30]);

    $mock = \Mockery::mock(\Modules\Booking\Services\AvailabilityService::class);
    $mock->shouldReceive('isSlotAvailable')->andReturn(false);
    $this->app->instance(\Modules\Booking\Services\AvailabilityService::class, $mock);

    $response = $this->postJson('/api/booking', [
        'service_id' => $service->id,
        'date' => now()->addDays(7)->format('Y-m-d'),
        'start_time' => '10:00',
        'customer' => [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@test.com',
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
});

// --- Timezone ---

it('BookingCustomer a une timezone par défaut', function () {
    $customer = BookingCustomer::factory()->create();
    expect($customer->timezone)->toBe('America/Toronto');
});

it('BookingCustomer peut avoir une timezone personnalisée', function () {
    $customer = BookingCustomer::factory()->create(['timezone' => 'Europe/Paris']);
    expect($customer->fresh()->timezone)->toBe('Europe/Paris');
});
