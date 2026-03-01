<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Shared\Policies;

use App\Models\User;

abstract class AdminOnlyPolicy
{
    /**
     * The permission required for this policy.
     * Override in child classes to specify the permission.
     */
    protected string $permission = 'view_admin_panel';

    public function viewAny(User $user): bool
    {
        return $user->can($this->permission);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission);
    }

    public function update(User $user, $model = null): bool
    {
        return $user->can($this->permission);
    }

    public function delete(User $user, $model = null): bool
    {
        return $user->can($this->permission);
    }
}
