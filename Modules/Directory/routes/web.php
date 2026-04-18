<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Directory\Http\Controllers\Admin\DirectoryAdminController;
use Modules\Directory\Http\Controllers\Admin\ModerationController;
use Modules\Directory\Http\Controllers\CommunityController;
use Modules\Directory\Http\Controllers\LeaderboardController;
use Modules\Directory\Http\Controllers\ProfileController;
use Modules\Directory\Http\Controllers\CollectionController;
use Modules\Directory\Http\Controllers\PublicDirectoryController;
use Modules\Directory\Http\Controllers\RoadmapController;

Route::middleware('web')->group(function () {
    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('/collections/{slug}', [CollectionController::class, 'show'])->name('collections.show');
    Route::get('/annuaire', [PublicDirectoryController::class, 'index'])->name('directory.index');
    Route::get('/annuaire/classement', [LeaderboardController::class, 'index'])->name('directory.leaderboard');
    Route::get('/annuaire/comparer/{categorySlug}', [PublicDirectoryController::class, 'compare'])->name('directory.compare');
    Route::get('/roadmap', [RoadmapController::class, 'index'])->name('directory.roadmap');
    Route::get('/membre/{id}', [ProfileController::class, 'show'])->name('directory.profile');
    Route::get('/annuaire/{slug}', [PublicDirectoryController::class, 'show'])->name('directory.show')->middleware('doNotCacheResponse');
});

// Collections utilisateur (authenticated)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/user/collections', [CollectionController::class, 'myCollections'])->name('collections.my');
    Route::get('/user/collections/list', [CollectionController::class, 'listJson'])->name('collections.list');
    Route::post('/user/collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::delete('/user/collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    Route::post('/api/collections/toggle-tool', [CollectionController::class, 'toggleTool'])->name('collections.toggle-tool');
});

// Soumission + communauté (authenticated users)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/annuaire/api/scrape-detect', [PublicDirectoryController::class, 'scrapeAndDetect'])->name('directory.scrape-detect')->middleware('throttle:5,1');
    Route::post('/annuaire/proposer', [PublicDirectoryController::class, 'storeSubmission'])->name('directory.submit')->middleware('throttle:3,60');
    Route::post('/annuaire/{slug}/reviews', [CommunityController::class, 'storeReview'])->name('directory.reviews.store');
    Route::post('/annuaire/{slug}/discussions', [CommunityController::class, 'storeDiscussion'])->name('directory.discussions.store');
    Route::post('/annuaire/{slug}/resources', [CommunityController::class, 'storeResource'])->name('directory.resources.store')->middleware('throttle:5,60');
    Route::post('/annuaire/{slug}/youtube-meta', [CommunityController::class, 'fetchYoutubeMeta'])->name('directory.youtube-meta')->middleware('throttle:10,1');
    Route::post('/annuaire/community/{type}/{id}/like', [CommunityController::class, 'toggleLike'])->name('directory.community.like');
    Route::post('/annuaire/community/{type}/{id}/report', [CommunityController::class, 'report'])->name('directory.community.report');
    Route::post('/annuaire/{slug}/suggest', [CommunityController::class, 'storeSuggestion'])->name('directory.suggestions.store');
    Route::post('/annuaire/{slug}/screenshots', [CommunityController::class, 'storeScreenshot'])->name('directory.screenshots.store');
    Route::post('/annuaire/screenshot/{id}/vote', [CommunityController::class, 'voteScreenshot'])->name('directory.screenshots.vote');
    Route::post('/annuaire/screenshot/{id}/delete', [CommunityController::class, 'deleteScreenshot'])->name('directory.screenshots.delete')->can('view_admin_panel');
    Route::post('/annuaire/screenshot/{id}/promote', [CommunityController::class, 'promoteScreenshot'])->name('directory.screenshots.promote')->can('view_admin_panel');
    Route::post('/roadmap/{id}/vote', [RoadmapController::class, 'vote'])->name('directory.roadmap.vote');
});

Route::middleware(['web', 'auth', \Modules\Core\Http\Middleware\EnsureIsAdmin::class])->prefix('admin/directory')->name('admin.directory.')->group(function () {
    Route::get('/', [DirectoryAdminController::class, 'index'])->name('index');
    Route::get('/create', [DirectoryAdminController::class, 'create'])->name('create');
    Route::post('/', [DirectoryAdminController::class, 'store'])->name('store');
    Route::get('/{tool}/edit', [DirectoryAdminController::class, 'edit'])->name('edit');
    Route::put('/{tool}', [DirectoryAdminController::class, 'update'])->name('update');
    Route::patch('/{tool}/autosave', [DirectoryAdminController::class, 'autosave'])->name('autosave');
    Route::delete('/{tool}', [DirectoryAdminController::class, 'destroy'])->name('destroy');
    Route::post('/{tool}/capture-screenshot', [DirectoryAdminController::class, 'captureScreenshot'])->name('capture-screenshot');
    Route::post('/{tool}/upload-screenshot', [DirectoryAdminController::class, 'uploadScreenshot'])->name('upload-screenshot');
    Route::post('/{tool}/set-main-screenshot/{screenshotId}', [DirectoryAdminController::class, 'setMainScreenshot'])->name('set-main-screenshot');

    // Resources CRUD (admin)
    Route::get('/resources', [ModerationController::class, 'resources'])->name('resources');
    Route::get('/resources/{id}/edit', [ModerationController::class, 'editResource'])->name('resources.edit');
    Route::put('/resources/{id}', [ModerationController::class, 'updateResource'])->name('resources.update');

    // Moderation
    Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation');
    Route::post('/moderation/review/{id}/approve', [ModerationController::class, 'approveReview'])->name('moderation.review.approve');
    Route::post('/moderation/review/{id}/reject', [ModerationController::class, 'rejectReview'])->name('moderation.review.reject');
    Route::post('/moderation/resource/{id}/approve', [ModerationController::class, 'approveResource'])->name('moderation.resource.approve');
    Route::post('/moderation/resource/{id}/reject', [ModerationController::class, 'rejectResource'])->name('moderation.resource.reject');
    Route::post('/moderation/resource/{id}/delete', [ModerationController::class, 'deleteResource'])->name('moderation.resource.delete');
    Route::post('/moderation/report/{id}/resolve', [ModerationController::class, 'resolveReport'])->name('moderation.report.resolve');
    Route::post('/moderation/report/{id}/delete', [ModerationController::class, 'deleteReported'])->name('moderation.report.delete');
    Route::post('/moderation/suggestion/{id}/approve', [ModerationController::class, 'approveSuggestion'])->name('moderation.suggestion.approve');
    Route::post('/moderation/suggestion/{id}/reject', [ModerationController::class, 'rejectSuggestion'])->name('moderation.suggestion.reject');
});
