<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Blog\Models\Tag;

class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::withCount('articles')->orderBy('name')->paginate(20);

        return view('blog::admin.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('blog::admin.tags.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        Tag::create($validated);

        return redirect()->route('admin.blog.tags.index')
            ->with('success', 'Tag créé avec succès.');
    }

    public function edit(Tag $tag): View
    {
        return view('blog::admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => "required|string|max:100|unique:tags,name,{$tag->id}",
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        $tag->update($validated);

        return redirect()->route('admin.blog.tags.index')
            ->with('success', 'Tag mis à jour.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()->route('admin.blog.tags.index')
            ->with('success', 'Tag supprimé.');
    }
}
