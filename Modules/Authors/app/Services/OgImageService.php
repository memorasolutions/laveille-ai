<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;
use Throwable;

final class OgImageService
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    public function isEnabled(): bool
    {
        return function_exists('imagecreatetruecolor');
    }

    public function hash(AuthorPost $post, AuthorProfile $author): string
    {
        $stamp = $post->updated_at?->timestamp ?? 0;

        return md5($post->id.'-'.$author->id.'-'.$stamp);
    }

    public function relativePath(AuthorPost $post, AuthorProfile $author): string
    {
        return 'og-images/'.$this->hash($post, $author).'.png';
    }

    public function url(AuthorPost $post, AuthorProfile $author): string
    {
        return asset('storage/'.$this->relativePath($post, $author));
    }

    public function generate(AuthorPost $post, AuthorProfile $author): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $relative = $this->relativePath($post, $author);

        if (Storage::disk('public')->exists($relative)) {
            return Storage::disk('public')->path($relative);
        }

        try {
            $authorName = $author->display_name ?? $author->user?->name ?? $author->slug;
            $image = $this->render((string) $post->title, (string) $authorName, (string) $author->slug);

            Storage::disk('public')->makeDirectory('og-images');
            $absolute = Storage::disk('public')->path($relative);

            imagepng($image, $absolute, 8);
            imagedestroy($image);

            return $absolute;
        } catch (Throwable $e) {
            Log::channel('daily')->warning('authors.og_image.failed', [
                'post_id' => $post->id,
                'author_id' => $author->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function render(string $title, string $authorName, string $authorSlug): GdImage
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $this->drawGradient($image);
        $this->drawDots($image);
        $this->drawTitle($image, $title);
        $this->drawAuthorName($image, $authorName);
        $this->drawBranding($image);
        $this->drawInitialsAvatar($image, $authorName, $authorSlug);

        return $image;
    }

    private function drawGradient(GdImage $image): void
    {
        $teal = [6, 78, 90];
        $cream = [248, 250, 251];

        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $r = (int) ($teal[0] * (1 - $ratio) + $cream[0] * $ratio);
            $g = (int) ($teal[1] * (1 - $ratio) + $cream[1] * $ratio);
            $b = (int) ($teal[2] * (1 - $ratio) + $cream[2] * $ratio);
            $color = imagecolorallocate($image, $r, $g, $b);
            imagefilledrectangle($image, 0, $y, self::WIDTH, $y, $color);
        }
    }

    private function drawDots(GdImage $image): void
    {
        $dot = imagecolorallocatealpha($image, 154, 42, 6, 110);
        for ($x = 0; $x < self::WIDTH; $x += 40) {
            for ($y = 0; $y < self::HEIGHT; $y += 40) {
                imagesetpixel($image, $x, $y, $dot);
            }
        }
    }

    private function drawTitle(GdImage $image, string $title): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $fontPath = $this->resolveFontPath();
        $maxWidth = 820;
        $xStart = 70;
        $yStart = 180;

        if ($fontPath !== null) {
            $lines = $this->wrapTtf($title, $fontPath, 46.0, $maxWidth);
            $lineHeight = 64;
            $y = $yStart;
            foreach (array_slice($lines, 0, 5) as $line) {
                imagettftext($image, 46.0, 0, $xStart, $y, $white, $fontPath, $line);
                $y += $lineHeight;
            }

            return;
        }

        // Fallback GD bitmap font
        $lines = $this->wrapChars($title, 46);
        $y = $yStart;
        foreach (array_slice($lines, 0, 8) as $line) {
            imagestring($image, 5, $xStart, $y, $line, $white);
            $y += 28;
        }
    }

    private function drawAuthorName(GdImage $image, string $authorName): void
    {
        $cream = imagecolorallocate($image, 230, 240, 242);
        $fontPath = $this->resolveFontPath();
        if ($fontPath !== null) {
            imagettftext($image, 26.0, 0, 70, 540, $cream, $fontPath, 'Par '.$authorName);

            return;
        }
        imagestring($image, 5, 70, 530, 'Par '.$authorName, $cream);
    }

    private function drawBranding(GdImage $image): void
    {
        $cream = imagecolorallocate($image, 230, 240, 242);
        $fontPath = $this->resolveFontPath();
        if ($fontPath !== null) {
            imagettftext($image, 22.0, 0, 70, self::HEIGHT - 40, $cream, $fontPath, 'laveille.ai');

            return;
        }
        imagestring($image, 5, 70, self::HEIGHT - 50, 'laveille.ai', $cream);
    }

    private function drawInitialsAvatar(GdImage $image, string $authorName, string $authorSlug): void
    {
        $size = 110;
        $cx = self::WIDTH - 100;
        $cy = 110;

        $hue = crc32($authorSlug) % 360;
        [$r, $g, $b] = $this->hslToRgb($hue / 360, 0.6, 0.40);
        $bg = imagecolorallocate($image, $r, $g, $b);
        imagefilledellipse($image, $cx, $cy, $size, $size, $bg);

        $words = preg_split('/\s+/', trim($authorName)) ?: [];
        $initials = mb_strtoupper(mb_substr($words[0] ?? 'A', 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));

        $white = imagecolorallocate($image, 255, 255, 255);
        $fontPath = $this->resolveFontPath();
        if ($fontPath !== null) {
            $bbox = imagettfbbox(40.0, 0, $fontPath, $initials);
            $tw = $bbox[2] - $bbox[0];
            $th = $bbox[1] - $bbox[7];
            imagettftext($image, 40.0, 0, (int) ($cx - $tw / 2), (int) ($cy + $th / 2), $white, $fontPath, $initials);

            return;
        }
        $tw = mb_strlen($initials) * imagefontwidth(5);
        imagestring($image, 5, (int) ($cx - $tw / 2), (int) ($cy - imagefontheight(5) / 2), $initials, $white);
    }

    /** @return array{0:int,1:int,2:int} */
    private function hslToRgb(float $h, float $s, float $l): array
    {
        if ($s === 0.0) {
            $v = (int) round($l * 255);

            return [$v, $v, $v];
        }
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $r = $this->hue2rgb($p, $q, $h + 1 / 3);
        $g = $this->hue2rgb($p, $q, $h);
        $b = $this->hue2rgb($p, $q, $h - 1 / 3);

        return [(int) round($r * 255), (int) round($g * 255), (int) round($b * 255)];
    }

    private function hue2rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    /** @return list<string> */
    private function wrapTtf(string $text, string $fontPath, float $fontSize, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $test = $current === '' ? $word : $current.' '.$word;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $test);
            if (($bbox[2] - $bbox[0]) <= $maxWidth) {
                $current = $test;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /** @return list<string> */
    private function wrapChars(string $text, int $maxChars): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $test = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($test) <= $maxChars) {
                $current = $test;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function resolveFontPath(): ?string
    {
        $candidates = [
            storage_path('fonts/PlusJakartaSans-Bold.ttf'),
            public_path('fonts/PlusJakartaSans-Bold.ttf'),
            public_path('fonts/Plus_Jakarta_Sans.ttf'),
        ];
        foreach ($candidates as $path) {
            if (is_string($path) && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
