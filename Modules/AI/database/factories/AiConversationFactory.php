<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\AiConversation;

class AiConversationFactory extends Factory
{
    protected $model = AiConversation::class;

    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'session_id' => fake()->uuid(),
            'title' => fake()->sentence(3),
            'status' => \Modules\AI\Enums\ConversationStatus::AiActive,
            'model' => 'gpt-4o-mini',
            'system_prompt' => null,
            'context' => [],
            'metadata' => [],
            'tokens_used' => fake()->numberBetween(0, 5000),
            'cost_estimate' => fake()->randomFloat(4, 0, 1),
        ];
    }
}
