<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Séances en direct - export iCalendar (.ics) d'une séance pour « Ajouter à
 * mon calendrier ». GÉNÉRATION serveur, gâtée par le drapeau + l'accès au cours
 * (inscrit actif OU staff, anti-IDOR : la séance est re-scopée au cours).
 *
 * Aucune dépendance externe : le fichier .ics est un simple texte VCALENDAR
 * (RFC 5545). Les heures sont émises en UTC (suffixe Z), standard iCal.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LiveSession;

class LiveSessionController extends Controller
{
    /** Génère et renvoie le fichier .ics d'une séance (accès gâté serveur). */
    public function ics(Course $course, int $session): Response
    {
        abort_unless((bool) config('academy.live_sessions_enabled', false), 404);
        abort_unless(auth()->check(), 403);

        // Accès : inscrit actif OU staff du cours (anti-IDOR, autorisation serveur).
        $user      = auth()->user();
        $isStaff   = $user->can('academy.manage') || $course->hasRole($user, ['owner', 'instructor', 'editor', 'assistant']);
        $isEnrolled = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($isStaff || $isEnrolled, 403);

        // Séance re-résolue SCOPÉE au cours (anti-IDOR).
        $live = LiveSession::query()
            ->whereKey($session)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $ics = $this->buildIcs($live, $course);

        return response($ics, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="seance-' . $live->id . '.ics"',
        ]);
    }

    /** Construit le contenu VCALENDAR (RFC 5545) d'une séance. Heures en UTC (Z). */
    private function buildIcs(LiveSession $live, Course $course): string
    {
        $fmt   = static fn ($carbon): string => $carbon->utc()->format('Ymd\THis\Z');
        $start = $fmt($live->starts_at);
        $end   = $fmt($live->ends_at ?? $live->starts_at->copy()->addHour());
        $uid   = $live->id . '-live@' . (config('academy.site_host') ?: 'laveille.ai');

        $summary  = $this->escape($course->title . ' - ' . $live->title);
        $descLine = trim(($live->description ? $live->description . "\n\n" : '') . 'Lien : ' . $live->join_url);
        $desc     = $this->escape($descLine);
        $location = $this->escape($live->join_url);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MEMORA solutions//Academie//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $start,
            'DTEND:' . $end,
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $desc,
            'LOCATION:' . $location,
            'URL:' . $this->escape($live->join_url),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        // RFC 5545 : lignes séparées par CRLF.
        return implode("\r\n", $lines) . "\r\n";
    }

    /** Échappe les caractères réservés iCal (virgule, point-virgule, backslash, saut de ligne). */
    private function escape(string $value): string
    {
        $value = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $value);

        return str_replace(["\r\n", "\n", "\r"], '\\n', Str::of($value)->trim());
    }
}
