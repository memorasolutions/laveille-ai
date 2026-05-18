<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;
use Modules\News\Http\Controllers\Admin\ConcentreBuilderController;
use Modules\News\Http\Controllers\AdminNewsController;
use Modules\News\Http\Controllers\NewsSitemapController;
use Modules\News\Http\Controllers\PublicNewsController;

// ── Sitemap Google News dédié (R6 v1.6.0, best practice mai 2026) ──
Route::get('/news-sitemap.xml', [NewsSitemapController::class, 'index'])->name('news.sitemap');

// ── Routes publiques ──
Route::middleware('web')->group(function () {
    Route::get('/actualites', [PublicNewsController::class, 'index'])->name('news.index');

    // Redirect 301 : anciennes URLs /actualites/{id} → /actualites/{slug}
    Route::get('/actualites/{id}', function (string $id) {
        $article = \Modules\News\Models\NewsArticle::findOrFail((int) $id);

        return redirect()->route('news.show', $article, 301);
    })->where('id', '[0-9]+');

    // P17 #235 — wrapper smart : si slug existe affiche fiche, sinon 301 vers /actualites
    Route::get('/actualites/{slug}', function (string $slug) {
        $article = \Modules\News\Models\NewsArticle::where('slug', $slug)->first();
        if (! $article) {
            return redirect('/actualites', 301);
        }

        return app(\Modules\News\Http\Controllers\PublicNewsController::class)->show($article);
    })->where('slug', '[a-z0-9\-]+')->name('news.show');
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
        Route::get('articles/{article}/edit', [AdminNewsController::class, 'editArticle'])->name('articles.edit');
        Route::put('articles/{article}', [AdminNewsController::class, 'updateArticle'])->name('articles.update');
        Route::post('articles/{article}/rescore', [AdminNewsController::class, 'rescoreArticle'])->name('articles.rescore');
        Route::patch('articles/{article}/toggle', [AdminNewsController::class, 'toggleArticle'])->name('articles.toggle');
        Route::delete('articles/{article}', [AdminNewsController::class, 'destroyArticle'])->name('articles.destroy');
        Route::post('articles/{article}/upload-image', [AdminNewsController::class, 'uploadArticleImage'])->name('articles.upload-image');
    });

// ── Concentre Builder (admin, S90) ──
Route::prefix('admin/concentre-builder')
    ->name('admin.concentre.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::get('/', [ConcentreBuilderController::class, 'index'])->name('index');
        Route::get('/news', [ConcentreBuilderController::class, 'newsForWeek'])->name('news');
        Route::post('/generate', [ConcentreBuilderController::class, 'generate'])->name('generate');
        Route::post('/upload-image', [ConcentreBuilderController::class, 'uploadImage'])->name('upload-image')->middleware('throttle:10,1');
        Route::get('/runs/{id}', [ConcentreBuilderController::class, 'showRun'])->name('runs.show');
    });
