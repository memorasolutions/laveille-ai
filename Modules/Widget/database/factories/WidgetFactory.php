<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Widget\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Widget\Models\Widget;

class WidgetFactory extends Factory
{
    protected $model = Widget::class;

    public function definition(): array
    {
        return [
            'zone' => fake()->randomElement(['sidebar', 'footer', 'after_content']),
            'type' => fake()->randomElement(['html', 'recent_posts', 'newsletter', 'social_links', 'cta_button', 'custom_text']),
            'title' => fake()->words(3, true),
            'content' => '<p>' . fake()->paragraph() . '</p>',
            'settings' => [],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
