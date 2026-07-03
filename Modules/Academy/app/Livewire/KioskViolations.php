<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * MODE KIOSQUE — vue formateur des incidents consignés (sortie plein écran,
 * changement d'onglet, outils de développement suspectés, sortie volontaire)
 * pendant les tentatives de quiz surveillées de CE cours. Composant Livewire
 * rendu sous l'éditeur de cours, à côté de la correction des essais (même
 * patron d'autorisation que EssayGrading).
 *
 * PUREMENT DÉCLARATIF : aucune action de correction/invalidation ici, juste un
 * journal consultable par tentative. Ne touche jamais au score (voir
 * Services\QuizService::score, la seule source de vérité de la notation).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE), calqué
 * sur EssayGrading :
 *  - $courseId figé au montage (source de vérité serveur) ;
 *  - voir les incidents → 'manageEnrollments' (admin OU owner/instructor du
 *    cours), RÉ-AUTORISÉ à CHAQUE accès en lecture ;
 *  - les tentatives listées sont SCOPÉES à CE cours (course_id) — anti-IDOR :
 *    un formateur d'un autre cours ne peut jamais voir ces incidents.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\KioskViolation;
use Modules\Academy\Models\QuizAttempt;

class KioskViolations extends Component
{
    /** Identifiant du cours géré (figé au montage, source de vérité serveur). */
    public int $courseId;

    /**
     * Entrée. Voir les incidents kiosque exige de pouvoir gérer les inscriptions
     * (admin OU owner/instructor du cours). Vraie garde = authorize() serveur.
     */
    public function mount(Course $course): void
    {
        $this->authorize('manageEnrollments', $course);

        $this->courseId = $course->id;
    }

    /** Re-résout TOUJOURS le cours depuis la base (jamais depuis le navigateur). */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    #[Computed]
    public function course(): Course
    {
        return $this->resolveCourse();
    }

    /**
     * Tentatives de quiz de CE cours ayant AU MOINS un incident consigné,
     * scopées via course_id (anti-IDOR), les plus récentes en premier.
     * Ré-autorise à chaque lecture (défense en profondeur).
     */
    #[Computed]
    public function attemptsWithViolations(): EloquentCollection
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $attemptIds = KioskViolation::query()
            ->whereHas('quizAttempt', fn ($q) => $q->where('course_id', $course->id))
            ->distinct()
            ->pluck('quiz_attempt_id');

        return QuizAttempt::query()
            ->whereIn('id', $attemptIds)
            ->where('course_id', $course->id)
            ->with(['user:id,name,email', 'lessonItem:id,title,lesson_id'])
            ->orderByDesc('submitted_at')
            ->get();
    }

    /**
     * Incidents d'UNE tentative donnée, RE-SCOPÉE à ce cours (anti-IDOR) avant
     * toute lecture — un attempt_id d'un autre cours renvoie une liste vide.
     *
     * @return Collection<int, KioskViolation>
     */
    public function violationsFor(int $attemptId): Collection
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('course_id', $course->id)
            ->first();

        if ($attempt === null) {
            return collect();
        }

        return app(\Modules\Academy\Services\KioskViolationService::class)->forAttempt($attempt);
    }

    /** Libellé humain d'un type d'incident (français, pour l'affichage formateur). */
    public function violationLabel(string $type): string
    {
        return match ($type) {
            \Modules\Academy\Services\KioskViolationService::FULLSCREEN_EXIT    => 'Sortie du plein écran',
            \Modules\Academy\Services\KioskViolationService::TAB_BLUR          => "Changement d'onglet ou de fenêtre",
            \Modules\Academy\Services\KioskViolationService::DEVTOOLS_SUSPECTED => 'Outils de développement suspectés',
            \Modules\Academy\Services\KioskViolationService::VOLUNTARY_EXIT     => 'Sortie volontaire du mode kiosque',
            default => $type,
        };
    }

    public function render()
    {
        return view('academy::livewire.kiosk-violations');
    }
}
