<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Newsletter\Models\Subscriber;

class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->optional(0.7)->name(),
            'token' => Str::random(64),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['confirmed_at' => now()]);
    }

    public function pending(): static
    {
        return $this->state(['confirmed_at' => null, 'unsubscribed_at' => null]);
    }

    public function unsubscribed(): static
    {
        return $this->state(['unsubscribed_at' => now()]);
    }
}
