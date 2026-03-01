<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Widget\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Widget\Models\Widget;
use Modules\Widget\Services\WidgetService;

class WidgetController extends Controller
{
    public function index(): View
    {
        $widgetsByZone = [];
        foreach (Widget::ZONES as $zone) {
            $widgetsByZone[$zone] = Widget::forZone($zone)->orderBy('sort_order')->get();
        }

        return view('widget::admin.index', compact('widgetsByZone'));
    }

    public function create(): View
    {
        return view('widget::admin.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'zone' => 'required|in:' . implode(',', Widget::ZONES),
            'type' => 'required|in:' . implode(',', Widget::TYPES),
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = Widget::forZone($validated['zone'])->count();

        Widget::create($validated);

        return redirect()->route('admin.widgets.index')
            ->with('success', 'Widget créé avec succès.');
    }

    public function edit(Widget $widget): View
    {
        return view('widget::admin.edit', compact('widget'));
    }

    public function update(Request $request, Widget $widget): RedirectResponse
    {
        $oldZone = $widget->zone;

        $validated = $request->validate([
            'zone' => 'required|in:' . implode(',', Widget::ZONES),
            'type' => 'required|in:' . implode(',', Widget::TYPES),
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $widget->update($validated);

        // Clear old zone cache if zone changed
        if ($oldZone !== $validated['zone']) {
            WidgetService::clearCache($oldZone);
        }

        return redirect()->route('admin.widgets.index')
            ->with('success', 'Widget mis à jour.');
    }

    public function destroy(Widget $widget): RedirectResponse
    {
        $widget->delete();

        return redirect()->route('admin.widgets.index')
            ->with('success', 'Widget supprimé.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'zone' => 'required|in:' . implode(',', Widget::ZONES),
            'order' => 'required|array',
            'order.*' => 'integer|exists:widgets,id',
        ]);

        foreach ($request->input('order') as $index => $id) {
            Widget::where('id', $id)->update(['sort_order' => $index]);
        }

        WidgetService::clearCache($request->input('zone'));

        return response()->json(['success' => true]);
    }
}
