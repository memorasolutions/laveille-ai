<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\News\Models\NewsSource;
use Modules\News\Services\RssFetcherService;
use Modules\Settings\Facades\Settings;

class AdminNewsController extends Controller
{
    public function index(): View
    {
        $sources = NewsSource::withCount('articles')->paginate((int) Settings::get('news.admin_per_page', 20));

        return view('news::admin.sources.index', compact('sources'));
    }

    public function create(): View
    {
        return view('news::admin.sources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|unique:news_sources',
            'category' => 'nullable|string|max:255',
            'language' => ['nullable', 'string', Rule::in(['fr', 'en'])],
            'active' => 'boolean',
        ]);

        NewsSource::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'category' => $validated['category'] ?? null,
            'language' => $validated['language'] ?? 'fr',
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.news.sources.index')->with('success', __('Source RSS créée.'));
    }

    public function edit(NewsSource $source): View
    {
        return view('news::admin.sources.edit', compact('source'));
    }

    public function update(Request $request, NewsSource $source): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => ['required', 'url', Rule::unique('news_sources')->ignore($source->id)],
            'category' => 'nullable|string|max:255',
            'language' => ['nullable', 'string', Rule::in(['fr', 'en'])],
            'active' => 'boolean',
        ]);

        $source->update([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'category' => $validated['category'] ?? null,
            'language' => $validated['language'] ?? 'fr',
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.news.sources.index')->with('success', __('Source RSS mise à jour.'));
    }

    public function toggleActive(NewsSource $source): RedirectResponse
    {
        $source->update(['active' => ! $source->active]);

        return back()->with('success', __('Statut modifié.'));
    }

    public function destroy(NewsSource $source): RedirectResponse
    {
        $source->delete();

        return redirect()->route('admin.news.sources.index')->with('success', __('Source supprimée.'));
    }

    public function fetchNow(NewsSource $source, RssFetcherService $fetcher): RedirectResponse
    {
        $count = $fetcher->fetchSource($source);

        return back()->with('success', __(':count articles récupérés pour :name.', ['count' => $count, 'name' => $source->name]));
    }
}
