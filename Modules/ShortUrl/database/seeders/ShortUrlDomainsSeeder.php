<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ShortUrl\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ShortUrl\Models\ShortUrlDomain;

class ShortUrlDomainsSeeder extends Seeder
{
    public function run(): void
    {
        ShortUrlDomain::updateOrCreate(
            ['domain' => 'veille.la'],
            ['is_default' => true, 'is_active' => true]
        );

        ShortUrlDomain::updateOrCreate(
            ['domain' => 'go3.ca'],
            ['is_default' => false, 'is_active' => true]
        );
    }
}
