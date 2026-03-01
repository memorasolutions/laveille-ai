<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CookieCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CookieConsentController
{
    public function preferences(): View
    {
        return view('cookie-preferences');
    }

    public function accept(): RedirectResponse
    {
        $consent = $this->buildConsent(allOptional: true);

        return back()->withCookie(cookie('cookie_consent', (string) json_encode($consent), 365 * 24 * 60));
    }

    public function decline(): RedirectResponse
    {
        $consent = $this->buildConsent(allOptional: false);

        return back()->withCookie(cookie('cookie_consent', (string) json_encode($consent), 365 * 24 * 60));
    }

    public function customize(Request $request): RedirectResponse
    {
        $categories = CookieCategory::active()->ordered()->get();
        $consent = [];

        foreach ($categories as $category) {
            $consent[$category->name] = $category->isRequired() ? true : $request->boolean($category->name);
        }

        return back()->withCookie(cookie('cookie_consent', (string) json_encode($consent), 365 * 24 * 60));
    }

    private function buildConsent(bool $allOptional): array
    {
        $categories = CookieCategory::active()->ordered()->get();
        $consent = [];

        foreach ($categories as $category) {
            $consent[$category->name] = $category->isRequired() ? true : $allOptional;
        }

        return $consent;
    }
}
