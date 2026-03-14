<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\KnowledgeDocument;

class KnowledgeDocumentFactory extends Factory
{
    protected $model = KnowledgeDocument::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'source_type' => fake()->randomElement(['manual', 'faq', 'page', 'article', 'service']),
            'source_id' => null,
            'content' => fake()->paragraphs(3, true),
            'metadata' => ['category' => 'general'],
            'is_active' => true,
            'last_synced_at' => null,
            'tenant_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ofType(string $type): static
    {
        return $this->state(['source_type' => $type]);
    }
}
