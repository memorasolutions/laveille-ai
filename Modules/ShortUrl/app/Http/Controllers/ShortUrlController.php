<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\ShortUrl\Models\ShortUrl;
use Modules\ShortUrl\Models\ShortUrlDomain;
use Modules\ShortUrl\Services\ShortUrlService;

class ShortUrlController
{
    public function __construct(
        private readonly ShortUrlService $service
    ) {}

    public function index(): View
    {
        $shortUrls = ShortUrl::with(['user', 'domain'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('shorturl::admin.index', compact('shortUrls'));
    }

    public function create(): View
    {
        $domains = ShortUrlDomain::active()->get();

        return view('shorturl::admin.create', compact('domains'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'original_url' => ['required', 'url', 'max:2048'],
            'slug' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:short_urls,slug'],
            'title' => ['nullable', 'string', 'max:255'],
            'domain_id' => ['nullable', 'exists:short_url_domains,id'],
            'password' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_clicks' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'redirect_type' => ['nullable', 'in:301,302'],
            'tags' => ['nullable', 'string'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if (! empty($validated['tags'])) {
            $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'])));
        } else {
            $validated['tags'] = [];
        }

        $this->service->createShortUrl($validated, (int) auth()->id());

        return redirect()->route('admin.short-urls.index')
            ->with('success', 'Lien court créé avec succès.');
    }

    public function show(ShortUrl $shortUrl): View
    {
        $analytics = $this->service->getAnalytics($shortUrl, 30);

        return view('shorturl::admin.show', compact('shortUrl', 'analytics'));
    }

    public function edit(ShortUrl $shortUrl): View
    {
        $domains = ShortUrlDomain::active()->get();

        return view('shorturl::admin.edit', compact('shortUrl', 'domains'));
    }

    public function update(Request $request, ShortUrl $shortUrl): RedirectResponse
    {
        $validated = $request->validate([
            'original_url' => ['required', 'url', 'max:2048'],
            'slug' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:short_urls,slug,' . $shortUrl->id],
            'title' => ['nullable', 'string', 'max:255'],
            'domain_id' => ['nullable', 'exists:short_url_domains,id'],
            'password' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'max_clicks' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'redirect_type' => ['nullable', 'in:301,302'],
            'tags' => ['nullable', 'string'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if (isset($validated['tags']) && $validated['tags'] !== '') {
            $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'])));
        } else {
            $validated['tags'] = [];
        }

        if (! empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $oldSlug = $shortUrl->slug;
        $shortUrl->update($validated);

        Cache::forget("short_url:{$oldSlug}");
        if ($oldSlug !== $shortUrl->slug) {
            Cache::forget("short_url:{$shortUrl->slug}");
        }

        return back()->with('success', 'Lien court mis à jour.');
    }

    public function destroy(ShortUrl $shortUrl): RedirectResponse
    {
        Cache::forget("short_url:{$shortUrl->slug}");
        $shortUrl->delete();

        return redirect()->route('admin.short-urls.index')
            ->with('success', 'Lien court supprimé.');
    }

    public function toggleActive(ShortUrl $shortUrl): RedirectResponse
    {
        $shortUrl->is_active = ! $shortUrl->is_active;
        $shortUrl->save();

        Cache::forget("short_url:{$shortUrl->slug}");

        return back()->with('success', $shortUrl->is_active ? 'Lien activé.' : 'Lien désactivé.');
    }
}
