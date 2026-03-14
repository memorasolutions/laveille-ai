<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\FormBuilder\Models\Form;
use Modules\FormBuilder\Models\FormSubmission;

class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'data' => ['name' => fake()->name(), 'email' => fake()->safeEmail()],
            'status' => 'pending',
            'ip_address' => fake()->ipv4(),
        ];
    }
}
