<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\CloudflareCache\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\CloudflareCache\Jobs\PurgeCloudflareCacheJob;

class CacheablePurgeObserver
{
    public function saved(Model $model): void
    {
        $this->handleModelEvent($model);
    }

    public function deleted(Model $model): void
    {
        $this->handleModelEvent($model);
    }

    private function handleModelEvent(Model $model): void
    {
        if (config('cloudflarecache.enabled', true) === false) {
            return;
        }

        $modelClass = get_class($model);
        $config = config("cloudflarecache.models_to_watch.{$modelClass}");

        if (empty($config['routes']) || ! is_array($config['routes'])) {
            return;
        }

        $urls = [];

        foreach ($config['routes'] as $routeConfig) {
            if (! isset($routeConfig['name']) || ! is_string($routeConfig['name'])) {
                continue;
            }

            try {
                $parameters = [];
                if (isset($routeConfig['param_field']) && is_string($routeConfig['param_field'])) {
                    $paramValue = $model->{$routeConfig['param_field']} ?? null;
                    if ($paramValue !== null) {
                        $parameters = [$paramValue];
                    }
                }

                $url = route($routeConfig['name'], $parameters, absolute: true);
                $urls[] = $url;
            } catch (\Illuminate\Routing\Exceptions\RouteNotFoundException) {
                Log::warning('CacheablePurgeObserver: route not found', [
                    'model' => $modelClass,
                    'route_name' => $routeConfig['name'],
                ]);
            }
        }

        try {
            $urls[] = route('home', [], absolute: true);
        } catch (\Illuminate\Routing\Exceptions\RouteNotFoundException) {
            // Ignore if home route does not exist
        }

        $alwaysPurgeUrls = config('cloudflarecache.always_purge_urls', []);
        if (! empty($alwaysPurgeUrls) && is_array($alwaysPurgeUrls)) {
            $urls = array_merge($urls, $alwaysPurgeUrls);
        }

        $urls = array_values(array_unique($urls));

        if (! empty($urls)) {
            PurgeCloudflareCacheJob::dispatch($urls)->onQueue('cloudflare');
        }
    }
}
