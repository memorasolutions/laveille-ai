<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class,
        ]);
    }
}
