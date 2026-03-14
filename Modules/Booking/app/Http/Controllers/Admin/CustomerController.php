<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\Booking\Models\BookingCustomer;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = BookingCustomer::orderByDesc('last_booking_at')->paginate(20);

        return view('booking::admin.customers.index', compact('customers'));
    }

    public function show(BookingCustomer $customer)
    {
        $appointments = $customer->appointments()->with('service')->latest('start_at')->get();

        return view('booking::admin.customers.show', compact('customer', 'appointments'));
    }
}
