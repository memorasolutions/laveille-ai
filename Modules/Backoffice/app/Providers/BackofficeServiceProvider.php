<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Providers;

use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\ActivityLogsTable;
use Modules\Backoffice\Livewire\ArticlesTable;
use Modules\Backoffice\Livewire\CampaignsTable;
use Modules\Backoffice\Livewire\CategoriesTable;
use Modules\Backoffice\Livewire\CommentsTable;
use Modules\Backoffice\Livewire\FeatureFlagsTable;
use Modules\Backoffice\Livewire\GlobalSearch;
use Modules\Backoffice\Livewire\LookerStudioStats;
use Modules\Backoffice\Livewire\MediaTable;
use Modules\Backoffice\Livewire\MetaTagsTable;
use Modules\Backoffice\Livewire\NotificationBell;
use Modules\Backoffice\Livewire\PlansTable;
use Modules\Backoffice\Livewire\RolesTable;
use Modules\Backoffice\Livewire\SettingsManager;
use Modules\Backoffice\Livewire\SettingsTable;
use Modules\Backoffice\Livewire\ShortcodesTable;
use Modules\Backoffice\Livewire\SubscribersTable;
use Modules\Backoffice\Livewire\TranslationsManager;
use Modules\Backoffice\Livewire\UsersTable;
use Modules\Backoffice\Livewire\WebhooksManager;
use Modules\Core\Providers\BaseModuleServiceProvider;

class BackofficeServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Backoffice';

    protected string $nameLower = 'backoffice';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();
        $this->registerLivewireComponents();
        $this->registerBrandingComposer();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('backoffice-activity-logs-table', ActivityLogsTable::class);
        Livewire::component('backoffice-articles-table', ArticlesTable::class);
        Livewire::component('backoffice-categories-table', CategoriesTable::class);
        Livewire::component('backoffice-campaigns-table', CampaignsTable::class);
        Livewire::component('backoffice-webhooks-manager', WebhooksManager::class);
        Livewire::component('backoffice-subscribers-table', SubscribersTable::class);
        Livewire::component('backoffice-comments-table', CommentsTable::class);
        Livewire::component('backoffice-users-table', UsersTable::class);
        Livewire::component('backoffice-roles-table', RolesTable::class);
        Livewire::component('backoffice-settings-table', SettingsTable::class);
        Livewire::component('backoffice-settings-manager', SettingsManager::class);
        Livewire::component('backoffice-global-search', GlobalSearch::class);
        Livewire::component('backoffice-notification-bell', NotificationBell::class);
        Livewire::component('backoffice-plans-table', PlansTable::class);
        Livewire::component('backoffice-feature-flags-table', FeatureFlagsTable::class);
        Livewire::component('backoffice-meta-tags-table', MetaTagsTable::class);
        Livewire::component('backoffice-media-table', MediaTable::class);
        Livewire::component('shortcodes-table', ShortcodesTable::class);
        Livewire::component('backoffice-translations-manager', TranslationsManager::class);
        Livewire::component('backoffice-looker-studio-stats', LookerStudioStats::class);
    }

    protected function registerBrandingComposer(): void
    {
        View::composer('backoffice::*', BrandingViewComposer::class);
    }
}
