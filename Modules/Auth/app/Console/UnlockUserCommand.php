<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Console;

use App\Models\User;
use Illuminate\Console\Command;

class UnlockUserCommand extends Command
{
    protected $signature = 'auth:unlock-user {email}';

    protected $description = 'Unlock a locked user account';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return 1;
        }

        $user->failed_login_count = 0;
        $user->locked_until = null;
        $user->save();

        $this->info("User {$email} has been unlocked.");

        return 0;
    }
}
