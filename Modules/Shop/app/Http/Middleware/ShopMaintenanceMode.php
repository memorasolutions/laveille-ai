<?php

declare(strict_types=1);

namespace Modules\Shop\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kill switch boutique — bloque l'accès public quand SHOP_MAINTENANCE=true.
 * Super admin et admin Stripe gardent l'accès complet pour réparer / tester.
 *
 * Activation : SHOP_MAINTENANCE=true dans .env prod + php artisan optimize:clear.
 */
class ShopMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('shop.maintenance', false)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Mode JSON (AJAX cart, etc.) — répondre 503 JSON sans casser l'UI
        if ($request->expectsJson()) {
            return response()->json([
                'maintenance' => true,
                'message' => __('Boutique en maintenance.'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // Mode HTML — page maintenance dédiée avec Octopus thinking
        return response()
            ->view('shop::public.maintenance', [], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
