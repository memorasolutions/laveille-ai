<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Services\ApprovalWorkflowService;
use Modules\Booking\Services\BookingService;
use Modules\Booking\Services\TherapistAssignmentService;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['service', 'customer', 'assignedAdmin']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('therapist')) {
            $query->where('assigned_admin_id', $request->input('therapist'));
        }

        $appointments = $query->latest('start_at')->paginate(20);
        $therapists = app(TherapistAssignmentService::class)->getTherapists();

        return view('booking::admin.appointments.index', compact('appointments', 'therapists'));
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['service', 'customer', 'assignedAdmin']);
        $therapists = app(TherapistAssignmentService::class)->getTherapists();

        return view('booking::admin.appointments.show', compact('appointment', 'therapists'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled,completed']);

        if ($request->input('status') === 'confirmed') {
            app(BookingService::class)->confirm($appointment);
        } else {
            $appointment->update(['status' => $request->input('status')]);
        }

        return redirect()->route('admin.booking.appointments.show', $appointment)
            ->with('success', __('Statut mis à jour.'));
    }

    public function destroy(Appointment $appointment)
    {
        app(BookingService::class)->cancel($appointment->cancel_token, 'Annulé par l\'administrateur');

        return redirect()->route('admin.booking.appointments.index')
            ->with('success', __('Rendez-vous annulé.'));
    }

    public function assign(Request $request, Appointment $appointment)
    {
        $request->validate(['assigned_admin_id' => 'nullable|exists:users,id']);

        $service = app(TherapistAssignmentService::class);

        if ($request->input('assigned_admin_id')) {
            $service->assign($appointment, (int) $request->input('assigned_admin_id'));
        } else {
            $service->unassign($appointment);
        }

        return redirect()->route('admin.booking.appointments.show', $appointment)
            ->with('success', __('Thérapeute assigné.'));
    }

    public function approve(Request $request, Appointment $appointment)
    {
        app(ApprovalWorkflowService::class)->approve($appointment, $request->input('note'));

        return redirect()->route('admin.booking.appointments.show', $appointment)
            ->with('success', __('Rendez-vous approuvé.'));
    }

    public function reject(Request $request, Appointment $appointment)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        app(ApprovalWorkflowService::class)->reject($appointment, $request->input('reason'));

        return redirect()->route('admin.booking.appointments.show', $appointment)
            ->with('success', __('Rendez-vous rejeté.'));
    }
}
