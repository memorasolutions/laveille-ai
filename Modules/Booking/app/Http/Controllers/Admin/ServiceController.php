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
use Modules\Booking\Models\BookingService;

class ServiceController extends Controller
{
    public function index()
    {
        $services = BookingService::ordered()->get();

        return view('booking::admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('booking::admin.services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        BookingService::create($validated);

        return redirect()->route('admin.booking.services.index')
            ->with('success', __('Service créé avec succès.'));
    }

    public function edit(BookingService $service)
    {
        return view('booking::admin.services.edit', compact('service'));
    }

    public function update(Request $request, BookingService $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $service->update($validated);

        return redirect()->route('admin.booking.services.index')
            ->with('success', __('Service mis à jour.'));
    }

    public function destroy(BookingService $service)
    {
        $service->delete();

        return redirect()->route('admin.booking.services.index')
            ->with('success', __('Service supprimé.'));
    }
}
