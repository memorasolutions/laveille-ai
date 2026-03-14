<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Auto-seed permissions when using RefreshDatabase
    protected bool $seed = true;

    protected string $seeder = \Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class;
}
