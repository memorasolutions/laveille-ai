<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\RolesPermissions\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function getAllRoles(): Collection
    {
        return Role::with('permissions')->get();
    }

    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    public function createRole(string $name, array $permissions = []): Role|\Spatie\Permission\Contracts\Role
    {
        $role = Role::create(['name' => $name]);

        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role;
    }

    public function updateRole(Role $role, string $name, array $permissions = []): Role
    {
        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);

        return $role->fresh();
    }

    public function deleteRole(Role $role): bool
    {
        if (in_array($role->name, ['super_admin', 'admin'])) {
            return false;
        }

        return (bool) $role->delete();
    }

    public function assignRole(User $user, string $role): void
    {
        $user->assignRole($role);
    }

    public function removeRole(User $user, string $role): void
    {
        $user->removeRole($role);
    }

    public function syncRoles(User $user, array $roles): void
    {
        $user->syncRoles($roles);
    }
}
