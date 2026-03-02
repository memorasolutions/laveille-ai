<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Pages\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pages\Models\StaticPage;

class StaticPageController extends Controller
{
    public function index(): View
    {
        return view('pages::admin.pages.index');
    }

    public function create(): View
    {
        return view('pages::admin.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'nullable|in:draft,published',
            'template' => 'nullable|in:' . implode(',', array_keys(StaticPage::TEMPLATES)),
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['template'] = $validated['template'] ?? 'default';

        StaticPage::create($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page créée avec succès.');
    }

    public function preview(StaticPage $page): View
    {
        $template = $page->template ?? 'default';
        $viewName = "pages::public.templates.{$template}";

        if (! view()->exists($viewName)) {
            $viewName = 'pages::public.templates.default';
        }

        $isPreview = true;

        return view($viewName, compact('page', 'isPreview'));
    }

    public function edit(StaticPage $page): View
    {
        return view('pages::admin.pages.edit', compact('page'));
    }

    public function update(Request $request, StaticPage $page): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'nullable|in:draft,published',
            'template' => 'nullable|in:' . implode(',', array_keys(StaticPage::TEMPLATES)),
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $page->update($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page mise à jour.');
    }

    public function destroy(StaticPage $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page supprimée.');
    }
}
