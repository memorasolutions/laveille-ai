<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\News\Http\Controllers\AdminNewsController;
use Modules\News\Http\Controllers\PublicNewsController;

// ── Routes publiques ──
Route::middleware('web')->group(function () {
    Route::get('/actualites', [PublicNewsController::class, 'index'])->name('news.index');

    // Redirect 301 : anciennes URLs /actualites/{id} → /actualites/{slug}
    Route::get('/actualites/{id}', function (string $id) {
        $article = \Modules\News\Models\NewsArticle::findOrFail((int) $id);

        return redirect()->route('news.show', $article, 301);
    })->where('id', '[0-9]+');

    Route::get('/actualites/{article:slug}', [PublicNewsController::class, 'show'])->name('news.show');
});

// ── Routes admin ──
Route::prefix('admin/news')
    ->name('admin.news.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('sources', [AdminNewsController::class, 'index'])->name('sources.index');
        Route::get('sources/create', [AdminNewsController::class, 'create'])->name('sources.create');
        Route::post('sources', [AdminNewsController::class, 'store'])->name('sources.store');
        Route::get('sources/{source}/edit', [AdminNewsController::class, 'edit'])->name('sources.edit');
        Route::put('sources/{source}', [AdminNewsController::class, 'update'])->name('sources.update');
        Route::patch('sources/{source}/toggle', [AdminNewsController::class, 'toggleActive'])->name('sources.toggle');
        Route::post('sources/{source}/fetch', [AdminNewsController::class, 'fetchNow'])->name('sources.fetch');
        Route::delete('sources/{source}', [AdminNewsController::class, 'destroy'])->name('sources.destroy');

        // Articles
        Route::get('articles', [AdminNewsController::class, 'articles'])->name('articles.index');
        Route::patch('articles/{article}/toggle', [AdminNewsController::class, 'toggleArticle'])->name('articles.toggle');
        Route::delete('articles/{article}', [AdminNewsController::class, 'destroyArticle'])->name('articles.destroy');
    });
