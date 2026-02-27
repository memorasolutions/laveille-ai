<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Settings\Facades\Settings;

class SetBackofficeTheme
{
    public function handle(Request $request, Closure $next)
    {
        $theme = Settings::get('backoffice.theme', config('backoffice.theme', 'backend'));

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
