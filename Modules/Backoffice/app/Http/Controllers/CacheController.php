<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class CacheController
{
    public function index(): View
    {
        return view('backoffice::cache.index', [
            'title' => 'Gestion du cache',
            'subtitle' => 'Performance',
        ]);
    }

    public function clearCache(): RedirectResponse
    {
        Artisan::call('cache:clear');

        return back()->with('success', 'Cache applicatif vidé avec succès.');
    }

    public function clearConfig(): RedirectResponse
    {
        Artisan::call('config:clear');

        return back()->with('success', 'Cache de configuration vidé avec succès.');
    }

    public function clearViews(): RedirectResponse
    {
        Artisan::call('view:clear');

        return back()->with('success', 'Cache des vues vidé avec succès.');
    }

    public function clearRoutes(): RedirectResponse
    {
        Artisan::call('route:clear');

        return back()->with('success', 'Cache des routes vidé avec succès.');
    }

    public function clearAll(): RedirectResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return back()->with('success', 'Tous les caches ont été vidés avec succès.');
    }
}
