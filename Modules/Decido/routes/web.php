<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Decido\Http\Controllers\PollManageController;
use Modules\Decido\Http\Controllers\PublicPollController;
use Modules\Decido\Http\Middleware\DecidoUnderConstruction;

Route::middleware(DecidoUnderConstruction::class)->group(function () {
    Route::get('/decido', [PollManageController::class, 'index'])->middleware('auth')->name('decido.index');
    Route::get('/decido/creer', [PollManageController::class, 'create'])->middleware('auth')->name('decido.create');
    Route::post('/decido', [PollManageController::class, 'store'])->middleware('auth')->name('decido.store');

    Route::get('/decido/{poll}/gerer/{adminToken}', [PollManageController::class, 'manage'])->name('decido.manage');
    Route::post('/decido/{poll}/gerer/{adminToken}/fermer', [PollManageController::class, 'close'])->name('decido.close');
    Route::get('/decido/{poll}/gerer/{adminToken}/export.csv', [PollManageController::class, 'exportCsv'])->name('decido.export.csv');
    Route::get('/decido/{poll}/gerer/{adminToken}/export.ics', [PollManageController::class, 'exportIcs'])->name('decido.export.ics');
    Route::post('/decido/{poll}/gerer/{adminToken}/lien-court', [PollManageController::class, 'createShortLink'])->name('decido.shortlink');
    Route::get('/decido/{poll}/gerer/{adminToken}/qr.png', [PollManageController::class, 'qrCode'])->name('decido.qr');

    Route::get('/decido/{slug}', [PublicPollController::class, 'show'])->name('decido.vote.show');
    Route::post('/decido/{slug}/voter', [PublicPollController::class, 'vote'])->name('decido.vote.store');
});
