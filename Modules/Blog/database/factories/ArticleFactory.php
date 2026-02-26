<?php

declare(strict_types=1);

namespace Modules\Blog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Article;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['draft', 'published', 'archived']);

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'slug' => null,
            'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'excerpt' => fake()->sentence(15),
            'featured_image' => null,
            'status' => $status,
            'published_at' => $status === 'published' ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'category' => fake()->randomElement(['Tech', 'Laravel', 'Design', 'Business', null]),
            'tags' => fake()->randomElements(['php', 'laravel', 'vue', 'tailwind', 'api'], fake()->numberBetween(0, 3)),
            'meta' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published', 'published_at' => now()->subDay()]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }
}
