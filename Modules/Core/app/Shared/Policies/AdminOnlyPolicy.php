<?php

declare(strict_types=1);

namespace Modules\Core\Shared\Policies;

use App\Models\User;

abstract class AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, $model = null): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, $model = null): bool
    {
        return $user->hasRole('super_admin');
    }
}
