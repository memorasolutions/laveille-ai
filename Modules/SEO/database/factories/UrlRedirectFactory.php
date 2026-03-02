<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SEO\Models\UrlRedirect;

class UrlRedirectFactory extends Factory
{
    protected $model = UrlRedirect::class;

    public function definition(): array
    {
        return [
            'from_url' => '/old/' . fake()->unique()->slug(2),
            'to_url' => '/new/' . fake()->slug(2),
            'status_code' => 301,
            'is_active' => true,
            'note' => fake()->optional()->sentence(),
        ];
    }
}
