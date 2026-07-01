<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Composant Livewire « Authoring IA » — génère plan + questions, aperçu, puis brouillon
 * SELF: < 5 lignes de glue (dispatch, reset état)
 * RAISON: Séparation présentation / logique métier (services + action dédiés).
 *         Le formateur VOIT avant d'écrire — jamais de publication automatique.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01) :
 *  - courseId figé au montage ; resolveCourse() recharge depuis la BD.
 *  - authorize('manageStructure', $course) RE-VÉRIFIÉE dans CHAQUE action publique.
 *  - Drapeau config vérifié avant tout appel IA.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\Academy\Actions\InsertOutlineDraftAction;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Services\AcademyAuthoringAiService;

class AiAuthoringModal extends Component
{
    /**
     * Disjoncteur DoS financier : l'authoring IA est réservé au personnel formateur
     * (authorize('manageStructure', ...) déjà en place) — limite plus généreuse que
     * le tuteur (10/min) mais bien réelle. Clé par utilisateur ET par action, comme
     * TutorChat::sendQuestion().
     */
    private const AI_RATE_LIMIT_MAX = 20;

    private const AI_RATE_LIMIT_DECAY_SECONDS = 3600;

    public int $courseId;

    public string $outlinePrompt   = '';
    public string $questionsPrompt = '';
    public int    $questionsCount  = 5;

    /** @var array<string, mixed> */
    public array $generatedOutline    = [];

    /** @var array<int, array<string, mixed>> */
    public array $generatedQuestions  = [];

    public bool   $isLoadingOutline   = false;
    public bool   $isLoadingQuestions = false;
    public bool   $outlineInserted    = false;
    public bool   $questionsInserted  = false;
    public string $errorMessage       = '';

    public function mount(Course $course): void
    {
        $this->authorize('update', $course);
        $this->courseId = $course->id;
    }

    public function generateOutline(): void
    {
        if (! config('academy.ai_authoring_enabled', false)) {
            $this->dispatch('notify', message: 'Fonctionnalité désactivée.');

            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->validate(['outlinePrompt' => ['required', 'string', 'max:2000']]);

        if ($this->tooManyAiRequests('outline')) {
            $this->errorMessage = "Vous avez atteint la limite de générations de plan IA pour cette heure (20/heure). Réessayez plus tard.";

            return;
        }

        $this->isLoadingOutline = true;
        $this->errorMessage     = '';

        try {
            $this->generatedOutline  = app(AcademyAuthoringAiService::class)->generateOutline($this->outlinePrompt);
            $this->outlineInserted   = false;

            if (empty($this->generatedOutline)) {
                $this->errorMessage = 'La génération n\'a pas produit de plan valide. Réessayez avec un prompt plus précis.';
            }
        } finally {
            $this->isLoadingOutline = false;
        }
    }

    public function confirmInsertOutline(): void
    {
        if (! config('academy.ai_authoring_enabled', false)) {
            $this->dispatch('notify', message: 'Fonctionnalité désactivée.');

            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if (empty($this->generatedOutline)) {
            $this->errorMessage = 'Aucun plan à insérer.';

            return;
        }

        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $result = app(InsertOutlineDraftAction::class)($course, $this->generatedOutline, $user);

        if ($result['chapters_created'] === 0 && $result['lessons_created'] === 0) {
            $this->errorMessage = 'Aucun chapitre n\'a pu être inséré (vérifiez les doublons de titre).';

            return;
        }

        $this->dispatch('outline-inserted', chaptersCreated: $result['chapters_created'], lessonsCreated: $result['lessons_created']);
        $this->generatedOutline = [];
        $this->outlineInserted  = true;
    }

    public function generateQuestions(): void
    {
        if (! config('academy.ai_authoring_enabled', false)) {
            $this->dispatch('notify', message: 'Fonctionnalité désactivée.');

            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->validate([
            'questionsPrompt' => ['required', 'string', 'max:2000'],
            'questionsCount'  => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($this->tooManyAiRequests('questions')) {
            $this->errorMessage = "Vous avez atteint la limite de générations de questions IA pour cette heure (20/heure). Réessayez plus tard.";

            return;
        }

        $this->isLoadingQuestions = true;
        $this->errorMessage       = '';

        try {
            $this->generatedQuestions  = app(AcademyAuthoringAiService::class)->generateQuizQuestions($this->questionsPrompt, $this->questionsCount);
            $this->questionsInserted   = false;

            if (empty($this->generatedQuestions)) {
                $this->errorMessage = 'La génération n\'a pas produit de questions valides. Réessayez.';
            }
        } finally {
            $this->isLoadingQuestions = false;
        }
    }

    public function confirmInsertQuestions(): void
    {
        if (! config('academy.ai_authoring_enabled', false)) {
            $this->dispatch('notify', message: 'Fonctionnalité désactivée.');

            return;
        }

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if (empty($this->generatedQuestions)) {
            $this->errorMessage = 'Aucune question à insérer.';

            return;
        }

        $category = QuestionCategory::firstOrCreate(
            ['owner_id' => Auth::id(), 'name' => 'IA – À réviser'],
            ['position' => 0]
        );

        $count = 0;

        foreach ($this->generatedQuestions as $item) {
            Question::create([
                'category_id' => $category->id,
                'owner_id'    => Auth::id(),
                'type'        => 'mcq',
                'prompt'      => (string) ($item['prompt'] ?? ''),
                'payload'     => [
                    'choices'     => $item['options'] ?? [],
                    'correct'     => $item['correct_index'] ?? 0,
                    'explanation' => $item['explanation'] ?? '',
                ],
                'explanation' => (string) ($item['explanation'] ?? ''),
                'is_active'   => false,
            ]);

            $count++;
        }

        $this->dispatch('questions-inserted', count: $count);
        $this->generatedQuestions = [];
        $this->questionsInserted  = true;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('academy::livewire.ai-authoring-modal');
    }

    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Disjoncteur DoS financier (défense en profondeur, en plus du budget IA global) :
     * enregistre la tentative et retourne true si la limite horaire par utilisateur
     * ET par action est atteinte. Clé au format `ai-authoring-{action}:{userId}`.
     */
    private function tooManyAiRequests(string $action): bool
    {
        $key = "ai-authoring-{$action}:" . (string) Auth::id();

        if (RateLimiter::tooManyAttempts($key, self::AI_RATE_LIMIT_MAX)) {
            return true;
        }

        RateLimiter::hit($key, self::AI_RATE_LIMIT_DECAY_SECONDS);

        return false;
    }
}
