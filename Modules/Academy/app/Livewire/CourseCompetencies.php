<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - ASSOCIATION compétences <-> cours/items + SUIVI d'acquisition par étudiant
 * (rapport formateur). Rendu dans l'éditeur de cours.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR) :
 *  - Le cours est re-résolu serveur (binding) ; ENTRÉE gâtée manageStructure (mount).
 *  - Chaque mutation RÉ-AUTORISE manageStructure sur CE cours (jamais de confiance au
 *    navigateur) ; la compétence est re-résolue SCOPÉE owner (admin = toutes) = anti-IDOR ;
 *    l'item ciblé est RE-SCOPÉ au cours (un item d'un autre cours est refusé).
 *  - Le RAPPORT par étudiant est en plus gâté manageEnrollments (lecture seule) et scopé
 *    aux inscrits actifs du cours. Données dérivées serveur (CompetencyService).
 *  - @can en Blade = affichage ; l'autorisation reste SERVEUR.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Competency;
use Modules\Academy\Models\CompetencyLink;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\CompetencyService;

class CourseCompetencies extends Component
{
    public Course $course;

    /** Compétence sélectionnée pour l'association rapide à un item (null = aucune). */
    public ?int $selectedCompetencyId = null;

    public function mount(Course $course): void
    {
        // Le cours est re-résolu serveur ; on AUTORISE l'entrée (manageStructure).
        $this->authorize('manageStructure', $course);
        $this->course = $course;
    }

    /** Compétences disponibles : les MIENNES (ou toutes si admin), actives en priorité. */
    #[Computed]
    public function availableCompetencies()
    {
        $query = Competency::query()->orderByDesc('is_active')->orderBy('name');

        if (! Auth::user()?->can('academy.manage')) {
            $query->where('owner_id', Auth::id());
        }

        return $query->get();
    }

    /**
     * Items de leçon de CE cours (re-scopés serveur, anti-IDOR), avec leur chemin lisible.
     *
     * @return \Illuminate\Support\Collection<int, LessonItem>
     */
    #[Computed]
    public function courseItems()
    {
        return LessonItem::query()
            ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $this->course->id))
            ->with(['lesson:id,chapter_id,title'])
            ->orderBy('id')
            ->get(['id', 'lesson_id', 'type', 'title']);
    }

    /** Liens existants de CE cours (cours entier + items), pour l'affichage. */
    #[Computed]
    public function courseLinks()
    {
        $itemIds = $this->courseItems->pluck('id')->all();

        return CompetencyLink::query()
            ->with('competency:id,name')
            ->where(function ($q) use ($itemIds): void {
                $q->where('course_id', $this->course->id);
                if ($itemIds !== []) {
                    $q->orWhereIn('lesson_item_id', $itemIds);
                }
            })
            ->get();
    }

    /** Re-résout une compétence SCOPÉE owner (admin = toutes). ModelNotFound sinon (anti-IDOR). */
    private function resolveCompetency(int $id): Competency
    {
        $query = Competency::query()->whereKey($id);
        if (! Auth::user()?->can('academy.manage')) {
            $query->where('owner_id', Auth::id());
        }

        return $query->firstOrFail();
    }

    /** Associe une compétence au COURS ENTIER (idempotent). */
    public function attachToCourse(int $competencyId): void
    {
        $this->authorize('manageStructure', $this->course);
        $competency = $this->resolveCompetency($competencyId);

        CompetencyLink::firstOrCreate([
            'competency_id'  => $competency->id,
            'course_id'      => $this->course->id,
            'lesson_item_id' => null,
        ]);
        $this->dispatch('competency-link-changed');
    }

    /** Associe une compétence à UN ITEM du cours (item re-scopé au cours, anti-IDOR). */
    public function attachToItem(int $competencyId, int $lessonItemId): void
    {
        $this->authorize('manageStructure', $this->course);
        $competency = $this->resolveCompetency($competencyId);

        // L'item DOIT appartenir à CE cours (sinon un id arbitraire serait accepté).
        $item = LessonItem::query()
            ->whereKey($lessonItemId)
            ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $this->course->id))
            ->firstOrFail();

        CompetencyLink::firstOrCreate([
            'competency_id'  => $competency->id,
            'course_id'      => null,
            'lesson_item_id' => $item->id,
        ]);
        $this->dispatch('competency-link-changed');
    }

    /** Détache un lien (re-vérifié comme appartenant à CE cours / ses items, anti-IDOR). */
    public function detach(int $linkId): void
    {
        $this->authorize('manageStructure', $this->course);

        $itemIds = $this->courseItems->pluck('id')->all();

        $link = CompetencyLink::query()
            ->whereKey($linkId)
            ->where(function ($q) use ($itemIds): void {
                $q->where('course_id', $this->course->id);
                if ($itemIds !== []) {
                    $q->orWhereIn('lesson_item_id', $itemIds);
                }
            })
            ->firstOrFail();

        $link->delete();
        $this->dispatch('competency-link-changed');
    }

    /** Le RAPPORT par étudiant est-il autorisé (manageEnrollments) ? Affichage seulement. */
    #[Computed]
    public function canViewReport(): bool
    {
        return Gate::allows('manageEnrollments', $this->course);
    }

    /**
     * Matrice de suivi (compétences × étudiants inscrits actifs). Gâtée manageEnrollments,
     * agrégée par lot (anti-N+1), lecture seule. Vide si non autorisé.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function matrix(): array
    {
        if (! $this->canViewReport) {
            return ['competencies' => collect(), 'students' => collect(), 'states' => []];
        }

        return CompetencyService::courseMatrix($this->course);
    }

    public function render()
    {
        return view('academy::livewire.course-competencies');
    }
}
