<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Contracts\SettingsReaderInterface;

class SetBackofficeTheme
{
    public function __construct(private readonly SettingsReaderInterface $settings) {}

    public function handle(Request $request, Closure $next)
    {
        $theme = $this->settings->get('backoffice.theme', config('backoffice.theme', 'backend'));

        $modules = config('backoffice.theme_modules', []);

        foreach ($modules as $namespace => $moduleName) {
            $themePath = module_path($moduleName, 'resources/views/themes/'.$theme);

            if (is_dir($themePath)) {
                app('view')->getFinder()->prependNamespace($namespace, $themePath);
            }
        }

        return $next($request);
    }
}
