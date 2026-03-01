<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleRevision;

class ArticleRevisionFactory extends Factory
{
    protected $model = ArticleRevision::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'excerpt' => $this->faker->sentence(),
            'status' => 'draft',
            'meta' => null,
            'revision_number' => 1,
            'summary' => null,
        ];
    }
}
