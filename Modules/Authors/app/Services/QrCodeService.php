<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class QrCodeService
{
    private const QR_CODES_DIR = 'app/public/qrcodes';

    public function generatePng(string $url, int $size = 512): string
    {
        $hash = sha1($url);
        $path = storage_path(self::QR_CODES_DIR.'/'.$hash.'.png');

        if (File::exists($path)) {
            return $path;
        }

        $this->ensureDirectoryExists(dirname($path));

        $qrCodeData = QrCode::format('png')->size($size)->generate($url);
        File::put($path, $qrCodeData);

        return $path;
    }

    public function generatePdfHighRes(string $url, int $size = 2048): string
    {
        $hash = sha1($url);
        $path = storage_path(self::QR_CODES_DIR.'/'.$hash.'.pdf');

        if (File::exists($path)) {
            return $path;
        }

        $this->ensureDirectoryExists(dirname($path));

        $svgContent = QrCode::format('svg')->size($size)->generate($url);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($svgContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        File::put($path, $dompdf->output());

        return $path;
    }

    public function getPublicUrl(string $path): string
    {
        $relativePath = str_replace(storage_path('app/public/'), '', $path);
        return asset('storage/'.$relativePath);
    }

    public function cleanupOld(int $daysOld = 90): int
    {
        $directory = storage_path(self::QR_CODES_DIR);
        if (! File::exists($directory)) {
            return 0;
        }

        $files = File::allFiles($directory);
        $cutoffDate = Carbon::now()->subDays($daysOld);
        $deletedCount = 0;

        foreach ($files as $file) {
            if (Carbon::createFromTimestamp($file->getMTime())->lt($cutoffDate)) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }
    }
}
