<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Team\Http\Controllers\TeamController;
use Modules\Team\Http\Controllers\TeamMemberController;

// Public route: accept invitation (authenticated users only)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('teams/invitations/{token}/accept', [TeamMemberController::class, 'acceptInvitation'])
        ->name('teams.invitations.accept');
});

// Admin routes
Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    // View
    Route::get('teams', [TeamController::class, 'index'])->name('admin.teams.index')->middleware('permission:view_teams');

    // Create (must be before {team} wildcard)
    Route::get('teams/create', [TeamController::class, 'create'])->name('admin.teams.create')->middleware('permission:create_teams');
    Route::post('teams', [TeamController::class, 'store'])->name('admin.teams.store')->middleware('permission:create_teams');

    // Show (wildcard, after specific create route)
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('admin.teams.show')->middleware('permission:view_teams');
    Route::post('teams/{team}/invite', [TeamMemberController::class, 'invite'])->name('admin.teams.invite')->middleware('permission:create_teams');

    // Edit/Update
    Route::get('teams/{team}/edit', [TeamController::class, 'edit'])->name('admin.teams.edit')->middleware('permission:update_teams');
    Route::put('teams/{team}', [TeamController::class, 'update'])->name('admin.teams.update')->middleware('permission:update_teams');
    Route::patch('teams/{team}/members/{user}/role', [TeamMemberController::class, 'updateRole'])->name('admin.teams.members.role')->middleware('permission:update_teams');

    // Delete
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('admin.teams.destroy')->middleware('permission:delete_teams');
    Route::delete('teams/{team}/members/{user}', [TeamMemberController::class, 'removeMember'])->name('admin.teams.members.remove')->middleware('permission:delete_teams');
});
