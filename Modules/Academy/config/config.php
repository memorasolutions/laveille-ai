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
    | Authoring IA (génération de plan de cours et de questions — LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, l'éditeur de cours affiche un panneau « ✨ Authoring IA » :
    | le formateur entre un prompt → l'IA génère un plan structuré (chapitres +
    | leçons) ou des questions QCM → il relit l'aperçu → confirme l'insertion
    | en BROUILLON (jamais de publication automatique).
    | Défaut false : panneau absent, aucun appel IA, comportement inchangé.
    | Activer via ACADEMY_AI_AUTHORING_ENABLED=true dans le .env.
    */
    'ai_authoring_enabled' => env('ACADEMY_AI_AUTHORING_ENABLED', false),

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
    /*
    |--------------------------------------------------------------------------
    | Analytiques prédictifs (score de risque d'abandon)
    |--------------------------------------------------------------------------
    | Quand true, le RiskScoreService est actif : les formateurs voient les
    | scores de risque de leurs apprenants, les étudiants voient un bandeau
    | d'encouragement personnalisé, et l'admin accède au tableau de bord org.
    | Défaut false : aucune donnée prédictive n'est affichée ou calculée.
    | Activer via ACADEMY_PREDICTIVE_ANALYTICS_ENABLED=true dans le .env.
    |
    | Note Loi 25 QC : le score sert l'accompagnement pédagogique uniquement.
    | Visible seulement au formateur/admin du cours et à l'apprenant lui-même.
    */
    'predictive_analytics_enabled' => env('ACADEMY_PREDICTIVE_ANALYTICS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Répétition espacée native (SRS - différenciateur rétention LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, à la complétion d'une leçon des cartes de révision (concepts
    | et mini-quiz) sont créées pour l'apprenant, reprogrammées au moment
    | optimal par l'algorithme SM-2. Un bouton « Réviser (N cartes) » apparaît
    | dans l'espace, et la relance planifiée « academy:srs-remind » notifie les
    | apprenants ayant des cartes dues (via l'interrupteur maître des notifs).
    | Défaut false : aucune carte créée, aucun bouton, commande no-op.
    | Activer via ACADEMY_SRS_ENABLED=true dans le .env.
    */
    'srs_enabled' => env('ACADEMY_SRS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Nudges comportementaux (relances intelligentes - LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, la commande planifiée « academy:nudge » parcourt chaque jour les
    | inscrits ACTIFS et envoie, au plus, UNE relance bienveillante par apprenant et
    | par jour (tous types confondus), choisie selon son COMPORTEMENT réel (jalon
    | franchi, révisions à reprendre, inactivité) via RiskScoreService. Jamais
    | culpabilisant, toujours un deeplink vers la bonne action.
    | DOUBLE garde : ce drapeau ET l'interrupteur maître des notifications. Chaque
    | envoi respecte l'opt-out unique « nudge » de l'apprenant (Loi 25 QC / LCAP).
    | Défaut false : commande no-op, aucun envoi.
    | Activer via ACADEMY_NUDGES_ENABLED=true dans le .env.
    */
    'nudges_enabled' => env('ACADEMY_NUDGES_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | D09 — Open Badges 3.0 vérifiables (standard 1EdTech)
    |--------------------------------------------------------------------------
    | Quand true, les endpoints publics /academie/badges/{serial}[/assertion]
    | et /academie/badges/user-badge/{id}/assertion sont actifs. Chaque
    | certificat ou badge gagné expose une assertion JSON-LD conforme OB 3.0 /
    | Verifiable Credentials (W3C + 1EdTech), avec identifiant apprenant
    | pseudonyme (HMAC-SHA256, Loi 25 QC / RGPD).
    | OFF (défaut) → routes 404, aucun bouton « Badge vérifiable » affiché.
    | Activer via ACADEMY_OPEN_BADGES_ENABLED=true dans le .env.
    */
    'open_badges_enabled' => env('ACADEMY_OPEN_BADGES_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Séances en direct / visioconférence natives (capacité LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, un formateur peut planifier des séances en direct rattachées
    | à un cours (date/heure Québec + lien Zoom/Teams/Google Meet) depuis
    | l'éditeur de cours ; les inscrits actifs les voient sur la page du cours,
    | les rejoignent (la présence est enregistrée), et peuvent les ajouter à
    | leur calendrier (.ics). La relance planifiée « academy:live-remind »
    | rappelle les séances imminentes (via l'interrupteur maître des notifs).
    |
    | MVP « par lien » : le formateur colle l'URL de sa réunion (fournisseur
    | par défaut = Google Meet). Architecture extensible à l'API plus tard
    | (auto-création du lien Meet via Google Calendar API — phase 2).
    | Défaut false : aucun onglet, routes/actions 404, commande no-op.
    | Activer via ACADEMY_LIVE_SESSIONS_ENABLED=true dans le .env.
    */
    'live_sessions_enabled' => env('ACADEMY_LIVE_SESSIONS_ENABLED', false),

    'notifications' => [
        'enabled' => env('ACADEMY_NOTIFICATIONS_ENABLED', false),

        'defaults' => [
            'announcement'     => true,
            'forum_reply'      => true,
            'graded'           => true,
            'course_completed' => true,
            'due_reminder'     => true,
            'srs_reminder'     => true,
            'live_reminder'    => true,
            // Préférence unique gouvernant tous les nudges (opt-in raisonnable, opt-out clair).
            'nudge'            => true,
        ],

        // Fenêtre des rappels de séance en direct (en heures avant le début).
        'live_reminder_window_hours' => env('ACADEMY_LIVE_REMINDER_WINDOW_HOURS', 24),

        // Fenêtre des rappels d'échéance (en jours avant l'échéance).
        'reminder_window_days' => env('ACADEMY_REMINDER_WINDOW_DAYS', 2),
    ],
];
