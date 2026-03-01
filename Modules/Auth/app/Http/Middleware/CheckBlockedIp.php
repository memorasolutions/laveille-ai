<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Models\BlockedIp;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $ip = $request->ip();

            if ($ip && BlockedIp::isBlocked($ip)) {
                abort(403, 'Votre adresse IP a été bloquée.');
            }
        } catch (\Illuminate\Database\QueryException) {
            // Table may not exist yet (e.g. fresh install or testing)
        }

        return $next($request);
    }
}
