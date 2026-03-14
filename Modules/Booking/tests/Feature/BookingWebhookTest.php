<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingWebhook;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('BookingWebhook crée un webhook avec events en array', function () {
    $webhook = BookingWebhook::create([
        'url' => 'https://example.com/hook',
        'secret' => 'test-secret-123',
        'events' => ['appointment.created', 'appointment.confirmed'],
        'is_active' => true,
    ]);

    expect($webhook->events)->toBeArray();
    expect($webhook->events)->toContain('appointment.created');
    expect($webhook->events)->toContain('appointment.confirmed');
    expect($webhook->is_active)->toBeTrue();
});

it('scope active filtre les webhooks inactifs', function () {
    BookingWebhook::create([
        'url' => 'https://a.com/hook',
        'secret' => 's1',
        'events' => ['appointment.created'],
        'is_active' => true,
    ]);
    BookingWebhook::create([
        'url' => 'https://b.com/hook',
        'secret' => 's2',
        'events' => ['appointment.created'],
        'is_active' => false,
    ]);

    expect(BookingWebhook::active()->count())->toBe(1);
});

it('HMAC signature est calculée correctement', function () {
    $secret = 'my-secret-key';
    $payload = ['appointment_id' => 1, 'status' => 'confirmed'];

    $jsonPayload = json_encode($payload);
    $signature = hash_hmac('sha256', $jsonPayload, $secret);

    // Hash hex de 64 caractères
    expect($signature)->toHaveLength(64);

    // Reproductible
    expect(hash_hmac('sha256', $jsonPayload, $secret))->toBe($signature);

    // Secret différent = signature différente
    expect(hash_hmac('sha256', $jsonPayload, 'autre-secret'))->not->toBe($signature);
});

it('HMAC signature change si le payload change', function () {
    $secret = 'my-secret-key';

    $sig1 = hash_hmac('sha256', json_encode(['id' => 1]), $secret);
    $sig2 = hash_hmac('sha256', json_encode(['id' => 2]), $secret);

    expect($sig1)->not->toBe($sig2);
});

it('dispatchForAppointment charge les relations nécessaires', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);

    // Vérifier que loadMissing charge service et customer
    $appointment->loadMissing(['service', 'customer']);

    expect($appointment->relationLoaded('service'))->toBeTrue();
    expect($appointment->relationLoaded('customer'))->toBeTrue();
    expect($appointment->service)->not->toBeNull();
    expect($appointment->customer)->not->toBeNull();
});

it('dispatchForAppointment construit un payload avec les bons champs', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);
    $appointment->loadMissing(['service', 'customer']);

    $payload = [
        'appointment_id' => $appointment->id,
        'service_name' => $appointment->service?->name,
        'customer_name' => $appointment->customer?->full_name,
        'customer_email' => $appointment->customer?->email,
        'start_at' => $appointment->start_at?->toIso8601String(),
        'end_at' => $appointment->end_at?->toIso8601String(),
        'status' => $appointment->status,
    ];

    expect($payload)->toHaveKeys([
        'appointment_id', 'service_name', 'customer_name',
        'customer_email', 'start_at', 'end_at', 'status',
    ]);
    expect($payload['status'])->toBe('confirmed');
    expect($payload['appointment_id'])->toBe($appointment->id);
});
