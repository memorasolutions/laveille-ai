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
use Modules\AI\Enums\TicketPriority;
use Modules\AI\Enums\TicketStatus;
use Modules\AI\Models\Ticket;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Open,
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'user_id' => User::factory(),
            'agent_id' => null,
            'category' => fake()->randomElement(['billing', 'technical', 'general']),
        ];
    }
}
