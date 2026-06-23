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

    /*
    |--------------------------------------------------------------------------
    | Notifications courriel d'activité (V5-c, parité Moodle)
    |--------------------------------------------------------------------------
    | INTERRUPTEUR MAITRE - défaut FALSE.
    |
    | Tant que « enabled » = false, AUCUN courriel de notification n'est envoyé :
    | les notifications sont préparées et journalisées (log info) mais jamais
    | transmises à Brevo. Cela évite tout envoi prématuré pendant que l'Académie
    | est « en construction » (aucun vrai étudiant). À l'ouverture publique,
    | poser ACADEMY_NOTIFICATIONS_ENABLED=true dans le .env de production.
    |
    | Cet interrupteur est vérifié au point d'envoi UNIQUE
    | (AcademyNotificationService::send) ainsi qu'en tête de chaque méthode
    | publique et de la commande de rappels : aucun chemin ne peut le contourner.
    |
    | « defaults » : préférence par défaut par type quand l'utilisateur n'a rien
    | choisi (opt-in raisonnable - les notifications transactionnelles importantes
    | sont activées). L'utilisateur peut désactiver chaque type depuis son espace.
    */
    'notifications' => [
        'enabled' => env('ACADEMY_NOTIFICATIONS_ENABLED', false),

        'defaults' => [
            'announcement'     => true,
            'forum_reply'      => true,
            'graded'           => true,
            'course_completed' => true,
            'due_reminder'     => true,
        ],

        // Fenêtre des rappels d'échéance (en jours avant l'échéance).
        'reminder_window_days' => env('ACADEMY_REMINDER_WINDOW_DAYS', 2),
    ],
];
