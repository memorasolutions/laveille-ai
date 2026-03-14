<?php

declare(strict_types=1);

namespace Modules\Roadmap\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Roadmap\Models\Idea;
use Modules\Roadmap\Models\IdeaComment;

class IdeaCommentFactory extends Factory
{
    protected $model = IdeaComment::class;

    public function definition(): array
    {
        return [
            'idea_id' => Idea::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'is_official' => false,
        ];
    }
}
