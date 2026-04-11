<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ShortUrl\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\ShortUrl\Models\ShortUrl;
use Modules\Settings\Facades\Settings;
use Modules\ShortUrl\Services\ShortUrlService;

class UserShortUrlController
{
    public function __construct(
        private readonly ShortUrlService $service
    ) {}

    public function index(): View
    {
        $query = ShortUrl::where('user_id', auth()->id())->with('domain')->latest();
        $allLinks = $query->get();
        $shortUrls = ShortUrl::where('user_id', auth()->id())
            ->with('domain')
            ->latest()
            ->paginate((int) Settings::get('shorturl.user_per_page', 20));

        $linksJson = $allLinks->map(function ($link) {
            return [
                'id' => $link->id,
                'title' => $link->title ?? '',
                'slug' => $link->slug ?? '',
                'original_url' => $link->original_url,
                'short_url' => $link->getShortUrl(),
                'clicks_count' => $link->clicks_count ?? 0,
                'created_at_human' => $link->created_at->diffForHumans(),
                'expires_at' => $link->expires_at ? true : false,
                'expires_at_formatted' => $link->expires_at?->format('d/m/Y'),
                'is_expired' => $link->isExpired(),
                'has_password' => ! empty($link->password),
                'can_extend' => $link->expires_at && \Illuminate\Support\Facades\Route::has('shorturl.user.extend'),
                'tags' => $link->tags ?? [],
                'edit_url' => route('shorturl.user.edit', $link),
                'delete_url' => route('shorturl.user.destroy', $link),
                'extend_url' => \Illuminate\Support\Facades\Route::has('shorturl.user.extend') ? route('shorturl.user.extend', $link) : '',
                'stats_url' => route('shorturl.stats', $link->slug),
                'qr_url' => route('shorturl.qr', $link->slug),
            ];
        })->values();

        return view('shorturl::user.index', compact('shortUrls', 'allLinks', 'linksJson'));
    }

    public function tagsSuggest(Request $request): JsonResponse
    {
        $search = strip_tags(trim((string) $request->query('q', '')));

        $tags = ShortUrl::where('user_id', auth()->id())
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->filter(fn ($tag) => empty($search) || mb_stripos($tag, $search) !== false)
            ->sort()
            ->take(10)
            ->values()
            ->all();

        return response()->json($tags);
    }

    public function create(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('shorturl.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'original_url' => ['required', 'url', 'max:2048'],
            'slug' => ['nullable', 'alpha_dash', 'max:50', 'unique:short_urls,slug', 'not_in:'.implode(',', \Modules\ShortUrl\Models\ShortUrl::RESERVED_SLUGS)],
            'title' => ['nullable', 'max:255'],
            'description' => ['nullable', 'max:1000'],
            'password' => ['nullable', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_clicks' => ['nullable', 'integer', 'min:1'],
            'og_title' => ['nullable', 'max:255'],
            'og_description' => ['nullable', 'max:500'],
            'og_image' => ['nullable', 'url', 'max:500'],
            'thumbnail' => ['nullable', 'url', 'max:500'],
            'utm_source' => ['nullable', 'max:255'],
            'utm_medium' => ['nullable', 'max:255'],
            'utm_campaign' => ['nullable', 'max:255'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30', 'regex:/^[a-zA-Z0-9àâäéèêëïîôùûüçÀÂÄÉÈÊËÏÎÔÙÛÜÇ \-_]+$/u'],
        ]);

        if (! empty($validated['tags'])) {
            $validated['tags'] = array_values(array_filter(array_map(fn ($t) => strip_tags(trim($t)), $validated['tags'])));
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $validated['domain_id'] = $this->service->getDefaultDomain()?->id;
        $validated['redirect_type'] = 301;

        if (empty($validated['thumbnail'])) {
            $meta = $this->service->scrapeMetadata($validated['original_url']);
            $validated['thumbnail'] = $meta['thumbnail'] ?? null;
        }

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
            'slug' => ['nullable', 'alpha_dash', 'max:50', Rule::unique('short_urls', 'slug')->ignore($shortUrl->id), 'not_in:'.implode(',', \Modules\ShortUrl\Models\ShortUrl::RESERVED_SLUGS)],
            'title' => ['nullable', 'max:255'],
            'description' => ['nullable', 'max:1000'],
            'password' => ['nullable', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_clicks' => ['nullable', 'integer', 'min:1'],
            'og_title' => ['nullable', 'max:255'],
            'og_description' => ['nullable', 'max:500'],
            'og_image' => ['nullable', 'url', 'max:500'],
            'thumbnail' => ['nullable', 'url', 'max:500'],
            'utm_source' => ['nullable', 'max:255'],
            'utm_medium' => ['nullable', 'max:255'],
            'utm_campaign' => ['nullable', 'max:255'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30', 'regex:/^[a-zA-Z0-9àâäéèêëïîôùûüçÀÂÄÉÈÊËÏÎÔÙÛÜÇ \-_]+$/u'],
        ]);

        if (! empty($validated['tags'])) {
            $validated['tags'] = array_values(array_filter(array_map(fn ($t) => strip_tags(trim($t)), $validated['tags'])));
        } else {
            $validated['tags'] = [];
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $shortUrl->update($validated);

        return back()->with('success', __('Lien raccourci mis à jour avec succès.'));
    }

    public function scrapeMeta(Request $request): JsonResponse
    {
        $request->validate(['url' => ['required', 'url', 'max:2048']]);

        $meta = $this->service->scrapeMetadata($request->input('url'));

        return response()->json($meta);
    }

    public function destroy(ShortUrl $shortUrl): RedirectResponse
    {
        abort_if($shortUrl->user_id !== auth()->id(), 403);

        $shortUrl->delete();

        return redirect()->route('shorturl.user.index')->with('success', __('Lien raccourci supprimé.'));
    }

    public function extend(ShortUrl $shortUrl): RedirectResponse
    {
        abort_if($shortUrl->user_id !== auth()->id(), 403);

        $limit = now()->addMonthsNoOverflow(12);

        if ($shortUrl->expires_at && $shortUrl->expires_at->gt($limit)) {
            return back()->with('warning', __('Ce lien est déjà prolongé au maximum (12 mois).'));
        }

        $shortUrl->forceFill([
            'expires_at' => $limit,
            'expiry_notified_at' => null,
        ])->save();

        return back()->with('success', __('Lien prolongé de 12 mois.'));
    }
}
