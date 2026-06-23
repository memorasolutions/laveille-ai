<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Service calendrier V5-b. Fusionne en lecture :
 *   (a) evenements MANUELS (table academy_calendar_events) ;
 *   (b) echeances DERIVEES des devoirs publies (Assignment.due_at) calculées
 *       a la volee - jamais dupliquees en base.
 *
 * SECURITE :
 *  - upcomingForUser scope les cours via les inscriptions actives de l'utilisateur
 *    (anti-IDOR : un etudiant ne voit jamais un cours ou il n'est pas inscrit).
 *  - forCourse ne filtre pas par user : c'est l'appelant (Livewire/Controller)
 *    qui doit avoir verifié l'autorisation avant d'invoquer ce service.
 *  - Aucune ecriture dans ce service (lecture seule).
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
     * Echeances FUTURES de tous les cours ou l'utilisateur est inscrit actif.
     *
     * Scope strict (anti-IDOR) : les inscriptions actives de $user servent de
     * filtre ; un etudiant ne voit jamais un cours ou il n'est pas inscrit.
     * Tri : date ascendante (la plus proche en premier).
     *
     * @param  int  $limit  Nombre maximum de resultats (défaut 10).
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

        // Charge les cours en une seule requete pour eviter N+1.
        $courses = Course::whereIn('id', $enrolledIds)->get()->keyBy('id');

        $all = collect();

        foreach ($enrolledIds as $courseId) {
            $course = $courses->get($courseId);
            if ($course === null) {
                continue;
            }
            $all = $all->merge($this->forCourse($course));
        }

        return $all
            ->filter(fn (array $ev): bool => $ev['starts_at']->isFuture())
            ->sortBy('starts_at')
            ->take($limit)
            ->values();
    }

    /**
     * Tous les evenements d'un cours : manuels + derives, tries par date.
     *
     * NOTE : l'autorisation d'accès au cours est de la responsabilite de
     * l'appelant (Livewire::mount() / Controller). Ce service ne fait
     * qu'agréger ; il ne vérfie PAS les droits.
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
     * Evenements manuels depuis academy_calendar_events (non supprimes).
     * Retourne une Collection de tableaux normalises.
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
     * Echeances derivees : Assignment.due_at publies du cours.
     * JAMAIS dupliquees en base - calculees ici uniquement.
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
     * Tableau uniforme pour un evenement manuel.
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
     * Tableau uniforme pour une echeance derivee d'un devoir.
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
