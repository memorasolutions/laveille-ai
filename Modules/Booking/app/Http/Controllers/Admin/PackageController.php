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
use Modules\Booking\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::ordered()->get();

        return view('booking::admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('booking::admin.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'session_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'regular_price' => 'nullable|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'applicable_service_ids' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Package::create($validated);

        return redirect()->route('admin.booking.packages.index')
            ->with('success', __('Forfait créé avec succès.'));
    }

    public function edit(Package $package)
    {
        return view('booking::admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'session_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'regular_price' => 'nullable|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'applicable_service_ids' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $package->update($validated);

        return redirect()->route('admin.booking.packages.index')
            ->with('success', __('Forfait mis à jour avec succès.'));
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()->route('admin.booking.packages.index')
            ->with('success', __('Forfait supprimé avec succès.'));
    }
}
