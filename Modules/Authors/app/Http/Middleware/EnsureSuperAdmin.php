<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $isSuperAdmin = false;

        if (method_exists($user, 'hasRole')) {
            try {
                $isSuperAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');
            } catch (\Throwable) {
                // Silently ignore Spatie\Permission exceptions
            }
        }

        if (! $isSuperAdmin && isset($user->is_super_admin)) {
            $isSuperAdmin = (bool) $user->is_super_admin;
        }

        if (! $isSuperAdmin && $user->id === 1 && in_array(app()->environment(), ['local', 'testing'], true)) {
            $isSuperAdmin = true;
        }

        abort_unless($isSuperAdmin, 403, 'Accès super-admin requis.');

        return $next($request);
    }
}
