<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\FormBuilder\Http\Controllers\Admin\FormController;
use Modules\FormBuilder\Http\Controllers\Admin\FormSubmissionController;
use Modules\FormBuilder\Http\Controllers\PublicFormController;

// Admin routes
Route::prefix('admin/formbuilder')
    ->name('admin.formbuilder.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class, 'permission:manage_forms'])
    ->group(function () {
        Route::resource('forms', FormController::class)->except(['show']);

        Route::prefix('forms/{form}')->name('forms.')->group(function () {
            Route::get('submissions/export', [FormSubmissionController::class, 'export'])->name('submissions.export');
            Route::resource('submissions', FormSubmissionController::class)->only(['index', 'show', 'destroy']);
        });
    });

// Public routes
Route::middleware(['web'])->group(function () {
    Route::get('forms/{form}', [PublicFormController::class, 'show'])->name('formbuilder.show');
    Route::post('forms/{form}/submit', [PublicFormController::class, 'submit'])->name('formbuilder.submit');
});
