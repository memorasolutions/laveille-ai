<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Admin\ArticleController;
use Modules\Blog\Http\Controllers\Admin\ArticleRevisionController;
use Modules\Blog\Http\Controllers\Admin\CategoryController;
use Modules\Blog\Http\Controllers\Admin\CommentAdminController;
use Modules\Blog\Http\Controllers\Admin\TagController;
use Modules\Blog\Http\Controllers\OEmbedController;
use Modules\Core\Http\Middleware\EnsureIsAdmin;
use Modules\Core\Http\Middleware\SetBackofficeTheme;

// oEmbed endpoint (public, no auth)
Route::get('oembed', OEmbedController::class)->middleware('web')->name('oembed');


// Routes publiques blog (nécessite FrontTheme)
if (\Nwidart\Modules\Facades\Module::find('FrontTheme')?->isEnabled()) {
    Route::middleware(['web', \Modules\FrontTheme\Http\Middleware\SetFrontendTheme::class])->group(function () {
        Route::get('/blog', [\Modules\Blog\Http\Controllers\PublicPostController::class, 'index'])->name('blog.index')->middleware('cacheResponse:600');
        Route::get('/blog/{slug}', [\Modules\Blog\Http\Controllers\PublicPostController::class, 'show'])->name('blog.show')->middleware('cacheResponse:600');
        Route::get('/categorie/{slug}', [\Modules\Blog\Http\Controllers\PublicPostController::class, 'category'])->name('blog.category');
        Route::get('/feed', [\Modules\Blog\Http\Controllers\FeedController::class, 'rss'])->name('blog.feed')->middleware('cacheResponse:3600');
    });
}

// Routes admin
Route::prefix('admin/blog')
    ->name('admin.blog.')
    ->middleware(['web', 'auth', 'two.factor', EnsureIsAdmin::class, SetBackofficeTheme::class])
    ->group(function () {
        // Articles - view
        Route::middleware('permission:view_articles')->group(function () {
            Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        });

        // Articles - create (must be before {article} wildcard routes)
        Route::get('articles/create', [ArticleController::class, 'create'])->name('articles.create')->middleware('permission:create_articles');
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store')->middleware('permission:create_articles');

        // Articles - wildcard view routes
        Route::middleware('permission:view_articles')->group(function () {
            Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
            Route::get('articles/{article}/preview', [ArticleController::class, 'preview'])->name('articles.preview');
            Route::get('articles/{article}/revisions', [ArticleRevisionController::class, 'index'])->name('articles.revisions');
            Route::get('articles/{article}/revisions/{revision}', [ArticleRevisionController::class, 'show'])->name('articles.revisions.show');
            Route::get('articles/{article}/revisions/{revision}/diff', [ArticleRevisionController::class, 'diff'])->name('articles.revisions.diff');
        });

        // Articles - edit/update
        Route::middleware('permission:update_articles')->group(function () {
            Route::get('articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
            Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
            Route::post('articles/{article}/publish', [ArticleController::class, 'publish'])->name('articles.publish');
            Route::post('articles/{article}/unpublish', [ArticleController::class, 'unpublish'])->name('articles.unpublish');
            Route::post('articles/{article}/regenerate-seo', [ArticleController::class, 'regenerateSeo'])->name('articles.regenerate-seo');
            Route::post('articles/{article}/translate', [ArticleController::class, 'translateArticle'])->name('articles.translate');
            Route::post('articles/{article}/regenerate-summary', [ArticleController::class, 'regenerateSummary'])->name('articles.regenerate-summary');
            Route::post('articles/{article}/analyze', [ArticleController::class, 'analyzeContent'])->name('articles.analyze');
            Route::post('articles/{article}/youtube-summary', [ArticleController::class, 'youtubeSummary'])->name('articles.youtube-summary');
            Route::patch('articles/{article}/autosave', [ArticleController::class, 'autosave'])->name('articles.autosave');
            Route::post('articles/{article}/revisions/{revision}/restore', [ArticleRevisionController::class, 'restore'])->name('articles.revisions.restore');
        });

        // Articles - delete
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy')->middleware('permission:delete_articles');

        // Categories - view
        Route::middleware('permission:view_articles')->group(function () {
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        });

        // Categories - create
        Route::middleware('permission:create_articles')->group(function () {
            Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::post('categories/quick-create', [CategoryController::class, 'quickCreate'])->name('categories.quick-create');
        });

        // Categories - edit/update
        Route::middleware('permission:update_articles')->group(function () {
            Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        });

        // Categories - delete
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('permission:delete_articles');

        // Tags - view
        Route::middleware('permission:view_articles')->group(function () {
            Route::get('tags', [TagController::class, 'index'])->name('tags.index');
        });

        // Tags - create
        Route::middleware('permission:create_articles')->group(function () {
            Route::get('tags/create', [TagController::class, 'create'])->name('tags.create');
            Route::post('tags', [TagController::class, 'store'])->name('tags.store');
        });

        // Tags - edit/update
        Route::middleware('permission:update_articles')->group(function () {
            Route::get('tags/{tag}/edit', [TagController::class, 'edit'])->name('tags.edit');
            Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        });

        // Tags - delete
        Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy')->middleware('permission:delete_articles');

        // Modération des commentaires
        Route::get('comments', [CommentAdminController::class, 'index'])->name('comments.index')->middleware('permission:view_comments');
        Route::middleware('permission:update_comments')->group(function () {
            Route::get('comments/{comment}/approve', [CommentAdminController::class, 'approve'])->name('comments.approve');
            Route::get('comments/{comment}/spam', [CommentAdminController::class, 'spam'])->name('comments.spam');
        });
        Route::delete('comments/{comment}', [CommentAdminController::class, 'destroy'])->name('comments.destroy')->middleware('permission:delete_comments');

        // Soumissions d'articles (admin review)
        Route::get('submissions', [\Modules\Blog\Http\Controllers\Admin\SubmissionController::class, 'index'])->name('submissions.index');
        Route::post('submissions/{article}/approve', [\Modules\Blog\Http\Controllers\Admin\SubmissionController::class, 'approve'])->name('submissions.approve');
        Route::post('submissions/{article}/reject', [\Modules\Blog\Http\Controllers\Admin\SubmissionController::class, 'reject'])->name('submissions.reject');
    });

// Soumission d'articles par les membres (authentifié)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/proposer-article', [\Modules\Blog\Http\Controllers\ArticleSubmissionController::class, 'create'])->name('blog.submissions.create');
    Route::post('/proposer-article', [\Modules\Blog\Http\Controllers\ArticleSubmissionController::class, 'store'])->name('blog.submissions.store');
    Route::get('/mes-articles', [\Modules\Blog\Http\Controllers\ArticleSubmissionController::class, 'mySubmissions'])->name('blog.submissions.mine');

    // Bookmarks
    Route::post('/bookmark/toggle', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);
        if (class_exists(\Modules\Core\Models\Bookmark::class)) {
            $added = \Modules\Core\Models\Bookmark::toggle(auth()->id(), $validated['type'], $validated['id']);

            return response()->json(['bookmarked' => $added]);
        }
        abort(404);
    })->name('bookmark.toggle');

    Route::get('/mes-favoris', function () {
        $bookmarks = \Modules\Core\Models\Bookmark::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('bookmarkable_type');

        return view('blog::submissions.bookmarks', compact('bookmarks'));
    })->name('bookmarks.index');
});
