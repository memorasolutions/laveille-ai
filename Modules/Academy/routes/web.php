<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Academy\Http\Controllers\AcademyController;
use Modules\Academy\Http\Controllers\OpenBadgeController;
use Modules\Academy\Http\Controllers\CalendarController;
use Modules\Academy\Http\Controllers\CertificateController;
use Modules\Academy\Http\Controllers\CompletionController;
use Modules\Academy\Http\Controllers\DatabaseController;
use Modules\Academy\Http\Controllers\CourseBackupController;
use Modules\Academy\Http\Controllers\CourseReportController;
use Modules\Academy\Http\Controllers\ChoiceController;
use Modules\Academy\Http\Controllers\FeedbackController;
use Modules\Academy\Http\Controllers\ForumController;
use Modules\Academy\Http\Controllers\H5pPlayerController;
use Modules\Academy\Http\Controllers\ItemEngagementController;
use Modules\Academy\Http\Controllers\KioskController;
use Modules\Academy\Http\Controllers\CourseController;
use Modules\Academy\Http\Controllers\EnrollmentController;
use Modules\Academy\Http\Controllers\ExportController;
use Modules\Academy\Http\Controllers\LessonController;
use Modules\Academy\Http\Controllers\LiveSessionController;
use Modules\Academy\Http\Controllers\LtiLaunchController;
use Modules\Academy\Http\Controllers\PurchaseController;
use Modules\Academy\Http\Controllers\QuizController;
use Modules\Academy\Http\Controllers\VideoRedirectController;
use Modules\Academy\Http\Controllers\WikiController;
use Modules\Academy\Http\Controllers\WorkshopController;
use Modules\Academy\Http\Middleware\AcademyCsp;
use Modules\Academy\Http\Middleware\AcademyUnderConstruction;

