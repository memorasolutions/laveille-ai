<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Service calendrier V5-b. Fusionne en lecture :
 *   (a) événements MANUELS (table academy_calendar_events) ;
 *   (b) échéances DÉRIVÉES des devoirs publiés (Assignment.due_at) calculées
 *       à la volée - jamais dupliquées en base ;
 *   (c) séances en direct (Models\LiveSession, RÉUTILISÉES sans duplication),
 *       UNIQUEMENT dans monthForUser() (calendrier global, Vague 4) - voir note
 *       de conception ci-dessous.
 *
 * SÉCURITÉ :
 *  - upcomingForUser scope les cours via les inscriptions actives de l'utilisateur
 *    (anti-IDOR : un étudiant ne voit jamais un cours où il n'est pas inscrit).
 *  - forCourse ne filtre pas par user : c'est l'appelant (Livewire/Controller)
 *    qui doit avoir vérifié l'autorisation avant d'invoquer ce service.
 *  - monthForUser scope les cours via scopedCourseIdsForUser (inscriptions
 *    actives UNION cours gérés - anti-IDOR, voir sa docblock).
 *  - Aucune écriture dans ce service (lecture seule).
 *
 * DÉCISION DE CONCEPTION (Vague 4 - calendrier global) : upcomingForUser() et
 * forCourse() ne sont VOLONTAIREMENT PAS modifiées pour inclure les séances en
 * direct. upcomingForUser() alimente SendDueRemindersCommand, qui envoie un
 * "rappel d'échéance" (gabarit dueReminder) pour CHAQUE évènement retourné ;
 * les séances en direct ont déjà leur propre rappel dédié (LiveRemindCommand /
 * AcademyNotificationService::liveSessionReminder). Les fusionner ici aurait
 * doublé les courriels reçus par l'apprenant. Le calendrier global (nouvelle
 * surface, lecture seule) utilise donc monthForUser(), qui réutilise le MÊME
 * modèle LiveSession et le MÊME patron de normalisation que le reste de cette
 * classe, sans toucher aux méthodes existantes (zéro régression).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\CalendarEvent;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\LiveSession;

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

    /**
     * Identifiants des cours « pertinents » pour le calendrier global d'un
     * utilisateur (Vague 4) : UNION de ses inscriptions actives (vue apprenant)
     * et des cours qu'il gère (vue formateur/admin), pour qu'un utilisateur à
     * double casquette voie tout en une seule vue (pas de bascule).
     *
     * ANTI-IDOR : un admin (academy.manage) voit TOUS les cours (parité avec
     * Dashboard::myCourses) ; un formateur ne voit que les cours où il figure
     * dans course_roles (n'importe quel rôle) ; un étudiant simple ne voit que
     * ses inscriptions actives. Aucun autre cours ne peut fuiter.
     *
     * @return Collection<int, int>
     */
    public function scopedCourseIdsForUser(User $user): Collection
    {
        $enrolledIds = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('course_id');

        if ($user->can('academy.manage')) {
            $managedIds = Course::query()->pluck('id');
        } else {
            $managedIds = CourseRole::query()
                ->where('user_id', $user->id)
                ->pluck('course_id');
        }

        return $enrolledIds->merge($managedIds)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Calendrier global MENSUEL d'un utilisateur (Vague 4) : événements manuels,
     * échéances dérivées ET séances en direct (RÉUTILISATION du modèle LiveSession
     * existant, jamais dupliqué) de TOUS les cours pertinents (voir
     * scopedCourseIdsForUser), tombant dans le mois demandé. Passés ET futurs
     * (vue calendrier, contrairement à upcomingForUser qui ne garde que le futur).
     *
     * Anti-N+1 : 4 requêtes globales (cours, manuels, dérivés, séances) puis
     * fusion/tri en PHP.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function monthForUser(User $user, int $year, int $month): Collection
    {
        $courseIds = $this->scopedCourseIdsForUser($user);

        if ($courseIds->isEmpty()) {
            return collect();
        }

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $courses = Course::whereIn('id', $courseIds)->get()->keyBy('id');

        // NOTE : ->toBase() FORCÉ après chaque map() - la Collection Eloquent ne se
        // convertit en Collection de base QUE si elle contient au moins un élément
        // qui n'est pas un Model (voir Eloquent\Collection::map()). Une collection
        // VIDE reste donc une Collection Eloquent, et son merge() exigerait alors
        // ->getKey() sur les tableaux fusionnés -> erreur. ->toBase() élimine
        // l'ambiguïté quel que soit le nombre de résultats (0 ou plus).
        $manual = CalendarEvent::whereIn('course_id', $courseIds)
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->filter(fn (CalendarEvent $ev): bool => $courses->has($ev->course_id))
            ->map(fn (CalendarEvent $ev): array => $this->normalizeManual($ev, $courses->get($ev->course_id)))
            ->toBase();

        $derived = Assignment::query()
            ->whereIn('course_id', $courseIds)
            ->where('is_published', true)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$start, $end])
            ->get()
            ->filter(fn (Assignment $a): bool => $courses->has($a->course_id))
            ->map(fn (Assignment $a): array => $this->normalizeDerived($a, $courses->get($a->course_id)))
            ->toBase();

        // Séances en direct : RÉUTILISE Models\LiveSession tel quel (aucune table
        // dupliquée). Gâté par le même drapeau que le reste de la fonctionnalité
        // (academy.live_sessions_enabled) : désactivé -> comportement identique
        // à avant l'ajout du calendrier global.
        $live = collect();
        if ((bool) config('academy.live_sessions_enabled', false)) {
            $live = LiveSession::query()
                ->whereIn('course_id', $courseIds)
                ->whereBetween('starts_at', [$start, $end])
                ->get()
                ->filter(fn (LiveSession $s): bool => $courses->has($s->course_id))
                ->map(fn (LiveSession $s): array => $this->normalizeLive($s, $courses->get($s->course_id)))
                ->toBase();
        }

        return $manual
            ->merge($derived)
            ->merge($live)
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

    /**
     * Tableau uniforme pour une séance en direct (Vague 4 - calendrier global).
     * RÉUTILISE Models\LiveSession existant (aucun champ dupliqué) ; suit le
     * même patron que normalizeManual/normalizeDerived.
     *
     * @return array<string, mixed>
     */
    private function normalizeLive(LiveSession $session, Course $course): array
    {
        return [
            'id'           => 'live-' . $session->id,
            'source'       => 'live',
            'event_id'     => $session->id,
            'course_id'    => $session->course_id,
            'course_slug'  => $course->slug,
            'course_title' => $course->title,
            'title'        => $session->title,
            'description'  => $session->description,
            'type'         => 'live',
            'starts_at'    => $session->starts_at,
            'ends_at'      => $session->ends_at,
            'item_link'    => null,
            'is_past'      => $session->starts_at->isPast(),
        ];
    }
}
