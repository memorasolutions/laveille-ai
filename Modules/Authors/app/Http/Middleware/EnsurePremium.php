<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePremium
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! method_exists($user, 'subscribed') || ! $user->subscribed('default')) {
            return redirect()->route('authors.upgrade')->with('warning', 'Cette section nécessite un abonnement Premium.');
        }

        return $next($request);
    }
}
