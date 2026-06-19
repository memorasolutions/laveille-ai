<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Policies\CoursePolicy;
use Modules\Academy\Policies\LessonPolicy;
use Modules\Core\Providers\BaseModuleServiceProvider;

class AcademyServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Academy';

    protected string $nameLower = 'academy';

    public function boot(): void
    {
        $this->bootModule();

        // Enregistrement des policies du module Academy (M1)
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
