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
use Modules\Booking\Services\ApprovalWorkflowService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// --- ApprovalWorkflowService ---

it('requiresApproval retourne true si require_approval est activé', function () {
    $service = ServiceModel::factory()->create(['require_approval' => true]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id]);

    expect(app(ApprovalWorkflowService::class)->requiresApproval($appointment))->toBeTrue();
});

it('requiresApproval retourne false par défaut', function () {
    $service = ServiceModel::factory()->create(['require_approval' => false]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id]);

    expect(app(ApprovalWorkflowService::class)->requiresApproval($appointment))->toBeFalse();
});

it('submitForApproval met le statut à pending_approval', function () {
    $service = ServiceModel::factory()->create(['require_approval' => true]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'pending']);

    app(ApprovalWorkflowService::class)->submitForApproval($appointment);

    expect($appointment->fresh()->status)->toBe('pending_approval');
});

it('approve met le statut à confirmed et approval_note + approved_at', function () {
    $service = ServiceModel::factory()->create(['require_approval' => true]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'pending_approval']);

    app(ApprovalWorkflowService::class)->approve($appointment, 'Approuvé par admin');

    $fresh = $appointment->fresh();
    expect($fresh->status)->toBe('confirmed');
    expect($fresh->approval_note)->toBe('Approuvé par admin');
    expect($fresh->approved_at)->not->toBeNull();
});

it('reject met le statut à rejected avec raison', function () {
    $service = ServiceModel::factory()->create(['require_approval' => true]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'pending_approval']);

    app(ApprovalWorkflowService::class)->reject($appointment, 'Informations insuffisantes');

    $fresh = $appointment->fresh();
    expect($fresh->status)->toBe('rejected');
    expect($fresh->cancel_reason)->toBe('Informations insuffisantes');
    expect($fresh->cancelled_at)->not->toBeNull();
});

// --- Admin customers ---

it('un admin peut voir la liste des clients', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    BookingCustomer::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('admin.booking.customers.index'));
    $response->assertOk();
});

it('un admin peut voir le détail d\'un client', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $customer = BookingCustomer::factory()->create(['first_name' => 'Jean', 'last_name' => 'Tremblay']);

    $response = $this->actingAs($admin)->get(route('admin.booking.customers.show', $customer));
    $response->assertOk();
    $response->assertSee('Jean Tremblay');
});

it('un admin peut approuver un rendez-vous', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create(['require_approval' => true]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'pending_approval']);

    $response = $this->actingAs($admin)->put(route('admin.booking.appointments.approve', $appointment), ['note' => 'OK']);
    $response->assertRedirect();

    expect($appointment->fresh()->status)->toBe('confirmed');
});

it('un admin peut rejeter un rendez-vous', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->givePermissionTo('manage_booking');
    $service = ServiceModel::factory()->create(['require_approval' => true]);
    $customer = BookingCustomer::factory()->create();
    $appointment = Appointment::factory()->create(['service_id' => $service->id, 'customer_id' => $customer->id, 'status' => 'pending_approval']);

    $response = $this->actingAs($admin)->put(route('admin.booking.appointments.reject', $appointment), ['reason' => 'Pas assez de détails']);
    $response->assertRedirect();

    expect($appointment->fresh()->status)->toBe('rejected');
});
