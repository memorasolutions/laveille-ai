<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Booking\Mail\BookingConfirmation;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;
use Modules\Booking\Notifications\BookingStatusNotification;
use Modules\Booking\Notifications\NewBookingAdminNotification;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('BookingConfirmation contient pièce jointe ICS', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
    ]);

    $mailable = new BookingConfirmation($appointment);
    $mailable->build();

    $attachments = $mailable->rawAttachments;
    expect($attachments)->toHaveCount(1);
    expect($attachments[0]['name'])->toBe('rendez-vous.ics');
    expect($attachments[0]['options']['mime'])->toBe('text/calendar');
    expect($attachments[0]['data'])->toContain('BEGIN:VCALENDAR');
});

it('NewBookingAdminNotification peut être envoyée via mail et database', function () {
    Notification::fake();

    $admin = \App\Models\User::factory()->create();
    $notification = new NewBookingAdminNotification(1, 'Massage', 'Jean Tremblay', '15/06/2026 10:00');
    $admin->notify($notification);

    Notification::assertSentTo($admin, NewBookingAdminNotification::class);
});

it('NewBookingAdminNotification toArray retourne les bonnes données', function () {
    $notification = new NewBookingAdminNotification(42, 'Coupe', 'Marie Dupont', '20/06/2026 14:00');
    $data = $notification->toArray(null);

    expect($data)->toMatchArray([
        'type' => 'new_booking',
        'appointment_id' => 42,
        'service_name' => 'Coupe',
        'customer_name' => 'Marie Dupont',
        'date' => '20/06/2026 14:00',
    ]);
});

it('BookingStatusNotification approved a le bon sujet', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
    ]);

    $notification = new BookingStatusNotification($appointment, 'approved');
    $mail = $notification->toMail($customer);

    expect($mail->subject)->toBe('Rendez-vous confirmé');
});

it('BookingStatusNotification rejected a le bon sujet', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
    ]);

    $notification = new BookingStatusNotification($appointment, 'rejected');
    $mail = $notification->toMail($customer);

    expect($mail->subject)->toBe('Rendez-vous refusé');
});

it('BookingStatusNotification cancelled a le bon sujet', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
    ]);

    $notification = new BookingStatusNotification($appointment, 'cancelled');
    $mail = $notification->toMail($customer);

    expect($mail->subject)->toBe('Rendez-vous annulé');
});
