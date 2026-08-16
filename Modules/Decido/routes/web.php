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
    // Politique de rétention (2026-07-19) : "Prolonger de 3 mois", déclenché depuis le courriel
    // d'avertissement J-14 (via la page de gestion) ou depuis le menu d'actions de la page de
    // gestion elle-même. Même groupe/pattern d'autorisation que fermer/export/lien-court/qr
    // (authorizeManage : owner connecté OU jeton admin valide).
    Route::post('/decido/{poll}/gerer/{adminToken}/prolonger', [PollManageController::class, 'extend'])->name('decido.extend');
    // LOT 1 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 2) : échéance de réponse
    // facultative, modifiable après coup depuis la page de gestion (en plus du champ de création).
    Route::post('/decido/{poll}/gerer/{adminToken}/echeance', [PollManageController::class, 'updateDeadline'])->name('decido.deadline');
    // LOT 3 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 7) : suivi des non-répondants
    // SANS carnet d'adresses - un simple entier facultatif (nombre de personnes attendues),
    // modifiable depuis la page de gestion. Aucune collecte de courriel.
    Route::post('/decido/{poll}/gerer/{adminToken}/attendus', [PollManageController::class, 'updateExpected'])->name('decido.expected');
    // LOT 5 (docs/specs/2026-08-16-decido-reste-a-faire.md) : interrupteur PAR SONDAGE du résumé
    // quotidien d'activité (decido:notify-poll-activity) - jamais un réglage global de compte.
    Route::post('/decido/{poll}/gerer/{adminToken}/notifications', [PollManageController::class, 'updateActivityNotifications'])->name('decido.notifications');
    Route::get('/decido/{poll}/gerer/{adminToken}/export.csv', [PollManageController::class, 'exportCsv'])->name('decido.export.csv');
    Route::get('/decido/{poll}/gerer/{adminToken}/export.ics', [PollManageController::class, 'exportIcs'])->name('decido.export.ics');
    Route::post('/decido/{poll}/gerer/{adminToken}/lien-court', [PollManageController::class, 'createShortLink'])->name('decido.shortlink');
    Route::get('/decido/{poll}/gerer/{adminToken}/qr.png', [PollManageController::class, 'qrCode'])->name('decido.qr');

    Route::get('/decido/{slug}', [PublicPollController::class, 'show'])->name('decido.vote.show');
    // throttle:20,1 = anti-bourrage d'urnes (20 votes/min/IP), même finding round 5 que ci-dessus
    // - un votant anonyme sans compte pouvait sinon générer un nombre illimité de cookies
    // decido_voter_* pour spammer un sondage.
    Route::post('/decido/{slug}/voter', [PublicPollController::class, 'vote'])->middleware('throttle:20,1')->name('decido.vote.store');
    // LOT 1, point 3 : "aucune date ne me convient" - même limite anti-abus que le vote normal.
    Route::post('/decido/{slug}/aucune-date', [PublicPollController::class, 'decline'])->middleware('throttle:20,1')->name('decido.vote.decline');
    // LOT 1, point 1 : export ICS public, débloqué seulement après clôture avec créneau final -
    // réutilise PollExportService::exportIcs() (même service que la version organisateur), sans
    // exiger le jeton admin puisque le créneau final est déjà visible publiquement sur cette page
    // une fois le sondage clôturé.
    Route::get('/decido/{slug}/calendrier.ics', [PublicPollController::class, 'exportIcs'])->name('decido.vote.ics');
    // LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 4) : "Effacer ma réponse" -
    // geste explicite et irréversible qui efface TOUTE la participation du votant (votes + déclin
    // + commentaire) à ce sondage. Même limite anti-abus que le vote/déclin normal ; la vraie
    // protection reste le voter_token du cookie chiffré (PublicPollController::clearVote()),
    // jamais un paramètre de cette route.
    Route::post('/decido/{slug}/effacer', [PublicPollController::class, 'clearVote'])->middleware('throttle:20,1')->name('decido.vote.clear');
});
