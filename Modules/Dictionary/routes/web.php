<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Dictionary\Http\Controllers\Admin\TermAdminController;
use Modules\Dictionary\Http\Controllers\PublicDictionaryController;

Route::middleware('web')->group(function () {
    Route::get('/glossaire', [PublicDictionaryController::class, 'index'])->name('dictionary.index');
    Route::get('/glossaire/{slug}', [PublicDictionaryController::class, 'show'])->name('dictionary.show');
});

// Suggestions glossaire (authentifié)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/glossaire/{slug}/suggest', function (\Illuminate\Http\Request $request, string $slug) {
        $term = \Modules\Dictionary\Models\Term::published()->where('slug->'.app()->getLocale(), $slug)->firstOrFail();

        $validated = $request->validate([
            'field' => ['required', $term->suggestableFieldValidation()],
            'suggested_value' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        \Modules\Directory\Models\ToolSuggestion::create([
            'user_id' => auth()->id(),
            'suggestable_type' => \Modules\Dictionary\Models\Term::class,
            'suggestable_id' => $term->id,
            'field' => $validated['field'],
            'current_value' => $term->{$validated['field']} ?? null,
            'suggested_value' => $validated['suggested_value'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Merci ! Votre suggestion sera examinée par notre équipe.'));
    })->name('dictionary.suggestions.store');
});

Route::middleware(['web', 'auth'])->prefix('admin/dictionary')->name('admin.dictionary.')->group(function () {
    Route::get('/', [TermAdminController::class, 'index'])->name('index');
    Route::get('/create', [TermAdminController::class, 'create'])->name('create');
    Route::post('/', [TermAdminController::class, 'store'])->name('store');
    Route::get('/{term}/edit', [TermAdminController::class, 'edit'])->name('edit');
    Route::put('/{term}', [TermAdminController::class, 'update'])->name('update');
    Route::patch('/{term}/autosave', [TermAdminController::class, 'autosave'])->name('autosave');
    Route::delete('/{term}', [TermAdminController::class, 'destroy'])->name('destroy');
});
