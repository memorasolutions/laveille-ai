<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Settings\Facades\Settings;

class PasswordHistoryRule implements ValidationRule
{
    public function __construct(private ?int $userId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->userId === null) {
            return;
        }

        $historyCount = (int) Settings::get('security.password_history_count', 5);

        $previousHashes = DB::table('password_histories')
            ->where('user_id', $this->userId)
            ->orderByDesc('created_at')
            ->limit($historyCount)
            ->pluck('password_hash');

        foreach ($previousHashes as $oldHash) {
            if (Hash::check((string) $value, $oldHash)) {
                $fail('Ce mot de passe a deja ete utilise recemment. Veuillez en choisir un different.');

                return;
            }
        }
    }
}
