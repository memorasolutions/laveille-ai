<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FileAttente\Http\Controllers\BoardController;
use Modules\FileAttente\Http\Controllers\DisplayController;
use Modules\FileAttente\Http\Controllers\ParticipantController;
use Modules\FileAttente\Http\Middleware\FileAttenteUnderConstruction;
use Modules\FileAttente\Http\Middleware\PrivacyHeaders;

// Jetons Str::random : alphanumériques. Les contraintes évitent toute collision avec d'autres routes.
Route::middleware(['web', PrivacyHeaders::class, FileAttenteUnderConstruction::class])->group(function (): void {
    Route::pattern('manageToken', '[A-Za-z0-9]{20,64}');
    Route::pattern('displayToken', '[A-Za-z0-9]{20,64}');
    Route::pattern('participantToken', '[A-Za-z0-9]{20,64}');

    // Création
    Route::get('/outils/file-attente', [BoardController::class, 'create'])->name('fileattente.create');
    Route::post('/outils/file-attente', [BoardController::class, 'store'])->name('fileattente.store');

    // Animateur (jeton de gestion)
    Route::get('/outils/file-attente/g/{manageToken}', [BoardController::class, 'manage'])->name('fileattente.manage');
    Route::get('/g/{manageToken}/etat', [BoardController::class, 'etat'])->name('fileattente.manage.etat');
    Route::get('/g/{manageToken}/pdf', [BoardController::class, 'pdf'])->name('fileattente.pdf');
    Route::post('/g/{manageToken}/participants', [BoardController::class, 'add'])->name('fileattente.add');
    Route::post('/g/{manageToken}/participants/{id}/arrive', [BoardController::class, 'arrive'])->whereNumber('id')->name('fileattente.arrive');
    Route::post('/g/{manageToken}/participants/{id}/remove', [BoardController::class, 'remove'])->whereNumber('id')->name('fileattente.remove');
    Route::post('/g/{manageToken}/reorder', [BoardController::class, 'reorder'])->name('fileattente.reorder');
    Route::post('/g/{manageToken}/call-next', [BoardController::class, 'callNext'])->name('fileattente.callnext');
    Route::post('/g/{manageToken}/undo-call/{id}', [BoardController::class, 'undoCall'])->whereNumber('id')->name('fileattente.undocall');
    Route::post('/g/{manageToken}/terminate', [BoardController::class, 'terminate'])->name('fileattente.terminate');

    // Affichage / projection (lecture seule)
    Route::get('/outils/file-attente/a/{displayToken}', [DisplayController::class, 'show'])->name('fileattente.display');
    Route::get('/a/{displayToken}/etat', [DisplayController::class, 'etat'])->name('fileattente.display.etat');

    // Participant : le GET (scan du QR) ne mute RIEN
    Route::get('/f/{participantToken}', [ParticipantController::class, 'show'])->name('fileattente.scan');
    Route::post('/f/{participantToken}/join', [ParticipantController::class, 'join'])->name('fileattente.join');
    Route::post('/f/{participantToken}/leave', [ParticipantController::class, 'leave'])->name('fileattente.leave');
    Route::get('/f/{participantToken}/etat', [ParticipantController::class, 'etat'])->name('fileattente.scan.etat');
});
