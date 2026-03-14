<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\SlaPolicy;

class SlaPolicyController extends Controller
{
    public function index(): View
    {
        $slaPolicies = SlaPolicy::all();

        return view('ai::admin.sla.index', compact('slaPolicies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'first_response_hours' => 'required|integer|min:1',
            'resolution_hours' => 'required|integer|min:1',
        ]);

        SlaPolicy::create($data);

        return back()->with('success', __('La politique SLA a été créée.'));
    }

    public function update(Request $request, SlaPolicy $slaPolicy): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'first_response_hours' => 'required|integer|min:1',
            'resolution_hours' => 'required|integer|min:1',
        ]);

        $slaPolicy->update($data);

        return back()->with('success', __('La politique SLA a été mise à jour.'));
    }

    public function destroy(SlaPolicy $slaPolicy): RedirectResponse
    {
        $slaPolicy->delete();

        return back()->with('success', __('La politique SLA a été supprimée.'));
    }
}
