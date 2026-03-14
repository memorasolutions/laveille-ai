<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SEO\Models\MetaTag;

class SeoController
{
    public function index(): View
    {
        return view('backoffice::seo.index');
    }

    public function create(): View
    {
        return view('backoffice::seo.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url_pattern' => ['required', 'string', 'max:255', 'unique:seo_meta_tags'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_image' => ['nullable', 'url', 'max:500'],
            'twitter_card' => ['nullable', 'in:summary,summary_large_image,app,player'],
            'robots' => ['nullable', 'string', 'max:100'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        MetaTag::create($validated);

        return redirect()->route('admin.seo.index')
            ->with('success', 'Tag SEO créé avec succès.');
    }

    public function edit(MetaTag $metaTag): View
    {
        return view('backoffice::seo.edit', compact('metaTag'));
    }

    public function update(Request $request, MetaTag $metaTag): RedirectResponse
    {
        $validated = $request->validate([
            'url_pattern' => ['required', 'string', 'max:255', 'unique:seo_meta_tags,url_pattern,'.$metaTag->id],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_image' => ['nullable', 'url', 'max:500'],
            'twitter_card' => ['nullable', 'in:summary,summary_large_image,app,player'],
            'robots' => ['nullable', 'string', 'max:100'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $metaTag->update($validated);

        return redirect()->route('admin.seo.index')
            ->with('success', 'Tag SEO mis à jour avec succès.');
    }

    public function destroy(MetaTag $metaTag): RedirectResponse
    {
        $metaTag->delete();

        return redirect()->route('admin.seo.index')
            ->with('success', 'Tag SEO supprimé avec succès.');
    }
}
