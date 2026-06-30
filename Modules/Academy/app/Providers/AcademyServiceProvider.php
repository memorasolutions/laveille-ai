<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Academy\Console\CourseReindexCommand;
use Modules\Academy\Console\SendDueRemindersCommand;
use Modules\Academy\Livewire\CompetencyManager;
use Modules\Academy\Livewire\CourseAnalytics;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Livewire\CourseCalendar;
use Modules\Academy\Livewire\CourseCompetencies;
use Modules\Academy\Livewire\CourseCreate;
use Modules\Academy\Livewire\CourseImport;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Livewire\CourseReports;
use Modules\Academy\Livewire\CourseRoster;
use Modules\Academy\Livewire\Dashboard;
use Modules\Academy\Livewire\EssayGrading;
use Modules\Academy\Livewire\NotificationMasterSwitch;
use Modules\Academy\Livewire\NotificationPreferences;
use Modules\Academy\Livewire\QuestionBankManager;
use Modules\Academy\Livewire\StudentAssignments;
use Modules\Academy\Livewire\StudentCompetencies;
use Modules\Academy\Livewire\StudentGrades;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Policies\ChapterPolicy;
use Modules\Academy\Policies\CoursePolicy;
use Modules\Academy\Policies\LessonItemPolicy;
use Modules\Academy\Policies\LessonPolicy;
use Modules\Core\Providers\BaseModuleServiceProvider;

class AcademyServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Academy';

    protected string $nameLower = 'academy';

    public function boot(): void
    {
        $this->bootModule();

        // Composants Blade anonymes du module (ex. <x-academy::video-player>)
        // Pattern identique à CoreServiceProvider::registerViews()
        $sourcePath = module_path($this->name, 'resources/views');
        Blade::anonymousComponentPath($sourcePath . '/components', $this->nameLower);

        // Enregistrement des policies du module Academy (M1)
        // Modèle « rôle + ownership » : gestion par-cours scoped via course_roles,
        // chapitres/leçons/items autorisés via leur cours parent (ownership-aware).
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(LessonItem::class, LessonItemPolicy::class);

        // PHASE 2 - Espace personnel front-end (composant Livewire role-aware).
        // Même pattern que Authors/Backoffice : Livewire::component('namespace.kebab', Class).
        // Rendu via @livewire('academy.dashboard') dans la vue page public/dashboard.blade.php.
        $this->registerLivewireComponents();

        // M7 — Commande Artisan de réindexation Scout
        // V5-c — Commande de rappels d'échéance planifiés (gardée par l'interrupteur maître).
        if ($this->app->runningInConsole()) {
            $this->commands([
                CourseReindexCommand::class,
                SendDueRemindersCommand::class,
            ]);
        }
    }

    /**
     * Enregistre les composants Livewire du module (pattern Authors/Backoffice).
     */
    protected function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component('academy.dashboard', Dashboard::class);

        // PHASE 5 (FE-5) - Création de cours front-end (gâtée create()).
        // Crée le Course (status='draft') + le CourseRole 'owner' du créateur en
        // transaction, puis redirige vers l'éditeur. Voir CourseCreate.
        Livewire::component('academy.course-create', CourseCreate::class);

        // F15 - Import / restauration d'un cours depuis une sauvegarde .json (gâté
        // create()). Téléverse -> aperçu -> confirme -> crée un cours NEUF (brouillon,
        // owner = importateur) avec remappage des références internes. Voir CourseImport.
        Livewire::component('academy.course-import', CourseImport::class);

        // PHASE 3 (FE-3) - Éditeur de cours front-end (métadonnées + structure).
        // Chaque mutation est gardée serveur par $this->authorize(...) (voir CourseEditor).
        Livewire::component('academy.course-editor', CourseEditor::class);

        // PHASE 4 (FE-4) - Roster (inscriptions) + rôles de cours (équipe).
        // Gardes serveur : manageEnrollments (roster) / manageRoles (équipe),
        // objets re-résolus et scopés au cours (anti-IDOR). Voir CourseRoster.
        Livewire::component('academy.course-roster', CourseRoster::class);

        // PHASE D (D1) - Analytics par cours (pilotage), LECTURE SEULE.
        // Gate serveur manageEnrollments + métriques scopées au cours (anti-IDOR).
        // Voir CourseAnalytics + AnalyticsService.
        Livewire::component('academy.course-analytics', CourseAnalytics::class);

        // F23 - Rapports et journaux par cours (participation + journal d'activité),
        // LECTURE SEULE. Gate serveur manageEnrollments + données scopées au cours
        // (anti-IDOR), journal dérivé des timestamps existants. Voir CourseReports
        // + CourseReportService.
        Livewire::component('academy.course-reports', CourseReports::class);

        // PHASE E (E2) - Devoirs : GÉRANT (créer/éditer/publier/supprimer gâté
        // manageStructure ; corriger + gradebook gâté manageEnrollments), objets
        // re-résolus et scopés au cours (anti-IDOR). Voir CourseAssignments.
        Livewire::component('academy.course-assignments', CourseAssignments::class);

        // ESSAI (type Moodle « Essay ») - CORRECTION MANUELLE des tentatives de quiz
        // contenant un essai (gâté manageEnrollments), tentative re-résolue scopée au
        // cours (anti-IDOR), points bornés au barème, recalcul + complétion en
        // transaction. Voir EssayGrading + EssayGradingService.
        Livewire::component('academy.essay-grading', EssayGrading::class);

        // PHASE E (E2) - Devoirs : ÉTUDIANT (voir les devoirs publiés de ses cours
        // suivis, soumettre/éditer SA remise, voir sa note). user_id forcé = auth,
        // inscription active re-vérifiée serveur. Voir StudentAssignments.
        Livewire::component('academy.student-assignments', StudentAssignments::class);

        // PHASE V2-b - Carnet PONDÉRÉ : ÉTUDIANT (SA note finale pondérée + lettre +
        // détail par catégorie, LECTURE SEULE). Scopé à auth()->id() (anti-IDOR).
        // Voir StudentGrades + GradebookService.
        Livewire::component('academy.student-grades', StudentGrades::class);

        // QB2 - Banque de questions réutilisable (catégories + questions des 4 types).
        // OWNER-SCOPED : un formateur ne gère QUE ses propres catégories/questions
        // (owner_id = auth) ; l'admin (academy.manage) voit tout. Chaque mutation est
        // gardée serveur (entité re-résolue scopée owner = anti-IDOR). Voir QuestionBankManager.
        Livewire::component('academy.question-bank-manager', QuestionBankManager::class);

        // F22 - RÉFÉRENTIEL de COMPÉTENCES (CRUD owner-scopé) ; l'admin (academy.manage)
        // voit tout. Chaque mutation est gardée serveur (entité re-résolue scopée owner =
        // anti-IDOR). Rendu via @livewire('academy.competency-manager'). Voir CompetencyManager.
        Livewire::component('academy.competency-manager', CompetencyManager::class);

        // F22 - ASSOCIATION compétences <-> cours/items + rapport d'acquisition par étudiant.
        // Gâté manageStructure (association) et manageEnrollments (rapport) ; cours re-résolu,
        // item re-scopé au cours, compétence scopée owner (anti-IDOR). Voir CourseCompetencies.
        Livewire::component('academy.course-competencies', CourseCompetencies::class);

        // F22 - ÉTUDIANT : « Mes compétences » (LECTURE SEULE), scopé à auth()->id()
        // (anti-IDOR), état dérivé serveur (CompetencyService). Voir StudentCompetencies.
        Livewire::component('academy.student-competencies', StudentCompetencies::class);

        // V5-b - Calendrier d'echeances par cours. Etudiant inscrit = lecture seule ;
        // gerant (manageStructure) = CRUD d'evenements manuels. Les echeances derivees
        // des devoirs sont calculees en lecture par CalendarService (anti-duplication).
        // Autorisation verifiee dans mount() et a chaque mutation. Voir CourseCalendar.
        Livewire::component('academy.course-calendar', CourseCalendar::class);

        // V5-c - Réglages des notifications courriel (opt-in/opt-out par type).
        // Gaté auth ; chaque mutation s'applique UNIQUEMENT à auth()->user().
        // Rendu via @livewire('academy.notification-preferences'). Voir NotificationPreferences.
        Livewire::component('academy.notification-preferences', NotificationPreferences::class);

        // D02 - Interrupteur MAÎTRE des notifications courriel, pilotable par l'admin
        // (sans .env). Réservé can('academy.manage'), autorisation re-vérifiée à chaque
        // action (anti-IDOR). Rendu via @livewire('academy.notification-master-switch').
        // Voir NotificationMasterSwitch + AcademyNotificationService::setMasterEnabled().
        Livewire::component('academy.notification-master-switch', NotificationMasterSwitch::class);

        // DeckPlayer : présentation de leçon en cartes plein écran (drapeau academy.lesson_deck_mode).
        // Activé via ACADEMY_LESSON_DECK_MODE=true dans le .env.
        Livewire::component('academy.deck-player', \Modules\Academy\Livewire\DeckPlayer::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
