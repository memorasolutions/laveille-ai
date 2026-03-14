<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Booking\Models\Appointment;

class RecurrenceService
{
    private const MAX_OCCURRENCES = 52;

    public function generateRecurrences(Appointment $parent, string $type, string $endDate): array
    {
        $validTypes = ['daily', 'weekly', 'biweekly', 'monthly'];
        if (! in_array($type, $validTypes)) {
            throw new \InvalidArgumentException('Type de récurrence invalide.');
        }

        $startAt = Carbon::parse($parent->start_at);
        $endAt = Carbon::parse($parent->end_at);
        $durationMinutes = (int) $startAt->diffInMinutes($endAt);
        $endRecurrence = Carbon::parse($endDate)->endOfDay();

        $parent->update([
            'recurrence_type' => $type,
            'recurrence_end_date' => $endDate,
        ]);

        $createdIds = [];
        $current = $startAt->copy();
        $count = 0;

        while ($count < self::MAX_OCCURRENCES) {
            $current = match ($type) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'biweekly' => $current->addWeeks(2),
                'monthly' => $current->addMonth(),
            };

            if ($current->gt($endRecurrence)) {
                break;
            }

            $child = Appointment::create([
                'service_id' => $parent->service_id,
                'customer_id' => $parent->customer_id,
                'start_at' => $current->toDateTimeString(),
                'end_at' => $current->copy()->addMinutes($durationMinutes)->toDateTimeString(),
                'status' => 'pending',
                'cancel_token' => Str::random(32),
                'source' => $parent->source ?? 'web',
                'recurrence_parent_id' => $parent->id,
                'recurrence_type' => $type,
            ]);

            $createdIds[] = $child->id;
            $count++;
        }

        return $createdIds;
    }

    public function cancelSeries(Appointment $parent): int
    {
        $cancelled = Appointment::where('recurrence_parent_id', $parent->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->update(['status' => 'cancelled']);

        if (! in_array($parent->status, ['completed', 'cancelled'])) {
            $parent->update(['status' => 'cancelled']);
            $cancelled++;
        }

        return $cancelled;
    }
}
