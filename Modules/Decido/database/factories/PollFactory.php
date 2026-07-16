<?php

declare(strict_types=1);

namespace Modules\Decido\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Decido\Enums\PollStatus;
use Modules\Decido\Enums\PollType;
use Modules\Decido\Enums\VoteMode;
use Modules\Decido\Models\Poll;

class PollFactory extends Factory
{
    protected $model = Poll::class;

    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type' => PollType::CLASSIC->value,
            'vote_mode' => VoteMode::SINGLE_CHOICE->value,
            'timezone' => 'America/Toronto',
            'status' => PollStatus::OPEN->value,
            'admin_token_hash' => hash('sha256', fake()->uuid()),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => PollStatus::OPEN->value]);
    }

    public function closed(): static
    {
        return $this->state(['status' => PollStatus::CLOSED->value]);
    }
}
