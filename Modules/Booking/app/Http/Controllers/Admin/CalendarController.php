<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\Appointment;

class CalendarController extends Controller
{
    public function index()
    {
        return view('booking::admin.calendar.index');
    }

    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $start = Carbon::parse($request->input('start'));
        $end = Carbon::parse($request->input('end'));

        $appointments = Appointment::with(['service', 'customer'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_at', '<', $start)
                            ->where('end_at', '>', $end);
                    });
            })
            ->get();

        $colors = [
            'pending' => '#ffc107',
            'confirmed' => '#198754',
            'cancelled' => '#dc3545',
            'completed' => '#0dcaf0',
            'pending_approval' => '#6c757d',
            'rejected' => '#dc3545',
            'no_show' => '#212529',
        ];

        $events = $appointments->map(fn (Appointment $a) => [
            'id' => $a->id,
            'title' => $a->service->name.' - '.$a->customer->full_name,
            'start' => $a->start_at->toIso8601String(),
            'end' => $a->end_at->toIso8601String(),
            'color' => $colors[$a->status] ?? '#6c757d',
            'extendedProps' => [
                'status' => $a->status,
            ],
            'url' => route('admin.booking.appointments.show', $a->id),
        ]);

        return response()->json($events);
    }
}
