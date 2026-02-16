<?php

declare(strict_types=1);

namespace Modules\Storage\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    public function upload(UploadedFile $file, string $path = '', string $disk = 'public'): string
    {
        return $file->store($path, $disk);
    }

    public function delete(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    public function exists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    public function url(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }

    public function size(string $path, string $disk = 'public'): int
    {
        return Storage::disk($disk)->size($path);
    }

    public function files(string $directory = '', string $disk = 'public'): array
    {
        return Storage::disk($disk)->files($directory);
    }

    public function directories(string $directory = '', string $disk = 'public'): array
    {
        return Storage::disk($disk)->directories($directory);
    }

    public function move(string $from, string $to, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->move($from, $to);
    }

    public function copy(string $from, string $to, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->copy($from, $to);
    }

    public function diskUsage(string $disk = 'public'): array
    {
        $files = Storage::disk($disk)->allFiles();
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += Storage::disk($disk)->size($file);
        }

        return [
            'files_count' => count($files),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
