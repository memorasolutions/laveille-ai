<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Roadmap\Enums\IdeaStatus;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;

class IdeaFactory extends Factory
{
    protected $model = Idea::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'board_id' => Board::factory(),
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(IdeaStatus::cases()),
            'category' => $this->faker->randomElement(['feature', 'bug', 'improvement', 'ux']),
            'vote_count' => 0,
            'comment_count' => 0,
            'pinned' => false,
        ];
    }
}
