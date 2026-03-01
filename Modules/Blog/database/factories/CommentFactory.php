<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Comment;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'article_id' => null,
            'user_id' => null,
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->safeEmail(),
            'content' => $this->faker->paragraph(),
            'status' => 'pending',
            'parent_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function spam(): static
    {
        return $this->state(['status' => 'spam']);
    }
}
