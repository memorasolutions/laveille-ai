<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Modules\Editor\Services\ShortcodeService;

if (! function_exists('render_shortcodes')) {
    function render_shortcodes(string $content): string
    {
        return app(ShortcodeService::class)->render($content);
    }
}
