<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'name' => 'Auth',

    // Force l'entrée par code (OTP, /magic-link) quand la demande de connexion vient du SSO de
    // l'académie (url.intended pointe vers /oauth/authorize). Le super-admin garde le mot de passe.
    // Lu par config('auth.force_otp_in_sso_context'). Mettre AUTH_FORCE_OTP_IN_SSO=false pour désactiver.
    'force_otp_in_sso_context' => (bool) env('AUTH_FORCE_OTP_IN_SSO', true),
];
