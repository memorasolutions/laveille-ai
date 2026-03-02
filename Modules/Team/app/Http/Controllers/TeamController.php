<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

namespace Modules\Team\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Team\Models\Team;
use Modules\Team\Services\TeamService;

class TeamController extends Controller
{
    public function index(): View
    {
        $teams = Team::withCount('members')->latest()->paginate(15);

        return view('team::teams.index', compact('teams'));
    }

    public function create(): View
    {
        return view('team::teams.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        TeamService::create($request->user(), $validated);

        return redirect()->route('admin.teams.index')
            ->with('success', __('Équipe créée avec succès.'));
    }

    public function show(Team $team): View
    {
        $team->load('members', 'owner', 'pendingInvitations.inviter');

        return view('team::teams.show', compact('team'));
    }

    public function edit(Team $team): View
    {
        return view('team::teams.edit', compact('team'));
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $team->update($validated);

        return redirect()->route('admin.teams.index')
            ->with('success', __('Équipe mise à jour.'));
    }

    public function destroy(Team $team): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasRole('super_admin') && ! $team->isOwner($user)) {
            abort(403);
        }

        $team->delete();

        return redirect()->route('admin.teams.index')
            ->with('success', __('Équipe supprimée.'));
    }
}
