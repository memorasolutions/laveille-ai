<?php

declare(strict_types=1);

namespace Modules\Backup\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    public function getBackups(): array
    {
        $disk = Storage::disk(config('backup.backup.destination.disks.0', 'local'));
        $prefix = config('backup.backup.name', 'laravel-backup');
        $files = $disk->files($prefix);

        return collect($files)
            ->filter(fn (string $file) => str_ends_with($file, '.zip'))
            ->map(fn (string $file) => [
                'path' => $file,
                'name' => basename($file),
                'size' => $disk->size($file),
                'date' => $disk->lastModified($file),
            ])
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function runBackup(bool $dbOnly = false): int
    {
        $command = $dbOnly ? 'backup:run --only-db' : 'backup:run';

        return Artisan::call($command);
    }

    public function deleteBackup(string $path): bool
    {
        $disk = Storage::disk(config('backup.backup.destination.disks.0', 'local'));

        return $disk->delete($path);
    }

    public function cleanOldBackups(): int
    {
        return Artisan::call('backup:clean');
    }
}
