<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Roadmap\Models\RoadmapCategory;

class RoadmapCategoryFactory extends Factory
{
    protected $model = RoadmapCategory::class;

    public function definition(): array
    {
        $name = ucwords($this->faker->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => '#'.substr($this->faker->hexColor(), 0, 6),
            'description' => $this->faker->optional()->sentence(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'tenant_id' => null,
        ];
    }
}
