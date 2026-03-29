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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\ShortUrl\Services\ShortUrlService;

class PublicShortUrlController
{
    public function __construct(
        private readonly ShortUrlService $service
    ) {}

    public function create(): View
    {
        $domains = \Modules\ShortUrl\Models\ShortUrlDomain::where('is_active', true)->get();

        return view('shorturl::public.create', compact('domains'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'url' => 'required|url|max:2048',
            'slug' => ['nullable', 'string', 'alpha_dash', 'max:50', 'unique:short_urls,slug', 'not_in:'.implode(',', \Modules\ShortUrl\Models\ShortUrl::RESERVED_SLUGS)],
            'title' => 'nullable|string|max:255',
        ];

        if (Auth::check()) {
            $rules += [
                'domain_id' => 'nullable|exists:short_url_domains,id',
                'description' => 'nullable|string|max:1000',
                'password' => 'nullable|string|max:255',
                'expires_at' => 'nullable|date|after:now',
                'max_clicks' => 'nullable|integer|min:1',
                'utm_source' => 'nullable|string|max:255',
                'utm_medium' => 'nullable|string|max:255',
                'utm_campaign' => 'nullable|string|max:255',
                'og_title' => 'nullable|string|max:255',
                'og_description' => 'nullable|string|max:500',
                'og_image' => 'nullable|url|max:500',
            ];
        }

        $request->validate($rules);

        $url = $request->input('url');

        if (Auth::check()) {
            $data = [
                'original_url' => $url,
                'domain_id' => $request->input('domain_id') ?: $this->service->getDefaultDomain()?->id,
                'slug' => $request->input('slug') ?: null,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'password' => $request->input('password'),
                'expires_at' => $request->input('expires_at'),
                'max_clicks' => $request->input('max_clicks'),
                'utm_source' => $request->input('utm_source'),
                'utm_medium' => $request->input('utm_medium'),
                'utm_campaign' => $request->input('utm_campaign'),
                'og_title' => $request->input('og_title'),
                'og_description' => $request->input('og_description'),
                'og_image' => $request->input('og_image'),
                'redirect_type' => 301,
            ];

            if (! empty($data['password'])) {
                $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
            }

            if (empty($data['thumbnail'])) {
                $meta = $this->service->scrapeMetadata($url);
                $data['thumbnail'] = $meta['thumbnail'] ?? null;
            }

            $shortUrl = $this->service->createShortUrl($data, Auth::id());
        } else {
            $shortUrl = $this->service->createAnonymous($url, $request->ip());
        }

        return response()->json([
            'success' => true,
            'short_url' => $shortUrl->getShortUrl(),
            'slug' => $shortUrl->slug,
            'clicks_count' => $shortUrl->clicks_count,
            'expires_at' => $shortUrl->expires_at?->toIso8601String(),
            'is_anonymous' => $shortUrl->is_anonymous,
        ]);
    }

    public function stats(string $slug): View
    {
        $shortUrl = $this->service->resolve($slug);
        abort_if(! $shortUrl, 404);

        $analytics = $this->service->getAnalytics($shortUrl);

        return view('shorturl::public.stats', compact('shortUrl', 'analytics'));
    }

    public function qrCode(Request $request, string $slug): Response
    {
        $shortUrl = $this->service->resolve($slug);
        abort_if(! $shortUrl, 404);

        $url = $shortUrl->getShortUrl();
        $size = (int) $request->input('size', 300);
        $size = min(max($size, 100), 1000);

        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size($size)
                ->margin(1)
                ->generate($url);

            return response($qr, 200, ['Content-Type' => 'image/png']);
        }

        // Fallback : QR via Google Charts API (redirect)
        return response('', 302, [
            'Location' => 'https://quickchart.io/qr?text='.urlencode($url).'&size='.$size,
        ]);
    }
}
