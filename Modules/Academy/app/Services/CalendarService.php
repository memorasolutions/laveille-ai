<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Service calendrier V5-b. Fusionne en lecture :
 *   (a) événements MANUELS (table academy_calendar_events) ;
 *   (b) échéances DÉRIVÉES des devoirs publiés (Assignment.due_at) calculées
 *       à la volée - jamais dupliquées en base.
 *
 * SÉCURITÉ :
 *  - upcomingForUser scope les cours via les inscriptions actives de l'utilisateur
 *    (anti-IDOR : un étudiant ne voit jamais un cours où il n'est pas inscrit).
 *  - forCourse ne filtre pas par user : c'est l'appelant (Livewire/Controller)
 *    qui doit avoir vérifié l'autorisation avant d'invoquer ce service.
 *  - Aucune écriture dans ce service (lecture seule).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\CalendarEvent;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;

class CalendarService
{
    // -------------------------------------------------------------------------
    // API publique
    // -------------------------------------------------------------------------

    /**
     * Échéances FUTURES de tous les cours où l'utilisateur est inscrit actif.
     *
     * Scope strict (anti-IDOR) : les inscriptions actives de $user servent de
     * filtre ; un étudiant ne voit jamais un cours où il n'est pas inscrit.
     * Tri : date ascendante (la plus proche en premier).
     *
     * Implémentation anti-N+1 : 3 requêtes globales (cours, événements, devoirs)
     * puis fusion/tri en PHP, au lieu d'une boucle par cours.
     *
     * @param  int  $limit  Nombre maximum de résultats (défaut 10).
     * @return Collection<int, array<string, mixed>>
     */
    public function upcomingForUser(User $user, int $limit = 10): Collection
    {
        $enrolledIds = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('course_id');

        if ($enrolledIds->isEmpty()) {
            return collect();
        }

        // Requête 1 : cours indexés par id (évite N+1).
        $courses = Course::whereIn('id', $enrolledIds)->get()->keyBy('id');

        // Requête 2 : tous les événements manuels des cours inscrits.
        $manualAll = CalendarEvent::whereIn('course_id', $enrolledIds)
            ->orderBy('starts_at')
            ->get();

        // Requête 3 : tous les devoirs publiés avec échéance des cours inscrits.
        $derivedAll = Assignment::query()
            ->whereIn('course_id', $enrolledIds)
            ->where('is_published', true)
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->get();

        // Normalisation en PHP (pas de requête supplémentaire).
        $manual = $manualAll
            ->filter(fn (CalendarEvent $ev): bool => $courses->has($ev->course_id))
            ->map(fn (CalendarEvent $ev): array => $this->normalizeManual($ev, $courses->get($ev->course_id)))
            ->values();

        $derived = $derivedAll
            ->filter(fn (Assignment $a): bool => $courses->has($a->course_id))
            ->map(fn (Assignment $a): array => $this->normalizeDerived($a, $courses->get($a->course_id)))
            ->values();

        return $manual
            ->merge($derived)
            ->filter(fn (array $ev): bool => $ev['starts_at']->isFuture())
            ->sortBy('starts_at')
            ->take($limit)
            ->values();
    }

    /**
     * Tous les événements d'un cours : manuels + dérivés, triés par date.
     *
     * NOTE : l'autorisation d'accès au cours est de la responsabilité de
     * l'appelant (Livewire::mount() / Controller). Ce service ne fait
     * qu'agréger ; il ne vérifie PAS les droits.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forCourse(Course $course): Collection
    {
        $manual  = $this->manualEvents($course);
        $derived = $this->derivedEvents($course);

        return $manual
            ->merge($derived)
            ->sortBy('starts_at')
            ->values();
    }

    // -------------------------------------------------------------------------
    // Agregation interne
    // -------------------------------------------------------------------------

    /**
     * Événements manuels depuis academy_calendar_events (non supprimés).
     * Retourne une Collection de tableaux normalisés.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function manualEvents(Course $course): Collection
    {
        return CalendarEvent::forCourse($course->id)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $ev): array => $this->normalizeManual($ev, $course));
    }

    /**
     * Échéances dérivées : Assignment.due_at publiés du cours.
     * JAMAIS dupliquées en base - calculées ici uniquement.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function derivedEvents(Course $course): Collection
    {
        return Assignment::query()
            ->where('course_id', $course->id)
            ->where('is_published', true)
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->get()
            ->map(fn (Assignment $a): array => $this->normalizeDerived($a, $course));
    }

    // -------------------------------------------------------------------------
    // Normalisation en tableau uniforme
    // -------------------------------------------------------------------------

    /**
     * Tableau uniforme pour un événement manuel.
     *
     * @return array<string, mixed>
     */
    private function normalizeManual(CalendarEvent $ev, Course $course): array
    {
        return [
            'id'           => 'ev-' . $ev->id,
            'source'       => 'manual',
            'event_id'     => $ev->id,
            'course_id'    => $ev->course_id,
            'course_slug'  => $course->slug,
            'course_title' => $course->title,
            'title'        => $ev->title,
            'description'  => $ev->description,
            'type'         => $ev->type,
            'starts_at'    => $ev->starts_at,
            'ends_at'      => $ev->ends_at,
            'item_link'    => null,
            'is_past'      => $ev->starts_at->isPast(),
        ];
    }

    /**
     * Tableau uniforme pour une échéance dérivée d'un devoir.
     *
     * @return array<string, mixed>
     */
    private function normalizeDerived(Assignment $assignment, Course $course): array
    {
        return [
            'id'           => 'asgn-' . $assignment->id,
            'source'       => 'derived',
            'event_id'     => null,
            'course_id'    => $assignment->course_id,
            'course_slug'  => $course->slug,
            'course_title' => $course->title,
            'title'        => $assignment->title . ' (devoir)',
            'description'  => null,
            'type'         => 'due',
            'starts_at'    => $assignment->due_at,
            'ends_at'      => null,
            'item_link'    => null,
            'is_past'      => $assignment->due_at->isPast(),
        ];
    }
}
