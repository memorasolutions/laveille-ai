<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('super_admin') || ($user->hasRole('admin') && ! $model->hasRole('super_admin'));
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('super_admin') && $user->id !== $model->id;
    }
}
