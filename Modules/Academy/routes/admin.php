<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Routes admin du module Academy — enregistrées depuis AcademyServiceProvider
 * Préfixe : /admin/academy  |  Nom : admin.academy.*
 * Gating  : middleware 'web' + 'auth' + EnsureIsAdmin + can:academy.manage
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academy\Http\Controllers\Admin\AdminCourseController;
use Modules\Academy\Http\Controllers\Admin\AdminInstructorController;
use Modules\Academy\Http\Controllers\Admin\AdminStructureController;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

Route::prefix('admin/academy')
    ->name('admin.academy.')
    ->middleware(['web', 'auth', EnsureIsAdmin::class, SetBackofficeTheme::class, 'can:academy.manage'])
    ->group(function (): void {

        // ── CRUD Cours ─────────────────────────────────────────────────────────
        Route::get('courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/create', [AdminCourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [AdminCourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course}', [AdminCourseController::class, 'update'])->name('courses.update');
        Route::post('courses/{course}/toggle-status', [AdminCourseController::class, 'toggleStatus'])->name('courses.toggle-status');
        Route::post('courses/{course}/archive', [AdminCourseController::class, 'archive'])->name('courses.archive');

        // ── Structure : Chapitres ───────────────────────────────────────────────
        Route::post('courses/{course}/chapters', [AdminStructureController::class, 'addChapter'])->name('chapters.store');
        Route::put('chapters/{chapter}', [AdminStructureController::class, 'updateChapter'])->name('chapters.update');
        Route::delete('chapters/{chapter}', [AdminStructureController::class, 'deleteChapter'])->name('chapters.destroy');

        // ── Structure : Leçons ─────────────────────────────────────────────────
        Route::post('chapters/{chapter}/lessons', [AdminStructureController::class, 'addLesson'])->name('lessons.store');
        Route::get('lessons/{lesson}/edit', [AdminStructureController::class, 'editLesson'])->name('lessons.edit');
        Route::put('lessons/{lesson}', [AdminStructureController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('lessons/{lesson}', [AdminStructureController::class, 'deleteLesson'])->name('lessons.destroy');

        // ── Structure : Éléments de leçon ──────────────────────────────────────
        Route::post('lessons/{lesson}/items', [AdminStructureController::class, 'addItem'])->name('lesson-items.store');
        Route::put('items/{item}', [AdminStructureController::class, 'updateItem'])->name('lesson-items.update');
        Route::delete('items/{item}', [AdminStructureController::class, 'deleteItem'])->name('lesson-items.destroy');

        // ── Instructeurs ────────────────────────────────────────────────────────
        Route::post('courses/{course}/roles', [AdminInstructorController::class, 'store'])->name('course-roles.store');
        Route::delete('roles/{courseRole}', [AdminInstructorController::class, 'destroy'])->name('course-roles.destroy');
    });
