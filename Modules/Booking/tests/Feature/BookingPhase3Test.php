<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Services\SmsNotificationService;
use Modules\Booking\Services\SmsService;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

function createSmsAppointment(string $phone = '514-555-0100', string $status = 'pending'): Appointment
{
    $service = BookingService::create([
        'name' => 'Massage',
        'slug' => 'massage-'.uniqid(),
        'duration_minutes' => 60,
        'price' => 100,
        'is_active' => true,
    ]);
    $customer = BookingCustomer::create([
        'first_name' => 'Jean',
        'last_name' => 'Tremblay',
        'email' => 'jean'.uniqid().'@test.com',
        'phone' => $phone,
    ]);

    return Appointment::create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addMinutes(60),
        'status' => $status,
        'cancel_token' => 'tok-'.uniqid(),
    ]);
}

// --- SmsService ---

it('SmsService envoie en mode log', function () {
    config(['booking.sms.enabled' => true, 'booking.sms.provider' => 'log']);

    $smsService = new SmsService;
    $result = $smsService->send('+15145550100', 'Test message');

    expect($result)->toBeTrue();
});

it('SmsService ne fait rien si désactivé', function () {
    config(['booking.sms.enabled' => false]);

    $smsService = new SmsService;
    $result = $smsService->send('+15145550100', 'Test');

    expect($result)->toBeFalse();
});

// --- SmsNotificationService ---

it('SmsNotificationService envoie confirmation avec téléphone', function () {
    config([
        'booking.sms.enabled' => true,
        'booking.sms.provider' => 'log',
        'booking.sms.templates.confirmation' => 'Confirmé: {service} le {date} à {time}. STOP=désabonner',
    ]);

    $mockSms = $this->mock(SmsService::class);
    $mockSms->shouldReceive('send')
        ->once()
        ->withArgs(fn ($phone, $msg) => $phone === '+15145550100' && str_contains($msg, 'Massage'))
        ->andReturn(true);

    $appointment = createSmsAppointment('514-555-0100');
    $service = new SmsNotificationService($mockSms);
    $service->sendConfirmation($appointment);
});

it('SmsNotificationService n\'envoie pas sans téléphone', function () {
    config(['booking.sms.enabled' => true]);

    $mockSms = $this->mock(SmsService::class);
    $mockSms->shouldReceive('send')->never();

    $svc = BookingService::create([
        'name' => 'Massage', 'slug' => 'massage-'.uniqid(),
        'duration_minutes' => 60, 'price' => 100, 'is_active' => true,
    ]);
    $customer = BookingCustomer::create([
        'first_name' => 'Jean', 'last_name' => 'Tremblay',
        'email' => 'jean'.uniqid().'@test.com', 'phone' => null,
    ]);
    $appointment = Appointment::create([
        'service_id' => $svc->id, 'customer_id' => $customer->id,
        'start_at' => now()->addDay(), 'end_at' => now()->addDay()->addMinutes(60),
        'status' => 'pending', 'cancel_token' => 'tok-'.uniqid(),
    ]);

    $service = new SmsNotificationService($mockSms);
    $service->sendConfirmation($appointment);
});

it('SmsNotificationService formate le téléphone canadien +1', function () {
    config([
        'booking.sms.enabled' => true,
        'booking.sms.provider' => 'log',
        'booking.sms.templates.confirmation' => 'Test',
    ]);

    $mockSms = $this->mock(SmsService::class);
    $mockSms->shouldReceive('send')
        ->once()
        ->with('+15145550100', \Mockery::any())
        ->andReturn(true);

    $appointment = createSmsAppointment('514-555-0100');
    $service = new SmsNotificationService($mockSms);
    $service->sendConfirmation($appointment);
});

// --- Webhook SMS inbound ---

it('webhook SMS OUI confirme le rendez-vous', function () {
    config(['booking.sms.provider' => 'vonage']);

    $appointment = createSmsAppointment('5145550100', 'pending');

    $response = $this->postJson('/webhook/sms/booking', [
        'text' => 'OUI',
        'msisdn' => '15145550100',
    ]);

    $response->assertNoContent();

    $appointment->refresh();
    expect($appointment->status)->toBe('confirmed');
});

it('webhook SMS NON annule le rendez-vous', function () {
    config(['booking.sms.provider' => 'vonage']);

    $appointment = createSmsAppointment('5145550100', 'pending');

    $response = $this->postJson('/webhook/sms/booking', [
        'text' => 'NON',
        'msisdn' => '15145550100',
    ]);

    $response->assertNoContent();

    $appointment->refresh();
    expect($appointment->status)->toBe('cancelled');
    expect($appointment->cancelled_at)->not->toBeNull();
});

it('webhook SMS retourne 204 même sans client', function () {
    config(['booking.sms.provider' => 'vonage']);

    $response = $this->postJson('/webhook/sms/booking', [
        'text' => 'OUI',
        'msisdn' => '15145559999',
    ]);

    $response->assertNoContent();
});

it('webhook SMS ignore les mots-clés inconnus', function () {
    config(['booking.sms.provider' => 'vonage']);

    $appointment = createSmsAppointment('5145550100', 'pending');

    $response = $this->postJson('/webhook/sms/booking', [
        'text' => 'BONJOUR',
        'msisdn' => '15145550100',
    ]);

    $response->assertNoContent();

    $appointment->refresh();
    expect($appointment->status)->toBe('pending');
});
