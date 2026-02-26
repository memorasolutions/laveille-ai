<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Modules\Auth\Models\LoginAttempt;
use Modules\Settings\Facades\Settings;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        LoginAttempt::create([
            'user_id' => $event->user?->id,
            'email' => $event->credentials['email'] ?? 'unknown',
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => request()->userAgent(),
            'status' => 'failed',
            'logged_in_at' => now(),
        ]);

        $this->incrementLockout($event->credentials['email'] ?? null);
    }

    private function incrementLockout(?string $email): void
    {
        if (! $email) {
            return;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return;
        }

        $user->increment('failed_login_count');

        $maxAttempts = (int) Settings::get('security.max_login_attempts', 5);
        $lockoutMinutes = (int) Settings::get('security.lockout_duration', 30);

        if ($user->fresh()->failed_login_count >= $maxAttempts) {
            $user->update(['locked_until' => now()->addMinutes($lockoutMinutes)]);
        }
    }
}