Route::prefix('academie')->name('academy.')->middleware(AcademyCsp::class)->group(function () {
    // M2 — Pages publiques (gâtées « en construction » : superadmin uniquement tant que le flag est actif)
    Route::middleware(AcademyUnderConstruction::class)->group(function () {
        Route::get('/', [AcademyController::class, 'index'])->name('index');
        Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

        // M3 — Lecteur de leçon. Réconciliation B01 + BUG-001 (2026-07-01) : le middleware
        // `auth` GLOBAL a été retiré (il bloquait aussi les cours PUBLIC, contraire à
        // l'objectif SEO/GEO du site). L'accès est désormais décidé DANS LessonController@show,
        // avec la MÊME condition de visibilité que CourseController@show (BUG-002) :
        // `public` = accessible à tous (y compris anonymes) ; `private`/`unlisted` = un
        // anonyme est redirigé vers la connexion (`redirect()->guest(route('login'))`,
        // la vraie faille corrigée par B01), un connecté non-inscrit/non-staff reçoit 404.
        Route::get('courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'show'])
            ->name('lessons.show');

        // Consumer LTI 1.3 minimal (Academy branche des outils EXTERNES, jamais
        // l'inverse). Connexion requise ; l'item est re-résolu et ré-autorisé
        // intégralement côté serveur dans LtiLaunchController (anti-IDOR, même
        // pattern DRY que le proxy vidéo signé / H5P via LessonAccessService).
        Route::get('courses/{course:slug}/lessons/{lesson}/lti/{item}/launch', [LtiLaunchController::class, 'launch'])
            ->whereNumber('item')
            ->middleware('auth')
            ->name('lti.launch');

        // PHASE 2 - Espace personnel front-end UNIQUE et role-aware (connexion requise).
        // Gâté comme le reste (AcademyUnderConstruction) + `auth`. Le contenu est
        // adapté au rôle par le composant Livewire (requêtes scopées au user).
        Route::get('espace', fn () => view('academy::public.dashboard'))
            ->middleware('auth')
            ->name('dashboard');

        // SRS - Session de révision espacée (répétition espacée native, LMS 2026).
        // Connexion requise ; le composant Livewire SrsReviewer abort 404 si le
        // drapeau academy.srs_enabled est désactivé et ne révise QUE les cartes de
        // auth()->user() (scope serveur, anti-IDOR). Deeplink des courriels de relance.
        Route::get('espace/reviser', fn () => view('academy::public.srs-review'))
            ->middleware('auth')
            ->name('srs.review');

        // CAT — Test de positionnement adaptatif (recommande une leçon de départ).
        // Connexion requise ; le composant Livewire PlacementTest::mount() abort 404
        // si le drapeau academy.placement_test_enabled est désactivé et abort 403 si
        // l'apprenant n'est pas inscrit ACTIF à ce cours (anti-IDOR). Le cours est
        // re-résolu côté serveur (binding par slug), jamais fait confiance au client.
        Route::get('courses/{course:slug}/positionnement', function (\Modules\Academy\Models\Course $course) {
            return view('academy::public.placement-test', ['course' => $course]);
        })
            ->middleware('auth')
            ->name('placement.show');

        // V5-c - Préférences de notification courriel (opt-in/opt-out par type).
        // Connexion requise ; le composant Livewire n'agit que sur auth()->user()
        // (jamais un id du client). Lien présent dans chaque courriel (Loi 25 / LCAP).
        Route::get('notifications', fn () => view('academy::public.notification-preferences'))
            ->middleware('auth')
            ->name('notifications.preferences');

        // D02 - Interrupteur MAÎTRE des notifications (admin), pilotable sans .env.
        // Double garde : middleware can:academy.manage ICI + ré-autorisation dans le
        // composant Livewire à chaque action (anti-IDOR). Le réglage est persisté en
        // table settings (clé academy_notifications_enabled), avec repli .env si le
        // module Settings est absent.
        Route::get('notifications/admin', fn () => view('academy::public.notification-master-switch'))
            ->middleware(['auth', 'can:academy.manage'])
            ->name('notifications.master');

        // Messagerie directe (DM) formateur <-> apprenant (parité Moodle, LMS 2026).
        // Connexion requise ; chaque composant Livewire re-vérifie
        // config('academy.direct_messaging_enabled') (404 si désactivé) ET que
        // l'utilisateur est bien participant de la conversation demandée
        // (abort_if anti-IDOR, pattern Modules/Authors — voir ConversationThread::mount()).
        // Déclarées AVANT les routes wildcard courses/{course:slug} par précaution,
        // même si le préfixe courses/ ne peut normalement pas entrer en collision.
        Route::get('messages', fn () => view('academy::public.messages-index'))
            ->middleware('auth')
            ->name('messages.index');

        Route::get('messages/nouveau', fn () => view('academy::public.messages-new'))
            ->middleware('auth')
            ->name('messages.new');

        Route::get('messages/{conversation}', function (\Modules\Academy\Models\DirectMessageConversation $conversation) {
            abort_if(! $conversation->hasParticipant(auth()->user()), 403);

            return view('academy::public.messages-show', ['conversation' => $conversation]);
        })
            ->middleware('auth')
            ->name('messages.show');

        // PHASE 5 (FE-5) - Création de cours front-end.
        // Connexion requise ; l'autorisation d'entrée (create) vit dans
        // CourseCreate::mount() via $this->authorize('create', Course::class)
        // et est RÉ-AUTORISÉE à la création (jamais de confiance au navigateur).
        // Déclarée AVANT les routes wildcard courses/{course:slug} pour que
        // « creer » ne soit jamais capté comme un slug de cours.
        Route::get('creer', fn () => view('academy::public.course-create'))
            ->middleware('auth')
            ->name('courses.create');

        // F15 - Import / restauration d'un cours depuis une sauvegarde .json.
        // Connexion requise ; l'autorisation d'entrée (create) vit dans
        // CourseImport::mount() et est RÉ-AUTORISÉE à l'import. Déclarée AVANT les
        // routes wildcard courses/{course:slug} pour que « importer » ne soit jamais
        // capté comme un slug de cours.
        Route::get('importer', fn () => view('academy::public.course-import'))
            ->middleware('auth')
            ->name('courses.import');

        // QB2 - Éditeur de la BANQUE DE QUESTIONS réutilisable (owner-scoped).
        // Connexion requise ; l'autorisation d'entrée (instructor/admin) vit dans
        // QuestionBankManager::mount() (abort 403 sinon) et chaque mutation est
        // ré-autorisée + owner-scopée côté serveur (anti-IDOR). Déclarée AVANT les
        // routes wildcard courses/{course:slug} pour que « banque » ne soit jamais
        // capté comme un slug de cours.
        Route::get('banque', fn () => view('academy::public.question-bank'))
            ->middleware('auth')
            ->name('questions.bank');

        // F22 - RÉFÉRENTIEL de COMPÉTENCES (résultats / outcomes), owner-scopé.
        // Connexion requise ; l'autorisation d'entrée (instructor/admin) vit dans
        // CompetencyManager::mount() (abort 403 sinon) et chaque mutation est
        // ré-autorisée + owner-scopée côté serveur (anti-IDOR). Déclarée AVANT les
        // routes wildcard courses/{course:slug} pour que « competences » ne soit
        // jamais capté comme un slug de cours.
        Route::get('competences', fn () => view('academy::public.competencies'))
            ->middleware('auth')
            ->name('competencies');

        // Phase 1 — Éditeur de gabarits de diplômes (Konva.js, drapeau
        // academy.diploma_editor_enabled). Connexion requise ; l'autorisation
        // d'entrée (academy.manage OU rôle instructor) ET le drapeau vivent dans
        // DiplomaTemplateEditor::mount() (abort 404/403), owner-scopé (anti-IDOR).
        // Déclarée AVANT les routes wildcard courses/{course:slug} pour que
        // « diplomes » ne soit jamais capté comme un slug de cours.
        Route::get('diplomes/gabarits/{templateId?}', function (?int $templateId = null) {
            return view('academy::public.diploma-template-editor', ['templateId' => $templateId]);
        })
            ->whereNumber('templateId')
            ->middleware('auth')
            ->name('diplomas.templates.editor');

        // PHASE 3 (FE-3) - Éditeur de cours front-end (« mode édition »).
        // Connexion requise ; le cours est re-résolu côté serveur (binding par slug)
        // puis ré-autorisé À CHAQUE action par le composant Livewire (jamais de
        // confiance à un ID/état du navigateur). L'autorisation d'entrée vit dans
        // CourseEditor::mount() via $this->authorize('update', $course).
        Route::get('courses/{course:slug}/gerer', function (\Modules\Academy\Models\Course $course) {
            return view('academy::public.course-editor', ['course' => $course]);
        })
            ->middleware('auth')
            ->name('courses.manage');

        // F15 - Téléchargement de la sauvegarde (.json) d'un cours. Connexion requise ;
        // le cours est re-résolu serveur puis autorisé (manageStructure) dans le
        // contrôleur (anti-IDOR). Aucune donnée personnelle d'étudiant n'est exportée.
        Route::get('courses/{course:slug}/exporter', [CourseBackupController::class, 'export'])
            // throttle:20,1 = anti-abus (téléchargement de fichier généré, même plafond
            // que les autres GET à génération dynamique : calendrier iCal, exports CSV).
            ->middleware(['auth', 'throttle:20,1'])
            ->name('courses.export');

        // Séances en direct - export iCalendar (.ics) « Ajouter à mon calendrier ».
        // Connexion requise ; accès gâté serveur (drapeau live_sessions_enabled +
        // inscrit actif OU staff du cours) dans le contrôleur (anti-IDOR). throttle:20,1
        // = même plafond anti-abus que les autres GET à génération dynamique.
        Route::get('courses/{course:slug}/live/{session}/ics', [LiveSessionController::class, 'ics'])
            ->middleware(['auth', 'throttle:20,1'])
            ->whereNumber('session')
            ->name('courses.live.ics');

        // PHASE D (D1) - Tableau de bord d'analytics PAR COURS (pilotage).
        // Connexion requise ; le cours est re-résolu côté serveur (binding par slug)
        // puis ré-autorisé par le composant Livewire (authorize('manageEnrollments',
        // $course) = admin OU owner/instructor) à chaque rendu. Lecture seule, scopé
        // au cours (anti-IDOR) : aucune donnée d'un autre cours ne fuit.
        Route::get('courses/{course:slug}/analytics', function (\Modules\Academy\Models\Course $course) {
            return view('academy::public.course-analytics', ['course' => $course]);
        })
            ->middleware('auth')
            ->name('courses.analytics');

        // F23 - Rapports et journaux PAR COURS (participation + journal d'activité).
        // Connexion requise ; le cours est re-résolu côté serveur (binding par slug)
        // puis ré-autorisé par le composant Livewire (manageEnrollments = admin OU
        // owner/instructor) à chaque rendu. Lecture seule, scopé au cours (anti-IDOR).
        Route::get('courses/{course:slug}/rapports', function (\Modules\Academy\Models\Course $course) {
            return view('academy::public.course-reports', ['course' => $course]);
        })
            ->middleware('auth')
            ->name('courses.reports');

        // F23 - Export CSV du rapport de participation. Re-résolu + autorisé serveur
        // dans le contrôleur (anti-IDOR). throttle = même plafond que les autres GET
        // à génération dynamique (analytics export, calendrier iCal).
        Route::get('courses/{course:slug}/rapports/participation.csv', [CourseReportController::class, 'participationCsv'])
            ->middleware(['auth', 'throttle:20,1'])
            ->name('courses.reports.participation.csv');

        // Analytiques prédictifs — vue organisationnelle (admin uniquement).
        // Gate `academy.manage` + drapeau `predictive_analytics_enabled` vérifiés dans
        // OrgAnalyticsDashboard::mount() (lecture seule, anti-IDOR).
        Route::get('admin/analytiques', function () {
            if (! config('academy.predictive_analytics_enabled', false)) {
                return view('academy::public.dashboard'); // redirige vers espace si off
            }

            return view('academy::public.org-analytics-dashboard');
        })
            ->middleware(['auth', 'can:academy.manage'])
            ->name('admin.org-analytics');

        // V5-b - Calendrier d'echeances par cours (etudiant inscrit OU gerant).
        // Autorisation verifiee par CourseCalendar::mount() (inscription active
        // OU manageStructure). Lecture seule pour l'etudiant, CRUD pour le gerant.
        Route::get('courses/{course:slug}/calendrier', function (\Modules\Academy\Models\Course $course) {
            return view('academy::public.course-calendar', ['course' => $course]);
        })
            ->middleware('auth')
            ->name('courses.calendar');
    });

    // Consumer LTI 1.3 — retour OIDC (form_post) de l'outil externe. Pas d'auth :
    // l'appelant est l'outil tiers, pas l'apprenant connecté ; la sécurité vient
    // du state/nonce à usage unique et de la validation du jeton (LtiLaunchService).
    // throttle:30,1 = anti-abus (même plafond que les autres GET/POST publics à
    // génération dynamique du module).
    Route::match(['get', 'post'], 'lti/callback', [LtiLaunchController::class, 'callback'])
        ->middleware('throttle:30,1')
        ->name('lti.callback');

    // D09 — OpenBadge 3.0 vérifiables (public, pas d'auth, gâtés par academy.open_badges_enabled).
    // Route user-badge AVANT {serial} pour éviter que « user-badge » soit capté comme serial.
    Route::get('badges/user-badge/{id}/assertion', [OpenBadgeController::class, 'userBadgeAssertion'])
        ->middleware('throttle:60,1')
        ->name('badges.user-badge-assertion');
    Route::get('badges/{serial}/assertion', [OpenBadgeController::class, 'assertion'])
        ->middleware('throttle:60,1')
        ->name('badges.assertion');
    Route::get('badges/{serial}', [OpenBadgeController::class, 'show'])
        ->name('badges.show');

    // M6 — Certificats publics vérifiables (pas d'auth requise)
    Route::get('certificats/{public_url_slug}', [CertificateController::class, 'show'])->name('certificates.show');

    // D11 — Téléchargement PDF du certificat (parité Moodle). Public et vérifiable
    // comme la page HTML ; throttle:30,1 = anti-abus (GET qui génère un PDF). Repli
    // automatique vers la page HTML si dompdf est absent (géré dans le contrôleur).
    Route::get('certificats/{public_url_slug}/pdf', [CertificateController::class, 'download'])
        ->middleware('throttle:30,1')
        ->name('certificates.download');

    // V5-b — Export iCal du calendrier d'un cours. Auth requis ; acces gate dans
    // CalendarController::ical() : inscrit actif OU manageStructure. throttle:20,1
    // = anti-abus (GET qui genere un fichier). Declare hors AcademyUnderConstruction
    // pour etre accessible meme en mode maintenance (meme pattern que les certificats).
    Route::get('courses/{course:slug}/calendrier.ics', [CalendarController::class, 'ical'])
        ->middleware(['auth', 'throttle:20,1'])
        ->name('courses.calendar.ical');

    // M1 — Inscription à un cours (auth requis). throttle:20,1 = anti-DoS léger (C2).
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])
        ->middleware(['auth', 'throttle:20,1'])
        ->name('courses.enroll');

    // M5 — Achat d'un cours payant via Stripe Checkout (auth requis)
    Route::get('courses/{course:slug}/purchase', PurchaseController::class)
        ->middleware('auth')
        ->name('courses.purchase');

    // M7 — Exports CSV admin (gâtés par permission academy.reports.view)
    Route::middleware(['auth', 'can:academy.reports.view'])
        ->prefix('admin/export')
        ->name('admin.export.')
        ->group(function () {
            Route::get('/enrollments', [ExportController::class, 'exportEnrollments'])->name('enrollments');
            Route::get('/completions', [ExportController::class, 'exportCompletions'])->name('completions');
            Route::get('/progress', [ExportController::class, 'exportProgress'])->name('progress');
        });

    // M4 — Complétion, quiz, vote. throttle:20,1 (20 req/min/utilisateur) = anti-DoS
    // léger sur les MUTATIONS étudiant POST (C2). Ne bride QUE ces POST gardés `auth` ;
    // les GET (lecture) et l'admin ne sont jamais affectés.
    Route::middleware(['auth', 'throttle:20,1'])->group(function () {
        // Bouton « Marquer comme terminé » (video / doc)
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/complete',
            [CompletionController::class, 'store']
        )->name('lessons.complete');

        // Démarrer un quiz
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/quiz/start',
            [QuizController::class, 'startQuiz']
        )->name('quiz.start');

        // Soumettre les réponses d'un quiz
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/quiz/submit',
            [QuizController::class, 'submitQuiz']
        )->name('quiz.submit');

        // V1-f — Valider UNE question (mode rétroaction immédiate uniquement). Le
        // scoring de la question est fait SERVEUR (jamais la décision client) ; la
        // réponse validée est verrouillée en session.
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/quiz/verify',
            [QuizController::class, 'verifyQuestion']
        )->name('quiz.verify');

        // MODE KIOSQUE — consignation d'un incident (sortie plein écran, changement
        // d'onglet, devtools suspectés, sortie volontaire) pendant une tentative
        // surveillée. Auth + inscription vérifiées (authorizeAccess), anti-IDOR strict
        // (la tentative doit appartenir à l'utilisateur courant), throttle:20,1 du
        // groupe parent (anti-DoS léger, cohérent avec les autres mutations étudiant).
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/quiz/kiosk-violation',
            [KioskController::class, 'recordViolation']
        )->name('quiz.kiosk-violation');

        // CHOICE — voter à un sondage (item « choice »). Auth + inscription vérifiées,
        // item re-résolu (anti-IDOR), choix bornés aux options, 1 vote/étudiant (upsert).
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/choice/vote',
            [ChoiceController::class, 'vote']
        )->name('choice.vote');

        // FEEDBACK — répondre à un sondage / questionnaire de rétroaction (item
        // « feedback », multi-questions, non noté). Auth + inscription vérifiées, item
        // re-résolu (anti-IDOR), réponses bornées aux questions du payload, 1 réponse
        // nommée/étudiant (upsert) ou anonyme (user_id null + borne de session).
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/feedback/submit',
            [FeedbackController::class, 'submit']
        )->name('feedback.submit');

        // FORUM — discussions attachées à une leçon (item « forum », type Moodle
        // « Forum »). Auth + inscription/gérant vérifiés, item re-résolu (anti-IDOR),
        // honeypot `hp_url`, bornes serveur. Ouvrir un sujet / répondre (étudiant ou
        // gérant) + modération (épingler/verrouiller/supprimer) gatée manageEnrollments.
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/forum/topics',
            [ForumController::class, 'createTopic']
        )->name('forum.topics.create');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/forum/topics/{topicId}/reply',
            [ForumController::class, 'reply']
        )->name('forum.topics.reply');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/forum/topics/{topicId}/pin',
            [ForumController::class, 'pinTopic']
        )->name('forum.topics.pin');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/forum/topics/{topicId}/lock',
            [ForumController::class, 'lockTopic']
        )->name('forum.topics.lock');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/forum/topics/{topicId}/delete',
            [ForumController::class, 'deleteTopic']
        )->name('forum.topics.delete');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/forum/posts/{postId}/delete',
            [ForumController::class, 'deletePost']
        )->name('forum.posts.delete');

        // F19 - WIKI : pages collaboratives attachées à une leçon (item « wiki », type
        // Moodle « Wiki »). Auth + inscription/gérant vérifiés, item re-résolu (anti-IDOR),
        // honeypot `hp_url`, bornes serveur. Créer / éditer une page (collaboratif selon
        // allow_student_edit) + restaurer une révision (gérant ou auteur) + modération
        // (verrouiller/supprimer) gatée manageEnrollments.
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/wiki/pages',
            [WikiController::class, 'createPage']
        )->name('wiki.pages.create');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/wiki/pages/{pageId}/update',
            [WikiController::class, 'editPage']
        )->name('wiki.pages.update');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/wiki/pages/{pageId}/revisions/{revisionId}/restore',
            [WikiController::class, 'restoreRevision']
        )->name('wiki.pages.restore');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/wiki/pages/{pageId}/lock',
            [WikiController::class, 'lockPage']
        )->name('wiki.pages.lock');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/wiki/pages/{pageId}/delete',
            [WikiController::class, 'deletePage']
        )->name('wiki.pages.delete');

        // F20 - BASE DE DONNÉES collaborative attachée à une leçon (item « database », type
        // Moodle « Database »). Auth + inscription/gérant vérifiés, item re-résolu (anti-IDOR),
        // honeypot `hp_url`, validation des valeurs PAR TYPE dérivée du schéma. Ajouter une
        // fiche (gaté allow_student_add) + éditer/supprimer SA fiche + modération
        // (approuver) gatée manageEnrollments. Le SCHÉMA se gère dans l'éditeur de cours.
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/database/entries',
            [DatabaseController::class, 'addEntry']
        )->name('database.entries.create');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/database/entries/{entryId}/update',
            [DatabaseController::class, 'updateEntry']
        )->name('database.entries.update');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/database/entries/{entryId}/delete',
            [DatabaseController::class, 'deleteEntry']
        )->name('database.entries.delete');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/database/entries/{entryId}/approve',
            [DatabaseController::class, 'approveEntry']
        )->name('database.entries.approve');

        // F21 - ATELIER (Workshop) : évaluation par les pairs attachée à une leçon (item
        // « workshop », type Moodle « Workshop »). Auth + inscription/gérant vérifiés, item
        // re-résolu (anti-IDOR), honeypot `hp_url`, VALIDATION DE PHASE serveur. Étudiant :
        // remettre SON travail (phase submission) + évaluer les travaux ATTRIBUÉS (phase
        // assessment, anti-IDOR sur assessor_id, anti auto-évaluation). Gérant : changer la
        // phase + attribuer les évaluations (allocation déterministe), gatés manageEnrollments.
        // La GRILLE de critères se gère dans l'éditeur de cours.
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/workshop/submit',
            [WorkshopController::class, 'submitWork']
        )->name('workshop.submit');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/workshop/assessments/{assessmentId}/assess',
            [WorkshopController::class, 'assess']
        )->name('workshop.assess');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/workshop/phase',
            [WorkshopController::class, 'setPhase']
        )->name('workshop.phase');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/workshop/allocate',
            [WorkshopController::class, 'allocate']
        )->name('workshop.allocate');

        // F18 - NOTES + COMMENTAIRES sur un item de leçon (parité Moodle
        // ratings/comments). Noter / commenter EXIGE l'inscription active (trait),
        // item re-résolu (anti-IDOR), honeypot `hp_url` + bornes serveur sur le
        // commentaire, note bornée 1..5 (1 note/utilisateur). Suppression d'un
        // commentaire : auteur OU gérant (manageEnrollments), soft-delete.
        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/comments',
            [ItemEngagementController::class, 'storeComment']
        )->name('items.comments.store');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/comments/{commentId}/delete',
            [ItemEngagementController::class, 'deleteComment']
        )->name('items.comments.delete');

        Route::post(
            'courses/{course:slug}/lessons/{lesson}/items/{itemId}/rate',
            [ItemEngagementController::class, 'rate']
        )->name('items.rate');
    });
});

