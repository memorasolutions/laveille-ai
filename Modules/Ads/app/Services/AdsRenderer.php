<?php

declare(strict_types=1);

namespace Modules\Ads\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Ads\Models\AdPlacement;

class AdsRenderer
{
    public function render(string $key): ?string
    {
        return Cache::remember("ad_placement:{$key}", 600, function () use ($key) {
            $ad = AdPlacement::active()->byKey($key)->first();

            return $ad?->ad_code;
        });
    }

    public function renderShortcodes(string $content): string
    {
        return (string) preg_replace_callback('/\[ad key="([^"]+)"\]/', function ($matches) {
            return $this->render($matches[1]) ?? '';
        }, $content);
    }

    public function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("ad_placement:{$key}");

            return;
        }

        AdPlacement::all()->each(fn (AdPlacement $ad) => Cache::forget("ad_placement:{$ad->key}"));
    }
}
