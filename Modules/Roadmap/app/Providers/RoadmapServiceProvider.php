<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Roadmap\Services\IdeaService;
use Modules\Roadmap\Services\RoadmapAiService;
use Modules\Roadmap\Services\VotingService;

class RoadmapServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Roadmap';

    protected string $nameLower = 'roadmap';

    public function boot(): void
    {
        $this->bootModule();

        if (class_exists(\Modules\Core\Services\ModeratableRegistry::class)) {
            \Modules\Core\Services\ModeratableRegistry::register('ideas', \Modules\Roadmap\Models\Idea::class);
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(VotingService::class);
        $this->app->singleton(IdeaService::class);
        $this->app->singleton(RoadmapAiService::class);
    }
}
