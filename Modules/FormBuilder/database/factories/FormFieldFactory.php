<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\FormBuilder\Models\Form;
use Modules\FormBuilder\Models\FormField;

class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    public function definition(): array
    {
        $label = fake()->words(2, true);

        return [
            'form_id' => Form::factory(),
            'type' => fake()->randomElement(['text', 'email', 'textarea', 'select']),
            'label' => $label,
            'name' => Str::snake($label),
            'placeholder' => fake()->optional()->sentence(3),
            'options' => null,
            'validation_rules' => null,
            'is_required' => fake()->boolean(60),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
