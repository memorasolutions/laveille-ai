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
    | Tuteur IA — Fenêtre d'accès + quota (recommandation de veille juillet 2026)
    |--------------------------------------------------------------------------
    | Quand true, le Tuteur IA applique la fenêtre d'accès ET le quota mensuel
    | configurés PAR COURS (voir Course::ai_tutor_window_type et suivants,
    | éditables par le formateur dans l'éditeur de cours). Un rappel calme est
    | envoyé à J-<ai_tutor_reminder_days_before> et J-1 avant expiration,
    | précisant que SEUL le tuteur est coupé (le cours reste accessible).
    |
    | CRITIQUE (zéro effet rétroactif surprise) : la date d'expiration effective
    | d'un apprenant déjà inscrit est CALCULÉE ET FIGÉE à l'inscription — modifier
    | la config du cours après coup n'affecte QUE les nouvelles inscriptions.
    | Voir Services\TutorAccessService.
    |
    | Défaut false : comportement du Tuteur IA EXACTEMENT identique à aujourd'hui
    | (aucune vérification de fenêtre/quota, accès illimité tant qu'actif).
    | Activer via ACADEMY_AI_TUTOR_ACCESS_CONTROL_ENABLED=true dans le .env.
    */
    'ai_tutor_access_control_enabled' => env('ACADEMY_AI_TUTOR_ACCESS_CONTROL_ENABLED', false),

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
    | Traduction IA d'un champ de cours (formateur - brouillon éditable)
    |--------------------------------------------------------------------------
    | Quand true, l'éditeur de cours affiche un panneau « 🌐 Traduction IA » :
    | le formateur colle un texte (description, résumé...) → l'IA propose une
    | traduction → il relit et modifie l'aperçu → il VALIDE. AUCUNE écriture
    | automatique dans le cours : Course/Lesson/Chapter n'utilisent PAS Spatie
    | Translatable dans ce projet (pas de colonne JSON multi-langue), donc le
    | résultat reste un brouillon que le formateur copie lui-même où il en a
    | besoin (voir Modules\Academy\Services\AcademyTranslationAiService).
    | Défaut false : panneau absent, aucun appel IA, comportement inchangé.
    | Activer via ACADEMY_AI_TRANSLATION_ENABLED=true dans le .env.
    */
    'ai_translation_enabled' => env('ACADEMY_AI_TRANSLATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Narration TTS de la leçon (accessibilité - Web Speech API native)
    |--------------------------------------------------------------------------
    | Quand true, un bouton « 🔊 Écouter cette leçon » apparaît sur la page de
    | leçon (vue classique). Utilise EXCLUSIVEMENT l'API Web Speech native du
    | navigateur (window.speechSynthesis) côté client : zéro coût, zéro clé
    | API, aucun service tiers payant appelé. Voix française privilégiée si
    | disponible sur le poste de l'apprenant.
    | Défaut false : bouton absent, aucun script chargé.
    | Activer via ACADEMY_TTS_ENABLED=true dans le .env.
    */
    'tts_enabled' => env('ACADEMY_TTS_ENABLED', false),

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

    /*
    |--------------------------------------------------------------------------
    | Auto-création du lien Google Meet (phase 2 des séances en direct)
    |--------------------------------------------------------------------------
    | Quand true ET que des identifiants Google valides sont configurés
    | (voir Services\GoogleMeetService), le formateur peut cocher « Générer
    | automatiquement le lien Meet » lors de la création d'une séance
    | provider=meet : le lien est créé via l'API Google Calendar
    | (conferenceData hangoutsMeet) au lieu d'être collé manuellement.
    |
    | Défaut false : case absente, AUCUN appel Google, comportement du champ
    | join_url EXACTEMENT identique à aujourd'hui (saisie manuelle). Le repli
    | manuel reste TOUJOURS disponible même si le drapeau est actif (l'appel
    | API peut échouer silencieusement — voir GoogleMeetService::createMeetLink).
    | Activer via ACADEMY_GOOGLE_MEET_AUTOCREATE_ENABLED=true dans le .env.
    |
    | « service_account_json » : contenu JSON complet de la clé du compte de
    | service Google (Google Cloud Console > IAM > Comptes de service > Clés),
    | collé en une ligne dans GOOGLE_MEET_SERVICE_ACCOUNT_JSON. JAMAIS commité
    | en dur — variable d'environnement uniquement.
    |
    | « service_account_json_path » : alternative à la variable ci-dessus —
    | chemin absolu vers le fichier JSON sur le serveur (hors du dépôt git),
    | via GOOGLE_MEET_SERVICE_ACCOUNT_JSON_PATH. Utile si le fichier est
    | déposé hors-repo par l'hébergeur plutôt que collé dans le .env.
    |
    | « impersonate_user » : adresse courriel du compte Google Workspace au
    | nom duquel les événements sont créés (délégation domain-wide). Requis
    | pour que l'API génère un vrai lien Meet — un compte de service seul,
    | sans délégation, ne peut pas créer de conférence Meet.
    | Voir GOOGLE_MEET_IMPERSONATE_USER dans le .env.
    */
    'google_meet_autocreate_enabled' => env('ACADEMY_GOOGLE_MEET_AUTOCREATE_ENABLED', false),

    'google_meet_service_account_json' => env('GOOGLE_MEET_SERVICE_ACCOUNT_JSON'),

    'google_meet_service_account_json_path' => env('GOOGLE_MEET_SERVICE_ACCOUNT_JSON_PATH'),

    'google_meet_impersonate_user' => env('GOOGLE_MEET_IMPERSONATE_USER'),

    /*
    |--------------------------------------------------------------------------
    | Gamification moderne — XP, niveaux, classement PAR COHORTE (LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, chaque VRAIE action pédagogique (leçon complétée, quiz réussi,
    | cours complété) crédite un XP pondéré et documenté (voir
    | GamificationService), affiché dans « Mon espace » avec un niveau et un
    | classement scopé À LA COHORTE de l'apprenant (jamais un classement global).
    | Idempotent (une même action ne crédite jamais deux fois) et respectueux de
    | la Loi 25 QC : l'apprenant peut se retirer du classement à tout moment.
    | Défaut false : aucun crédit XP, aucun bloc affiché, aucun classement.
    | Activer via ACADEMY_GAMIFICATION_ENABLED=true dans le .env.
    */
    'gamification_enabled' => env('ACADEMY_GAMIFICATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | xAPI léger (couche de traçabilité pédagogique standard — dette F16)
    |--------------------------------------------------------------------------
    | Quand true, chaque événement pédagogique DÉJÀ enregistré ailleurs (leçon
    | complétée, cours complété, tentative de quiz soumise, XP crédité, carte
    | SRS révisée, présence à une séance en direct) est EN PLUS reformaté en un
    | statement standard actor-verb-object (voir Services\XapiRecorderService)
    | et journalisé dans academy_xapi_statements. AUCUNE donnée métier n'est
    | dupliquée : c'est un REFORMATTAGE de lecture, fondation pour un futur
    | tuteur IA / graphe de compétences (pas d'UI aujourd'hui).
    | Défaut false : aucun statement créé, zéro requête SQL supplémentaire,
    | comportement actuel inchangé.
    | Activer via ACADEMY_XAPI_ENABLED=true dans le .env.
    */
    'xapi_enabled' => env('ACADEMY_XAPI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Test de positionnement adaptatif (CAT — Computer Adaptive Testing)
    |--------------------------------------------------------------------------
    | Quand true, un apprenant inscrit peut passer un test court (≈5-8 questions
    | de la banque du cours) qui s'ajuste en temps réel à sa performance (plus
    | difficile après 2 bonnes réponses consécutives, plus facile après 2
    | mauvaises) puis recommande une leçon de départ (évite de relire le
    | déjà-su). Jamais bloquant : l'apprenant garde toujours le choix d'y aller
    | ou de commencer au début. Voir AdaptivePlacementService.
    | Défaut false : bouton absent, route 404, aucun calcul effectué.
    | Activer via ACADEMY_PLACEMENT_TEST_ENABLED=true dans le .env.
    */
    'placement_test_enabled' => env('ACADEMY_PLACEMENT_TEST_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Éditeur de gabarits de diplômes (Phase 1 — système de diplomation moderne)
    |--------------------------------------------------------------------------
    | Quand true, un formateur (ou l'admin) peut créer/éditer des gabarits de
    | diplôme personnalisés via un éditeur visuel Konva.js (positionnement des
    | éléments — logo, nom, titre du cours, date, signature, QR, texte libre —
    | en pourcentages, portable entre écrans/impressions). Le RENDU FINAL reste
    | en HTML/CSS (DiplomaRenderService), jamais en canvas ; le PDF réutilise le
    | même moteur dompdf que CertificateController (aucun 2e pipeline). Le QR
    | encode la MÊME URL de vérification publique que le certificat existant
    | (academy.certificates.show) — un seul système de confiance, jamais deux.
    | Défaut false : route/actions de l'éditeur 404, le système de certificat
    | existant (gabarit unique actuel) continue de fonctionner à l'identique.
    | Activer via ACADEMY_DIPLOMA_EDITOR_ENABLED=true dans le .env.
    */
    'diploma_editor_enabled' => env('ACADEMY_DIPLOMA_EDITOR_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Discussion sociale structurée par vidéo (LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, un item de leçon « video » porte son propre forum (réutilise
    | intégralement ForumTopic/ForumPost/ForumController/ForumService, aucun
    | nouveau système) : un apprenant peut poser une question/commentaire ancré
    | à un instant précis de la vidéo (« à 2:34, je ne comprends pas... »),
    | affiché en badge horodaté cliquable sous chaque sujet/réponse concerné.
    | Le fil peut être trié par ordre de publication (défaut) ou par ordre du
    | contenu vidéo. Voir ForumService::parseTimestamp()/formatTimestamp().
    | Défaut false : aucun champ horodatage affiché, comportement du forum
    | inchangé, un item « video » reste 404 sur les routes forum (rétrocompat).
    | Activer via ACADEMY_VIDEO_DISCUSSION_ENABLED=true dans le .env.
    */
    'video_discussion_enabled' => env('ACADEMY_VIDEO_DISCUSSION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Paliers d'abonnement (freemium/pro/organisation — LMS 2026)
    |--------------------------------------------------------------------------
    | Quand true, l'accès à certaines fonctionnalités Academy est EN PLUS filtré
    | PAR PALIER D'ABONNEMENT de l'utilisateur courant (voir Services\TierGateService,
    | Models\SubscriptionTier, /admin/academy/subscription-tiers). Un palier ne
    | peut JAMAIS réactiver une fonctionnalité dont le drapeau global
    | academy.*_enabled ci-dessus est désactivé : il ne fait que restreindre
    | DAVANTAGE un ensemble déjà actif (gating par utilisateur, pas un 2e
    | interrupteur global).
    | Défaut false : aucun gating par palier, comportement actuel inchangé (tout
    | ce que les drapeaux globaux débloquent reste accessible à tous, comme
    | aujourd'hui). Activer via ACADEMY_SUBSCRIPTION_TIERS_ENABLED=true.
    |
    | ZÉRO STRIPE : les prix des paliers sont des données NEUTRES saisies par
    | l'admin (`price_cents`), jamais un produit/prix Stripe créé par le code.
    | `stripe_price_id` reste NULL tant que l'admin ne colle pas manuellement
    | l'identifiant d'un vrai prix créé dans le Dashboard Stripe.
    |
    | « feature_keys » : liste CANONIQUE des clés assignables à un palier depuis
    | l'admin — chaque clé correspond à une fonctionnalité Academy existante
    | (documentée par son propre drapeau global ci-dessus). Source unique de
    | vérité pour le formulaire admin + la validation (jamais de clé libre).
    */
    'subscription_tiers_enabled' => env('ACADEMY_SUBSCRIPTION_TIERS_ENABLED', false),

    'subscription_tier_feature_keys' => [
        'academy_ai_tutor'             => "Tuteur IA ancré au cours",
        'academy_ai_feedback'          => "Feedback IA sur réponses ouvertes",
        'academy_ai_authoring'         => "Authoring IA (plan de cours + questions)",
        'academy_ai_translation'       => "Traduction IA d'un champ de cours",
        'academy_tts'                  => "Narration TTS de la leçon",
        'academy_predictive_analytics' => "Analytiques prédictifs (score de risque)",
        'academy_srs'                  => "Répétition espacée (SRS)",
        'academy_nudges'               => "Nudges comportementaux",
        'academy_open_badges'          => "Open Badges 3.0 vérifiables",
        'academy_live_sessions'        => "Séances en direct / visioconférence",
        'academy_gamification'         => "Gamification (XP, niveaux, classement)",
        'academy_placement_test'       => "Test de positionnement adaptatif",
        'academy_video_discussion'     => "Discussion sociale par vidéo",
    ],

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
            // Tuteur IA — rappel calme avant expiration de la fenêtre d'accès (J-7/J-1).
            'ai_tutor_access_reminder' => true,
            // Préférence unique gouvernant tous les nudges (opt-in raisonnable, opt-out clair).
            'nudge'            => true,
            // Gamification (LMS 2026) : opt-in raisonnable — visible par défaut dans le
            // classement de sa cohorte, retrait possible à tout moment (Loi 25 QC).
            'gamification_leaderboard' => true,
        ],

        // Fenêtre des rappels de séance en direct (en heures avant le début).
        'live_reminder_window_hours' => env('ACADEMY_LIVE_REMINDER_WINDOW_HOURS', 24),

        // Fenêtre des rappels d'échéance (en jours avant l'échéance).
        'reminder_window_days' => env('ACADEMY_REMINDER_WINDOW_DAYS', 2),
    ],
];
