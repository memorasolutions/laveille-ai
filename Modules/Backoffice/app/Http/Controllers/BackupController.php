<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Backup\Services\BackupService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backupService) {}

    public function index(): View
    {
        $backups = $this->backupService->getBackups();

        return view('backoffice::backups.index', compact('backups'));
    }

    public function run(): RedirectResponse
    {
        Artisan::queue('backup:run');

        return back()->with('success', 'Sauvegarde lancée en arrière-plan.');
    }

    public function download(Request $request): StreamedResponse
    {
        $path = $request->input('path');
        $disk = Storage::disk(config('backup.backup.destination.disks.0', 'local'));

        abort_if(! $disk->exists($path), 404);

        return response()->streamDownload(
            fn () => fpassthru($disk->readStream($path)),
            basename($path),
            ['Content-Type' => 'application/zip']
        );
    }

    public function delete(Request $request): RedirectResponse
    {
        $path = $request->input('path');

        if ($this->backupService->deleteBackup($path)) {
            return back()->with('success', 'Sauvegarde supprimée.');
        }

        return back()->with('error', 'Erreur lors de la suppression.');
    }
}
