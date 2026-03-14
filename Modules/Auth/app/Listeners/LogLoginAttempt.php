<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Auth\Models\LoginAttempt;

class LogLoginAttempt
{
    public function handle(Login $event): void
    {
        LoginAttempt::create([
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => request()->userAgent(),
            'status' => 'success',
            'logged_in_at' => now(),
        ]);

        // Reset lockout on successful login
        $event->user->update([
            'failed_login_count' => 0,
            'locked_until' => null,
        ]);
    }
}
