<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

namespace Modules\Team\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Team\Models\Team;
use Modules\Team\Services\TeamService;

class TeamMemberController extends Controller
{
    public function invite(Request $request, Team $team): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,member',
        ]);

        try {
            TeamService::invite($team, $validated['email'], $validated['role'], $request->user());

            return redirect()->back()
                ->with('success', __('Invitation envoyée à :email.', ['email' => $validated['email']]));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    public function acceptInvitation(string $token): RedirectResponse
    {
        try {
            $team = TeamService::acceptInvitation($token);

            return redirect()->route('user.dashboard')
                ->with('success', __('Vous avez rejoint l\'équipe ":team".', ['team' => $team->name]));
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('user.dashboard')->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function removeMember(Team $team, User $user): RedirectResponse
    {
        try {
            TeamService::removeMember($team, $user);

            return redirect()->back()
                ->with('success', __('Membre retiré de l\'équipe.'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateRole(Request $request, Team $team, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,member',
        ]);

        try {
            TeamService::updateRole($team, $user, $validated['role']);

            return redirect()->back()
                ->with('success', __('Rôle mis à jour.'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
