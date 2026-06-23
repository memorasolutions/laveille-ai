<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Support\Facades\Route;
use Modules\Academy\Http\Controllers\AcademyController;
use Modules\Academy\Http\Controllers\CalendarController;
use Modules\Academy\Http\Controllers\CertificateController;
use Modules\Academy\Http\Controllers\CompletionController;
use Modules\Academy\Http\Controllers\ChoiceController;
use Modules\Academy\Http\Controllers\FeedbackController;
use Modules\Academy\Http\Controllers\ForumController;
use Modules\Academy\Http\Controllers\CourseController;
use Modules\Academy\Http\Controllers\EnrollmentController;
use Modules\Academy\Http\Controllers\ExportController;
use Modules\Academy\Http\Controllers\LessonController;
use Modules\Academy\Http\Controllers\PurchaseController;
use Modules\Academy\Http\Controllers\QuizController;
use Modules\Academy\Http\Middleware\AcademyCsp;
use Modules\Academy\Http\Middleware\AcademyUnderConstruction;

Route::prefix('academie')->name('academy.')->middleware(AcademyCsp::class)->group(function () {
    // M2 — Pages publiques (gâtées « en construction » : superadmin uniquement tant que le flag est actif)
    Route::middleware(AcademyUnderConstruction::class)->group(function () {
        Route::get('/', [AcademyController::class, 'index'])->name('index');
        Route::get('courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

        // M3 — Lecteur de leçon (auth requis pour les vidéos protégées, mais la route est publique)
        Route::get('courses/{course:slug}/lessons/{lesson}', [LessonController::class, 'show'])
            ->name('lessons.show');

        // PHASE 2 - Espace personnel front-end UNIQUE et role-aware (connexion requise).
        // Gâté comme le reste (AcademyUnderConstruction) + `auth`. Le contenu est
        // adapté au rôle par le composant Livewire (requêtes scopées au user).
        Route::get('espace', fn () => view('academy::public.dashboard'))
            ->middleware('auth')
            ->name('dashboard');

        // PHASE 5 (FE-5) - Création de cours front-end.
        // Connexion requise ; l'autorisation d'entrée (create) vit dans
        // CourseCreate::mount() via $this->authorize('create', Course::class)
        // et est RÉ-AUTORISÉE à la création (jamais de confiance au navigateur).
        // Déclarée AVANT les routes wildcard courses/{course:slug} pour que
        // « creer » ne soit jamais capté comme un slug de cours.
        Route::get('creer', fn () => view('academy::public.course-create'))
            ->middleware('auth')
            ->name('courses.create');

        // QB2 - Éditeur de la BANQUE DE QUESTIONS réutilisable (owner-scoped).
        // Connexion requise ; l'autorisation d'entrée (instructor/admin) vit dans
        // QuestionBankManager::mount() (abort 403 sinon) et chaque mutation est
        // ré-autorisée + owner-scopée côté serveur (anti-IDOR). Déclarée AVANT les
        // routes wildcard courses/{course:slug} pour que « banque » ne soit jamais
        // capté comme un slug de cours.
        Route::get('banque', fn () => view('academy::public.question-bank'))
            ->middleware('auth')
            ->name('questions.bank');

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

        // V5-b - Calendrier d'echeances par cours (etudiant inscrit OU gerant).
        // Autorisation verifiee par CourseCalendar::mount() (inscription active
        // OU manageStructure). Lecture seule pour l'etudiant, CRUD pour le gerant.
        Route::get('courses/{course:slug}/calendrier', function (\Modules\Academy\Models\Course $course) {
            return view('academy::public.course-calendar', ['course' => $course]);
        })
            ->middleware('auth')
            ->name('courses.calendar');
    });

    // M6 — Certificats publics vérifiables (pas d'auth requise)
    Route::get('certificats/{public_url_slug}', [CertificateController::class, 'show'])->name('certificates.show');

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
    });
});
