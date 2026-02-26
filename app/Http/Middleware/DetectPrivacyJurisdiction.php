<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class DetectPrivacyJurisdiction
{
    /**
     * Detect user privacy jurisdiction from Accept-Language header.
     * Always defaults to the strictest standard (opt-in) for safety.
     *
     * Jurisdictions: canada_quebec, gdpr, ccpa, pipeda
     */
    public function handle(Request $request, Closure $next): Response
    {
        $jurisdiction = session('privacy_jurisdiction')
            ?? $this->detect($request->header('Accept-Language', ''));

        session(['privacy_jurisdiction' => $jurisdiction]);
        View::share('privacy_jurisdiction', $jurisdiction);

        return $next($request);
    }

    private function detect(string $header): string
    {
        $preferred = strtolower(explode(',', $header)[0]);
        $preferred = explode(';', $preferred)[0];

        return match (true) {
            str_contains($preferred, 'fr-ca'),
            str_contains($preferred, 'en-ca') => 'canada_quebec',
            str_contains($preferred, 'en-us') => 'ccpa',
            str_contains($preferred, 'fr-fr'),
            str_contains($preferred, 'de'),
            str_contains($preferred, 'it'),
            str_contains($preferred, 'es'),
            str_contains($preferred, 'nl'),
            str_contains($preferred, 'pt'),
            str_contains($preferred, 'pl'),
            str_contains($preferred, 'sv'),
            str_contains($preferred, 'da'),
            str_contains($preferred, 'fi'),
            str_contains($preferred, 'el'),
            str_contains($preferred, 'cs'),
            str_contains($preferred, 'ro'),
            str_contains($preferred, 'hu'),
            str_contains($preferred, 'bg'),
            str_contains($preferred, 'hr'),
            str_contains($preferred, 'sk'),
            str_contains($preferred, 'sl') => 'gdpr',
            default => 'pipeda',
        };
    }
}
