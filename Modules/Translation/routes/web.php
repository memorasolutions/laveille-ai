<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Translation\Http\Controllers\TranslationController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('translations', TranslationController::class)->names('translation');
});
