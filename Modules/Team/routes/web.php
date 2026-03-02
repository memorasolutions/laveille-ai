<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

use Illuminate\Support\Facades\Route;
use Modules\Team\Http\Controllers\TeamController;
use Modules\Team\Http\Controllers\TeamMemberController;

// Public route: accept invitation (authenticated users only)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('teams/invitations/{token}/accept', [TeamMemberController::class, 'acceptInvitation'])
        ->name('teams.invitations.accept');
});

// Admin routes
Route::middleware(['web', 'auth', 'permission:manage_teams'])->prefix('admin')->group(function () {
    Route::resource('teams', TeamController::class)->names([
        'index' => 'admin.teams.index',
        'create' => 'admin.teams.create',
        'store' => 'admin.teams.store',
        'show' => 'admin.teams.show',
        'edit' => 'admin.teams.edit',
        'update' => 'admin.teams.update',
        'destroy' => 'admin.teams.destroy',
    ]);

    Route::post('teams/{team}/invite', [TeamMemberController::class, 'invite'])
        ->name('admin.teams.invite');
    Route::delete('teams/{team}/members/{user}', [TeamMemberController::class, 'removeMember'])
        ->name('admin.teams.members.remove');
    Route::patch('teams/{team}/members/{user}/role', [TeamMemberController::class, 'updateRole'])
        ->name('admin.teams.members.role');
});
