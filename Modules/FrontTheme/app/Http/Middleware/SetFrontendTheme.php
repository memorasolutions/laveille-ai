<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Core\Contracts\SettingsReaderInterface;

class SetFrontendTheme
{
    public function __construct(private readonly SettingsReaderInterface $settings) {}

    public function handle(Request $request, Closure $next)
    {
        $theme = $this->settings->get('frontend.theme', config('frontend.theme', 'bloggar'));
        $modules = config('frontend.theme_modules', []);

        foreach ($modules as $namespace => $moduleName) {
            $themePath = module_path($moduleName, 'resources/views/themes/'.$theme);

            if (is_dir($themePath)) {
                app('view')->getFinder()->prependNamespace($namespace, $themePath);
            }
        }

        $assetsPath = asset(config('frontend.assets_path', 'themes').'/'.$theme);
        View::share('theme_assets_path', $assetsPath);

        return $next($request);
    }
}
