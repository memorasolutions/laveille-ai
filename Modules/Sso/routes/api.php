<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Endpoints SCIM 2.0 (RFC 7643 / RFC 7644), montés à la racine /scim/v2/*
 * (chemin standard attendu par les IdP — Okta/Azure AD/Google Workspace ne
 * permettent généralement pas de personnaliser un préfixe /api). Voir
 * Modules\Sso\Providers\RouteServiceProvider::mapScimRoutes() qui charge ce
 * fichier SANS le préfixe "api/" habituel des autres modules.
 *
 * Authentification : Bearer token statique par organisation, voir
 * Http\Middleware\AuthenticateScimToken (PAS de session, PAS de CSRF —
 * middleware group "api" standard Laravel, stateless).
 *
 * Rate limiting : throttle:60,1 minimum (spec) sur toutes les routes.
 */

use Illuminate\Support\Facades\Route;
use Modules\Sso\Http\Controllers\Scim\ScimGroupController;
use Modules\Sso\Http\Controllers\Scim\ScimUserController;
use Modules\Sso\Http\Controllers\Scim\SchemasController;
use Modules\Sso\Http\Controllers\Scim\ServiceProviderConfigController;
use Modules\Sso\Http\Middleware\AuthenticateScimToken;

Route::prefix('scim/v2')->name('scim.')->middleware(['throttle:60,1', AuthenticateScimToken::class])->group(function () {
    Route::get('ServiceProviderConfig', ServiceProviderConfigController::class)->name('service_provider_config');
    Route::get('Schemas', SchemasController::class)->name('schemas');

    Route::get('Users', [ScimUserController::class, 'index'])->name('users.index');
    Route::post('Users', [ScimUserController::class, 'store'])->name('users.store');
    Route::get('Users/{id}', [ScimUserController::class, 'show'])->name('users.show');
    Route::put('Users/{id}', [ScimUserController::class, 'update'])->name('users.update');
    Route::patch('Users/{id}', [ScimUserController::class, 'patch'])->name('users.patch');
    Route::delete('Users/{id}', [ScimUserController::class, 'destroy'])->name('users.destroy');

    // Groups SCIM — hors scope V1, répond 501 (voir ScimGroupController).
    Route::get('Groups', [ScimGroupController::class, 'index'])->name('groups.index');
    Route::post('Groups', [ScimGroupController::class, 'store'])->name('groups.store');
    Route::get('Groups/{id}', [ScimGroupController::class, 'show'])->name('groups.show');
});
