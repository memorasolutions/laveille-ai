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

        // Source unique de vérité : User::isSuperAdmin() (email superadmin + rôle
        // "super_admin", underscore — convention réelle du seeder/du reste du site).
        // Ne JAMAIS dupliquer cette logique ici : une divergence ("super-admin" avec
        // trait d'union, ou rôle "admin") cause un 403 silencieux pour le vrai
        // superadmin en production (le repli id===1 ci-dessous ne s'applique qu'en
        // local/testing et masque le bug pendant le développement).
        if (method_exists($user, 'isSuperAdmin')) {
            try {
                $isSuperAdmin = $user->isSuperAdmin();
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
