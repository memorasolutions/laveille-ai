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
use Modules\AI\Models\InternalNote;

/** @extends Factory<InternalNote> */
class InternalNoteFactory extends Factory
{
    protected $model = InternalNote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'conversation_id' => AiConversation::factory(),
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
        ];
    }
}
