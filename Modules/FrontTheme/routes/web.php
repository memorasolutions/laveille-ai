<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FrontTheme\Http\Controllers\ContactController;
use Modules\FrontTheme\Http\Controllers\HomeController;
use Modules\FrontTheme\Http\Middleware\SetFrontendTheme;

Route::middleware(['web', SetFrontendTheme::class])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home')->middleware('cacheResponse:600');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

    // Redirections legacy WordPress (SEO 301)
    Route::get('/{year}/{month}/{day}/{slug}', function (string $year, string $month, string $day, string $slug) {
        return redirect()->route('blog.show', $slug, 301);
    })->where(['year' => '\d{4}', 'month' => '\d{2}', 'day' => '\d{2}']);

    Route::get('/les-articles', fn () => redirect()->route('blog.index', [], 301));
    Route::get('/les-articles/{slug}', fn (string $slug) => redirect()->route('blog.index', [], 301));
    Route::get('/category/{slug}', fn (string $slug) => redirect()->route('blog.category', $slug, 301));
});
