<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Modules\Privacy\Models\CookieCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CookieCategoryController
{
    public function index(): View
    {
        $categories = CookieCategory::orderBy('order')->get();

        return view('backoffice::cookie-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('backoffice::cookie-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:cookie_categories,name',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'required' => 'boolean',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['required'] = $request->boolean('required');
        $validated['is_active'] = $request->boolean('is_active', true);

        CookieCategory::create($validated);

        return redirect()->route('admin.cookie-categories.index')->with('success', 'Catégorie créée avec succès.');
    }

    public function edit(CookieCategory $cookieCategory): View
    {
        return view('backoffice::cookie-categories.edit', ['category' => $cookieCategory]);
    }

    public function update(Request $request, CookieCategory $cookieCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:cookie_categories,name,'.$cookieCategory->id,
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'required' => 'boolean',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['required'] = $request->boolean('required');
        $validated['is_active'] = $request->boolean('is_active', true);

        $cookieCategory->update($validated);

        return redirect()->route('admin.cookie-categories.index')->with('success', 'Catégorie mise à jour avec succès.');
    }

    public function destroy(CookieCategory $cookieCategory): RedirectResponse
    {
        if ($cookieCategory->isRequired()) {
            return redirect()->route('admin.cookie-categories.index')->with('error', 'Impossible de supprimer une catégorie obligatoire.');
        }

        $cookieCategory->delete();

        return redirect()->route('admin.cookie-categories.index')->with('success', 'Catégorie supprimée avec succès.');
    }
}