// F16 - LECTEUR H5P (page chargée dans l'iframe sandbox du lecteur de leçon).
// Déclarée HORS du groupe AcademyCsp : le contrôleur pose sa PROPRE CSP (jsdelivr
// autorisé pour le player h5p-standalone), que le middleware AcademyCsp écraserait
// sinon. L'accès est gaté ENTIÈREMENT côté contrôleur (anti-IDOR + inscription /
// gérant / item preview), comme le fait LessonController pour la vidéo.
Route::prefix('academie')->name('academy.')->group(function () {
    Route::get(
        'courses/{course:slug}/lessons/{lesson}/items/{itemId}/h5p',
        [H5pPlayerController::class, 'play']
    )->name('h5p.play');

    // PROXY VIDÉO SIGNÉ (« protéger l'accès, pas l'iframe ») : le lien ScreenPal
    // réel ne fuite plus dans le HTML de la leçon. L'iframe pointe vers CETTE
    // route (URL::temporarySignedRoute, 4 h) ; middleware `signed` = signature +
    // expiration validées automatiquement par Laravel. L'autorisation d'accès
    // à l'item est RE-VÉRIFIÉE intégralement dans VideoRedirectController
    // (LessonAccessService, DRY avec LessonController/H5pPlayerController) :
    // une signature valide seule ne suffit jamais, elle ne fait que borner la
    // fenêtre de rejeu d'un lien déjà autorisé.
    // ACTION: throttle:60,1 sur le proxy vidéo signé (audit sécurité 2026-07-02)
    // SELF: 1 ligne. RAISON: la signature bloque déjà la forge d'itemId, mais rien
    // ne limitait le débit de requêtes légitimement signées (bande passante ScreenPal).
    Route::get(
        'courses/{course:slug}/lessons/{lesson}/items/{itemId}/video-redirect',
        VideoRedirectController::class
    )
        ->middleware(['signed', 'throttle:60,1'])
        ->name('lessons.video-redirect');
});
