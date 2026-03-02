<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Testimonials\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Testimonials\Models\Testimonial;

class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'author_name' => fake()->name(),
            'author_title' => fake()->jobTitle(),
            'author_avatar' => null,
            'content' => fake()->paragraphs(2, true),
            'rating' => fake()->numberBetween(3, 5),
            'order' => fake()->numberBetween(0, 50),
            'is_approved' => true,
        ];
    }

    public function unapproved(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
