<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Contrôleur calendrier V5-b. Gère uniquement l'export iCal (.ics).
 * Le CRUD des événements manuels est géré par le composant Livewire
 * CourseCalendar (autorisation serveur, ré-résolution par route).
 *
 * SÉCURITÉ :
 *  - Auth obligatoire (middleware 'auth' sur la route).
 *  - Accès : inscrit actif (étudiant) OU gérant (manageStructure).
 *  - Le cours est ré-résolu via le binding de route (slug, jamais un id client).
 *  - Aucune donnée du client n'intervient dans le contenu .ics.
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
     * Télécharge le calendrier d'un cours au format iCal (.ics).
     *
     * Accès gate :
     *   - inscrit actif du cours (étudiant) ;
     *   - OU gérant (admin OU owner/instructor/editor via manageStructure).
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

        // Retire les guillemets du slug pour éviter l'injection dans Content-Disposition.
        $filename = 'academie-' . str_replace('"', '', $course->slug) . '-calendrier.ics';

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
     * Génère le contenu iCal (RFC 5545) en pur PHP.
     * Aucune dépendance composer, génération de chaînes simples.
     * Chaque ligne est repliée à 75 octets (RFC 5545 section 3.1,
     * compatibilité Outlook et Apple Calendar).
     *
     * @param  Collection<int, array<string, mixed>>  $events
     */
    private function buildIcs(Course $course, Collection $events): string
    {
        $lines   = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//MEMORA solutions//Académie//FR';
        $lines[] = 'X-WR-CALNAME:' . $this->escapeIcs($course->title);
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';

        // Domaine dynamique (évite le domaine codé en dur).
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'laveille.ai';

        foreach ($events as $ev) {
            /** @var \Illuminate\Support\Carbon $start */
            $start = $ev['starts_at'];
            $end   = $ev['ends_at'] ?? $start;
            $uid   = 'academy-' . $ev['id'] . '@' . $host;

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

        // Repliage RFC 5545 : chaque ligne > 75 octets est découpée avec CRLF + espace.
        $folded = array_map([$this, 'foldLine'], $lines);

        return implode("\r\n", $folded) . "\r\n";
    }

    /**
     * Replie une ligne dépassant 75 octets (RFC 5545 section 3.1).
     * Découpe sur les frontières de caractères UTF-8 pour éviter la corruption.
     */
    private function foldLine(string $line): string
    {
        $folded = '';
        $bytes  = 0;

        foreach (mb_str_split($line, 1, 'UTF-8') as $char) {
            $charLen = strlen($char); // octets, pas caractères
            if ($bytes + $charLen > 75 && $bytes > 0) {
                $folded .= "\r\n ";
                $bytes   = 1; // l'espace de continuation compte pour 1 octet
            }
            $folded .= $char;
            $bytes  += $charLen;
        }

        return $folded;
    }

    /**
     * Échappe les caractères spéciaux pour le format iCal (RFC 5545 section 3.3.11).
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
