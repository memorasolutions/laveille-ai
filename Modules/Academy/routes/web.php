<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Academy\Http\Controllers\AcademyController;
use Modules\Academy\Http\Controllers\CertificateController;
use Modules\Academy\Http\Controllers\CompletionController;
use Modules\Academy\Http\Controllers\CourseController;
use Modules\Academy\Http\Controllers\EnrollmentController;
use Modules\Academy\Http\Controllers\ExportController;
use Modules\Academy\Http\Controllers\LessonController;
use Modules\Academy\Http\Controllers\PurchaseController;
use Modules\Academy\Http\Controllers\QuizController;
use Modules\Academy\Http\Middleware\AcademyCsp;
use Modules\Academy\Http\Middleware\AcademyUnderConstruction;

Route::prefix('academie')->name('academy.')->middleware(AcademyCsp::class)->group(function () {
    // M2 — Pages publiques (gâtées « en construction » : superadmin uniquement tant que le flag est actif)
    Route::middleware(AcademyUnderConstruction::class)->group(function () {
        Route::get('/', [AcademyController::class, 'index'])->name('index');
        Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

        // M3 — Lecteur de leçon (auth requis pour les vidéos protégées, mais la route est publique)
        Route::get('courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'show'])
            ->name('lessons.show');
    });

    // M6 — Certificats publics vérifiables (pas d'auth requise)
    Route::get('certificats/{public_url_slug}', [CertificateController::class, 'show'])->name('certificates.show');

    // M1 — Inscription à un cours (auth requis)
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])
        ->middleware('auth')
        ->name('courses.enroll');

    // M5 — Achat d'un cours payant via Stripe Checkout (auth requis)
    Route::get('courses/{course:slug}/purchase', PurchaseController::class)
        ->middleware('auth')
        ->name('courses.purchase');

    // M7 — Exports CSV admin (gâtés par permission academy.reports.view)
    Route::middleware(['auth', 'can:academy.reports.view'])
        ->prefix('admin/export')
        ->name('admin.export.')
        ->group(function () {
            Route::get('/enrollments', [ExportController::class, 'exportEnrollments'])->name('enrollments');
            Route::get('/completions', [ExportController::class, 'exportCompletions'])->name('completions');
            Route::get('/progress', [ExportController::class, 'exportProgress'])->name('progress');
        });

    // M4 — Complétion, quiz
    Route::middleware('auth')->group(function () {
        // Bouton « Marquer comme terminé » (video / doc)
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/complete',
            [CompletionController::class, 'store']
        )->name('lessons.complete');

        // Démarrer un quiz
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/quiz/start',
            [QuizController::class, 'startQuiz']
        )->name('quiz.start');

        // Soumettre les réponses d'un quiz
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/quiz/submit',
            [QuizController::class, 'submitQuiz']
        )->name('quiz.submit');
    });
});
