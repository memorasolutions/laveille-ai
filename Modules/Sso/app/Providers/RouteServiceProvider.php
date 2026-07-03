<?php

namespace Modules\Sso\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Sso';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the SCIM routes for the application.
     *
     * PAS de préfixe "api/" : SCIM impose un chemin standard /scim/v2/*
     * (voir routes/api.php) — les IdP clients (Okta/Azure AD/Google
     * Workspace) attendent ce chemin exact, non personnalisable côté client.
     * Groupe middleware "api" conservé (stateless, pas de CSRF) sans le
     * préfixe habituel des autres modules.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->group(module_path($this->name, '/routes/api.php'));
    }
}
