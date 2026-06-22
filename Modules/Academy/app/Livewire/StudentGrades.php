<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - ÉTUDIANT : MA note finale pondérée + lettre + détail par catégorie.
 * Composant Livewire rendu dans l'espace personnel. LECTURE SEULE.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR) :
 *  - tout est scopé à auth()->id() : un étudiant ne voit QUE ses propres notes,
 *    JAMAIS celles d'un autre (anti-IDOR) ;
 *  - seuls les cours où il est inscrit ACTIF sont considérés, re-vérifié serveur ;
 *  - on n'affiche que les cours PONDÉRÉS (au moins une catégorie de notes) ;
 *    les autres restent sans note finale (rétrocompat).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\GradebookService;

class StudentGrades extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::check(), 403);
    }

    /**
     * MES notes finales pondérées par cours suivi (inscription active), avec le
     * détail par catégorie. Strictement scopé à auth()->id() (anti-IDOR). Seuls
     * les cours dotés d'au moins une catégorie de notes apparaissent.
     *
     * @return Collection<int, array{course: Course, final: float, letter: string, categories: array<int, array<string, mixed>>}>
     */
    #[Computed]
    public function grades(): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        $courseIds = Enrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('course_id');

        if ($courseIds->isEmpty()) {
            return collect();
        }

        return Course::whereIn('id', $courseIds)
            ->orderBy('title')
            ->get()
            ->map(function (Course $course) use ($user): ?array {
                if (! GradebookService::hasWeighting($course)) {
                    return null; // cours non pondéré → pas de note finale
                }

                $g = GradebookService::finalGradeFor($user, $course);

                return [
                    'course'     => $course,
                    'final'      => $g['final'],
                    'letter'     => $g['letter'],
                    'categories' => $g['categories'],
                ];
            })
            ->filter()
            ->values();
    }

    public function render()
    {
        return view('academy::livewire.student-grades');
    }
}
