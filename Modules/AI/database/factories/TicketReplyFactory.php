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
use Modules\AI\Models\Ticket;
use Modules\AI\Models\TicketReply;

/** @extends Factory<TicketReply> */
class TicketReplyFactory extends Factory
{
    protected $model = TicketReply::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'is_internal' => false,
        ];
    }
}
