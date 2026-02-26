<?php

declare(strict_types=1);

namespace Modules\RolesPermissions\Database\Seeders;

use Illuminate\Database\Seeder;

class RolesPermissionsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);
    }
}
