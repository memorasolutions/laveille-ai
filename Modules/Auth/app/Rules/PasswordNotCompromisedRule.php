<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Modules\Settings\Facades\Settings;

class PasswordNotCompromisedRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Settings::get('security.password_check_hibp', false)) {
            return;
        }

        $sha1 = strtoupper(sha1((string) $value));
        $prefix = substr($sha1, 0, 5);
        $suffix = substr($sha1, 5);

        try {
            $response = Http::timeout(3)->get("https://api.pwnedpasswords.com/range/{$prefix}");

            if ($response->successful()) {
                foreach (explode("\n", $response->body()) as $line) {
                    $parts = explode(':', trim($line));

                    if (count($parts) === 2 && strtoupper($parts[0]) === $suffix && (int) $parts[1] > 0) {
                        $fail('Ce mot de passe a ete compromis dans une fuite de donnees. Veuillez en choisir un autre.');

                        return;
                    }
                }
            }
        } catch (\Exception) {
            // Fail open : si l'API est indisponible, on laisse passer
        }
    }
}
