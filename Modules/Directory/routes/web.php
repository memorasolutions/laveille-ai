<?php

use Illuminate\Support\Facades\Route;
use Modules\Directory\Http\Controllers\Admin\DirectoryAdminController;
use Modules\Directory\Http\Controllers\Admin\ModerationController;
use Modules\Directory\Http\Controllers\CommunityController;
use Modules\Directory\Http\Controllers\LeaderboardController;
use Modules\Directory\Http\Controllers\ProfileController;
use Modules\Directory\Http\Controllers\PublicDirectoryController;
use Modules\Directory\Http\Controllers\RoadmapController;

Route::middleware('web')->group(function () {
    Route::get('/annuaire', [PublicDirectoryController::class, 'index'])->name('directory.index');
    Route::get('/annuaire/classement', [LeaderboardController::class, 'index'])->name('directory.leaderboard');
    Route::get('/roadmap', [RoadmapController::class, 'index'])->name('directory.roadmap');
    Route::get('/membre/{id}', [ProfileController::class, 'show'])->name('directory.profile');
    Route::get('/annuaire/{slug}', [PublicDirectoryController::class, 'show'])->name('directory.show')->middleware('doNotCacheResponse');
    Route::post('/annuaire/api/scrape-detect', [PublicDirectoryController::class, 'scrapeAndDetect'])->name('directory.scrape-detect');
    Route::post('/annuaire/proposer', [PublicDirectoryController::class, 'storeSubmission'])->name('directory.submit');
});

// Community routes (authenticated users)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/annuaire/{slug}/reviews', [CommunityController::class, 'storeReview'])->name('directory.reviews.store');
    Route::post('/annuaire/{slug}/discussions', [CommunityController::class, 'storeDiscussion'])->name('directory.discussions.store');
    Route::post('/annuaire/{slug}/resources', [CommunityController::class, 'storeResource'])->name('directory.resources.store');
    Route::post('/annuaire/community/{type}/{id}/like', [CommunityController::class, 'toggleLike'])->name('directory.community.like');
    Route::post('/annuaire/community/{type}/{id}/report', [CommunityController::class, 'report'])->name('directory.community.report');
    Route::post('/annuaire/{slug}/suggest', [CommunityController::class, 'storeSuggestion'])->name('directory.suggestions.store');
    Route::post('/annuaire/{slug}/screenshots', [CommunityController::class, 'storeScreenshot'])->name('directory.screenshots.store');
    Route::post('/annuaire/screenshot/{id}/vote', [CommunityController::class, 'voteScreenshot'])->name('directory.screenshots.vote');
    Route::post('/roadmap/{id}/vote', [RoadmapController::class, 'vote'])->name('directory.roadmap.vote');
});

Route::middleware(['web', 'auth'])->prefix('admin/directory')->name('admin.directory.')->group(function () {
    Route::get('/', [DirectoryAdminController::class, 'index'])->name('index');
    Route::get('/create', [DirectoryAdminController::class, 'create'])->name('create');
    Route::post('/', [DirectoryAdminController::class, 'store'])->name('store');
    Route::get('/{tool}/edit', [DirectoryAdminController::class, 'edit'])->name('edit');
    Route::put('/{tool}', [DirectoryAdminController::class, 'update'])->name('update');
    Route::delete('/{tool}', [DirectoryAdminController::class, 'destroy'])->name('destroy');

    // Moderation
    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation');
    Route::post('/moderation/review/{id}/approve', [ModerationController::class, 'approveReview'])->name('moderation.review.approve');
    Route::post('/moderation/review/{id}/reject', [ModerationController::class, 'rejectReview'])->name('moderation.review.reject');
    Route::post('/moderation/resource/{id}/approve', [ModerationController::class, 'approveResource'])->name('moderation.resource.approve');
    Route::post('/moderation/resource/{id}/reject', [ModerationController::class, 'rejectResource'])->name('moderation.resource.reject');
    Route::post('/moderation/report/{id}/resolve', [ModerationController::class, 'resolveReport'])->name('moderation.report.resolve');
    Route::post('/moderation/report/{id}/delete', [ModerationController::class, 'deleteReported'])->name('moderation.report.delete');
    Route::post('/moderation/suggestion/{id}/approve', [ModerationController::class, 'approveSuggestion'])->name('moderation.suggestion.approve');
    Route::post('/moderation/suggestion/{id}/reject', [ModerationController::class, 'rejectSuggestion'])->name('moderation.suggestion.reject');
});
