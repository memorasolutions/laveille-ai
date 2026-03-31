<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\Voting\Http\Controllers\Admin\VotingSettingsController;
use Modules\Voting\Http\Controllers\VoteController;

Route::middleware(['web', 'auth', 'throttle:50,60'])
    ->post('/community/vote/{type}/{id}', [VoteController::class, 'toggle'])
    ->name('community.vote');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('voting/settings', [VotingSettingsController::class, 'edit'])->name('voting.settings.edit');
        Route::post('voting/settings', [VotingSettingsController::class, 'update'])->name('voting.settings.update');
    });
