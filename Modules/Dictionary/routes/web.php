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
    Route::get('/glossaire', [PublicDictionaryController::class, 'index'])->name('dictionary.index')->middleware('cacheResponse:3600');
    // Doublons consolidés : anciens slugs (ajoutés via admin) → fiche canonique (301, AVANT le wildcard)
    Route::redirect('/glossaire/mcp-model-context-protocol', '/glossaire/mcp', 301);
    Route::redirect('/glossaire/differential-privacy', '/glossaire/confidentialite-differentielle', 301);
    Route::redirect('/glossaire/hallucination-ia', '/glossaire/hallucination', 301);
    Route::redirect('/glossaire/tokens', '/glossaire/token', 301);
    Route::redirect('/glossaire/moe', '/glossaire/mixture-of-experts', 301);
    Route::redirect('/glossaire/context-window', '/glossaire/fenetre-de-contexte', 301);
    Route::redirect('/glossaire/shadow-ai', '/glossaire/ia-fantome', 301);
    Route::redirect('/glossaire/infiltration-de-requete', '/glossaire/prompt-injection', 301);
    Route::redirect('/glossaire/knowledge-distillation', '/glossaire/distillation-de-modele', 301);
    Route::redirect('/glossaire/affinage', '/glossaire/fine-tuning', 301);
    Route::redirect('/glossaire/edge-ai', '/glossaire/ia-embarquee', 301);
    Route::redirect('/glossaire/spoiler', '/glossaire/data-poisoning', 301);
    Route::get('/glossaire/{slug}', [PublicDictionaryController::class, 'show'])->name('dictionary.show')->middleware('cacheResponse:3600');
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
