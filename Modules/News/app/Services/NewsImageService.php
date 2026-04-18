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
            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

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

    /**
     * Génère une image OG 1200x630 avec gradient, vrai logo SVG et titre.
     * Utilise Imagick pour le rendu SVG natif.
     */
    public static function generateFallbackImage(int $articleId, string $title, ?string $categoryTag = null): ?string
    {
        $outputPath = public_path("storage/news/images/{$articleId}.webp");
        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($outputDir, 0755, true);
        }

        try {
            $w = 1200;
            $h = 630;

            // Gradient vertical teal → navy
            $gradient = new \Imagick();
            $gradient->newPseudoImage($w, $h, 'gradient:#0B7285-#1a2332');

            // Overlay noir 40%
            $overlay = new \Imagick();
            $overlay->newImage($w, $h, new \ImagickPixel('rgba(0,0,0,0.4)'));
            $gradient->compositeImage($overlay, \Imagick::COMPOSITE_OVER, 0, 0);
            $overlay->destroy();

            // Logo SVG (200x200, fond transparent, centré en haut)
            $logoPath = public_path('images/logo-eye-white.svg');
            if (file_exists($logoPath)) {
                $logo = new \Imagick();
                $logo->setBackgroundColor('transparent');
                $logo->readImage($logoPath);
                $logo->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                $logo->resizeImage(200, 200, \Imagick::FILTER_LANCZOS, 1);
                $gradient->compositeImage($logo, \Imagick::COMPOSITE_OVER, (int) (($w - 200) / 2), 50);
                $logo->destroy();
            }

            // Titre (très gros, blanc, centré)
            $fontBold = resource_path('fonts/Inter-SemiBold.ttf');
            if (file_exists($fontBold)) {
                $len = mb_strlen($title);
                $fontSize = $len < 25 ? 64 : ($len <= 40 ? 54 : 44);

                $wrapped = wordwrap($title, 28, "\n");
                $lines = explode("\n", $wrapped);
                if (count($lines) > 3) {
                    $lines = array_slice($lines, 0, 3);
                    $lines[2] = mb_substr($lines[2], 0, 25) . '...';
                }

                $draw = new \ImagickDraw();
                $draw->setFont($fontBold);
                $draw->setFontSize($fontSize);
                $draw->setFillColor(new \ImagickPixel('white'));
                $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
                $gradient->annotateImage($draw, $w / 2, 340, 0, implode("\n", $lines));
            }

            // Catégorie
            $fontRegular = resource_path('fonts/Inter-Regular.ttf');
            if ($categoryTag && file_exists($fontRegular)) {
                $drawCat = new \ImagickDraw();
                $drawCat->setFont($fontRegular);
                $drawCat->setFontSize(28);
                $drawCat->setFillColor(new \ImagickPixel('rgba(255,255,255,0.6)'));
                $drawCat->setTextAlignment(\Imagick::ALIGN_CENTER);
                $gradient->annotateImage($drawCat, $w / 2, 520, 0, $categoryTag);
            }

            // Sous-titre laveille.ai
            if (file_exists($fontRegular)) {
                $drawSub = new \ImagickDraw();
                $drawSub->setFont($fontRegular);
                $drawSub->setFontSize(24);
                $drawSub->setFillColor(new \ImagickPixel('rgba(255,255,255,0.5)'));
                $drawSub->setTextAlignment(\Imagick::ALIGN_CENTER);
                $gradient->annotateImage($drawSub, $w / 2, 580, 0, 'laveille.ai');
            }

            $gradient->setImageFormat('webp');
            $gradient->setCompressionQuality(85);
            $gradient->writeImage($outputPath);
            $gradient->destroy();

            return "/storage/news/images/{$articleId}.webp";
        } catch (\Throwable $e) {
            Log::warning("NewsImage fallback failed for article {$articleId}: " . $e->getMessage());

            return null;
        }
    }
}
