<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Health\Models\HealthIncident;

class IncidentController extends Controller
{
    public function index(): View
    {
        $incidents = HealthIncident::orderByDesc('created_at')->paginate(15);

        return view('health::admin.incidents.index', compact('incidents'));
    }

    public function create(): View
    {
        return view('health::admin.incidents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:investigating,identified,monitoring,resolved',
            'severity' => 'required|in:critical,major,minor',
        ]);

        if ($validated['status'] === 'resolved') {
            $validated['resolved_at'] = now();
        }

        HealthIncident::create($validated);

        return redirect()->route('admin.health.incidents.index')
            ->with('success', __('Incident créé avec succès.'));
    }

    public function edit(HealthIncident $incident): View
    {
        return view('health::admin.incidents.edit', compact('incident'));
    }

    public function update(Request $request, HealthIncident $incident): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:investigating,identified,monitoring,resolved',
            'severity' => 'required|in:critical,major,minor',
        ]);

        if ($validated['status'] === 'resolved' && $incident->status !== 'resolved') {
            $validated['resolved_at'] = now();
        }

        $incident->update($validated);

        return redirect()->route('admin.health.incidents.index')
            ->with('success', __('Incident mis à jour avec succès.'));
    }

    public function destroy(HealthIncident $incident): RedirectResponse
    {
        $incident->delete();

        return redirect()->route('admin.health.incidents.index')
            ->with('success', __('Incident supprimé avec succès.'));
    }
}
