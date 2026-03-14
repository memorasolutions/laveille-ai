<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Services\BookingService;

class PublicBookingController extends Controller
{
    public function store(Request $request, BookingService $bookingService): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:booking_services,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'customer.first_name' => 'required|string|max:100',
            'customer.last_name' => 'required|string|max:100',
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'nullable|string|max:20',
            'customer.notes' => 'nullable|string|max:1000',
        ]);

        try {
            $appointment = $bookingService->book($validated);

            return response()->json([
                'success' => true,
                'appointment_id' => $appointment->id,
                'message' => config('booking.brand.confirmation_message'),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
