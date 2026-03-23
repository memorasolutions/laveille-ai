<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Support\Facades\Route;
use Modules\Acronyms\Http\Controllers\Admin\AcronymAdminController;
use Modules\Acronyms\Http\Controllers\PublicAcronymController;

Route::middleware('web')->group(function () {
    Route::get('/acronymes-education', [PublicAcronymController::class, 'index'])->name('acronyms.index');
    Route::get('/acronymes-education/{slug}', [PublicAcronymController::class, 'show'])->name('acronyms.show');
});

// Suggestions acronymes (authentifié, si module Directory actif)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/acronymes-education/{slug}/suggest', function (\Illuminate\Http\Request $request, string $slug) {
        if (! class_exists(\Modules\Directory\Models\ToolSuggestion::class)) {
            abort(404);
        }

        $acronym = \Modules\Acronyms\Models\Acronym::published()
            ->where('slug->' . app()->getLocale(), $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'field' => ['required', 'in:full_name,description,website_url,other'],
            'suggested_value' => ['required', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        \Modules\Directory\Models\ToolSuggestion::create([
            'user_id' => auth()->id(),
            'suggestable_type' => \Modules\Acronyms\Models\Acronym::class,
            'suggestable_id' => $acronym->id,
            'field' => $validated['field'],
            'current_value' => $acronym->{$validated['field']} ?? null,
            'suggested_value' => $validated['suggested_value'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Merci ! Votre suggestion sera examinée par notre équipe.'));
    })->name('acronyms.suggestions.store');
});

Route::middleware(['web', 'auth'])->prefix('admin/acronyms')->name('admin.acronyms.')->group(function () {
    Route::get('/', [AcronymAdminController::class, 'index'])->name('index');
    Route::get('/create', [AcronymAdminController::class, 'create'])->name('create');
    Route::post('/', [AcronymAdminController::class, 'store'])->name('store');
    Route::get('/{acronym}/edit', [AcronymAdminController::class, 'edit'])->name('edit');
    Route::put('/{acronym}', [AcronymAdminController::class, 'update'])->name('update');
    Route::delete('/{acronym}', [AcronymAdminController::class, 'destroy'])->name('destroy');
});
