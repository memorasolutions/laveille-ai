<?php

declare(strict_types=1);

use Modules\Editor\Services\ShortcodeService;

if (! function_exists('render_shortcodes')) {
    function render_shortcodes(string $content): string
    {
        return app(ShortcodeService::class)->render($content);
    }
}
