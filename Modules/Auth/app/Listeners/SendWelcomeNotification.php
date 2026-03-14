<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use App\Models\User;
use Modules\Core\Events\UserCreated;
use Modules\Notifications\Notifications\WelcomeNotification;

class SendWelcomeNotification
{
    public function handle(UserCreated $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $user->notify(new WelcomeNotification);
    }
}
