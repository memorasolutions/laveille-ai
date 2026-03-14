<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Services\ICalService;

class CustomerPortalController extends Controller
{
    public function index(string $token)
    {
        $customer = BookingCustomer::where('portal_token', $token)->firstOrFail();

        $upcoming = $customer->appointments()
            ->with('service')
            ->where('start_at', '>', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('start_at')
            ->get();

        $past = $customer->appointments()
            ->with('service')
            ->where(fn ($q) => $q->where('start_at', '<=', now())->orWhere('status', 'cancelled'))
            ->orderByDesc('start_at')
            ->limit(10)
            ->get();

        return view('booking::public.portal.index', compact('customer', 'upcoming', 'past'));
    }

    public function cancel(Request $request, string $token, Appointment $appointment)
    {
        $customer = BookingCustomer::where('portal_token', $token)->firstOrFail();

        if ($appointment->customer_id !== $customer->id) {
            abort(403);
        }

        $minHours = config('booking.min_notice_hours', 48);
        if ($appointment->start_at->lessThan(now()->addHours($minHours))) {
            return back()->withErrors(['error' => "L'annulation nécessite un préavis de {$minHours} heures."]);
        }

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $request->input('cancel_reason'),
        ]);

        return back()->with('success', 'Votre rendez-vous a été annulé.');
    }

    public function ical(string $token)
    {
        $customer = BookingCustomer::where('portal_token', $token)->firstOrFail();

        $upcoming = $customer->appointments()
            ->with('service')
            ->where('start_at', '>', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('start_at')
            ->get();

        $content = app(ICalService::class)->generateCalendar(
            $upcoming,
            "Rendez-vous - {$customer->full_name}"
        );

        return response($content)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="rendez-vous.ics"');
    }
}
