<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            $routeName = $request->route()?->getName();

            if ($routeName !== 'password.force-change' && $routeName !== 'password.force-change.update' && $routeName !== 'logout') {
                return redirect()->route('password.force-change')
                    ->with('status', 'Vous devez changer votre mot de passe avant de continuer.');
            }
        }

        return $next($request);
    }
}
