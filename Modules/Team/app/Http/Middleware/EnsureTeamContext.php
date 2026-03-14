<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Team\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Team\Models\Team;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->current_team_id) {
                /** @var Team|null $team */
                $team = $user->currentTeam;
                if ($team && $team->hasMember($user)) {
                    $request->attributes->set('team', $team);
                } else {
                    $user->update(['current_team_id' => null]);
                }
            }

            if (! $user->current_team_id && $user->teams()->exists()) {
                /** @var Team|null $firstTeam */
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
