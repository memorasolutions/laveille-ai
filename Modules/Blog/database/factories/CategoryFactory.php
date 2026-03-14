<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Blog\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
        $name = Str::title($this->faker->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => $this->faker->optional()->paragraph(),
            'color' => $this->faker->randomElement($colors),
            'is_active' => true,
        ];
    }
}
