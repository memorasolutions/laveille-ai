<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Admin\ArticleController;
use Modules\Blog\Http\Controllers\Admin\ArticleRevisionController;
use Modules\Blog\Http\Controllers\Admin\CategoryController;
use Modules\Blog\Http\Controllers\Admin\CommentAdminController;
use Modules\Blog\Http\Controllers\Admin\TagController;
use Modules\Blog\Http\Controllers\PublicTagController;
use Modules\Blog\Http\Controllers\AuthorController;
use Modules\Blog\Http\Controllers\CommentController;
use Modules\Blog\Http\Controllers\FeedController;
use Modules\Blog\Http\Controllers\PublicArticleController;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

// Routes admin (existantes)
Route::prefix('admin/blog')
    ->name('admin.blog.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        Route::resource('articles', ArticleController::class);
        Route::post('articles/{article}/publish', [ArticleController::class, 'publish'])->name('articles.publish');
        Route::post('articles/{article}/unpublish', [ArticleController::class, 'unpublish'])->name('articles.unpublish');
        Route::post('articles/{article}/regenerate-seo', [ArticleController::class, 'regenerateSeo'])->name('articles.regenerate-seo');
        Route::post('articles/{article}/translate', [ArticleController::class, 'translateArticle'])->name('articles.translate');
        Route::post('articles/{article}/regenerate-summary', [ArticleController::class, 'regenerateSummary'])->name('articles.regenerate-summary');
        Route::post('articles/{article}/analyze', [ArticleController::class, 'analyzeContent'])->name('articles.analyze');

        // Révisions d'articles
        Route::get('articles/{article}/revisions', [ArticleRevisionController::class, 'index'])->name('articles.revisions');
        Route::get('articles/{article}/revisions/{revision}', [ArticleRevisionController::class, 'show'])->name('articles.revisions.show');
        Route::get('articles/{article}/revisions/{revision}/diff', [ArticleRevisionController::class, 'diff'])->name('articles.revisions.diff');
        Route::post('articles/{article}/revisions/{revision}/restore', [ArticleRevisionController::class, 'restore'])->name('articles.revisions.restore');

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::post('categories/quick-create', [CategoryController::class, 'quickCreate'])->name('categories.quick-create');

        Route::resource('tags', TagController::class)->except(['show']);

        // Modération des commentaires
        Route::get('comments', [CommentAdminController::class, 'index'])->name('comments.index');
        Route::get('comments/{comment}/approve', [CommentAdminController::class, 'approve'])->name('comments.approve');
        Route::get('comments/{comment}/spam', [CommentAdminController::class, 'spam'])->name('comments.spam');
        Route::delete('comments/{comment}', [CommentAdminController::class, 'destroy'])->name('comments.destroy');
    });

// Routes publiques
Route::prefix('blog')
    ->name('blog.')
    ->middleware(['web'])
    ->group(function () {
        Route::middleware('cacheResponse')->group(function () {
            Route::get('/', [PublicArticleController::class, 'index'])->name('index');
            Route::get('/feed.xml', [FeedController::class, 'feed'])->name('feed');
            Route::get('/author/{user}', [AuthorController::class, 'show'])->name('author');
            Route::get('/tag/{tag:slug}', [PublicTagController::class, 'show'])->name('tag');
            Route::get('/{article:slug}', [PublicArticleController::class, 'show'])->name('show');
        });
        Route::post('/{article:slug}/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
