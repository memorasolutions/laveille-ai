<?php

declare(strict_types=1);

namespace Modules\Decido\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Decido\Models\Poll;
use Modules\Decido\Models\PollOption;

class PollOptionFactory extends Factory
{
    protected $model = PollOption::class;

    public function definition(): array
    {
        return [
            'poll_id' => Poll::factory(),
            'label' => fake()->words(3, true),
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 0,
        ];
    }
}
