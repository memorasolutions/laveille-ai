<?php

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\FrontTheme\Services\ThemeService;

class ThemeMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $themeService = app(ThemeService::class);

        $theme = $request->query('theme')
            ?? session('theme')
            ?? config('theme.active', 'default');

        if ($theme) {
            $themeService->set($theme);
            session(['theme' => $theme]);
        }

        return $next($request);
    }
}
