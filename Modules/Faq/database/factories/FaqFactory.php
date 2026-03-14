<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Faq\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Faq\Models\Faq;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => fake()->sentence().' ?',
            'answer' => fake()->paragraphs(2, true),
            'category' => fake()->randomElement(['General', 'Facturation', 'Technique', 'Compte']),
            'order' => fake()->numberBetween(0, 50),
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
