<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Privacy\Http\Controllers\LegalController;

// Legal pages (public, no auth required) — layout frontend USNews
$legalMiddleware = class_exists(\Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class)
    ? ['web', \Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class]
    : ['web'];

Route::middleware($legalMiddleware)->controller(LegalController::class)->group(function () {
    Route::get('/privacy-policy', 'privacyPolicy')->name('legal.privacy');
    Route::get('/terms-of-use', 'termsOfUse')->name('legal.terms');
    Route::get('/cookie-policy', 'cookiePolicy')->name('legal.cookies');
    Route::get('/conditions-de-vente', 'salesConditions')->name('legal.sales');
    Route::get('/rights-request', 'rightsRequest')->name('legal.rights');
    Route::post('/rights-request', 'rightsRequestStore')->name('legal.rights.store');
});
