<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\WaitlistEntry;

class WaitlistEntryFactory extends Factory
{
    protected $model = WaitlistEntry::class;

    public function definition(): array
    {
        return [
            'service_id' => BookingService::factory(),
            'customer_id' => BookingCustomer::factory(),
            'preferred_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'preferred_time_start' => $this->faker->randomElement(['09:00', '10:00', '11:00', '14:00', '15:00']),
            'status' => 'waiting',
        ];
    }
}
