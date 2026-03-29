<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\News\Http\Controllers\PublicNewsController;

Route::middleware('web')->group(function () {
    Route::get('/actualites', [PublicNewsController::class, 'index'])->name('news.index');
    Route::get('/actualites/{article}', [PublicNewsController::class, 'show'])->name('news.show');
});
