<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\DupeModel;

class DupeModelFactory extends Factory
{
    protected $model = DupeModel::class;

    public function definition(): array
    {
        return [

        ];
    }
}
