<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Books\Http\Controllers\PublicBookController;
use Modules\Books\Http\Middleware\BooksUnderConstruction;

Route::middleware(['web', BooksUnderConstruction::class])->group(function () {
    Route::get('/livres', [PublicBookController::class, 'index'])->name('books.index');
    Route::get('/livres/{slug}', [PublicBookController::class, 'show'])->name('books.show');
});
