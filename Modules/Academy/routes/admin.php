<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Routes admin du module Academy — enregistrées depuis AcademyServiceProvider
 * Préfixe : /admin/academy  |  Nom : admin.academy.*
 * Gating  : middleware 'web' + 'auth' + EnsureIsAdmin + can:academy.manage
 *
 * D03 (2026-07-02) — ménage dette technique « doublon admin legacy ∥ gestion
 * front-end » (voir .outils/academie-roadmap-moderne-vs-moodle-2026-06-30.md).
 * Le CRUD cours/structure/instructeurs vivait ici EN DOUBLE de l'éditeur
 * front-end (CourseEditor/CourseRoster, refonte 2026-06-20) et était déjà
 * flagué DORMANT depuis v1.65.296 (sidebar retirée, filet accessible par URL
 * directe seulement — voir CHANGELOG). AdminCourseController/
 * AdminStructureController/AdminInstructorController ont été SUPPRIMÉS
 * (logique 100 % dupliquée, admin=academy.manage a déjà accès total au
 * front-end via CoursePolicy). Les 3 URLs GET historiques sont conservées en
 * redirection douce (zéro 404 sur un bookmark existant) ; les actions
 * d'écriture (store/update/toggle-status/archive/chapitres/leçons/items/
 * rôles) n'ont plus de vue pour les déclencher et ont donc été retirées.
 *
 * Les paliers d'abonnement (subscription-tiers) restent INCHANGÉS : config
 * PLATEFORME globale (freemium/pro/organisation), pas owner-scopée par cours,
 * sans équivalent front-end — ce n'est PAS un doublon (voir
 * AdminSubscriptionTierController).
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academy\Http\Controllers\Admin\AdminSubscriptionTierController;
use Modules\Academy\Models\Course;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

Route::prefix('admin/academy')
    ->name('admin.academy.')
    ->middleware(['web', 'auth', EnsureIsAdmin::class, SetBackofficeTheme::class, 'can:academy.manage'])
    ->group(function (): void {

        // ── Cours : redirections douces vers l'unique surface (front-end) ──────
        Route::get('courses', fn () => redirect()->route('academy.dashboard'))
            ->name('courses.index');
        Route::get('courses/create', fn () => redirect()->route('academy.courses.create'))
            ->name('courses.create');
        Route::get('courses/{course}/edit', fn (Course $course) => redirect()->route('academy.courses.manage', $course->slug))
            ->name('courses.edit');

        // ── Paliers d'abonnement (freemium/pro/organisation — LMS 2026) ────────
        // Zéro Stripe : prix NEUTRE saisi par l'admin, stripe_price_id à remplir
        // plus tard manuellement. Voir Services\TierGateService.
        Route::get('subscription-tiers', [AdminSubscriptionTierController::class, 'index'])->name('subscription-tiers.index');
        Route::get('subscription-tiers/create', [AdminSubscriptionTierController::class, 'create'])->name('subscription-tiers.create');
        Route::post('subscription-tiers', [AdminSubscriptionTierController::class, 'store'])->name('subscription-tiers.store');
        Route::get('subscription-tiers/{subscriptionTier}/edit', [AdminSubscriptionTierController::class, 'edit'])->name('subscription-tiers.edit');
        Route::put('subscription-tiers/{subscriptionTier}', [AdminSubscriptionTierController::class, 'update'])->name('subscription-tiers.update');
        Route::post('subscription-tiers/{subscriptionTier}/toggle-status', [AdminSubscriptionTierController::class, 'toggleStatus'])->name('subscription-tiers.toggle-status');
        Route::delete('subscription-tiers/{subscriptionTier}', [AdminSubscriptionTierController::class, 'destroy'])->name('subscription-tiers.destroy');
    });
