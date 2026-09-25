<?php

declare(strict_types=1);

namespace Modules\Signature\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ServiceProvider du module Signature (générateur de signature HTML de courriel,
 * /outils/signature-courriel). Squelette calqué sur Modules\Decido\Providers\DecidoServiceProvider
 * (pattern nwidart standard du projet) - voir .devis/outil_signature_html/PLAN-OUTIL-SIGNATURE.md,
 * section 11.1.
 *
 * Module désactivable (modules_statuses.json) : quand désactivé, ce provider ne boot jamais (le
 * comportement standard nwidart-laravel-modules, identique pour tout autre module du projet), donc
 * aucune route/migration/commande de Signature ne se charge - rien ailleurs dans le site ne
 * référence Modules\Signature\* en dur (voir tests/Feature/SignatureModuleDisabledTest.php).
 */
class SignatureServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Signature';

    protected string $nameLower = 'signature';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $sourcePath = module_path($this->name, 'resources/views');
        Blade::anonymousComponentPath($sourcePath.'/components', $this->nameLower);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Signature\Console\WarnExpiringSignaturesCommand::class,
            \Modules\Signature\Console\PurgeExpiredSignaturesCommand::class,
            \Modules\Signature\Console\RestoreSignatureFromQuarantineCommand::class,
            \Modules\Signature\Console\PurgeSignatureQuarantineCommand::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        // Planification réelle : routes/console.php (source unique du projet pour les tâches
        // planifiées), pas ici - même choix que DecidoServiceProvider.
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($module_config, $existing)]);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
