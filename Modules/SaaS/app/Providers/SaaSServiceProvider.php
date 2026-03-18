<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Cashier;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\SaaS\Models\Plan;
use Modules\SaaS\Observers\PlanObserver;
use Modules\SaaS\Policies\PlanPolicy;

class SaaSServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'SaaS';

    protected string $nameLower = 'saas';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->bootModule();
        Gate::policy(Plan::class, PlanPolicy::class);
        Plan::observe(PlanObserver::class);

        $this->registerSubscriptionGates();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(\Modules\SaaS\Services\BillingService::class);
        $this->app->singleton(\Modules\SaaS\Services\SaasMetricsService::class);
        $this->app->singleton(\Modules\SaaS\Services\MeteringService::class);
        $this->app->singleton(SaasMetricProvider::class);
        $this->app->tag([SaasMetricProvider::class], 'metric_providers');
        $this->app->singleton(\Modules\SaaS\Services\ReferralService::class);

        // Désactiver les routes par défaut de Cashier pour utiliser notre webhook personnalisé
        Cashier::ignoreRoutes();
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\SaaS\Console\SendTrialExpiryNotifications::class,
        ]);
    }

    private function registerSubscriptionGates(): void
    {
        $plans = config('saas.plans', []);

        foreach ($plans as $slug => $plan) {
            $features = $plan['features'] ?? [];

            foreach ($features as $feature) {
                if (! Gate::has($feature)) {
                    Gate::define($feature, function ($user) use ($feature) {
                        if ($user->hasRole(['super_admin', 'admin'])) {
                            return true;
                        }

                        if (! $user->subscribed('default')) {
                            return false;
                        }

                        $subscription = $user->subscription('default');
                        $currentPlan = Plan::where('stripe_price_id', $subscription?->stripe_price)->first();

                        return $currentPlan && in_array($feature, $currentPlan->features ?? [], true);
                    });
                }
            }
        }
    }
}
