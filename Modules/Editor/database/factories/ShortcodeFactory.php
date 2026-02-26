<?php

declare(strict_types=1);

namespace Modules\Editor\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Editor\Models\Shortcode;

class ShortcodeFactory extends Factory
{
    protected $model = Shortcode::class;

    public function definition(): array
    {
        return [
            'tag' => fake()->unique()->word(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'html_template' => '<div class="{{ $class }}">{{ $content }}</div>',
            'parameters' => ['class', 'content'],
            'has_content' => true,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function selfClosing(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_content' => false,
            'html_template' => '<span>{{ $text }}</span>',
            'parameters' => ['text'],
        ]);
    }
}
