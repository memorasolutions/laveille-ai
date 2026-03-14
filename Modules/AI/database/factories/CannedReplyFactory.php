<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\CannedReply;

/** @extends Factory<CannedReply> */
class CannedReplyFactory extends Factory
{
    protected $model = CannedReply::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'shortcut' => fake()->optional()->lexify('???'),
            'category' => fake()->randomElement(['general', 'billing', 'support']),
            'is_active' => true,
            'user_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
