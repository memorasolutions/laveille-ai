<?php

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\RiskScoreService;

/**
 * Bandeau de risque personnel (vue étudiant) affiché dans le dashboard apprenant.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01) :
 *  - Le score n'est calculé que pour l'utilisateur connecté (auth()->id()).
 *  - Le courseId est figé au montage (serveur) ; aucune valeur client n'est
 *    acceptée pour identifier l'utilisateur évalué.
 *  - L'inscription active est vérifiée au montage ET dans #[Computed] riskData.
 *  - Le drapeau feature est vérifié avant tout calcul (riskData retourne null).
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class StudentRiskBanner extends Component
{
    /** Identifiant du cours (figé au montage, source de vérité serveur). */
    public int $courseId;

    /**
     * Entrée. Re-résout le cours côté serveur, vérifie l'inscription active.
     */
    public function mount(Course $course): void
    {
        $this->courseId = $course->id;

        abort_unless(
            Enrollment::query()->where([
                'user_id'   => auth()->id(),
                'course_id' => $course->id,
                'status'    => 'active',
            ])->exists(),
            403,
        );
    }

    /**
     * Données de risque personnelles. Retourne null si le drapeau est OFF ou si
     * l'apprenant n'est plus inscrit (anti-race condition).
     *
     * Toujours scopé à auth()->id() — jamais de user_id depuis le client.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function riskData(): ?array
    {
        if (! config('academy.predictive_analytics_enabled', false)) {
            return null;
        }

        $course = Course::find($this->courseId);
        if ($course === null) {
            return null;
        }

        // Re-vérification inscription (sécurité, anti-IDOR).
        if (! Enrollment::query()->where([
            'user_id'   => auth()->id(),
            'course_id' => $course->id,
            'status'    => 'active',
        ])->exists()) {
            return null;
        }

        return app(RiskScoreService::class)->scoreForEnrollee((int) auth()->id(), $course);
    }

    public function render()
    {
        return view('academy::livewire.student-risk-banner');
    }
}
