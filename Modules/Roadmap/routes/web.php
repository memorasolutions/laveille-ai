<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Roadmap\Http\Controllers\Admin\BoardController;
use Modules\Roadmap\Http\Controllers\Admin\IdeaController;
use Modules\Roadmap\Http\Controllers\Admin\RoadmapAnalyticsController;
use Modules\Roadmap\Http\Controllers\PublicBoardController;

// Admin routes
Route::middleware(['auth', 'verified'])
    ->prefix('admin/roadmap')
    ->name('admin.roadmap.')
    ->group(function () {
        // Boards CRUD
        Route::middleware('permission:manage_roadmap')->group(function () {
            Route::resource('boards', BoardController::class);
        });

        // Analytics
        Route::middleware('permission:manage_roadmap')->group(function () {
            Route::get('analytics', [RoadmapAnalyticsController::class, 'index'])->name('analytics');
        });

        // Ideas management
        Route::middleware('permission:manage_roadmap')->group(function () {
            Route::get('ideas', [IdeaController::class, 'index'])->name('ideas.index');
            Route::get('ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');
            Route::patch('ideas/{idea}/status', [IdeaController::class, 'updateStatus'])->name('ideas.update-status');
            Route::post('ideas/{idea}/merge', [IdeaController::class, 'merge'])->name('ideas.merge');
            Route::delete('ideas/{idea}', [IdeaController::class, 'destroy'])->name('ideas.destroy');
            Route::post('ideas/{idea}/comment', [IdeaController::class, 'addOfficialComment'])->name('ideas.official-comment');
        });

        // Vote toggle (authenticated users)
        Route::middleware('permission:view_roadmap')->group(function () {
            Route::post('ideas/{idea}/vote', [IdeaController::class, 'toggleVote'])->name('ideas.vote');
            Route::post('ideas/{idea}/comment-public', [IdeaController::class, 'addComment'])->name('ideas.comment');
        });

        // Submit idea (authenticated users)
        Route::middleware('permission:view_roadmap')->group(function () {
            Route::post('boards/{board}/ideas', [IdeaController::class, 'store'])->name('ideas.store');
        });
    });

// Public routes (authenticated, basic frontend — easy to replace)
Route::middleware(['auth', 'verified'])
    ->prefix('roadmap')
    ->name('roadmap.')
    ->group(function () {
        Route::get('/', [PublicBoardController::class, 'index'])->name('boards.index');
        Route::get('/{board}', [PublicBoardController::class, 'show'])->name('boards.show');
        Route::get('/{board}/kanban', [PublicBoardController::class, 'kanban'])->name('boards.kanban');
        Route::post('/{board}/ideas', [PublicBoardController::class, 'storeIdea'])->name('ideas.store');
        Route::post('/ideas/{idea}/vote', [PublicBoardController::class, 'vote'])->name('ideas.vote');
        Route::post('/ideas/{idea}/comment', [PublicBoardController::class, 'comment'])->name('ideas.comment');
    });
