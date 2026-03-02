<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\ContentRevision;

class ContentRevisionFactory extends Factory
{
    protected $model = ContentRevision::class;

    public function definition(): array
    {
        return [
            'revisionable_type' => 'Modules\\Notifications\\Models\\EmailTemplate',
            'revisionable_id' => 1,
            'user_id' => User::factory(),
            'data' => ['title' => fake()->sentence(), 'content' => fake()->paragraph()],
            'revision_number' => 1,
            'summary' => fake()->optional()->sentence(),
        ];
    }
}
