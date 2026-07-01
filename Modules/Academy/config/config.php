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
    | Tuteur IA ancré au cours (différenciateur LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, un panneau conversationnel flottant apparaît sur la page de
    | leçon pour les apprenants inscrits (et le staff). Le tuteur répond
    | UNIQUEMENT à partir du contenu de la leçon (RAG), en français québécois.
    | Défaut false : rien ne s'affiche, aucun appel IA n'est effectué.
    | Activer via ACADEMY_AI_TUTOR_ENABLED=true dans le .env.
    */
    'ai_tutor_enabled' => env('ACADEMY_AI_TUTOR_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Feedback IA sur réponses ouvertes (correction assistée - LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, le formateur voit un bouton « Proposer un feedback IA » sur
    | la correction des remises rédigées (devoirs/études de cas). L'IA évalue
    | la remise SELON LA RUBRIQUE et propose un feedback + une note SUGGÉRÉE,
    | en BROUILLON ÉDITABLE. Le formateur garde TOUJOURS le dernier mot :
    | l'IA ne note JAMAIS l'apprenant automatiquement (proposition seulement).
    | Défaut false : bouton absent, aucun appel IA, comportement inchangé.
    | Activer via ACADEMY_AI_FEEDBACK_ENABLED=true dans le .env.
    */
    'ai_feedback_enabled' => env('ACADEMY_AI_FEEDBACK_ENABLED', false),

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
    | Mode DeckPlayer (présentation en cartes plein écran, zéro défilement)
    |--------------------------------------------------------------------------
    | Quand false (défaut), la leçon s'affiche sous forme de longue page
    | déroulante (comportement historique, inchangé).
    | Quand true, chaque item de leçon est affiché comme une carte plein écran
    | navigable clavier + boutons (DeckPlayer Livewire).
    | Activer via ACADEMY_LESSON_DECK_MODE=true dans le .env.
    */
    'lesson_deck_mode' => env('ACADEMY_LESSON_DECK_MODE', false),

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
    | F16 - Contenu interactif H5P (player h5p-standalone via CDN)
    |--------------------------------------------------------------------------
    | Le paquet .h5p (zip) est extrait sur le disque public (non exécutable) et
    | rendu DANS UN IFRAME SANDBOX par h5p-standalone chargé depuis jsdelivr.
    | Aucune dépendance composer/npm : tout vient du CDN (le CI ne build pas).
    |
    | « cdn_base » : base jsdelivr du player (versionnée, sans slash final).
    | Le bundle principal, le bundle de cadre et la feuille de style en
    | découlent (dist/main.bundle.js, dist/frame.bundle.js, dist/styles/h5p.css).
    |
    | « cdn_version » : version du paquet h5p-standalone (centralisée pour les MAJ).
    | ATTENTION : changer cette version (et « cdn_base ») IMPOSE de recalculer les
    | empreintes SRI ci-dessous (« sri ») sinon le navigateur refusera de charger
    | les ressources. Recalcul :
    |   curl -sL <cdn_base>/dist/main.bundle.js | openssl dgst -sha384 -binary | openssl base64 -A
    |   curl -sL <cdn_base>/dist/styles/h5p.css | openssl dgst -sha384 -binary | openssl base64 -A
    |
    | « sri » : empreintes Sub-Resource Integrity (sha384) des 2 ressources CDN
    | chargées par la page player (intégrité : un CDN compromis ne peut pas servir
    | un script altéré). Vides => pas d'attribut integrity (repli sûr, rétrocompat).
    |
    | Bornes de sécurité de l'extraction d'un paquet .h5p (zip) :
    |   - « max_kb » : taille maximale du fichier COMPRESSÉ téléversé (défaut 30 Mo) ;
    |   - « max_entries » : nombre maximal d'entrées dans le zip (anti zip-bomb) ;
    |   - « max_extract_kb » : taille totale maximale une fois DÉCOMPRESSÉ (anti
    |     zip-bomb : un petit zip peut se décompresser en plusieurs Go).
    */
    'h5p' => [
        'cdn_base'    => env('ACADEMY_H5P_CDN_BASE', 'https://cdn.jsdelivr.net/npm/h5p-standalone@3.8.0'),
        'cdn_host'    => env('ACADEMY_H5P_CDN_HOST', 'cdn.jsdelivr.net'),
        'cdn_version' => env('ACADEMY_H5P_CDN_VERSION', '3.8.0'),

        // Empreintes SRI calculées pour h5p-standalone@3.8.0 (recalculer si MAJ).
        'sri' => [
            'main_js' => env('ACADEMY_H5P_SRI_MAIN', 'sha384-ujIyUnJaNPyNEo9Su9WLZLEVYcvmS5I0bIBH6gWAZFxNoKUZJXOWE5R8+VpuPLoy'),
            'css'     => env('ACADEMY_H5P_SRI_CSS', 'sha384-UmBzFWmi/UaVsCAkcnsIw7fNDru/FEInhq70Np+tRj7EkyrX+KGqj6wQUfGJnWQu'),
        ],

        'max_kb'         => (int) env('ACADEMY_H5P_MAX_KB', 30720),       // 30 Mo (compressé)
        'max_entries'    => (int) env('ACADEMY_H5P_MAX_ENTRIES', 5000),   // nb d'entrées du zip
        'max_extract_kb' => (int) env('ACADEMY_H5P_MAX_EXTRACT_KB', 204800), // 200 Mo (décompressé)
    ],

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
