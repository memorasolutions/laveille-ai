<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Privacy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Privacy\Models\RightsRequest;

class RightsRequestFactory extends Factory
{
    protected $model = RightsRequest::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'request_type' => $this->faker->randomElement([
                'access', 'rectification', 'erasure', 'portability',
                'opposition', 'limitation', 'withdrawal',
            ]),
            'description' => $this->faker->paragraph,
            'status' => 'pending',
            'jurisdiction' => $this->faker->randomElement(['gdpr', 'canada_quebec', 'pipeda', 'ccpa']),
            'deadline_at' => now()->addDays(30),
        ];
    }
}
