<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Services;

use Qirolab\Theme\Theme;

class ThemeService
{
    public function set(string $theme): void
    {
        Theme::set($theme);
    }

    public function get(): ?string
    {
        return Theme::active();
    }

    public function clear(): void
    {
        Theme::clear();
    }

    public function getAvailableThemes(): array
    {
        $themesPath = resource_path('themes');

        if (! is_dir($themesPath)) {
            return [];
        }

        return array_values(array_filter(
            scandir($themesPath),
            fn ($dir) => $dir !== '.' && $dir !== '..' && is_dir($themesPath.'/'.$dir)
        ));
    }
}
