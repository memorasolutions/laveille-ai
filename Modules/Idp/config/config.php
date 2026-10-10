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

    // Clients de confiance qui sautent l'écran de consentement (first-party, même organisation).
    // On NE se fie PAS au seul « sans propriétaire » (un client créé par artisan sans --user est
    // sans propriétaire sans pour autant être de confiance) : le NOM doit aussi figurer ici.
    // Défaut = le seul client prévu, le Moodle de formations.laveille.ai. Surcharge par
    // IDP_TRUSTED_CLIENT_NAMES (noms séparés par des virgules).
    'trusted_client_names' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('IDP_TRUSTED_CLIENT_NAMES', 'Moodle formations'))
    ))),
];
