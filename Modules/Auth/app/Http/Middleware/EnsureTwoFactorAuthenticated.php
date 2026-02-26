<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactor() && $request->session()->get('auth.2fa_confirmed') !== true) {
            if (! $request->session()->has('auth.2fa_user_id')) {
                $request->session()->put('auth.2fa_user_id', $user->id);
            }

            auth()->logout();
            $request->session()->put('auth.2fa_user_id', $user->id);

            return redirect()->route('auth.two-factor-challenge');
        }

        return $next($request);
    }
}
