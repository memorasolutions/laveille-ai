<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Category;

class CategoryController
{
    public function index(): View
    {
        $categories = Category::with('parent')->orderBy('position')->get();

        return view('ecommerce::admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parents = Category::whereNull('parent_id')->get();

        return view('ecommerce::admin.categories.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:ecommerce_categories,slug',
            'parent_id' => 'nullable|exists:ecommerce_categories,id',
            'is_active' => 'boolean',
            'position' => 'nullable|integer',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        Category::create($validated);

        session()->flash('success', 'Catégorie créée avec succès.');

        return redirect()->route('admin.ecommerce.categories.index');
    }

    public function edit(Category $category): View
    {
        $parents = Category::whereNull('parent_id')->where('id', '!=', $category->id)->get();

        return view('ecommerce::admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:ecommerce_categories,slug,'.$category->id,
            'parent_id' => ['nullable', 'exists:ecommerce_categories,id', 'not_in:'.$category->id],
            'is_active' => 'boolean',
            'position' => 'nullable|integer',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        session()->flash('success', 'Catégorie mise à jour avec succès.');

        return redirect()->route('admin.ecommerce.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        session()->flash('success', 'Catégorie supprimée avec succès.');

        return redirect()->route('admin.ecommerce.categories.index');
    }
}
