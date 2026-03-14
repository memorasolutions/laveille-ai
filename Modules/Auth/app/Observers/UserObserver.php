<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Observers;

use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\DB;

class UserObserver implements ShouldHandleEventsAfterCommit
{
    public function created(User $user): void
    {
        activity()->performedOn($user)->log("Utilisateur {$user->name} créé");

        if ($user->password) {
            DB::table('password_histories')->insert([
                'user_id' => $user->id,
                'password_hash' => $user->password,
                'created_at' => now(),
            ]);
        }
    }

    public function updated(User $user): void
    {
        activity()->performedOn($user)->withProperties(['changes' => $user->getChanges()])->log("Utilisateur {$user->name} modifié");

        if ($user->wasChanged('password')) {
            $oldHash = $user->getOriginal('password');

            if ($oldHash) {
                DB::table('password_histories')->insert([
                    'user_id' => $user->id,
                    'password_hash' => $oldHash,
                    'created_at' => now(),
                ]);
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['password_changed_at' => now()]);
        }
    }

    public function deleted(User $user): void
    {
        activity()->performedOn($user)->log("Utilisateur {$user->name} supprimé");
    }
}
