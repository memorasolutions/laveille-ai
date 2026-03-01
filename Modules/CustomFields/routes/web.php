<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\CustomFields\Http\Controllers\Admin\CustomFieldDefinitionController;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class, 'permission:manage_settings'])
    ->group(function () {
        Route::resource('custom-fields', CustomFieldDefinitionController::class)->except(['show']);
    });
