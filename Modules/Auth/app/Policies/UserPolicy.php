<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('manage_users') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('manage_users') && ! $model->hasRole('super_admin');
    }

    public function delete(User $user, User $model): bool
    {
        // Le superadmin #1 ne peut JAMAIS être supprimé
        if ($model->id === 1) {
            return false;
        }

        return $user->can('manage_users') && $user->id !== $model->id;
    }
}
