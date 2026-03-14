<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Services\AvailabilityService;

class WidgetController
{
    public function show(Request $request): Response
    {
        $serviceId = $request->query('service_id');
        $color = $request->query('color', '#0d6efd');
        $locale = $request->query('locale', 'fr');

        if ($serviceId) {
            $services = BookingService::where('id', $serviceId)->active()->get();
        } else {
            $services = BookingService::active()->ordered()->get();
        }

        $availability = app(AvailabilityService::class);
        $defaultDuration = (int) config('booking.slot_duration_minutes', 30);
        $dates = $availability->getAvailableDates(30);
        $availableSlots = [];
        foreach ($dates as $date) {
            $slots = $availability->getAvailableSlots($date, $defaultDuration);
            if (! empty($slots)) {
                $availableSlots[$date] = array_column($slots, 'start');
            }
        }

        $response = new Response(
            view('booking::widget.index', compact('services', 'availableSlots', 'color', 'locale'))
        );

        $response->header('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
