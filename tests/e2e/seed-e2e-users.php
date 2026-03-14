<?php

declare(strict_types=1);

/**
 * E2E test user seeder - run via: php artisan tinker tests/e2e/seed-e2e-users.php
 * Or: php tests/e2e/seed-e2e-users.php (with Laravel bootstrap)
 *
 * Creates/updates 4 test users with known passwords for Playwright E2E tests.
 */

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$password = bcrypt('e2e-test-password');

$users = [
    ['email' => 'e2e-superadmin@test.local', 'name' => 'E2E Super Admin', 'role' => 'super_admin'],
    ['email' => 'e2e-admin@test.local', 'name' => 'E2E Admin', 'role' => 'admin'],
    ['email' => 'e2e-editor@test.local', 'name' => 'E2E Editor', 'role' => 'editor'],
    ['email' => 'e2e-user@test.local', 'name' => 'E2E User', 'role' => 'user'],
];

foreach ($users as $data) {
    $user = User::updateOrCreate(
        ['email' => $data['email']],
        [
            'name' => $data['name'],
            'password' => $password,
            'email_verified_at' => now(),
            'is_active' => true,
        ]
    );

    $user->syncRoles([$data['role']]);
    echo "✓ {$data['email']} → {$data['role']}\n";
}

echo "\nE2E test users ready (password: e2e-test-password)\n";
