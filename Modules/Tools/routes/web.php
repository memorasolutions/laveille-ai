<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\Admin\ToolAdminController;
use Modules\Tools\Http\Controllers\PublicToolController;

Route::middleware('web')->group(function () {
    Route::get('/outils', [PublicToolController::class, 'index'])->name('tools.index');
    Route::get('/outils/{slug}', [PublicToolController::class, 'show'])->name('tools.show');
});

Route::middleware(['web', 'auth', \Modules\Core\Http\Middleware\EnsureIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('tools', [ToolAdminController::class, 'index'])->name('tools.index');
    Route::get('tools/create', [ToolAdminController::class, 'create'])->name('tools.create');
    Route::post('tools', [ToolAdminController::class, 'store'])->name('tools.store');
    Route::get('tools/{tool}/edit', [ToolAdminController::class, 'edit'])->name('tools.edit');
    Route::put('tools/{tool}', [ToolAdminController::class, 'update'])->name('tools.update');
    Route::post('tools/{tool}/toggle', [ToolAdminController::class, 'toggleActive'])->name('tools.toggle');
});
