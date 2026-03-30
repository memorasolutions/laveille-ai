<?php

namespace Modules\Core\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBanned
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->ban_expires_at) {
                if ($user->ban_expires_at > now()) {
                    $date = Carbon::parse($user->ban_expires_at)->format('d/m/Y à H:i');
                    abort(403, __('Votre compte est temporairement suspendu jusqu\'au :date.', ['date' => $date]));
                }

                // Auto-unban si expiré
                $user->ban_expires_at = null;
                $user->save();
            }
        }

        return $next($request);
    }
}
