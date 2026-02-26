<?php

declare(strict_types=1);

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Settings\Facades\Settings;

class PasswordPolicyRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minLength = (int) Settings::get('security.password_min_length', 8);
        $requireUppercase = (bool) Settings::get('security.password_require_uppercase', true);
        $requireNumber = (bool) Settings::get('security.password_require_number', true);
        $requireSpecial = (bool) Settings::get('security.password_require_special', false);

        if (strlen((string) $value) < $minLength) {
            $fail('Le mot de passe doit contenir au moins '.$minLength.' caractères.');
        }

        if ($requireUppercase && ! preg_match('/[A-Z]/', (string) $value)) {
            $fail('Le mot de passe doit contenir au moins une lettre majuscule.');
        }

        if ($requireNumber && ! preg_match('/[0-9]/', (string) $value)) {
            $fail('Le mot de passe doit contenir au moins un chiffre.');
        }

        if ($requireSpecial && ! preg_match('/[^a-zA-Z0-9]/', (string) $value)) {
            $fail('Le mot de passe doit contenir au moins un caractère spécial.');
        }
    }
}
