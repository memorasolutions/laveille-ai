<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Booking\Models\BookingCustomer;

class BookingCustomerFactory extends Factory
{
    protected $model = BookingCustomer::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+1'.fake()->numerify('##########'),
            'notes' => null,
            'timezone' => 'America/Toronto',
            'no_show_count' => 0,
            'portal_token' => Str::random(32),
        ];
    }
}
