<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Permission;

trait ChecksRoleElevation
{
    protected function ensureNoElevation(User $user, array $permissionIds): void
    {
        if (empty($permissionIds)) {
            return;
        }

        $permissionNames = Permission::whereIn('id', $permissionIds)
            ->pluck('name')
            ->toArray();

        if (! $user->hasAllPermissions($permissionNames)) {
            abort(403, 'Élévation de privilèges interdite.');
        }
    }

    protected function ensureNoLevelElevation(User $user, int $newLevel): void
    {
        $maxCurrentLevel = (int) ($user->roles->max('level') ?? 0);

        if ($newLevel > $maxCurrentLevel) {
            abort(403, 'Élévation de privilèges interdite.');
        }
    }
}
