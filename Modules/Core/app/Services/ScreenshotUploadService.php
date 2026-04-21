<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver as ImageGdDriver;
use Intervention\Image\ImageManager;

/**
 * Service centralisé upload screenshot.
 * Utilisé par News (uploadArticleImage), Directory moderation (uploadResourceScreenshot),
 * Directory admin (uploadScreenshot outil).
 *
 * @author MEMORA solutions <info@memora.ca>
 */
class ScreenshotUploadService
{
    /**
     * Upload, resize (cover 1200x630), save file + update model column.
     *
     * @param  bool  $prefixSlash  Si true (default), stocke le chemin colonne avec "/" leading. Si false, sans.
     * @param  callable|null  $postUpload  Callback optionnel après saveQuietly, avant clear cache. Signature: fn(Model $model, string $fullPath, string $targetRelativePath): void
     * @return array{ok: bool, message: string, url: ?string, error: ?string}
     */
    public function upload(
        UploadedFile $file,
        string $targetRelativePath,
        Model $model,
        string $targetColumn,
        bool $prefixSlash = true,
        ?callable $postUpload = null
    ): array {
        $fullPath = public_path($targetRelativePath);
        $directory = dirname($fullPath);

        try {
            if (! is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }

            if (file_exists($fullPath)) {
                @copy($fullPath, $fullPath . '.bak');
            }

            $manager = new ImageManager(new ImageGdDriver());
            $imageData = $manager->read($file->getRealPath())
                ->cover(1200, 630)
                ->toJpeg(85)
                ->toString();

            file_put_contents($fullPath, $imageData);

            $storedPath = ltrim($targetRelativePath, '/');
            $model->{$targetColumn} = $prefixSlash ? '/' . $storedPath : $storedPath;
            $model->updated_at = now();
            $model->saveQuietly();

            if ($postUpload !== null) {
                try {
                    $postUpload($model, $fullPath, $targetRelativePath);
                } catch (\Throwable $e) {
                    Log::warning('[ScreenshotUploadService] postUpload callback failed', [
                        'file' => $targetRelativePath,
                        'model' => get_class($model),
                        'model_id' => $model->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
                try {
                    \Spatie\ResponseCache\Facades\ResponseCache::clear();
                } catch (\Throwable $e) {
                    // Ne pas throw depuis cache clear
                }
            }

            $url = asset($targetRelativePath) . '?v=' . $model->updated_at->timestamp;

            return [
                'ok' => true,
                'message' => __('Screenshot uploadé (redimensionné 1200×630).'),
                'url' => $url,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[ScreenshotUploadService] upload failed', [
                'file' => $targetRelativePath,
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'column' => $targetColumn,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => __('Échec upload : :msg', ['msg' => $e->getMessage()]),
                'url' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
