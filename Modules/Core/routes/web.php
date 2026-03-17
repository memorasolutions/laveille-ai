<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\PreviewController;

Route::get('preview/{token}', PreviewController::class)
    ->middleware('web')
    ->name('preview.show')
    ->where('token', '[a-zA-Z0-9]{64}');
