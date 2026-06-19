<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Academy',

    /*
    |--------------------------------------------------------------------------
    | Mode « EN CONSTRUCTION » (go-live progressif)
    |--------------------------------------------------------------------------
    | Quand true (défaut), les pages publiques /academie/* affichent une page
    | sobre « bientôt disponible » à tout visiteur NON-superadmin ; le
    | superadmin voit le contenu réel (validation en prod avant lancement).
    | Mettre ACADEMY_UNDER_CONSTRUCTION=false dans le .env pour ouvrir au public.
    */
    'under_construction' => env('ACADEMY_UNDER_CONSTRUCTION', true),

    /*
    |--------------------------------------------------------------------------
    | Hébergeur vidéo autorisé (embed ScreenPal)
    |--------------------------------------------------------------------------
    | Domaine ajouté à la CSP frame-src des routes Academy.
    | Format : domaine uniquement, sans schéma ni slash final.
    | ScreenPal recommande de verrouiller les vidéos au domaine pour éviter
    | l'embed non autorisé. Voir section « Config ScreenPal » du rapport M3.
    */
    'video_embed_host' => env('ACADEMY_VIDEO_EMBED_HOST', 'screenpal.com'),

    /*
    |--------------------------------------------------------------------------
    | Domaine du site (pour frame-ancestors)
    |--------------------------------------------------------------------------
    | Autorise uniquement l'embed de ce site dans ses propres pages.
    */
    'site_host' => env('ACADEMY_SITE_HOST', 'laveille.ai'),
];
