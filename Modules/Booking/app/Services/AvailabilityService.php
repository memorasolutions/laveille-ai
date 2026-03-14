<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Carbon\Carbon;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\DateOverride;

class AvailabilityService
{
    protected string $timezone;

    protected array $workingHours;

    protected int $bufferMinutes;

    protected int $minNoticeHours;

    protected int $maxAdvanceDays;

    public function __construct()
    {
        $this->timezone = config('booking.timezone', 'America/Toronto');
        $this->workingHours = config('booking.working_hours', []);
        $this->bufferMinutes = (int) config('booking.buffer_minutes', 15);
        $this->minNoticeHours = (int) config('booking.min_notice_hours', 48);
        $this->maxAdvanceDays = (int) config('booking.max_advance_days', 60);
    }

    public function getAvailableSlots(string $date, int $durationMinutes): array
    {
        $dateObj = Carbon::parse($date, $this->timezone);
        $dayName = strtolower($dateObj->englishDayOfWeek);

        if (! $this->isDateAvailable($dateObj)) {
            return [];
        }

        $workingHours = $this->workingHours[$dayName] ?? null;
        if (! $workingHours) {
            return [];
        }

        $slots = [];
        $currentTime = Carbon::parse($date.' '.$workingHours['start'], $this->timezone);
        $endOfDay = Carbon::parse($date.' '.$workingHours['end'], $this->timezone);

        while ($currentTime->copy()->addMinutes($durationMinutes)->lte($endOfDay)) {
            if ($this->isSlotAvailable($date, $currentTime->format('H:i'), $durationMinutes)) {
                $slots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end' => $currentTime->copy()->addMinutes($durationMinutes)->format('H:i'),
                ];
            }
            $currentTime->addMinutes($durationMinutes + $this->bufferMinutes);
        }

        return $slots;
    }

    public function isSlotAvailable(string $date, string $startTime, int $durationMinutes): bool
    {
        $start = Carbon::parse($date.' '.$startTime, $this->timezone);
        $end = $start->copy()->addMinutes($durationMinutes);
        $now = Carbon::now($this->timezone);

        if ($now->diffInHours($start, false) < $this->minNoticeHours) {
            return false;
        }

        if (! $this->isDateAvailable($start)) {
            return false;
        }

        $dayName = strtolower($start->englishDayOfWeek);
        $workingHours = $this->workingHours[$dayName] ?? null;

        if (! $workingHours) {
            return false;
        }

        $dayStart = Carbon::parse($date.' '.$workingHours['start'], $this->timezone);
        $dayEnd = Carbon::parse($date.' '.$workingHours['end'], $this->timezone);

        if ($start->lt($dayStart) || $end->gt($dayEnd)) {
            return false;
        }

        return ! Appointment::where(function ($query) use ($start, $end) {
            $query->where(function ($q) use ($start, $end) {
                $q->where('start_at', '<', $end)->where('end_at', '>', $start);
            });
        })->whereIn('status', ['confirmed', 'pending'])->exists();
    }

    public function getAvailableDates(?int $daysAhead = null): array
    {
        $daysAhead = $daysAhead ?? $this->maxAdvanceDays;
        $availableDates = [];
        $now = Carbon::now($this->timezone);
        $defaultDuration = (int) config('booking.slot_duration_minutes', 30);

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = $now->copy()->addDays($i);
            $dayName = strtolower($date->englishDayOfWeek);

            if (! ($this->workingHours[$dayName] ?? null)) {
                continue;
            }

            if (! $this->isDateAvailable($date)) {
                continue;
            }

            $slots = $this->getAvailableSlots($date->format('Y-m-d'), $defaultDuration);
            if (! empty($slots)) {
                $availableDates[] = $date->format('Y-m-d');
            }
        }

        return $availableDates;
    }

    protected function isDateAvailable(Carbon $date): bool
    {
        $override = DateOverride::whereDate('date', $date->format('Y-m-d'))->first();

        if ($override && $override->override_type === 'blocked' && $override->all_day) {
            return false;
        }

        return true;
    }
}
