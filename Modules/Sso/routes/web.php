<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Routes SAML 2.0 (SSO entreprise). Aucun middleware `auth` : l'appelant est
 * soit un navigateur redirigé par l'IdP (login/acs), soit un IdP externe
 * lisant les métadonnées publiques (metadata) — la sécurité vient de la
 * validation stricte de l'assertion (signature/audience/destination/temps)
 * et de la garde anti-rejeu, pas d'une session Laravel préexistante.
 *
 * Drapeau maître : sso.enabled (vérifié en tête de CHAQUE contrôleur —
 * abort_if(!config('sso.enabled'), 404) — défense en profondeur même si
 * ce fichier de routes était chargé par erreur).
 */

use Illuminate\Support\Facades\Route;
use Modules\Sso\Http\Controllers\Admin\SsoConfigurationController;
use Modules\Sso\Http\Controllers\Saml\AcsController;
use Modules\Sso\Http\Controllers\Saml\LoginController;
use Modules\Sso\Http\Controllers\Saml\MetadataController;

Route::prefix('sso/saml')->name('sso.saml.')->group(function () {
    Route::get('metadata', MetadataController::class)
        ->middleware('throttle:60,1')
        ->name('metadata');

    Route::get('login', LoginController::class)
        ->middleware('throttle:30,1')
        ->name('login');

    // throttle:60,1 = anti-abus (endpoint public recevant une réponse POST
    // externe de l'IdP, jamais authentifié côté Laravel avant validation).
    Route::post('acs', AcsController::class)
        ->middleware('throttle:60,1')
        ->name('acs');
});

// Administration des configurations SSO par organisation (CRUD + émission de
// jetons SCIM). Gaté par auth + can('sso.manage') DANS le contrôleur
// (pattern identique aux autres modules admin), PAS seulement au niveau
// des routes — défense en profondeur (voir authorizeManage()).
Route::prefix('admin/sso/configurations')->name('admin.sso.configurations.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [SsoConfigurationController::class, 'index'])->name('index');
    Route::post('/', [SsoConfigurationController::class, 'store'])->name('store');
    Route::post('{ssoConfiguration}/scim-tokens', [SsoConfigurationController::class, 'issueScimToken'])->name('scim_tokens.store');
    Route::delete('{ssoConfiguration}/scim-tokens/{tokenId}', [SsoConfigurationController::class, 'revokeScimToken'])->name('scim_tokens.revoke');
});
