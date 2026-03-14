<?php

declare(strict_types=1);

namespace Modules\Roadmap\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Roadmap\Models\Board;

class BoardFactory extends Factory
{
    protected $model = Board::class;

    public function definition(): array
    {
        $name = $this->faker->sentence(3);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->paragraph(),
            'is_public' => true,
            'color' => '#6366f1',
            'sort_order' => 0,
        ];
    }
}
