<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\GroupRegistration;

class GroupRegistrationFactory extends Factory
{
    protected $model = GroupRegistration::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'customer_id' => BookingCustomer::factory(),
            'status' => 'registered',
            'registered_at' => now(),
        ];
    }
}
