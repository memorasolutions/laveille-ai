<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Tools\Models\Tool;

class ToolAdminController extends Controller
{
    public function index(): View
    {
        $tools = Tool::ordered()->get();

        return view('tools::admin.index', compact('tools'));
    }

    public function create(): View
    {
        return view('tools::admin.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tools,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        Tool::create($validated);

        return redirect()->route('admin.tools.index')->with('success', __('Outil créé.'));
    }

    public function edit(Tool $tool): View
    {
        return view('tools::admin.edit', compact('tool'));
    }

    public function update(Request $request, Tool $tool): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $tool->update($validated);

        return redirect()->route('admin.tools.index')->with('success', __('Outil mis à jour.'));
    }

    public function toggleActive(Tool $tool): RedirectResponse
    {
        $tool->update(['is_active' => ! $tool->is_active]);

        return back()->with('success', $tool->is_active ? __('Outil activé.') : __('Outil désactivé.'));
    }
}
