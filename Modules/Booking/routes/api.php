<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;

// Scaffold nwidart jamais implémenté (BookingController racine vide, aucune méthode d'écriture
// réelle) - route API supprimée par cohérence sécurité (2026-07-23), même motif que les 13
// scaffolds déjà nettoyés en rounds 7-9. Module désactivé donc non chargé actuellement, corrigé
// en prévention d'une réactivation future. Le vrai module Booking (Admin\* controllers,
// web.php) n'est pas affecté.

// Public widget API (pas d'auth - utilisé par le widget embed)
Route::post('booking', [\Modules\Booking\Http\Controllers\Api\PublicBookingController::class, 'store'])
    ->name('api.booking.public.store');
