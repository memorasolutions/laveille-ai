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
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'customer_id' => BookingCustomer::factory(),
            'service_id' => BookingService::factory(),
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify('+60 minutes'),
            'status' => 'pending',
            'cancel_token' => Str::random(32),
            'source' => 'web',
        ];
    }
}
