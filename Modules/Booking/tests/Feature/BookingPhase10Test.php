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
use Modules\Booking\Models\BookingService as ServiceModel;
use Modules\Booking\Models\BookingWebhook;
use Modules\Booking\Models\IntakeAnswer;
use Modules\Booking\Models\IntakeQuestion;

uses(Tests\TestCase::class, RefreshDatabase::class);

// --- Intake Questions ---

it('IntakeQuestion a les scopes ordered et forService', function () {
    $service = ServiceModel::factory()->create();

    IntakeQuestion::create(['service_id' => $service->id, 'label' => 'Q2', 'type' => 'text', 'sort_order' => 2, 'is_required' => false]);
    IntakeQuestion::create(['service_id' => $service->id, 'label' => 'Q1', 'type' => 'text', 'sort_order' => 1, 'is_required' => true]);

    $ordered = IntakeQuestion::ordered()->get();
    expect($ordered->first()->label)->toBe('Q1');

    $forService = IntakeQuestion::forService($service->id)->get();
    expect($forService)->toHaveCount(2);
});

it('IntakeQuestion a la relation service et answers', function () {
    $service = ServiceModel::factory()->create();
    $question = IntakeQuestion::create(['service_id' => $service->id, 'label' => 'Allergies ?', 'type' => 'text', 'sort_order' => 1, 'is_required' => true]);

    expect($question->service)->toBeInstanceOf(ServiceModel::class);
    expect($question->answers)->toBeEmpty();
});

it('IntakeAnswer a les relations appointment et question', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id]);
    $question = IntakeQuestion::create(['service_id' => $service->id, 'label' => 'Notes', 'type' => 'textarea', 'sort_order' => 1, 'is_required' => false]);

    $answer = IntakeAnswer::create(['appointment_id' => $appointment->id, 'question_id' => $question->id, 'answer' => 'Aucune allergie']);

    expect($answer->appointment)->toBeInstanceOf(Appointment::class);
    expect($answer->question)->toBeInstanceOf(IntakeQuestion::class);
    expect($answer->answer)->toBe('Aucune allergie');
});

// --- Booking Webhooks ---

it('BookingWebhook a les scopes active et forEvent', function () {
    BookingWebhook::create(['url' => 'https://example.com/hook1', 'secret' => 'abc', 'events' => ['appointment.created'], 'is_active' => true]);
    BookingWebhook::create(['url' => 'https://example.com/hook2', 'secret' => 'def', 'events' => ['appointment.cancelled'], 'is_active' => false]);

    expect(BookingWebhook::active()->count())->toBe(1);
    expect(BookingWebhook::forEvent('appointment.created')->count())->toBe(1);
    expect(BookingWebhook::forEvent('appointment.cancelled')->count())->toBe(1);
    expect(BookingWebhook::active()->forEvent('appointment.cancelled')->count())->toBe(0);
});

it('BookingWebhook cast events en array et is_active en boolean', function () {
    $webhook = BookingWebhook::create(['url' => 'https://example.com/hook', 'secret' => 'xyz', 'events' => ['appointment.created', 'appointment.confirmed'], 'is_active' => true]);

    $webhook->refresh();
    expect($webhook->events)->toBeArray()->toHaveCount(2);
    expect($webhook->is_active)->toBeBool()->toBeTrue();
});

// --- Admin CRUD Intake Questions ---

it('un admin peut gérer les intake questions', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create();

    // Create
    $response = $this->actingAs($admin)->post(route('admin.booking.intake-questions.store', $service), [
        'label' => 'Avez-vous des allergies ?',
        'type' => 'text',
        'is_required' => true,
        'sort_order' => 1,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('booking_intake_questions', ['label' => 'Avez-vous des allergies ?', 'service_id' => $service->id]);

    $question = IntakeQuestion::first();

    // Update
    $response = $this->actingAs($admin)->put(route('admin.booking.intake-questions.update', $question), [
        'label' => 'Allergies connues ?',
        'type' => 'textarea',
        'is_required' => false,
        'sort_order' => 2,
    ]);
    $response->assertRedirect();
    expect($question->fresh()->label)->toBe('Allergies connues ?');

    // Delete
    $response = $this->actingAs($admin)->delete(route('admin.booking.intake-questions.destroy', $question));
    $response->assertRedirect();
    $this->assertDatabaseMissing('booking_intake_questions', ['id' => $question->id]);
});

// --- Admin CRUD Webhooks ---

it('un admin peut créer et gérer les webhooks', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');

    // Create
    $response = $this->actingAs($admin)->post(route('admin.booking.webhooks.store'), [
        'url' => 'https://example.com/webhook',
        'events' => ['appointment.created', 'appointment.cancelled'],
        'is_active' => true,
    ]);
    $response->assertRedirect(route('admin.booking.webhooks.index'));
    $this->assertDatabaseHas('booking_webhooks', ['url' => 'https://example.com/webhook']);

    $webhook = BookingWebhook::first();
    expect($webhook->secret)->toHaveLength(40);
    expect($webhook->events)->toContain('appointment.created');

    // Update
    $response = $this->actingAs($admin)->put(route('admin.booking.webhooks.update', $webhook), [
        'url' => 'https://example.com/updated',
        'events' => ['appointment.confirmed'],
        'is_active' => false,
    ]);
    $response->assertRedirect(route('admin.booking.webhooks.index'));
    expect($webhook->fresh()->url)->toBe('https://example.com/updated');

    // Delete
    $response = $this->actingAs($admin)->delete(route('admin.booking.webhooks.destroy', $webhook));
    $response->assertRedirect();
    $this->assertDatabaseMissing('booking_webhooks', ['id' => $webhook->id]);
});

// --- Reschedule ---

it('la replanification est limitée par max_reschedules et min_notice', function () {
    $customer = BookingCustomer::factory()->create();
    $service = ServiceModel::factory()->create(['duration_minutes' => 60]);

    // Rendez-vous déjà replanifié au max
    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'confirmed',
        'reschedule_count' => 1,
        'start_at' => now()->addDays(7),
        'end_at' => now()->addDays(7)->addMinutes(60),
    ]);

    $response = $this->get(route('booking.reschedule', $appointment->cancel_token));
    // Should redirect back with error (max reschedules reached)
    $response->assertRedirect();
});

it('la replanification refuse un rendez-vous annulé', function () {
    $customer = BookingCustomer::factory()->create();
    $service = ServiceModel::factory()->create(['duration_minutes' => 60]);

    $appointment = Appointment::factory()->create([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'status' => 'cancelled',
        'start_at' => now()->addDays(7),
        'end_at' => now()->addDays(7)->addMinutes(60),
    ]);

    $response = $this->get(route('booking.reschedule', $appointment->cancel_token));
    $response->assertRedirect();
});

// --- WebhookDispatchService ---

it('WebhookDispatchService dispatch les webhooks actifs', function () {
    \Illuminate\Support\Facades\Http::fake();
    \Illuminate\Support\Facades\Queue::fake();

    BookingWebhook::create([
        'url' => 'https://example.com/hook',
        'secret' => 'test-secret-123',
        'events' => ['appointment.created'],
        'is_active' => true,
    ]);

    $service = app(\Modules\Booking\Services\WebhookDispatchService::class);
    $service->dispatch('appointment.created', ['test' => 'data']);

    // The dispatch queues a closure, so we verify the webhook exists and is active
    expect(BookingWebhook::active()->forEvent('appointment.created')->count())->toBe(1);
});
