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
use Modules\AI\Models\CsatSurvey;

class CsatSurveyFactory extends Factory
{
    protected $model = CsatSurvey::class;

    public function definition(): array
    {
        return [
            'score' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional(0.6)->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
