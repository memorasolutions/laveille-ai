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
use Modules\FormBuilder\Http\Controllers\Admin\FormController;
use Modules\FormBuilder\Http\Controllers\Admin\FormSubmissionController;
use Modules\FormBuilder\Http\Controllers\PublicFormController;

// Admin routes
Route::prefix('admin/formbuilder')
    ->name('admin.formbuilder.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        // Forms - view
        Route::get('forms', [FormController::class, 'index'])->name('forms.index')->middleware('permission:view_forms');

        // Forms - create
        Route::get('forms/create', [FormController::class, 'create'])->name('forms.create')->middleware('permission:create_forms');
        Route::post('forms', [FormController::class, 'store'])->name('forms.store')->middleware('permission:create_forms');

        // Forms - edit/update
        Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit')->middleware('permission:update_forms');
        Route::put('forms/{form}', [FormController::class, 'update'])->name('forms.update')->middleware('permission:update_forms');

        // Forms - delete
        Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy')->middleware('permission:delete_forms');

        // Submissions
        Route::prefix('forms/{form}')->name('forms.')->group(function () {
            Route::get('submissions/export', [FormSubmissionController::class, 'export'])->name('submissions.export')->middleware('permission:view_forms');
            Route::get('submissions', [FormSubmissionController::class, 'index'])->name('submissions.index')->middleware('permission:view_forms');
            Route::get('submissions/{submission}', [FormSubmissionController::class, 'show'])->name('submissions.show')->middleware('permission:view_forms');
            Route::delete('submissions/{submission}', [FormSubmissionController::class, 'destroy'])->name('submissions.destroy')->middleware('permission:delete_forms');
        });
    });

// Public routes
Route::middleware(['web'])->group(function () {
    Route::get('forms/{form}', [PublicFormController::class, 'show'])->name('formbuilder.show');
    Route::post('forms/{form}/submit', [PublicFormController::class, 'submit'])->name('formbuilder.submit');
});
