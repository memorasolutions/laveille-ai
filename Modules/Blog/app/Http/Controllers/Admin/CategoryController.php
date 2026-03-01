<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blog\Models\Category;

class CategoryController
{
    public function index(): View
    {
        return view('blog::admin.categories.index');
    }

    public function create(): View
    {
        return view('blog::admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_categories,name',
            'description' => 'nullable|string',
            'color' => 'required|string|size:7',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);

        Category::create($validated);

        session()->flash('success', 'Catégorie créée avec succès.');

        return redirect()->route('admin.blog.categories.index');
    }

    public function edit(Category $category): View
    {
        return view('blog::admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_categories,name,'.$category->id,
            'description' => 'nullable|string',
            'color' => 'required|string|size:7',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);

        $category->update($validated);

        session()->flash('success', 'Catégorie mise à jour avec succès.');

        return redirect()->route('admin.blog.categories.index');
    }

    public function quickCreate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_categories,name',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'color' => '#6366f1',
            'is_active' => true,
        ]);

        return response()->json(['id' => $category->id, 'name' => $category->name]);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        session()->flash('success', 'Catégorie supprimée avec succès.');

        return redirect()->route('admin.blog.categories.index');
    }
}
