<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Storage\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Storage\Services\StorageService;

class StorageAdminController extends Controller
{
    public function __construct(
        protected StorageService $storageService,
    ) {}

    public function index(): View
    {
        $disks = [];
        $grandTotal = 0;

        foreach (array_keys(config('filesystems.disks', [])) as $diskName) {
            try {
                $usage = $this->storageService->diskUsage($diskName);
                $disks[$diskName] = $usage;
                $grandTotal += $usage['total_size'];
            } catch (\Throwable) {
                $disks[$diskName] = [
                    'files_count' => 0,
                    'total_size' => 0,
                    'total_size_human' => '0 o',
                    'error' => true,
                ];
            }
        }

        return view('storage::admin.index', [
            'disks' => $disks,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function show(string $disk): View
    {
        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            abort(404);
        }

        return view('storage::admin.show', [
            'disk' => $disk,
            'files' => $this->storageService->files('', $disk),
            'directories' => $this->storageService->directories('', $disk),
            'usage' => $this->storageService->diskUsage($disk),
        ]);
    }
}
