<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Booking\Mail\BookingConfirmation;
use Modules\Booking\Models\Appointment;

class ApprovalWorkflowService
{
    public function __construct(
        protected WebhookDispatchService $webhookDispatcher,
    ) {}

    public function requiresApproval(Appointment $appointment): bool
    {
        return (bool) ($appointment->service->require_approval ?? false);
    }

    public function submitForApproval(Appointment $appointment): void
    {
        $appointment->update(['status' => 'pending_approval']);
        $this->webhookDispatcher->dispatchForAppointment('appointment.pending_approval', $appointment);
    }

    public function approve(Appointment $appointment, ?string $note = null): void
    {
        $appointment->update([
            'status' => 'confirmed',
            'approval_note' => $note,
            'approved_at' => now(),
        ]);

        if (config('booking.email.enabled', true)) {
            Mail::to($appointment->customer->email)->queue(new BookingConfirmation($appointment));
        }

        $this->webhookDispatcher->dispatchForAppointment('appointment.approved', $appointment);
    }

    public function reject(Appointment $appointment, string $reason): void
    {
        $appointment->update([
            'status' => 'rejected',
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        $this->webhookDispatcher->dispatchForAppointment('appointment.rejected', $appointment);
    }
}
