<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Calendrier GLOBAL (Vague 4, parité Moodle) : vue mensuelle agrégeant les
 * échéances/évènements de TOUS les cours pertinents de l'utilisateur connecté
 * (inscriptions actives + cours gérés - voir CalendarService::scopedCourseIdsForUser).
 * Lecture seule : la création d'événements manuels reste dans le calendrier
 * PAR COURS existant (CourseCalendar, route academie/courses/{course}/calendrier).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR, anti-IDOR) :
 *  - Drapeau academy.global_calendar_enabled re-vérifié à l'entrée (mount).
 *  - Les événements viennent EXCLUSIVEMENT de CalendarService::monthForUser(),
 *    qui scope les cours à l'utilisateur COURANT (auth()->id()) - jamais un id
 *    reçu du navigateur. Aucune mutation dans ce composant (lecture seule).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Services\CalendarService;

class GlobalCalendar extends Component
{
    public int $year;

    public int $month;

    /** Jour sélectionné (format Y-m-d) pour le panneau de détail, null = aucun. */
    public ?string $selectedDate = null;

    public function mount(): void
    {
        abort_unless($this->featureEnabled(), 404);

        $now         = now('America/Toronto');
        $this->year  = $now->year;
        $this->month = $now->month;
    }

    /** Drapeau maître de la fonctionnalité (défaut FALSE). */
    private function featureEnabled(): bool
    {
        return (bool) config('academy.global_calendar_enabled', false);
    }

    /** Événements du mois affiché, scopés SERVEUR à l'utilisateur courant. */
    #[Computed]
    public function events(): \Illuminate\Support\Collection
    {
        $user = Auth::user();
        if ($user === null) {
            return collect();
        }

        return (new CalendarService())->monthForUser($user, $this->year, $this->month);
    }

    /** Événements groupés par jour (clé Y-m-d, heure du Québec). */
    #[Computed]
    public function eventsByDay(): \Illuminate\Support\Collection
    {
        return $this->events->groupBy(
            fn (array $ev): string => $ev['starts_at']->copy()->timezone('America/Toronto')->format('Y-m-d')
        );
    }

    /** Événements du jour sélectionné (vide si aucune sélection ou aucun événement). */
    #[Computed]
    public function selectedDayEvents(): \Illuminate\Support\Collection
    {
        if ($this->selectedDate === null) {
            return collect();
        }

        return $this->eventsByDay->get($this->selectedDate, collect());
    }

    /**
     * Grille du mois affiché : semaines de 7 jours (lundi -> dimanche, convention
     * québécoise), avec des cases nulles en tête/queue pour aligner le 1er jour
     * du mois sur sa colonne. Chaque case non nulle = ['date' => 'Y-m-d', 'day' => int, 'is_today' => bool].
     *
     * @return array<int, array<int, array{date: string, day: int, is_today: bool}|null>>
     */
    #[Computed]
    public function weeks(): array
    {
        $start        = Carbon::create($this->year, $this->month, 1, 0, 0, 0, 'America/Toronto');
        $daysInMonth  = $start->daysInMonth;
        $todayStr     = now('America/Toronto')->format('Y-m-d');

        // isoWeekday : 1 = lundi ... 7 = dimanche. Nombre de cases vides avant le 1er jour.
        $leadingBlanks = $start->copy()->startOfMonth()->dayOfWeekIso - 1;

        $cells = array_fill(0, $leadingBlanks, null);

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date    = $start->copy()->day($day)->format('Y-m-d');
            $cells[] = [
                'date'     => $date,
                'day'      => $day,
                'is_today' => $date === $todayStr,
            ];
        }

        // Complète la dernière semaine avec des cases vides (grille rectangulaire).
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return array_chunk($cells, 7);
    }

    /** Libellé du mois affiché (français, ex. « Juillet 2026 »). */
    #[Computed]
    public function monthLabel(): string
    {
        return ucfirst(
            Carbon::create($this->year, $this->month, 1)->translatedFormat('MMMM YYYY')
        );
    }

    public function previousMonth(): void
    {
        $cursor = Carbon::create($this->year, $this->month, 1)->subMonthNoOverflow();
        $this->year  = $cursor->year;
        $this->month = $cursor->month;
        $this->selectedDate = null;
    }

    public function nextMonth(): void
    {
        $cursor = Carbon::create($this->year, $this->month, 1)->addMonthNoOverflow();
        $this->year  = $cursor->year;
        $this->month = $cursor->month;
        $this->selectedDate = null;
    }

    public function goToToday(): void
    {
        $now         = now('America/Toronto');
        $this->year  = $now->year;
        $this->month = $now->month;
        $this->selectedDate = $now->format('Y-m-d');
    }

    /**
     * Sélectionne un jour pour afficher son détail. Liste blanche : le jour doit
     * appartenir au mois actuellement affiché (anti-forgerie triviale, même si
     * aucune donnée sensible n'est en jeu ici - lecture seule).
     */
    public function selectDay(string $date): void
    {
        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $date);
        } catch (\Throwable) {
            return;
        }

        if ($parsed->year !== $this->year || $parsed->month !== $this->month) {
            return;
        }

        $this->selectedDate = ($this->selectedDate === $date) ? null : $date;
    }

    public function render()
    {
        return view('academy::livewire.global-calendar');
    }
}
