<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    // Cloudflare API (purge cache, etc.)
    'cloudflare' => [
        'zone_id'   => env('CLOUDFLARE_ZONE_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
    ],

    // Cloudflare Turnstile (S108 — anti-bot privacy-first newsletter + comments)
    'turnstile' => [
        'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY'),
        'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
    ],

    // Akismet (S108 backlog — anti-spam comments commercial fallback)
    'akismet' => [
        'key' => env('AKISMET_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI', '/auth/github/callback'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', '/auth/microsoft/callback'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', '/auth/linkedin/callback'),
    ],

    'x' => [
        'client_id' => env('X_CLIENT_ID'),
        'client_secret' => env('X_CLIENT_SECRET'),
        'redirect' => env('X_REDIRECT_URI', '/auth/x/callback'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI', '/auth/apple/callback'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'webhook_secret' => env('BREVO_WEBHOOK_SECRET'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        // Source de vérité UNIQUE de la cascade de modèles du résumé IA (Modules/News/AiSummaryService).
        // Ordre choisi le 2026-08-13 pour la protection des données : openai/gpt-4o-mini en tête
        // (politique de rétention publiée la plus protectrice parmi les modèles déjà utilisés,
        // fournisseur identifiable) ; deepseek/deepseek-chat en dernier recours seulement (sa
        // politique publiée admet une rétention sans durée bornée ET l'entraînement sur les
        // données reçues - jamais en tête). google/gemma-3-27b-it:free retiré : fournisseur
        // d'inférence non identifiable, donc politique invérifiable, incompatible avec la règle
        // "le texte source n'est jamais conservé".
        'summary_models' => [
            'openai/gpt-4o-mini',
            'deepseek/deepseek-chat',
        ],
        // Refus de collecte des données par le fournisseur (OpenRouter provider preferences),
        // valeur par défaut SÛRE = refus activé. https://openrouter.ai/docs/guides/routing/provider-selection
        'data_collection' => env('OPENROUTER_DATA_COLLECTION', 'deny'),
        // Mode sans rétention : ne route que vers des fournisseurs à politique Zero Data Retention.
        // Valeur par défaut SÛRE = activé. https://openrouter.ai/docs/guides/features/zdr
        'zdr' => env('OPENROUTER_ZDR', true),
        // Cascade de la traduction PAR LOT des titres (TranslationService::translateBatch),
        // distincte de celle du résumé : ici le modèle doit rendre un FORMAT (une ligne numérotée
        // par titre), pas seulement une bonne traduction. Pilotée par la configuration pour
        // pouvoir changer de modèle SANS redéploiement le jour où un fournisseur refuse la
        // rétention nulle - c'est ce refus, non journalisé, qui a immobilisé l'enrichissement de
        // l'annuaire pendant neuf jours (cf. CHANGELOG 1.206.0).
        //
        // ORDRE : le plus RAPIDE d'abord, mesuré. Le 2026-08-23, `openai/gpt-5` a expiré QUATRE
        // fois sur cette tâche, toujours de la même façon : « timed out after 45000 ms with 1166
        // bytes received » - la connexion s'établit, la génération commence, puis n'aboutit
        // jamais. Le placer en tête consommait tout le budget sans jamais laisser sa chance à un
        // modèle rapide, ce qui aurait éteint la fonction au lieu de la réparer. Traduire une
        // vingtaine de titres est une tâche triviale : elle ne justifie pas le modèle le plus
        // lourd, elle exige le plus prompt.
        'translation_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OPENROUTER_TRANSLATION_MODELS', 'openai/gpt-5-mini,deepseek/deepseek-v4-flash'))
        ))),

        // Budget TOTAL du lot de traduction, partagé entre les tentatives (cf. TranslationService).
        // 15 secondes : large pour un modèle prompt, très en deçà de la coupure de Cloudflare, et
        // tolérable sur un écran d'administration qui ne le paie que s'il a des titres étrangers.
        'translation_budget_seconds' => (int) env('OPENROUTER_TRANSLATION_BUDGET_SECONDS', 15),
    ],

    'browsershot' => [
        'node_path' => env('BROWSERSHOT_NODE_PATH'),
    ],

    'opengraph' => [
        'api_key' => env('OPENGRAPH_API_KEY'),
    ],

    'indexnow' => [
        'key'     => env('INDEXNOW_KEY', 'b79927568427fb2c3fe6a1c410f2c35b'),
        'enabled' => (bool) env('INDEXNOW_ENABLED', false),
    ],

    // Google AdSense (frontend — désactivé sur pages PII via @section('no_ads'))
    'adsense' => [
        'client_id' => env('ADSENSE_CLIENT_ID'),
    ],

    // Google Analytics (frontend — consent mode v2, gated privacy_enabled)
    'ga' => [
        'measurement_id'  => env('GA_MEASUREMENT_ID'),
        'privacy_enabled' => (bool) env('PRIVACY_GA_ENABLED', false),
    ],

    /*
     * Identité annoncée par nos requêtes SORTANTES vers des sites tiers.
     *
     * Source unique, parce que la même chaîne était déjà recopiée dans plusieurs services avec
     * QUATRE versions de Chrome différentes : une connaissance dupliquée finit toujours par
     * diverger. Configurable pour pouvoir la rafraîchir sans toucher au code.
     *
     * NE COUVRE PAS l'agent de GoogleFontService, qui est délibérément laissé à part : le sien
     * n'est pas une identité anti-robot mais un PARAMÈTRE FONCTIONNEL - c'est lui qui détermine
     * le format de police que Google renvoie. Deux chaînes qui se ressemblent, deux rôles
     * distincts : les fusionner serait la faute inverse de la duplication.
     */
    'http' => [
        'user_agent' => env(
            'HTTP_USER_AGENT',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
        ),
    ],

];
