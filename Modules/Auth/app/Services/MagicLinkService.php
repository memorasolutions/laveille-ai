<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Facades\Settings;

class MagicLinkService
{
    private const DEFAULT_EXPIRY_MINUTES = 15;

    public function generate(string $email): array
    {
        DB::table('magic_login_tokens')->where('email', $email)->delete();
        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiryMinutes = (int) Settings::get('magic_link_expiry_minutes', self::DEFAULT_EXPIRY_MINUTES);
        $expiresAt = now()->addMinutes($expiryMinutes);
        DB::table('magic_login_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
            'used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    // Verify a token for the given email
    // Returns User on success, null on failure
    // Marks token as used and deletes it
    public function verify(string $email, string $token): ?User
    {
        $record = DB::table('magic_login_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return null;
        }

        DB::table('magic_login_tokens')->where('id', $record->id)->delete();

        $user = User::where('email', $email)->first();

        // ACTION: refuser la connexion par code d'un compte désactivé ou verrouillé
        // MCP: SELF (< 5 lignes) | RAISON: point d'étranglement unique (web + API), même échec
        // qu'un code invalide pour ne pas révéler l'état du compte.
        if ($user !== null && (! $user->is_active || $user->isLocked())) {
            return null;
        }

        return $user;
    }

    // Check if an email has a valid (unexpired) token
    public function hasValidToken(string $email): bool
    {
        return DB::table('magic_login_tokens')
            ->where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->exists();
    }

    // Clean up expired tokens (for scheduled cleanup)
    public function cleanup(): int
    {
        return DB::table('magic_login_tokens')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
