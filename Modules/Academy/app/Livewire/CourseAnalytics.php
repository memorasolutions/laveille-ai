<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * D1 — Tableau de bord d'analytics PAR COURS (Phase D « pilotage »). Composant
 * Livewire en LECTURE SEULE rendu sur la route academy.courses.analytics.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant du cours est figé au montage ($courseId, source de vérité).
 *  - L'accès d'entrée est gâté par authorize('manageEnrollments', $course)
 *    (admin OU owner/instructor du cours = la même gate de pilotage que le roster).
 *  - À CHAQUE lecture, le cours est RE-RÉSOLU côté serveur (resolveCourse) ; toutes
 *    les métriques sont calculées par AnalyticsService SCOPÉES à CE cours
 *    (where course_id = $course->id). Aucune donnée d'un autre cours ne fuit :
 *    un gérant du cours A ne peut pas voir les stats du cours B via slug/ID forgé.
 *  - Aucune écriture : ce composant ne mute rien (zéro effet de bord).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\AnalyticsService;

class CourseAnalytics extends Component
{
    /** Identifiant du cours analysé (figé au montage, source de vérité serveur). */
    public int $courseId;

    /**
     * Entrée. Autorisation SERVEUR d'affichage : voir les statistiques d'un cours
     * exige de pouvoir le piloter (admin OU owner/instructor) — même gate que le
     * roster. Un étudiant / utilisateur lambda → 403.
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

    /** Le cours frais (sert les directives @can / l'en-tête de la vue). */
    #[Computed]
    public function course(): Course
    {
        return $this->resolveCourse();
    }

    /** Service de calcul (résolu via le conteneur). */
    private function analytics(): AnalyticsService
    {
        return app(AnalyticsService::class);
    }

    /**
     * Toutes les métriques, calculées en une passe serveur scopée à CE cours.
     * On re-résout + ré-autorise à chaque rendu : aucune confiance au navigateur.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function metrics(): array
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $service = $this->analytics();
        $dropoff = $service->lessonDropoff($course);

        return [
            'enrollment' => $service->enrollmentKpis($course),
            'completion' => $service->completionKpis($course),
            'dropoff' => $dropoff,
            'dropoffPoint' => $service->dropoffPoint($course, $dropoff),
            'activity' => $service->recentActivity($course),
            'certificates' => $service->certificatesCount($course),
            'atRisk' => $service->atRiskLearners($course),
        ];
    }

    public function render()
    {
        return view('academy::livewire.course-analytics');
    }
}
