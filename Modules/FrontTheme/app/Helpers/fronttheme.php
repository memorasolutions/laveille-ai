<?php

declare(strict_types=1);

if (! function_exists('fronttheme_layout')) {
    function fronttheme_layout(): string
    {
        $theme = config('fronttheme.active', 'gosass');

        return "fronttheme::themes.{$theme}.layouts.app";
    }
}

if (! function_exists('fronttheme_guest_layout')) {
    function fronttheme_guest_layout(): string
    {
        $theme = config('fronttheme.active', 'gosass');

        return "fronttheme::themes.{$theme}.layouts.guest";
    }
}
