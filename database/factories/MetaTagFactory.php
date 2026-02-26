<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SEO\Models\MetaTag;

/**
 * @extends Factory<MetaTag>
 */
class MetaTagFactory extends Factory
{
    protected $model = MetaTag::class;

    public function definition(): array
    {
        return [
            'url_pattern' => '/'.fake()->unique()->slug(2),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(10),
            'keywords' => implode(', ', fake()->words(5)),
            'og_title' => fake()->sentence(4),
            'og_description' => fake()->sentence(8),
            'og_image' => null,
            'twitter_card' => 'summary',
            'robots' => 'index, follow',
            'canonical_url' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withWildcard(): static
    {
        return $this->state(fn (array $attributes) => [
            'url_pattern' => '/blog/*',
        ]);
    }
}
