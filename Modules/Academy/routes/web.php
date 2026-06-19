<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Academy\Http\Controllers\AcademyController;
use Modules\Academy\Http\Controllers\CourseController;
use Modules\Academy\Http\Controllers\EnrollmentController;

Route::prefix('academie')->name('academy.')->group(function () {
    // M2 — Pages publiques
    Route::get('/', [AcademyController::class, 'index'])->name('index');
    Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

    // M1 — Inscription à un cours (auth requis)
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])
        ->middleware('auth')
        ->name('courses.enroll');
});
