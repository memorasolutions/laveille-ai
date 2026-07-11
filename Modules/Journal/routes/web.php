<?php

use Illuminate\Support\Facades\Route;
use Modules\Journal\Http\Controllers\JournalController;

// Publique (invité ou membre) : lecture d'un journal (visibilité gérée par
// JournalPolicy::view — publié = tout le monde, brouillon = propriétaire seul).
Route::get('/journaux/{journal}', [JournalController::class, 'show'])->name('journal.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/journaux', [JournalController::class, 'index'])->name('journal.index');
    Route::get('/journal/creer', [JournalController::class, 'create'])->name('journal.create');
    Route::post('/journaux', [JournalController::class, 'store'])->name('journal.store');
    Route::get('/journaux/{journal}/editer', [JournalController::class, 'edit'])->name('journal.edit');
    Route::delete('/journaux/{journal}', [JournalController::class, 'destroy'])->name('journal.destroy');
    Route::post('/journal/quick-add', [JournalController::class, 'quickAdd'])->name('journal.quick-add');
});
