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
use Modules\Academy\Livewire\Dashboard;
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
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
