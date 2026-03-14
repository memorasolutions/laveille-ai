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
use Illuminate\Support\Str;
use Modules\Booking\Models\BookingWebhook;

class BookingWebhookController extends Controller
{
    public function index()
    {
        $webhooks = BookingWebhook::latest()->get();

        return view('booking::admin.webhooks.index', compact('webhooks'));
    }

    public function create()
    {
        return view('booking::admin.webhooks.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array',
            'events.*' => 'in:appointment.created,appointment.confirmed,appointment.cancelled,appointment.rescheduled',
            'is_active' => 'boolean',
        ]);

        $validated['secret'] = Str::random(40);

        BookingWebhook::create($validated);

        return redirect()->route('admin.booking.webhooks.index')
            ->with('success', __('Webhook créé.'));
    }

    public function edit(BookingWebhook $webhook)
    {
        return view('booking::admin.webhooks.form', compact('webhook'));
    }

    public function update(Request $request, BookingWebhook $webhook)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array',
            'events.*' => 'in:appointment.created,appointment.confirmed,appointment.cancelled,appointment.rescheduled',
            'is_active' => 'boolean',
        ]);

        $webhook->update($validated);

        return redirect()->route('admin.booking.webhooks.index')
            ->with('success', __('Webhook mis à jour.'));
    }

    public function destroy(BookingWebhook $webhook)
    {
        $webhook->delete();

        return redirect()->back()->with('success', __('Webhook supprimé.'));
    }
}
