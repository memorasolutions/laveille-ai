<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Illuminate\Support\Collection;

class ICalService
{
    public function generateCalendar(Collection $appointments, string $calendarName): string
    {
        $timezone = config('booking.timezone', 'America/Toronto');
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Booking//Module//FR\r\n";
        $ics .= "X-WR-CALNAME:{$calendarName}\r\n";
        $ics .= "X-WR-TIMEZONE:{$timezone}\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";

        foreach ($appointments as $appointment) {
            $summary = $appointment->service?->name ?? 'Rendez-vous';
            $uid = "booking-{$appointment->id}@{$domain}";
            $dtStart = $appointment->start_at->setTimezone($timezone)->format('Ymd\THis');
            $dtEnd = $appointment->end_at->setTimezone($timezone)->format('Ymd\THis');

            $status = match ($appointment->status) {
                'confirmed' => 'CONFIRMED',
                'cancelled' => 'CANCELLED',
                default => 'TENTATIVE',
            };

            $ics .= "BEGIN:VEVENT\r\n";
            $ics .= "UID:{$uid}\r\n";
            $ics .= "DTSTART:{$dtStart}\r\n";
            $ics .= "DTEND:{$dtEnd}\r\n";
            $ics .= "SUMMARY:{$summary}\r\n";
            $ics .= "STATUS:{$status}\r\n";
            $ics .= "END:VEVENT\r\n";
        }

        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }
}
