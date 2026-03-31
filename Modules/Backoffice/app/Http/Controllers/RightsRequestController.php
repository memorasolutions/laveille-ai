<?php

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Privacy\Models\RightsRequest;

class RightsRequestController
{
    public function index(): View
    {
        $pending = RightsRequest::pending()->count();
        $overdue = RightsRequest::overdue()->count();
        $total = RightsRequest::count();
        $requests = RightsRequest::latest()->paginate(20);

        return view('backoffice::rights-requests.index', compact('pending', 'overdue', 'total', 'requests'));
    }

    public function show(RightsRequest $rightsRequest): View
    {
        return view('backoffice::rights-requests.show', compact('rightsRequest'));
    }

    public function markCompleted(RightsRequest $rightsRequest): RedirectResponse
    {
        $rightsRequest->markCompleted();

        return redirect()->back()->with('success', 'Demande marquée comme terminée.');
    }

    public function addNote(Request $request, RightsRequest $rightsRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:2000',
        ]);

        $rightsRequest->update($validated);

        return redirect()->back()->with('success', 'Note ajoutée avec succès.');
    }
}
