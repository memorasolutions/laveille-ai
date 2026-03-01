<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleFontService
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Download a Google Font and store it locally.
     *
     * @param  array<int>  $weights
     */
    public function download(string $fontFamily, array $weights = [300, 400, 500, 600, 700]): string
    {
        try {
            $slug = Str::slug($fontFamily);
            $fontDir = public_path("fonts/{$slug}");

            File::ensureDirectoryExists($fontDir);

            $cssUrl = $this->buildGoogleFontsUrl($fontFamily, $weights);
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])->get($cssUrl);

            if (! $response->successful()) {
                Log::warning("GoogleFontService: failed to fetch CSS for {$fontFamily}", [
                    'status' => $response->status(),
                ]);

                return '';
            }

            $cssContent = $response->body();
            $woff2Urls = $this->extractWoff2Urls($cssContent);

            foreach ($woff2Urls as $woff2Url) {
                $filename = basename((string) parse_url($woff2Url, PHP_URL_PATH));
                $localPath = "{$fontDir}/{$filename}";

                $fileResponse = Http::get($woff2Url);
                if ($fileResponse->successful()) {
                    File::put($localPath, $fileResponse->body());
                    $cssContent = str_replace($woff2Url, "/fonts/{$slug}/{$filename}", $cssContent);
                }
            }

            $localCssPath = "{$fontDir}/font.css";
            File::put($localCssPath, $cssContent);

            return "/fonts/{$slug}/font.css";
        } catch (\Exception $e) {
            Log::error("GoogleFontService: download failed for {$fontFamily}", [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    public function isDownloaded(string $fontFamily): bool
    {
        $slug = Str::slug($fontFamily);

        return File::exists(public_path("fonts/{$slug}/font.css"));
    }

    public function getLocalCssPath(string $fontFamily): string
    {
        $slug = Str::slug($fontFamily);

        return "/fonts/{$slug}/font.css";
    }

    /** @param  array<int>  $weights */
    private function buildGoogleFontsUrl(string $fontFamily, array $weights): string
    {
        $encodedName = str_replace(' ', '+', $fontFamily);
        $weightsString = implode(';', $weights);

        return "https://fonts.googleapis.com/css2?family={$encodedName}:wght@{$weightsString}&display=swap";
    }

    /** @return list<string> */
    private function extractWoff2Urls(string $cssContent): array
    {
        preg_match_all('/url\(([^)]+\.woff2)[^)]*\)/i', $cssContent, $matches);

        return array_map(fn (string $url) => trim($url, '\'"'), $matches[1] ?? []);
    }
}
