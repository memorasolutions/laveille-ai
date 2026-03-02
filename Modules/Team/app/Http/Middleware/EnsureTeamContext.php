<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

namespace Modules\Team\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->current_team_id) {
                $team = $user->currentTeam;
                if ($team && $team->hasMember($user)) {
                    $request->attributes->set('team', $team);
                } else {
                    $user->update(['current_team_id' => null]);
                }
            }

            if (! $user->current_team_id && $user->teams()->exists()) {
                $firstTeam = $user->teams()->orderBy('teams.id')->first();
                if ($firstTeam) {
                    $user->update(['current_team_id' => $firstTeam->id]);
                    $request->attributes->set('team', $firstTeam);
                }
            }
        }

        return $next($request);
    }
}
