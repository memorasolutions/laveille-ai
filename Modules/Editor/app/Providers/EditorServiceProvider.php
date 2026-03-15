<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Editor\Providers;

use Illuminate\Support\Facades\Blade;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Editor\Services\ShortcodeService;

class EditorServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Editor';

    protected string $nameLower = 'editor';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->bootModule();

        $helperFile = module_path($this->name, 'app/Helpers/shortcodes.php');
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }

        $sourcePath = module_path($this->name, 'resources/views');
        Blade::anonymousComponentPath($sourcePath.'/components', $this->nameLower);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(ShortcodeService::class);
    }
}
