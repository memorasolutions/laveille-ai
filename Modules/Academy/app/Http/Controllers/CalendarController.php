<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Controleur calendrier V5-b. Gere uniquement l'export iCal (.ics).
 * Le CRUD des evenements manuels est gere par le composant Livewire
 * CourseCalendar (autorisation serveur, re-resolution par route).
 *
 * SECURITE :
 *  - Auth obligatoire (middleware 'auth' sur la route).
 *  - Acces : inscrit actif (etudiant) OU gerant (manageStructure).
 *  - Le cours est re-resolu via le binding de route (slug, jamais un id client).
 *  - Aucune donnee du client n'intervient dans le contenu .ics.
 *  - Content-Disposition attachment (pas inline) = pas de rendu navigateur.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\CalendarService;

class CalendarController extends Controller
{
    // -------------------------------------------------------------------------
    // Export iCal
    // -------------------------------------------------------------------------

    /**
     * Telecharge le calendrier d'un cours au format iCal (.ics).
     *
     * Acces gate :
     *   - inscrit actif du cours (etudiant) ;
     *   - OU gerant (admin OU owner/instructor/editor via manageStructure).
     */
    public function ical(Course $course, CalendarService $service): Response
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $isEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        $isManager = $user->can('manageStructure', $course);

        abort_unless($isEnrolled || $isManager, 403);

        $events = $service->forCourse($course);
        $ics    = $this->buildIcs($course, $events);

        $filename = 'academie-' . $course->slug . '-calendrier.ics';

        return response($ics, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    // -------------------------------------------------------------------------
    // Generation iCal (pas de dependance composer)
    // -------------------------------------------------------------------------

    /**
     * Genere le contenu iCal (RFC 5545) en pur PHP.
     * Aucune dependance composer, generation de chaines simples.
     *
     * @param  Collection<int, array<string, mixed>>  $events
     */
    private function buildIcs(Course $course, Collection $events): string
    {
        $lines   = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//MEMORA solutions//Academie//FR';
        $lines[] = 'X-WR-CALNAME:' . $this->escapeIcs($course->title);
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';

        foreach ($events as $ev) {
            /** @var \Illuminate\Support\Carbon $start */
            $start = $ev['starts_at'];
            $end   = $ev['ends_at'] ?? $start;
            $uid   = 'academy-' . $ev['id'] . '@laveille.ai';

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'     . $uid;
            $lines[] = 'SUMMARY:' . $this->escapeIcs($ev['title']);
            $lines[] = 'DTSTART:' . $start->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'   . $end->utc()->format('Ymd\THis\Z');
            $lines[] = 'STATUS:CONFIRMED';

            if (! empty($ev['description'])) {
                $lines[] = 'DESCRIPTION:' . $this->escapeIcs((string) $ev['description']);
            }

            $lines[] = 'CATEGORIES:' . strtoupper((string) $ev['type']);
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Echappe les caracteres speciaux pour le format iCal (RFC 5545 section 3.3.11).
     * Ordre obligatoire : backslash d'abord, puis virgule et point-virgule.
     */
    private function escapeIcs(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace(["\r\n", "\r", "\n"], '\\n', $text);

        return $text;
    }
}
