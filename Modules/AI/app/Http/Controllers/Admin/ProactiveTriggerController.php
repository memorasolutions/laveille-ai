<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Models\ProactiveTrigger;

class ProactiveTriggerController extends Controller
{
    public function index(): View
    {
        $triggers = ProactiveTrigger::latest()->get();

        return view('ai::admin.proactive-triggers.index', compact('triggers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'event_type' => 'required|string|max:50',
            'conditions' => 'nullable|array',
            'message' => 'required|string',
            'is_active' => 'boolean',
            'delay_seconds' => 'nullable|integer|min:0',
        ]);

        ProactiveTrigger::create($data);

        return back()->with('success', __('Le déclencheur a été créé.'));
    }

    public function update(Request $request, ProactiveTrigger $trigger): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'event_type' => 'required|string|max:50',
            'conditions' => 'nullable|array',
            'message' => 'required|string',
            'is_active' => 'boolean',
            'delay_seconds' => 'nullable|integer|min:0',
        ]);

        $trigger->update($data);

        return back()->with('success', __('Le déclencheur a été mis à jour.'));
    }

    public function destroy(ProactiveTrigger $trigger): RedirectResponse
    {
        $trigger->delete();

        return back()->with('success', __('Le déclencheur a été supprimé.'));
    }

    public function toggle(ProactiveTrigger $trigger): RedirectResponse
    {
        $trigger->update(['is_active' => ! $trigger->is_active]);

        return back()->with('success', __('Le statut du déclencheur a été mis à jour.'));
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => 'required|string',
            'context' => 'nullable|array',
        ]);

        $triggers = ProactiveTrigger::active()
            ->forEvent($data['event_type'])
            ->get()
            ->filter(fn ($t) => $t->matchesContext($data['context'] ?? []))
            ->values();

        return response()->json($triggers);
    }
}
