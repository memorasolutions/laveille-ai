<?php

declare(strict_types=1);

namespace Modules\Auth\Observers;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class UserObserver implements ShouldHandleEventsAfterCommit
{
    public function created(User $user): void
    {
        activity()->performedOn($user)->log("Utilisateur {$user->name} créé");
    }

    public function updated(User $user): void
    {
        activity()->performedOn($user)->withProperties(['changes' => $user->getChanges()])->log("Utilisateur {$user->name} modifié");
    }

    public function deleted(User $user): void
    {
        activity()->performedOn($user)->log("Utilisateur {$user->name} supprimé");
    }
}
