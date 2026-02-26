<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController
{
    public function __invoke(string $locale): RedirectResponse
    {
        if (! in_array($locale, ['fr', 'en'], true)) {
            abort(400);
        }

        session(['locale' => $locale]);

        return back();
    }
}
