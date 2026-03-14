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
use Modules\Booking\Models\DateOverride;

class DateOverrideController extends Controller
{
    public function index()
    {
        $overrides = DateOverride::latest('date')->paginate(20);

        return view('booking::admin.date-overrides.index', compact('overrides'));
    }

    public function create()
    {
        return view('booking::admin.date-overrides.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'override_type' => 'required|in:blocked,available',
            'all_day' => 'boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:500',
        ]);

        $validated['all_day'] = $request->boolean('all_day');
        $validated['created_by_id'] = auth()->id();

        DateOverride::create($validated);

        return redirect()->route('admin.booking.date-overrides.index')
            ->with('success', __('Exception créée.'));
    }

    public function edit(DateOverride $dateOverride)
    {
        return view('booking::admin.date-overrides.edit', compact('dateOverride'));
    }

    public function update(Request $request, DateOverride $dateOverride)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'override_type' => 'required|in:blocked,available',
            'all_day' => 'boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:500',
        ]);

        $validated['all_day'] = $request->boolean('all_day');
        $dateOverride->update($validated);

        return redirect()->route('admin.booking.date-overrides.index')
            ->with('success', __('Exception mise à jour.'));
    }

    public function destroy(DateOverride $dateOverride)
    {
        $dateOverride->delete();

        return redirect()->route('admin.booking.date-overrides.index')
            ->with('success', __('Exception supprimée.'));
    }
}
