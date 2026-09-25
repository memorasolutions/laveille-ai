<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Signature\Http\Controllers\SignatureAssistantController;
use Modules\Signature\Http\Controllers\SignatureAssetController;
use Modules\Signature\Http\Controllers\SignatureDraftController;
use Modules\Signature\Http\Controllers\SignatureExportController;
use Modules\Signature\Http\Controllers\SignatureGuideController;
use Modules\Signature\Http\Controllers\SignatureImageController;
use Modules\Signature\Http\Controllers\SignatureManageController;
use Modules\Signature\Http\Controllers\UserSignatureController;
use Modules\Tools\Http\Middleware\EnsureToolNotUnderConstruction;

/*
 * Toutes les pages de L'OUTIL (assistant, guides, gestion par lien secret, « Mes signatures »)
 * passent par le gate « EN CONSTRUCTION » commun à tous les outils (M4.4, DRY strict) - brief du
 * 2026-09-25 : en production, seul le fondateur (superadmin) voit l'outil, tout le reste reçoit la
 * page « en construction » (noindex). Les DEUX routes de RESSOURCE déjà distribuée (image
 * publique, export par jeton déjà connu) restent HORS de ce groupe : un vieux courriel envoyé par
 * le fondateur pendant les tests doit continuer de fonctionner pour son destinataire, qui n'est
 * pas un « visiteur de l'outil ».
 */
Route::middleware(EnsureToolNotUnderConstruction::class.':signature-courriel')->group(function () {
    Route::get('/outils/signature-courriel', [SignatureAssistantController::class, 'create'])->name('signature.assistant');

    Route::post('/outils/signature-courriel/brouillon', [SignatureDraftController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('signature.draft.store');

    Route::get('/outils/signature-courriel/gerer/{token}', [SignatureManageController::class, 'showByToken'])->name('signature.manage');
    Route::patch('/outils/signature-courriel/gerer/{token}', [SignatureManageController::class, 'updateByToken'])
        ->middleware('throttle:20,1')
        ->name('signature.manage.update');
    Route::post('/outils/signature-courriel/gerer/{token}/prolonger', [SignatureManageController::class, 'extendByToken'])->name('signature.manage.extend');
    Route::post('/outils/signature-courriel/gerer/{token}/rotation', [SignatureManageController::class, 'rotateByToken'])->name('signature.manage.rotate');
    Route::post('/outils/signature-courriel/gerer/{token}/rattacher-compte', [SignatureManageController::class, 'attachByToken'])
        ->middleware('auth')
        ->name('signature.manage.attach');

    Route::post('/outils/signature-courriel/images', [SignatureImageController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('signature.images.store');

    Route::get('/outils/signature-courriel/export/outlook-classique/{token}', [SignatureExportController::class, 'show'])->name('signature.export.outlook');

    Route::get('/outils/signature-courriel/guide/gmail', [SignatureGuideController::class, 'gmail'])->name('signature.guide.gmail');
    Route::get('/outils/signature-courriel/guide/outlook-web', [SignatureGuideController::class, 'outlookWeb'])->name('signature.guide.outlook-web');

    Route::middleware('auth')->group(function () {
        // Mineur : POST /mes-signatures (UserSignatureController::store()) n'est appelé par AUCUN
        // JS/blade de l'outil - un membre crée sa signature via l'assistant public
        // (signature.draft.store, qui pose déjà user_id si connecté), jamais par cette route. Code
        // mort retiré plutôt que conservé "au cas où".
        Route::get('/mes-signatures', [UserSignatureController::class, 'index'])->name('signature.user.index');
        Route::get('/mes-signatures/{signature}', [UserSignatureController::class, 'edit'])->name('signature.user.edit');
        Route::patch('/mes-signatures/{signature}', [UserSignatureController::class, 'update'])->middleware('throttle:20,1')->name('signature.user.update');
        Route::post('/mes-signatures/{signature}/prolonger', [UserSignatureController::class, 'extend'])->name('signature.user.extend');
        Route::delete('/mes-signatures/{signature}', [UserSignatureController::class, 'destroy'])->name('signature.user.destroy');
    });
});

// Route publique de service d'image - HORS du gate construction (voir docblock ci-dessus). Hors du
// groupe de middleware habituel du site pour rester légère (section 11.3) : 'web' suffit (session
// non exigée), aucun paramètre de requête variable n'est jamais ajouté à cette URL (section 6.7-b).
// public_id (correctif B3) : 32 caractères hex CSPRNG, jamais l'id auto-incrémenté interne - une
// vieille URL numérique (/signature-assets/1.png) ne matche plus ce motif et répond 404.
Route::get('/signature-assets/{public_id}.{ext}', [SignatureAssetController::class, 'show'])
    ->where('public_id', '[0-9a-f]{32}')
    ->where('ext', 'png|jpg|jpeg|webp')
    ->name('signature.asset');
