<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ShortUrl\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\ShortUrl\Models\ShortUrl;
use Modules\ShortUrl\Services\ShortUrlService;

class UserShortUrlController
{
    public function __construct(
        private readonly ShortUrlService $service
    ) {}

    public function index(): View
    {
        $shortUrls = ShortUrl::where('user_id', auth()->id())
            ->with('domain')
            ->latest()
            ->paginate(20);

        return view('shorturl::user.index', compact('shortUrls'));
    }

    public function create(): View
    {
        return view('shorturl::user.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'original_url' => ['required', 'url', 'max:2048'],
            'slug' => ['nullable', 'alpha_dash', 'max:50', 'unique:short_urls,slug'],
            'title' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_clicks' => ['nullable', 'integer', 'min:1'],
            'og_title' => ['nullable', 'max:255'],
            'og_description' => ['nullable', 'max:500'],
            'og_image' => ['nullable', 'url', 'max:500'],
            'utm_source' => ['nullable', 'max:255'],
            'utm_medium' => ['nullable', 'max:255'],
            'utm_campaign' => ['nullable', 'max:255'],
        ]);

        $validated['domain_id'] = $this->service->getDefaultDomain()?->id;
        $validated['redirect_type'] = 301;

        $this->service->createShortUrl($validated, auth()->id());

        return redirect()->route('shorturl.user.index')->with('success', __('Lien raccourci créé avec succès.'));
    }

    public function edit(ShortUrl $shortUrl): View
    {
        abort_if($shortUrl->user_id !== auth()->id(), 403);

        return view('shorturl::user.edit', compact('shortUrl'));
    }

    public function update(Request $request, ShortUrl $shortUrl): RedirectResponse
    {
        abort_if($shortUrl->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'original_url' => ['required', 'url', 'max:2048'],
            'slug' => ['nullable', 'alpha_dash', 'max:50', Rule::unique('short_urls', 'slug')->ignore($shortUrl->id)],
            'title' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_clicks' => ['nullable', 'integer', 'min:1'],
            'og_title' => ['nullable', 'max:255'],
            'og_description' => ['nullable', 'max:500'],
            'og_image' => ['nullable', 'url', 'max:500'],
            'utm_source' => ['nullable', 'max:255'],
            'utm_medium' => ['nullable', 'max:255'],
            'utm_campaign' => ['nullable', 'max:255'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $shortUrl->update($validated);

        return back()->with('success', __('Lien raccourci mis à jour avec succès.'));
    }

    public function destroy(ShortUrl $shortUrl): RedirectResponse
    {
        abort_if($shortUrl->user_id !== auth()->id(), 403);

        $shortUrl->delete();

        return redirect()->route('shorturl.user.index')->with('success', __('Lien raccourci supprimé.'));
    }
}
