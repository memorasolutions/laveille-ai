<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;
use RuntimeException;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('plugin.registry', function () {
            $configs = [];

            foreach (Module::all() as $module) {
                $pluginPath = $module->getPath().'/plugin.json';

                if (File::exists($pluginPath)) {
                    $config = json_decode(File::get($pluginPath), true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $configs[$module->getName()] = $config;
                    }
                }
            }

            return $configs;
        });
    }

    public function boot(): void
    {
        /** @var array<string, array<string, mixed>> $registry */
        $registry = $this->app->make('plugin.registry');

        foreach ($registry as $moduleName => $plugin) {
            $dependencies = $plugin['dependencies'] ?? [];

            foreach ($dependencies as $dependency) {
                if (! Module::has($dependency) || ! Module::isEnabled($dependency)) {
                    throw new RuntimeException(
                        "Module [{$moduleName}] requires [{$dependency}] which is not enabled."
                    );
                }
            }
        }
    }
}
