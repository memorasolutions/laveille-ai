<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Modules\Authors\Models\ImageVariant;

final class ImagePipelineService
{
    private ImageManager $manager;

    private string $disk = 'public';

    public function __construct()
    {
        try {
            $this->manager = new ImageManager(new ImagickDriver());
        } catch (Exception) {
            $this->manager = new ImageManager(new GdDriver());
        }
    }

    public function process(string $sourcePath, int $authorProfileId, ?string $altText = null): array
    {
        try {
            if (! file_exists($sourcePath)) {
                throw new Exception("Source file not found");
            }

            $fileSize = filesize($sourcePath);
            if ($fileSize === false || $fileSize > 10 * 1024 * 1024) {
                throw new Exception("File too large (max 10MB)");
            }

            $mimeType = mime_content_type($sourcePath);
            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/heic'], true)) {
                throw new Exception("Unsupported image format: {$mimeType}");
            }

            $hash = Str::random(16);
            $basePath = "authors/{$authorProfileId}/{$hash}";
            $image = $this->manager->read($sourcePath);

            $variants = $this->processResponsiveVariants($image, $basePath, $authorProfileId, $altText);
            $ogPath = $this->processOpenGraphImage($image, $basePath, $authorProfileId, $altText);
            $twitterPath = $this->processTwitterCardImage($image, $basePath, $authorProfileId, $altText);

            return [
                'variants' => $variants,
                'og_image' => Storage::disk($this->disk)->url($ogPath),
                'twitter_card' => Storage::disk($this->disk)->url($twitterPath),
                'hash' => $hash,
            ];
        } catch (Exception $e) {
            Log::warning('Image processing failed', [
                'source' => $sourcePath,
                'author_id' => $authorProfileId,
                'error' => $e->getMessage(),
            ]);

            return [
                'variants' => [],
                'og_image' => '',
                'twitter_card' => '',
                'hash' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function processResponsiveVariants(ImageInterface $image, string $basePath, int $authorProfileId, ?string $altText): array
    {
        $widths = [320, 640, 1024, 1920, 2400];
        $formats = ['avif' => 50, 'webp' => 85, 'jpg' => 90];
        $variants = [];

        foreach ($widths as $width) {
            $resized = clone $image;
            $resized->scale(width: $width);

            foreach ($formats as $format => $quality) {
                $filename = "{$width}w.{$format}";
                $path = "{$basePath}/{$filename}";

                $encoded = match ($format) {
                    'avif' => $resized->toAvif(quality: $quality),
                    'webp' => $resized->toWebp(quality: $quality),
                    default => $resized->toJpeg(quality: $quality),
                };

                Storage::disk($this->disk)->put($path, (string) $encoded);

                $variant = ImageVariant::create([
                    'author_profile_id' => $authorProfileId,
                    'original_path' => $basePath,
                    'variant_path' => $path,
                    'format' => $format,
                    'size_width' => $width,
                    'size_height' => $resized->height(),
                    'file_size_bytes' => strlen((string) $encoded),
                    'alt_text' => $altText,
                ]);

                $variants[] = [
                    'id' => $variant->id,
                    'url' => Storage::disk($this->disk)->url($path),
                    'width' => $width,
                    'height' => $resized->height(),
                    'format' => $format,
                ];
            }
        }

        return $variants;
    }

    private function processOpenGraphImage(ImageInterface $image, string $basePath, int $authorProfileId, ?string $altText): string
    {
        $ogImage = clone $image;
        $ogImage->cover(1200, 630);
        $path = "{$basePath}/og.jpg";
        $encoded = (string) $ogImage->toJpeg(quality: 85);
        Storage::disk($this->disk)->put($path, $encoded);

        ImageVariant::create([
            'author_profile_id' => $authorProfileId,
            'original_path' => $basePath,
            'variant_path' => $path,
            'format' => 'jpg',
            'size_width' => 1200,
            'size_height' => 630,
            'file_size_bytes' => strlen($encoded),
            'alt_text' => $altText,
            'is_open_graph' => true,
        ]);

        return $path;
    }

    private function processTwitterCardImage(ImageInterface $image, string $basePath, int $authorProfileId, ?string $altText): string
    {
        $twImage = clone $image;
        $twImage->cover(1200, 600);
        $path = "{$basePath}/twitter.jpg";
        $encoded = (string) $twImage->toJpeg(quality: 85);
        Storage::disk($this->disk)->put($path, $encoded);

        ImageVariant::create([
            'author_profile_id' => $authorProfileId,
            'original_path' => $basePath,
            'variant_path' => $path,
            'format' => 'jpg',
            'size_width' => 1200,
            'size_height' => 600,
            'file_size_bytes' => strlen($encoded),
            'alt_text' => $altText,
            'is_twitter_card' => true,
        ]);

        return $path;
    }

    public function suggestAltText(string $imagePath): ?string
    {
        try {
            if (! file_exists($imagePath)) {
                return null;
            }

            $mimeType = mime_content_type($imagePath);
            if (! str_starts_with($mimeType, 'image/')) {
                return null;
            }

            $base64Image = base64_encode((string) file_get_contents($imagePath));

            $response = Http::timeout(30)
                ->withToken(config('services.openrouter.api_key'))
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'google/gemini-2.0-flash-exp:free',
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Décris cette image en français pour accessibilité (alt text concis, 1 phrase).'],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64Image}"]],
                        ],
                    ]],
                ]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (Exception $e) {
            Log::warning('Alt text suggestion failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function cleanup(int $authorProfileId, string $oldHash): void
    {
        try {
            $oldBasePath = "authors/{$authorProfileId}/{$oldHash}";
            $files = Storage::disk($this->disk)->files($oldBasePath);
            foreach ($files as $file) {
                Storage::disk($this->disk)->delete($file);
            }
            Storage::disk($this->disk)->deleteDirectory($oldBasePath);

            ImageVariant::where('author_profile_id', $authorProfileId)
                ->where('original_path', $oldBasePath)
                ->delete();
        } catch (Exception $e) {
            Log::warning('Image cleanup failed', ['error' => $e->getMessage()]);
        }
    }
}
