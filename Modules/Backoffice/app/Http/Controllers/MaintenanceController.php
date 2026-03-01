<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class MaintenanceController
{
    public function toggle(): RedirectResponse
    {
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');

            return back()->with('success', 'Mode maintenance désactivé.');
        }

        Artisan::call('down', [
            '--secret' => config('app.maintenance_secret', 'admin-access'),
        ]);

        return back()->with('success', 'Mode maintenance activé. Accès secret : /admin-access');
    }
}
