<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Analytics\Console\SyncGa4Command;
use Modules\Analytics\Console\SyncGscCommand;
use Modules\Analytics\Contracts\AnalyticsGateway;
use Modules\Analytics\Contracts\EditorialHistoryReader;
use Modules\Analytics\Services\ActivityLogEditorialHistory;
use Modules\Analytics\Services\GoogleAnalyticsGateway;
use Modules\Core\Providers\BaseModuleServiceProvider;

class AnalyticsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Analytics';

    protected string $nameLower = 'analytics';

    public function boot(): void
    {
        $this->bootModule();
        $this->commands([SyncGa4Command::class, SyncGscCommand::class]);
        $this->registerCommandSchedules();
    }

    /**
     * ACTION: planifier la collecte quotidienne (ga4 03h10, gsc 03h20, heure du Québec)
     * SELF: enregistrement de 2 lignes de planification
     * RAISON: onOneServer() évite le double passage si le cron tourne sur plusieurs serveurs ;
     *         les commandes sont inertes tant que la configuration Google manque.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('analytics:sync-ga4')->dailyAt('03:10')->timezone('America/Toronto')->onOneServer();
            $schedule->command('analytics:sync-gsc')->dailyAt('03:20')->timezone('America/Toronto')->onOneServer();
        });
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->bind(EditorialHistoryReader::class, ActivityLogEditorialHistory::class);
        // Remplaçable en test : $this->app->instance(AnalyticsGateway::class, $faux).
        $this->app->bind(AnalyticsGateway::class, GoogleAnalyticsGateway::class);
    }
}
