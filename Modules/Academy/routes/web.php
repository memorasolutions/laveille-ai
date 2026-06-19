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
use Modules\Academy\Http\Controllers\LessonController;
use Modules\Academy\Http\Middleware\AcademyCsp;

Route::prefix('academie')->name('academy.')->middleware(AcademyCsp::class)->group(function () {
    // M2 — Pages publiques
    Route::get('/', [AcademyController::class, 'index'])->name('index');
    Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

    // M1 — Inscription à un cours (auth requis)
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])
        ->middleware('auth')
        ->name('courses.enroll');

    // M3 — Lecteur de leçon (auth requis pour les vidéos protégées, mais la route est publique)
    Route::get('courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'show'])
        ->name('lessons.show');
});
