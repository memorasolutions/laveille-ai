<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Booking\Models\Appointment;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $todayCount = Appointment::whereDate('start_at', $now->toDateString())->count();
        $weekCount = Appointment::whereBetween('start_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count();
        $monthCount = Appointment::whereBetween('start_at', [$startOfMonth, $endOfMonth])->count();
        $pendingApprovalCount = Appointment::where('status', 'pending_approval')->count();

        $noShowCount = Appointment::whereBetween('start_at', [$startOfMonth, $endOfMonth])->where('status', 'no_show')->count();
        $noShowRate = $monthCount > 0 ? round(($noShowCount / $monthCount) * 100, 1) : 0;

        $monthRevenue = (float) Appointment::whereBetween('start_at', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['confirmed', 'completed'])
            ->join('booking_services', 'booking_appointments.service_id', '=', 'booking_services.id')
            ->sum('booking_services.price');

        $upcoming = Appointment::with(['service', 'customer'])
            ->where('start_at', '>', $now)
            ->where('status', 'confirmed')
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        return view('booking::admin.dashboard.index', compact(
            'todayCount', 'weekCount', 'monthCount', 'pendingApprovalCount',
            'noShowRate', 'monthRevenue', 'upcoming'
        ));
    }
}
