<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Settings\Facades\Settings;
use Symfony\Component\HttpFoundation\Response;

class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') || ! (bool) Settings::get('security.captcha_enabled', false)) {
            return $next($request);
        }

        $token = $request->input('g-recaptcha-response');
        if (! $token) {
            abort(422, 'Vérification CAPTCHA requise.');
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => Settings::get('security.recaptcha_secret_key', ''),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        $result = $response->json();
        if (! ($result['success'] ?? false) || ($result['score'] ?? 0) < 0.5) {
            abort(422, 'Vérification CAPTCHA échouée.');
        }

        return $next($request);
    }
}
