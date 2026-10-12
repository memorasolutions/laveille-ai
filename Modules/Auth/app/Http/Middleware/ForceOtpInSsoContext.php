<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Un apprenant renvoyé par le SSO de l'académie (Passport /oauth/authorize) atterrit sur la
 * connexion par code (OTP) plutôt que sur le formulaire de mot de passe. La connexion normale
 * (sans contexte SSO) n'est pas touchée. Le super-admin n'est pas visé ici : il est ramené au
 * mot de passe par MagicLinkController::sendLink().
 *
 * url.intended est LU seulement (jamais pull/intended) : le retour SSO en dépend.
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class ForceOtpInSsoContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth.force_otp_in_sso_context') || Auth::check()) {
            return $next($request);
        }

        // Le super-admin vient d'être renvoyé ici par MagicLinkController pour utiliser son mot de
        // passe : on le laisse voir le formulaire une fois, au lieu de le renvoyer en boucle vers
        // l'OTP (url.intended pointe encore vers /oauth/authorize, il est encore invité).
        if (session('force_password_login')) {
            return $next($request);
        }

        $intended = session('url.intended');

        if (is_string($intended) && str_contains($intended, '/oauth/authorize')) {
            return redirect()->route('magic-link.request');
        }

        return $next($request);
    }
}
