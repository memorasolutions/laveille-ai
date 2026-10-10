<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;
use Modules\Idp\Http\Controllers\UserInfoController;

// Chargé UNIQUEMENT si IDP_ENABLED (voir IdpServiceProvider).
Route::get('/oauth/userinfo', UserInfoController::class)
    ->middleware(['auth:idp', CheckToken::using('openid')])
    ->name('idp.userinfo');
