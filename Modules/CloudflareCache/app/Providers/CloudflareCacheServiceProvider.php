<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\CloudflareCache\Providers;

use Modules\Core\Providers\BaseModuleServiceProvider;

class CloudflareCacheServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'CloudflareCache';

    protected string $nameLower = 'cloudflarecache';

    public function boot(): void
    {
        $this->bootModule();
        $this->registerDynamicObservers();
    }

    public function register(): void
    {
        $this->app->singleton(\Modules\CloudflareCache\Services\CloudflareCacheService::class);
    }

    protected function registerDynamicObservers(): void
    {
        if (config('cloudflarecache.enabled', true) === false) {
            return;
        }

        $modelsToWatch = config('cloudflarecache.models_to_watch', []);
        if (empty($modelsToWatch) || ! is_array($modelsToWatch)) {
            return;
        }

        foreach (array_keys($modelsToWatch) as $modelClass) {
            if (is_string($modelClass) && class_exists($modelClass)) {
                $modelClass::observe(\Modules\CloudflareCache\Observers\CacheablePurgeObserver::class);
            }
        }
    }
}
