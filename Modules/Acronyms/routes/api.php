<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // API routes pour le module Acronyms
});
