<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Illuminate\Support\Collection;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\GroupRegistration;

class GroupBookingService
{
    public function register(Appointment $appointment, BookingCustomer $customer): GroupRegistration
    {
        if (! $appointment->service->is_group) {
            throw new \RuntimeException('Ce service n\'accepte pas les inscriptions de groupe.');
        }

        if ($this->spotsRemaining($appointment) <= 0) {
            throw new \RuntimeException('Ce cours de groupe est complet.');
        }

        return GroupRegistration::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'status' => 'registered',
            'registered_at' => now(),
        ]);
    }

    public function cancelRegistration(GroupRegistration $registration): void
    {
        $registration->update(['status' => 'cancelled']);
    }

    public function getRegistrations(Appointment $appointment): Collection
    {
        return GroupRegistration::active()
            ->forAppointment($appointment->id)
            ->with('customer')
            ->get();
    }

    public function spotsRemaining(Appointment $appointment): int
    {
        $active = GroupRegistration::active()->forAppointment($appointment->id)->count();

        return max(0, $appointment->service->max_participants - $active);
    }
}
