<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Composant Livewire « Traduction IA » — traduit un texte libre du cours en aperçu éditable
 * SELF: < 5 lignes de glue (dispatch, reset état)
 * RAISON: Formateur traduit un champ (ex. description) vers une autre langue, relit, VALIDE.
 *         AUCUNE écriture automatique en base : Course/Lesson/Chapter ne sont PAS Translatable
 *         dans ce projet (pas de colonne JSON multi-langue) — la traduction reste un brouillon
 *         que le formateur copie où il en a besoin. Ne PAS ajouter Translatable ici (hors scope).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01) :
 *  - courseId figé au montage ; resolveCourse() recharge depuis la BD.
 *  - authorize('manageStructure', $course) RE-VÉRIFIÉE dans CHAQUE action publique (y compris mount).
 *  - Drapeau config academy.ai_translation_enabled vérifié avant tout appel IA.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\AcademyTranslationAiService;

class TranslateFieldModal extends Component
{
    /**
     * Disjoncteur DoS financier : la traduction IA est réservée au personnel formateur
     * (authorize('manageStructure', ...) déjà en place) — limite plus généreuse que
     * le tuteur (10/min) mais bien réelle. Clé par utilisateur, comme TutorChat::sendQuestion().
     */
    private const AI_RATE_LIMIT_MAX = 20;

    private const AI_RATE_LIMIT_DECAY_SECONDS = 3600;

    public int $courseId;

    public string $sourceText = '';
    public string $sourceLocale = 'fr';
    public string $targetLocale = 'en';

    public string $translatedText = '';
    public bool $isLoading = false;
    public bool $isConfirmed = false;
    public string $errorMessage = '';

    public function mount(Course $course): void
    {
        $this->authorize('manageStructure', $course);
        $this->courseId = $course->id;
    }

    public function translate(): void
    {
        if (! config('academy.ai_translation_enabled', false)) {
            $this->dispatch('notify', message: 'Fonctionnalité désactivée.');

            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->validate([
            'sourceText'   => ['required', 'string', 'max:5000'],
            'sourceLocale' => ['required', 'string', 'in:fr,en'],
            'targetLocale' => ['required', 'string', 'in:fr,en'],
        ]);

        $rateLimitKey = 'ai-translate:' . (string) auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::AI_RATE_LIMIT_MAX)) {
            $this->errorMessage = "Vous avez atteint la limite de traductions IA pour cette heure (20/heure). Réessayez plus tard.";

            return;
        }

        RateLimiter::hit($rateLimitKey, self::AI_RATE_LIMIT_DECAY_SECONDS);

        $this->isLoading    = true;
        $this->errorMessage = '';
        $this->isConfirmed  = false;

        try {
            $this->translatedText = app(AcademyTranslationAiService::class)->translateField(
                $this->sourceText,
                $this->sourceLocale,
                $this->targetLocale
            );

            if (empty(trim($this->translatedText))) {
                $this->errorMessage = "La traduction n'a pas pu être générée. Réessayez.";
            }
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Confirme l'aperçu de traduction. AUCUNE écriture en base : le formateur
     * copie ensuite le texte où il en a besoin (voir limite documentée en tête).
     */
    public function confirmTranslation(): void
    {
        if (! config('academy.ai_translation_enabled', false)) {
            $this->dispatch('notify', message: 'Fonctionnalité désactivée.');

            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if (trim($this->translatedText) === '') {
            $this->errorMessage = 'Aucune traduction à confirmer.';

            return;
        }

        $this->isConfirmed = true;
        $this->dispatch('translation-confirmed');
    }

    public function resetTranslation(): void
    {
        $this->translatedText = '';
        $this->isConfirmed    = false;
        $this->errorMessage   = '';
    }

    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    public function render()
    {
        return view('academy::livewire.translate-field-modal');
    }
}
