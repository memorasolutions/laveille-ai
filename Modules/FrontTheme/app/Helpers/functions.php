<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

if (! function_exists('fronttheme_layout')) {
    function fronttheme_layout(): string
    {
        return 'fronttheme::layouts.master';
    }
}

if (! function_exists('fronttheme_asset')) {
    function fronttheme_asset(string $path): string
    {
        $theme = config('frontend.theme', 'bloggar');

        return asset('themes/'.$theme.'/'.ltrim($path, '/'));
    }
}
