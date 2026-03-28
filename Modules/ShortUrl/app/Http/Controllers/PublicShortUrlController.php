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
        return view('shorturl::public.create');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url|max:2048',
            'slug' => 'nullable|string|alpha_dash|max:50|unique:short_urls,slug',
            'title' => 'nullable|string|max:255',
        ]);

        $url = $request->input('url');

        if (Auth::check()) {
            $data = [
                'original_url' => $url,
                'domain_id' => $this->service->getDefaultDomain()?->id,
                'slug' => $request->input('slug') ?: null,
                'title' => $request->input('title'),
                'redirect_type' => 301,
            ];
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
            'Location' => 'https://quickchart.io/qr?text=' . urlencode($url) . '&size=' . $size,
        ]);
    }
}
