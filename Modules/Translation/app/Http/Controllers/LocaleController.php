<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Translation\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController
{
    public function __invoke(string $locale): RedirectResponse
    {
        $supported = config('app.supported_locales', ['fr', 'en']);
        if (! in_array($locale, $supported, true)) {
            abort(400);
        }

        session(['locale' => $locale]);

        return back();
    }
}
