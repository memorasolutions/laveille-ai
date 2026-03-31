<?php

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Privacy\Models\LegalPage;

class LegalPageController
{
    public function index(): View
    {
        $pages = LegalPage::latest()->get();

        return view('backoffice::legal-pages.index', compact('pages'));
    }

    public function edit(LegalPage $legalPage): View
    {
        return view('backoffice::legal-pages.edit', compact('legalPage'));
    }

    public function update(Request $request, LegalPage $legalPage): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'is_active' => 'boolean',
        ]);

        $legalPage->fill($validated);
        $legalPage->updated_by = auth()->id();
        $legalPage->save();

        return back()->with('success', 'Page légale mise à jour.');
    }
}
