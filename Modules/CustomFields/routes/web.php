<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\CustomFields\Http\Controllers\Admin\CustomFieldDefinitionController;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('custom-fields', [CustomFieldDefinitionController::class, 'index'])->name('custom-fields.index')->middleware('permission:view_settings');
        Route::get('custom-fields/create', [CustomFieldDefinitionController::class, 'create'])->name('custom-fields.create')->middleware('permission:manage_settings');
        Route::post('custom-fields', [CustomFieldDefinitionController::class, 'store'])->name('custom-fields.store')->middleware('permission:manage_settings');
        Route::get('custom-fields/{custom_field}/edit', [CustomFieldDefinitionController::class, 'edit'])->name('custom-fields.edit')->middleware('permission:manage_settings');
        Route::put('custom-fields/{custom_field}', [CustomFieldDefinitionController::class, 'update'])->name('custom-fields.update')->middleware('permission:manage_settings');
        Route::delete('custom-fields/{custom_field}', [CustomFieldDefinitionController::class, 'destroy'])->name('custom-fields.destroy')->middleware('permission:manage_settings');
    });
