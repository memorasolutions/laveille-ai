<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\KnowledgeChunk;
use Modules\AI\Models\KnowledgeDocument;

class KnowledgeChunkFactory extends Factory
{
    protected $model = KnowledgeChunk::class;

    public function definition(): array
    {
        return [
            'document_id' => KnowledgeDocument::factory(),
            'chunk_index' => 0,
            'content' => fake()->paragraph(5),
            'embedding' => null,
            'token_count' => fake()->numberBetween(100, 500),
        ];
    }

    public function withEmbedding(): static
    {
        $embedding = array_map(fn () => fake()->randomFloat(6, -1, 1), range(1, 1536));

        return $this->state(['embedding' => json_encode($embedding)]);
    }
}
