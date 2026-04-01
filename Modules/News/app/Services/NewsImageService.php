<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class NewsImageService
{
    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('public');
    }

    public function processFromUrl(string $url, int $articleId): ?string
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'])
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (! $response->successful()) {
                Log::warning("NewsImage: download failed {$url} (article {$articleId})");

                return null;
            }

            $content = $response->body();

            if (strlen($content) < 5120) {
                Log::warning("NewsImage: image too small (".strlen($content)." bytes) {$url}");

                return null;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($content);
            $image->cover(1200, 630);

            $webpContent = $image->toWebp(80)->toString();

            $path = "news/images/{$articleId}.webp";
            $this->disk()->put($path, $webpContent);

            return "/storage/{$path}";
        } catch (\Throwable $e) {
            Log::warning("NewsImage: exception for article {$articleId} - ".$e->getMessage());

            return null;
        }
    }

    public function exists(int $articleId): bool
    {
        return $this->disk()->exists("news/images/{$articleId}.webp");
    }

    public function getPublicPath(int $articleId): string
    {
        return "/storage/news/images/{$articleId}.webp";
    }
}
