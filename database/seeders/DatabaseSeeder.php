<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@laravel-core.test'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->assignRole('super_admin');

        $admin = User::firstOrCreate(
            ['email' => 'moderator@laravel-core.test'],
            [
                'name' => 'Modérateur',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

        if (! app()->isProduction()) {
            $users = User::factory()->count(5)->create();
            foreach ($users as $user) {
                $user->assignRole('user');
            }
        }

        if (class_exists('\Modules\Settings\Database\Seeders\SettingsDatabaseSeeder')) {
            $this->call([
                \Modules\Settings\Database\Seeders\SettingsDatabaseSeeder::class,
            ]);
        }
    }
}
