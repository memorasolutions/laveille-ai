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
use Modules\Academy\Livewire\CourseAnalytics;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Livewire\CourseCreate;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Livewire\CourseRoster;
use Modules\Academy\Livewire\Dashboard;
use Modules\Academy\Livewire\StudentAssignments;
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
        if ($this->app->runningInConsole()) {
            $this->commands([CourseReindexCommand::class]);
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

        // PHASE E (E2) - Devoirs : GÉRANT (créer/éditer/publier/supprimer gâté
        // manageStructure ; corriger + gradebook gâté manageEnrollments), objets
        // re-résolus et scopés au cours (anti-IDOR). Voir CourseAssignments.
        Livewire::component('academy.course-assignments', CourseAssignments::class);

        // PHASE E (E2) - Devoirs : ÉTUDIANT (voir les devoirs publiés de ses cours
        // suivis, soumettre/éditer SA remise, voir sa note). user_id forcé = auth,
        // inscription active re-vérifiée serveur. Voir StudentAssignments.
        Livewire::component('academy.student-assignments', StudentAssignments::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
