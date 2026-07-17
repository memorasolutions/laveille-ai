<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Decido\Http\Controllers\PollManageController;
use Modules\Decido\Http\Controllers\PublicPollController;
use Modules\Decido\Http\Middleware\DecidoUnderConstruction;

Route::middleware(DecidoUnderConstruction::class)->group(function () {
    Route::get('/decido', [PollManageController::class, 'index'])->middleware('auth')->name('decido.index');
    Route::get('/decido/creer', [PollManageController::class, 'create'])->middleware('auth')->name('decido.create');
    // Option E (skill /100 hors gate, veille pp_search juillet 2026 validée Perplexity+Codex+Gemini) :
    // /decido/creer n'est plus qu'un choix rapide de type - chaque type a son propre formulaire
    // dédié et allégé (routes distinctes, placées AVANT /decido/{slug} pour éviter toute ambiguïté
    // même si le nombre de segments diffère déjà).
    Route::get('/decido/creer/date', [PollManageController::class, 'createDate'])->middleware('auth')->name('decido.create.date');
    Route::get('/decido/creer/classique', [PollManageController::class, 'createClassic'])->middleware('auth')->name('decido.create.classic');
    // throttle:10,1 = anti-abus (10 sondages/min/utilisateur), trouvé manquant par une passe
    // adversariale indépendante (skill /100, round 5) - aucune limite ne bornait la création.
    Route::post('/decido', [PollManageController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('decido.store');

    Route::get('/decido/{poll}/gerer/{adminToken}', [PollManageController::class, 'manage'])->name('decido.manage');
    Route::post('/decido/{poll}/gerer/{adminToken}/fermer', [PollManageController::class, 'close'])->name('decido.close');
    Route::get('/decido/{poll}/gerer/{adminToken}/export.csv', [PollManageController::class, 'exportCsv'])->name('decido.export.csv');
    Route::get('/decido/{poll}/gerer/{adminToken}/export.ics', [PollManageController::class, 'exportIcs'])->name('decido.export.ics');
    Route::post('/decido/{poll}/gerer/{adminToken}/lien-court', [PollManageController::class, 'createShortLink'])->name('decido.shortlink');
    Route::get('/decido/{poll}/gerer/{adminToken}/qr.png', [PollManageController::class, 'qrCode'])->name('decido.qr');

    Route::get('/decido/{slug}', [PublicPollController::class, 'show'])->name('decido.vote.show');
    // throttle:20,1 = anti-bourrage d'urnes (20 votes/min/IP), même finding round 5 que ci-dessus
    // - un votant anonyme sans compte pouvait sinon générer un nombre illimité de cookies
    // decido_voter_* pour spammer un sondage.
    Route::post('/decido/{slug}/voter', [PublicPollController::class, 'vote'])->middleware('throttle:20,1')->name('decido.vote.store');
});
