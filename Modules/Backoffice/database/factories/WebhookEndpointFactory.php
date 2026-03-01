<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Backoffice\Models\WebhookEndpoint;

class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'url' => fake()->url(),
            'secret' => fake()->sha1(),
            'is_active' => true,
        ];
    }
}
