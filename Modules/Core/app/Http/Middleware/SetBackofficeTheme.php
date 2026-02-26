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
        $theme = Settings::get('backoffice.theme', config('backoffice.theme', 'wowdash'));

        $themePath = module_path('Backoffice', 'resources/views/themes/' . $theme);

        if (is_dir($themePath)) {
            app('view')->getFinder()->prependNamespace('backoffice', $themePath);
        }

        // Appliquer aussi le thème au module Auth (user dashboard)
        $authThemePath = module_path('Auth', 'resources/views/themes/' . $theme);

        if (is_dir($authThemePath)) {
            app('view')->getFinder()->prependNamespace('auth', $authThemePath);
        }

        // Appliquer le thème au module Blog (articles admin)
        $blogThemePath = module_path('Blog', 'resources/views/themes/' . $theme);
        if (is_dir($blogThemePath)) {
            app('view')->getFinder()->prependNamespace('blog', $blogThemePath);
        }

        // Appliquer le thème au module Pages
        $pagesThemePath = module_path('Pages', 'resources/views/themes/' . $theme);
        if (is_dir($pagesThemePath)) {
            app('view')->getFinder()->prependNamespace('pages', $pagesThemePath);
        }

        // Appliquer le thème au module Newsletter
        $newsletterThemePath = module_path('Newsletter', 'resources/views/themes/' . $theme);
        if (is_dir($newsletterThemePath)) {
            app('view')->getFinder()->prependNamespace('newsletter', $newsletterThemePath);
        }

        return $next($request);
    }
}
