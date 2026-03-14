<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Modules\Booking\Models\WaitlistEntry;

class WaitlistService
{
    public function join(int $serviceId, int $customerId, string $date, ?string $timeSlot = null): WaitlistEntry
    {
        return WaitlistEntry::create([
            'service_id' => $serviceId,
            'customer_id' => $customerId,
            'preferred_date' => $date,
            'preferred_time_start' => $timeSlot,
            'status' => 'waiting',
        ]);
    }

    public function notifyAvailability(string $date, int $serviceId): int
    {
        $entries = WaitlistEntry::waiting()
            ->forDate($date)
            ->where('service_id', $serviceId)
            ->get();

        foreach ($entries as $entry) {
            $entry->update([
                'status' => 'notified',
                'notified_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);
        }

        return $entries->count();
    }

    public function expireStale(): int
    {
        $entries = WaitlistEntry::notified()
            ->where('expires_at', '<', now())
            ->get();

        foreach ($entries as $entry) {
            $entry->update(['status' => 'expired']);
        }

        return $entries->count();
    }
}
