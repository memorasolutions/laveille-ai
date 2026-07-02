<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gamification moderne (LMS 2026) — bloc « Ma progression » (XP, niveau, palier
 * suivant) et classement PAR COHORTE dans l'espace personnel. Composant
 * LECTURE SEULE sur les données de classement (aucune donnée d'un autre
 * utilisateur n'est modifiable) ; la SEULE mutation possible est le retrait
 * volontaire du classement par l'utilisateur COURANT (Loi 25 QC).
 *
 * Double garde du drapeau : la vue qui inclut ce composant vérifie déjà
 * config('academy.gamification_enabled') avant le @livewire(...), et mount()
 * re-vérifie ici (défense en profondeur — un accès direct sans passer par la
 * vue reste sans effet).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\GamificationService;
use Modules\Academy\Services\TierGateService;

class Gamification extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::check(), 403);
    }

    /**
     * Le drapeau maître est-il activé ? La vue Blade masque déjà tout si false.
     * ACTION: fix Scénario F — le palier d'abonnement de l'utilisateur (TierGateService)
     * n'était jamais consulté ici, un palier sans la feature academy_gamification
     * voyait quand même le widget. SELF: 3 lignes. RAISON: refus propre (fail-closed),
     * cohérent avec les autres features gérées par palier (voir TierGateService).
     */
    #[Computed]
    public function enabled(): bool
    {
        return app(GamificationService::class)->isEnabled()
            && app(TierGateService::class)->hasFeature(Auth::user(), 'academy_gamification');
    }

    /**
     * Bloc « Ma progression » GLOBAL (tous cours confondus) : total XP, niveau,
     * palier suivant, % de la barre. Scopé à auth()->id() (anti-IDOR).
     *
     * @return array{total: int, level: int, points_into_level: int, points_to_next: int, percent: int, next_level_points: int}
     */
    #[Computed]
    public function progress(): array
    {
        $user = Auth::user();

        if ($user === null || ! $this->enabled) {
            return ['total' => 0, 'level' => 1, 'points_into_level' => 0, 'points_to_next' => 0, 'percent' => 0, 'next_level_points' => 0];
        }

        return app(GamificationService::class)->progressFor($user);
    }

    /**
     * Un classement par groupe pertinent pour l'utilisateur courant : sa cohorte
     * pour chaque cours où il en a une, sinon (repli) le cours lui-même s'il y
     * est activement inscrit. JAMAIS de classement global (règle produit).
     *
     * @return Collection<int, array{label: string, entries: Collection, viewerRank: int|null}>
     */
    #[Computed]
    public function leaderboards(): Collection
    {
        $user = Auth::user();

        if ($user === null || ! $this->enabled) {
            return collect();
        }

        $service = app(GamificationService::class);
        $groups  = collect();

        // 1. Une entrée par cohorte dont l'utilisateur est membre.
        $cohorts        = $service->cohortsFor($user);
        $coveredCourseIds = $cohorts->pluck('course_id')->filter()->all();

        foreach ($cohorts as $cohort) {
            $entries = $service->leaderboardForCohort($cohort);

            $groups->push([
                'label'      => $cohort->name.($cohort->course ? ' — '.$cohort->course->title : ''),
                'entries'    => $entries,
                'viewerRank' => optional($entries->firstWhere('user.id', $user->id))['rank'] ?? null,
            ]);
        }

        // 2. Repli « classement du cours » pour les inscriptions actives SANS
        // cohorte (évite de priver un apprenant de tout classement).
        $activeCourses = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotIn('course_id', $coveredCourseIds)
            ->with('course')
            ->get()
            ->map(fn (Enrollment $e) => $e->course)
            ->filter();

        foreach ($activeCourses as $course) {
            $entries = $service->leaderboardForCourse($course);

            $groups->push([
                'label'      => $course->title,
                'entries'    => $entries,
                'viewerRank' => optional($entries->firstWhere('user.id', $user->id))['rank'] ?? null,
            ]);
        }

        return $groups;
    }

    /** L'utilisateur courant accepte-t-il d'apparaître dans les classements ? */
    #[Computed]
    public function leaderboardVisible(): bool
    {
        $user = Auth::user();

        return $user !== null && app(GamificationService::class)->isLeaderboardVisible($user);
    }

    /**
     * Bascule le retrait/l'adhésion au classement pour l'utilisateur COURANT
     * uniquement (jamais un id venu du client — Loi 25 QC / anti-IDOR).
     */
    public function toggleLeaderboardVisibility(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $service = app(GamificationService::class);
        $service->setLeaderboardVisibility($user, ! $service->isLeaderboardVisible($user));

        unset($this->leaderboards, $this->leaderboardVisible);

        session()->flash('academy_gamification_status', 'Préférence de classement enregistrée.');
    }

    public function render()
    {
        return view('academy::livewire.gamification');
    }
}
