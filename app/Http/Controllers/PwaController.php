<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PwaController extends Controller
{
    /**
     * Génère le manifest PWA dynamique.
     * Support multi-tenant si le module Tenancy est actif.
     */
    public function manifest(): JsonResponse
    {
        $config = config('pwa');

        $manifest = [
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'description' => $config['description'],
            'theme_color' => $config['theme_color'],
            'background_color' => $config['background_color'],
            'display' => $config['display'],
            'orientation' => $config['orientation'],
            'scope' => $config['scope'],
            'start_url' => $config['start_url'],
            'lang' => $config['lang'],
            'categories' => $config['categories'],
            'icons' => $config['icons'],
        ];

        // Multi-tenant : surcharger avec les données du tenant actif
        if (class_exists(\Modules\Tenancy\Models\Tenant::class)) {
            try {
                $tenant = app('currentTenant');
                if ($tenant) {
                    $manifest['name'] = $tenant->name ?? $manifest['name'];
                    $manifest['short_name'] = $tenant->short_name ?? $manifest['short_name'];
                    $manifest['theme_color'] = $tenant->theme_color ?? $manifest['theme_color'];
                }
            } catch (\Throwable) {
                // Pas de tenant résolu, on garde les valeurs par défaut
            }
        }

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }

    /**
     * Affiche la page hors ligne.
     */
    public function offline(): View
    {
        return view('pwa.offline');
    }
}
