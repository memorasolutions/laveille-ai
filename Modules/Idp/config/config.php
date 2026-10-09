<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

return [
    'name' => 'Idp',

    // Interrupteur MAÎTRE : false (défaut) = AUCUNE route IdP enregistrée (404 partout).
    // Lu via env() au runtime (config:cache interdit dans ce projet).
    'enabled' => (bool) env('IDP_ENABLED', false),

    // Claims exposés par /api/oauth/userinfo.
    'claims' => ['sub', 'email', 'email_verified', 'name'],
];
