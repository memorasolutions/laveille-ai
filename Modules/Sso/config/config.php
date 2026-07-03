<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Sso',

    /*
    |--------------------------------------------------------------------------
    | Interrupteur MAÎTRE — SSO entreprise (SAML 2.0) + provisioning SCIM 2.0
    |--------------------------------------------------------------------------
    | Quand false (défaut), TOUTES les routes /sso/saml/* et /scim/v2/* répondent
    | 404 (abort_if en tête de chaque contrôleur ET dans le RouteServiceProvider),
    | aucune configuration SAML n'est chargée, aucun jeton SCIM n'est vérifié.
    | Zéro risque tant que ce drapeau n'est pas explicitement activé.
    | Activer via SSO_ENABLED=true dans le .env (par organisation cliente LMS).
    |
    | Deux niveaux de gating, MÊME convention que Modules\Academy :
    |  1) modules_statuses.json « Sso: true » — charge l'INFRASTRUCTURE du
    |     module (ServiceProvider/routes/migrations), comme Academy l'est
    |     déjà pour toutes ses fonctionnalités individuellement gatées.
    |  2) config('sso.enabled') ci-dessous (CE drapeau) — la VRAIE bascule
    |     de sécurité, désactivée par défaut : tant qu'il reste false,
    |     aucune route SSO/SCIM n'est fonctionnelle en pratique (404 partout).
    */
    'enabled' => env('SSO_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | SAML 2.0 — Service Provider (SP)
    |--------------------------------------------------------------------------
    | Paramètres globaux du SP Laravel. Les paramètres PAR IdP (entity_id, SSO
    | URL, certificat X.509, mapping d'attributs) vivent dans la table
    | sso_configurations (une ligne par organisation/tenant), pas ici.
    |
    | « strict » : mode strict du toolkit onelogin/php-saml — validations XML
    | strictes (signature, audience, destination, NotBefore/NotOnOrAfter).
    | JAMAIS désactiver en production.
    |
    | « response_max_age_seconds » : fenêtre de tolérance d'horloge pour
    | NotBefore/NotOnOrAfter (dérive d'horloge raisonnable entre IdP et SP).
    |
    | « inresponseto_ttl_days » : durée de rétention des InResponseTo déjà vus
    | (table sso_saml_replay_guard) avant purge — protection anti-rejeu.
    */
    'saml' => [
        'strict' => env('SSO_SAML_STRICT', true),
        'debug' => env('SSO_SAML_DEBUG', false),

        'sp_entity_id' => env('SSO_SAML_SP_ENTITY_ID', env('APP_URL').'/sso/saml/metadata'),

        'response_max_age_seconds' => (int) env('SSO_SAML_CLOCK_SKEW_SECONDS', 180),

        'inresponseto_ttl_days' => (int) env('SSO_SAML_REPLAY_TTL_DAYS', 7),

        // Attributs SAML par défaut si l'organisation ne fournit pas son propre
        // attribute_mapping (voir sso_configurations.attribute_mapping JSON).
        'default_attribute_mapping' => [
            'email' => 'email',
            'name' => 'name',
        ],

        // Contact technique/support exposé dans les métadonnées SP (XML public).
        'contact_technical_email' => env('SSO_SAML_CONTACT_EMAIL', 'info@memora.ca'),
        'organization_name' => env('SSO_SAML_ORG_NAME', 'MEMORA solutions'),
        'organization_url' => env('SSO_SAML_ORG_URL', 'https://memora.solutions'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SCIM 2.0 — Provisioning entrant (RFC 7643 / RFC 7644)
    |--------------------------------------------------------------------------
    | « default_page_size » / « max_page_size » : pagination GET /Users
    | (startIndex, count) — bornée pour éviter un count=100000 abusif.
    |
    | « groups_enabled » : Groups SCIM (GET/POST /scim/v2/Groups) — HORS SCOPE
    | dans cette V1 (voir rapport). Reste false : endpoints Groups répondent
    | 501 Not Implemented plutôt que de simuler un comportement partiel.
    */
    'scim' => [
        'default_page_size' => (int) env('SSO_SCIM_DEFAULT_PAGE_SIZE', 20),
        'max_page_size' => (int) env('SSO_SCIM_MAX_PAGE_SIZE', 100),
        'groups_enabled' => env('SSO_SCIM_GROUPS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting (OWASP — anti-abus)
    |--------------------------------------------------------------------------
    | Appliqué aux routes /sso/saml/acs et /scim/v2/* (voir routes/web.php et
    | routes/api.php). Format Laravel standard "tentatives,minutes".
    */
    'throttle' => env('SSO_THROTTLE', '60,1'),
];
