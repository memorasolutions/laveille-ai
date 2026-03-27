<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Voting\Http\Controllers\VoteController;

Route::middleware(['web', 'auth', 'throttle:50,60'])
    ->post('/community/vote/{type}/{id}', [VoteController::class, 'toggle'])
    ->name('community.vote');
