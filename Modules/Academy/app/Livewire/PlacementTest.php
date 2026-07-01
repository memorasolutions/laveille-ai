<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Test de positionnement adaptatif (CAT) - une question à la fois (esprit
 * DeckPlayer/SrsReviewer). L'apprenant répond, la difficulté s'ajuste en
 * temps réel (AdaptivePlacementService), puis une leçon de départ est
 * recommandée pour éviter de relire le déjà-su. JAMAIS bloquant : l'apprenant
 * garde toujours le choix d'aller à la recommandation OU de commencer au
 * début. Sécurité anti-triche : la bonne réponse ne vit QUE dans la session
 * serveur PHP (jamais dans une propriété publique sérialisée côté client),
 * exactement comme QuizController::startQuiz() du même module.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\PlacementAttempt;
use Modules\Academy\Services\AdaptivePlacementService;

class PlacementTest extends Component
{
    #[Locked]
    public Course $course;

    public bool $started = false;

    public bool $finished = false;

    /** Banque de questions insuffisante pour ce cours (aucune question tirable). */
    public bool $unavailable = false;

    /** 1-based, pour l'affichage « Question X ». */
    public int $questionIndex = 0;

    /** null = pas encore répondu à la question courante. */
    public ?bool $lastAnswerCorrect = null;

    public ?string $lastExplanation = null;

    public ?string $estimatedLevel = null;

    public ?int $recommendedLessonId = null;

    public ?string $recommendedLessonTitle = null;

    /** Toujours la toute première leçon du cours (choix "commencer au début"). */
    public ?int $firstLessonId = null;

    private function sessionKey(): string
    {
        return 'academy.placement.' . $this->course->id . '.' . Auth::id();
    }

    public function mount(Course $course, AdaptivePlacementService $service): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless($service->isEnabled(), 404);

        $enrolled = Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403);

        $this->course = $course;

        // Reprise après rafraîchissement de page (l'état vit en session serveur).
        $state = session($this->sessionKey());
        if ($state !== null && ! empty($state['current_question'])) {
            $this->started       = true;
            $this->questionIndex = count($state['trace']) + 1;
        }
    }

    public function startTest(AdaptivePlacementService $service): void
    {
        $categoryIds = $service->categoryIdsForCourse($this->course);
        $question    = $service->drawQuestion($categoryIds, 'moyen', []);

        if ($question === null) {
            $this->unavailable = true;

            return;
        }

        $state = [
            'difficulty'          => 'moyen',
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'asked_ids'           => [$question['bank_question_id']],
            'trace'               => [],
            'category_ids'        => $categoryIds,
            'current_question'    => $question,
        ];

        session([$this->sessionKey() => $state]);

        $this->started           = true;
        $this->questionIndex     = 1;
        $this->lastAnswerCorrect = null;
        $this->lastExplanation   = null;
    }

    public function submitAnswer(int $givenIndex, AdaptivePlacementService $service): void
    {
        if (! $this->started || $this->finished) {
            return;
        }

        $state = session($this->sessionKey());
        if ($state === null || ($state['current_question'] ?? null) === null) {
            return;
        }

        // Anti double-soumission : déjà répondu, en attente du clic « Suivant ».
        if ($this->lastAnswerCorrect !== null) {
            return;
        }

        $mapped    = $state['current_question'];
        $isCorrect = $service->scoreAnswer($mapped, $givenIndex);

        $state['trace'][] = [
            'question_id' => (int) ($mapped['bank_question_id'] ?? 0),
            'difficulty'  => (string) ($mapped['difficulty'] ?? 'moyen'),
            'correct'     => $isCorrect,
        ];

        if ($isCorrect) {
            $state['consecutive_correct']++;
            $state['consecutive_wrong'] = 0;
        } else {
            $state['consecutive_wrong']++;
            $state['consecutive_correct'] = 0;
        }

        session([$this->sessionKey() => $state]);

        $this->lastAnswerCorrect = $isCorrect;
        $this->lastExplanation   = (string) ($mapped['general_feedback'] ?? '');
    }

    public function advance(AdaptivePlacementService $service): void
    {
        if (! $this->started || $this->finished || $this->lastAnswerCorrect === null) {
            return;
        }

        $state = session($this->sessionKey());
        if ($state === null) {
            return;
        }

        $askedCount = count($state['trace']);
        $stop       = $service->shouldStop($askedCount, $state['difficulty'], $state['consecutive_correct'], $state['consecutive_wrong']);

        if ($stop) {
            $this->finalize($state['difficulty'], $state['trace'], $service);
            session()->forget($this->sessionKey());

            return;
        }

        $newDifficulty = $service->nextDifficulty($state['difficulty'], $state['consecutive_correct'], $state['consecutive_wrong']);
        if ($newDifficulty !== $state['difficulty']) {
            // Nouveau palier : une nouvelle série est requise avant de rebouger.
            $state['consecutive_correct'] = 0;
            $state['consecutive_wrong']   = 0;
        }

        $state['difficulty'] = $newDifficulty;

        $nextQuestion = $service->drawQuestion($state['category_ids'], $newDifficulty, $state['asked_ids']);

        if ($nextQuestion === null) {
            // Banque épuisée : dégradation gracieuse, on conclut avec ce qu'on a.
            $this->finalize($newDifficulty, $state['trace'], $service);
            session()->forget($this->sessionKey());

            return;
        }

        $state['asked_ids'][]     = $nextQuestion['bank_question_id'];
        $state['current_question'] = $nextQuestion;
        session([$this->sessionKey() => $state]);

        $this->questionIndex++;
        $this->lastAnswerCorrect = null;
        $this->lastExplanation   = null;
    }

    private function finalize(string $finalDifficulty, array $trace, AdaptivePlacementService $service): void
    {
        $level  = $service->estimateLevel($finalDifficulty);
        $lesson = $service->recommendStartingLesson($this->course, $level);
        $first  = $service->orderedLessons($this->course)->first();

        PlacementAttempt::create([
            'user_id'               => Auth::id(),
            'course_id'             => $this->course->id,
            'questions_asked'       => $trace,
            'estimated_level'       => $level,
            'recommended_lesson_id' => $lesson?->id,
            'completed_at'          => now(),
        ]);

        $this->finished               = true;
        $this->estimatedLevel         = $level;
        $this->recommendedLessonId    = $lesson?->id;
        $this->recommendedLessonTitle = $lesson?->title;
        $this->firstLessonId          = $first?->id;
    }

    /**
     * Vue sûre de la question courante : AUCUNE clé de correction exposée.
     *
     * @return array{question: string, choices: array<int, string>, difficulty: string}|null
     */
    #[Computed]
    public function currentQuestionView(): ?array
    {
        $state = session($this->sessionKey());
        if ($state === null || ($state['current_question'] ?? null) === null) {
            return null;
        }

        return [
            'question'   => $state['current_question']['question'] ?? '',
            'choices'    => $state['current_question']['choices'] ?? [],
            'difficulty' => $state['current_question']['difficulty'] ?? 'moyen',
        ];
    }

    public function maxQuestions(): int
    {
        return AdaptivePlacementService::MAX_QUESTIONS;
    }

    public function render(): \Illuminate\View\View
    {
        return view('academy::livewire.placement-test');
    }
}
