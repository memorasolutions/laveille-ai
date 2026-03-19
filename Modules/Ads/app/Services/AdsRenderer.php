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

    public function injectAfterParagraph(string $content, string $adKey, int $afterParagraph = 3): string
    {
        $ad = $this->render($adKey);
        if (! $ad) {
            return $content;
        }

        $paragraphs = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        $pCount = 0;
        $injected = false;

        for ($i = 0; $i < count($paragraphs); $i++) {
            $result .= $paragraphs[$i];
            if ($paragraphs[$i] === '</p>') {
                $pCount++;
                if ($pCount === $afterParagraph && ! $injected) {
                    $result .= "\n".$ad."\n";
                    $injected = true;
                }
            }
        }

        return $result;
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
