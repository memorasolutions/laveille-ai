<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authors\Http\Controllers\AuthorsController;
use Modules\Authors\Http\Controllers\MiniSiteController;

// Mini-site public (sans menu laveille, layout pleine page)
Route::get('/@{slug}', [MiniSiteController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('authors.mini-site.show');

Route::get('/@{slug}/feed.xml', [MiniSiteController::class, 'rss'])
    ->where('slug', '[a-z0-9-]+')
    ->name('authors.mini-site.rss');

Route::get('/@{slug}/feed.json', [MiniSiteController::class, 'jsonFeed'])
    ->where('slug', '[a-z0-9-]+')
    ->name('authors.mini-site.json-feed');

// Dashboard auteur (auth required)
Route::middleware(['web', 'auth'])->prefix('/auteur')->group(function () {
    Route::get('/dashboard', fn () => view('authors::dashboard'))
        ->name('authors.dashboard');

    Route::get('/curation/save', fn () => response()->json(['todo' => true]))
        ->name('authors.curation.save');
});

// Route TEST LOCAL UNIQUEMENT (à retirer en prod) — preview dashboard sans auth
if (app()->environment('local')) {
    Route::get('/auteur/test-dashboard/{authorProfileId}', function ($authorProfileId) {
        return view('authors::test-dashboard', ['authorProfileId' => (int) $authorProfileId]);
    })->name('authors.test-dashboard');
}

// Actions modération via signed URLs (depuis emails)
Route::middleware(['signed'])->group(function () {
    Route::get('/auteur/moderation/{article}/approve', fn () => response()->json(['todo' => true]))
        ->name('authors.moderation.approve');

    Route::get('/auteur/moderation/{article}/depublish', fn () => response()->json(['todo' => true]))
        ->name('authors.moderation.depublish');

    Route::get('/auteur/moderation/{article}/ban', fn () => response()->json(['todo' => true]))
        ->name('authors.moderation.ban');
});

// CRUD admin auteurs (legacy nwidart scaffold)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('authors', AuthorsController::class)->names('authors');
});
