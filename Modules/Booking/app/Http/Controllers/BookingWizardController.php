<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Services\AvailabilityService;
use Modules\Booking\Services\BookingService;
use Modules\Booking\Services\WebhookDispatchService;

class BookingWizardController extends Controller
{
    public function index()
    {
        return view('booking::public.wizard');
    }

    public function manage(string $cancel_token)
    {
        $appointment = Appointment::with(['service', 'customer'])
            ->where('cancel_token', $cancel_token)
            ->firstOrFail();

        return view('booking::public.manage', compact('appointment'));
    }

    public function cancel(Request $request, string $cancel_token)
    {
        app(BookingService::class)->cancel($cancel_token, $request->input('reason'));

        return redirect()->route('booking.manage', $cancel_token)
            ->with('success', __('Rendez-vous annulé avec succès.'));
    }

    public function reschedule(string $cancel_token)
    {
        $appointment = Appointment::with('service')
            ->where('cancel_token', $cancel_token)
            ->firstOrFail();

        if ($appointment->status === 'cancelled') {
            return back()->withErrors(['error' => __('Ce rendez-vous a été annulé.')]);
        }

        $maxReschedules = (int) config('booking.max_reschedules', 1);
        if ($appointment->reschedule_count >= $maxReschedules) {
            return back()->withErrors(['error' => __('Nombre maximal de replanifications atteint.')]);
        }

        $minNotice = (int) config('booking.min_notice_hours', 48);
        if ($appointment->start_at->lessThan(now()->addHours($minNotice))) {
            return back()->withErrors(['error' => __("La replanification nécessite un préavis de {$minNotice} heures.")]);
        }

        $availableSlots = app(AvailabilityService::class)->getAvailableSlots(30);

        return view('booking::public.reschedule', compact('appointment', 'availableSlots'));
    }

    public function processReschedule(Request $request, string $cancel_token)
    {
        $appointment = Appointment::with('service')
            ->where('cancel_token', $cancel_token)
            ->firstOrFail();

        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
        ]);

        if ($appointment->status === 'cancelled') {
            return back()->withErrors(['error' => __('Ce rendez-vous a été annulé.')]);
        }

        $maxReschedules = (int) config('booking.max_reschedules', 1);
        if ($appointment->reschedule_count >= $maxReschedules) {
            return back()->withErrors(['error' => __('Nombre maximal de replanifications atteint.')]);
        }

        $minNotice = (int) config('booking.min_notice_hours', 48);
        if ($appointment->start_at->lessThan(now()->addHours($minNotice))) {
            return back()->withErrors(['error' => __("La replanification nécessite un préavis de {$minNotice} heures.")]);
        }

        $duration = $appointment->service->duration_minutes;

        if (! app(AvailabilityService::class)->isSlotAvailable($request->input('date'), $request->input('start_time'), $duration)) {
            return back()->withErrors(['error' => __('Le créneau sélectionné n\'est plus disponible.')]);
        }

        $timezone = config('booking.timezone', 'America/Toronto');
        $startAt = Carbon::parse($request->input('date').' '.$request->input('start_time'), $timezone);
        $endAt = $startAt->copy()->addMinutes($duration);

        $appointment->update([
            'start_at' => $startAt,
            'end_at' => $endAt,
            'reschedule_count' => $appointment->reschedule_count + 1,
        ]);

        app(WebhookDispatchService::class)->dispatchForAppointment('appointment.rescheduled', $appointment);

        return redirect()->route('booking.manage', $cancel_token)
            ->with('success', __('Votre rendez-vous a été replanifié.'));
    }
}
