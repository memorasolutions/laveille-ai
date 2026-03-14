<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\KnowledgeUrl;

class KnowledgeUrlFactory extends Factory
{
    protected $model = KnowledgeUrl::class;

    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'label' => fake()->words(3, true),
            'hidden_source_name' => null,
            'robots_allowed' => true,
            'scrape_status' => 'pending',
            'scrape_frequency' => 'weekly',
            'max_pages' => 50,
            'is_active' => true,
        ];
    }

    public function robotsBlocked(): static
    {
        return $this->state([
            'robots_allowed' => false,
            'scrape_status' => 'robots_blocked',
        ]);
    }
}
