<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacyPolicy(): View
    {
        return view('legal.privacy-policy', [
            'config' => config('privacy'),
        ]);
    }

    public function termsOfUse(): View
    {
        return view('legal.terms-of-use', [
            'config' => config('privacy'),
        ]);
    }

    public function cookiePolicy(): View
    {
        return view('legal.cookie-policy', [
            'config' => config('privacy'),
        ]);
    }
}
