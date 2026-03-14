<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ShortUrl\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\ShortUrl\Models\ShortUrl;
use Modules\ShortUrl\Models\ShortUrlClick;
use Modules\ShortUrl\Models\ShortUrlDomain;

class ShortUrlService
{
    public function createShortUrl(array $data, int $userId): ShortUrl
    {
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug();
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['user_id'] = $userId;

        return ShortUrl::create($data);
    }

    public function generateSlug(int $length = 6): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($characters) - 1;

        do {
            $slug = '';
            for ($i = 0; $i < $length; $i++) {
                $slug .= $characters[random_int(0, $maxIndex)];
            }
        } while (ShortUrl::where('slug', $slug)->exists());

        return $slug;
    }

    public function resolve(string $slug): ?ShortUrl
    {
        return Cache::remember(
            "short_url:{$slug}",
            3600,
            fn () => ShortUrl::where('slug', $slug)->with('domain')->first()
        );
    }

    public function trackClick(ShortUrl $shortUrl, Request $request): void
    {
        $userAgent = $request->userAgent() ?? '';

        $deviceType = 'desktop';
        if (preg_match('/Mobile|Tablet|iPad|iPhone|Android/i', $userAgent)) {
            $deviceType = preg_match('/Tablet|iPad/i', $userAgent) ? 'tablet' : 'mobile';
        }

        $browser = 'Unknown';
        if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera)/i', $userAgent, $matches)) {
            $browser = $matches[1];
        }

        $os = 'Unknown';
        if (preg_match('/(Windows|Mac|Linux|Android|iOS)/i', $userAgent, $matches)) {
            $os = $matches[1];
        }

        ShortUrlClick::create([
            'short_url_id' => $shortUrl->id,
            'ip_address' => $request->ip(),
            'referrer' => $request->header('referer'),
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
            'clicked_at' => now(),
        ]);

        $shortUrl->incrementClicks();

        Cache::forget("short_url:{$shortUrl->slug}");
    }

    public function getAnalytics(ShortUrl $shortUrl, int $days = 30): array
    {
        $baseQuery = ShortUrlClick::where('short_url_id', $shortUrl->id);
        $dateThreshold = now()->subDays($days);

        return [
            'total_clicks' => $shortUrl->clicks_count,
            'clicks_by_day' => (clone $baseQuery)
                ->where('clicked_at', '>=', $dateThreshold)
                ->selectRaw('DATE(clicked_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray(),
            'top_referrers' => (clone $baseQuery)
                ->whereNotNull('referrer')
                ->selectRaw('referrer, COUNT(*) as count')
                ->groupBy('referrer')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray(),
            'devices' => (clone $baseQuery)
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->get()
                ->toArray(),
            'browsers' => (clone $baseQuery)
                ->selectRaw('browser, COUNT(*) as count')
                ->groupBy('browser')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray(),
            'countries' => (clone $baseQuery)
                ->selectRaw('country_code, COUNT(*) as count')
                ->groupBy('country_code')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    public function getDefaultDomain(): ?ShortUrlDomain
    {
        return ShortUrlDomain::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }
}
