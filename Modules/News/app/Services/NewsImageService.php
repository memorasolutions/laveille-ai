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

    /**
     * Génère une image OG 1200x630 avec gradient, logo et titre.
     * Utilisée quand aucune image n'est disponible pour un article.
     */
    public static function generateFallbackImage(int $articleId, string $title, ?string $categoryTag = null): ?string
    {
        $outputDir = public_path('storage/news/images');
        if (! is_dir($outputDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($outputDir, 0755, true);
        }

        $outputPath = "{$outputDir}/{$articleId}.webp";
        $relativePath = "/storage/news/images/{$articleId}.webp";
        $w = 1200;
        $h = 630;

        $palettes = [
            [[11, 114, 133], [26, 54, 93]],
            [[26, 54, 93], [11, 114, 133]],
            [[142, 68, 173], [44, 62, 80]],
            [[231, 76, 60], [142, 68, 173]],
            [[46, 204, 113], [22, 160, 133]],
            [[52, 152, 219], [41, 128, 185]],
            [[243, 156, 18], [211, 84, 0]],
            [[44, 62, 80], [52, 73, 94]],
        ];

        $idx = abs(crc32($title)) % count($palettes);
        [$c1, $c2] = $palettes[$idx];

        $img = imagecreatetruecolor($w, $h);

        for ($y = 0; $y < $h; $y++) {
            $r = (float) $y / $h;
            $color = imagecolorallocate($img, (int) ($c1[0] + ($c2[0] - $c1[0]) * $r), (int) ($c1[1] + ($c2[1] - $c1[1]) * $r), (int) ($c1[2] + ($c2[2] - $c1[2]) * $r));
            imagefilledrectangle($img, 0, $y, $w, $y, $color);
        }

        // Overlay noir semi-transparent pour contraste texte
        imagealphablending($img, true);
        $blackOverlay = imagecolorallocatealpha($img, 0, 0, 0, 60);
        imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, $blackOverlay);

        $white = imagecolorallocate($img, 255, 255, 255);
        $whiteAlpha = imagecolorallocatealpha($img, 255, 255, 255, 50);

        // Oeil stylise (logo laveille.ai)
        $eyeX = (int) ($w / 2);
        $eyeY = 120;
        $teal = imagecolorallocate($img, 11, 114, 133);
        $whiteEye = imagecolorallocate($img, 255, 255, 255);
        $orange = imagecolorallocate($img, 230, 126, 34);
        imagefilledellipse($img, $eyeX, $eyeY, 120, 60, $whiteEye);     // Forme oeil blanc
        imageellipse($img, $eyeX, $eyeY, 120, 60, $teal);               // Contour teal
        imagefilledellipse($img, $eyeX, $eyeY, 40, 40, $teal);          // Iris teal
        imagefilledellipse($img, $eyeX + 4, $eyeY - 4, 14, 14, $orange); // Pupille orange

        // Titre
        $fontBold = resource_path('fonts/Inter-SemiBold.ttf');
        $fontRegular = resource_path('fonts/Inter-Regular.ttf');

        if (file_exists($fontBold)) {
            $len = mb_strlen($title);
            $fontSize = $len > 50 ? 26 : ($len > 35 ? 30 : ($len > 20 ? 36 : 42));
            $wrapped = wordwrap($title, 42, "\n");
            $lines = array_slice(explode("\n", $wrapped), 0, 3);
            $yOffset = 280;

            foreach ($lines as $line) {
                $bbox = imagettfbbox($fontSize, 0, $fontBold, $line);
                $textW = $bbox[2] - $bbox[0];
                imagettftext($img, $fontSize, 0, (int) (($w - $textW) / 2), $yOffset, $white, $fontBold, $line);
                $yOffset += $fontSize + 12;
            }

            // Catégorie
            if ($categoryTag && file_exists($fontRegular)) {
                $bbox = imagettfbbox(16, 0, $fontRegular, $categoryTag);
                $catW = $bbox[2] - $bbox[0];
                imagettftext($img, 16, 0, (int) (($w - $catW) / 2), 520, $whiteAlpha, $fontRegular, $categoryTag);
            }

            // Sous-titre
            if (file_exists($fontRegular)) {
                $sub = 'laveille.ai';
                $bbox = imagettfbbox(16, 0, $fontRegular, $sub);
                $subW = $bbox[2] - $bbox[0];
                imagettftext($img, 16, 0, (int) (($w - $subW) / 2), 560, $whiteAlpha, $fontRegular, $sub);
            }
        }

        imagewebp($img, $outputPath, 85);
        imagedestroy($img);

        return file_exists($outputPath) ? $relativePath : null;
    }
}
