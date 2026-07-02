<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Composant Livewire DeckPlayer - présentation de leçon en cartes plein écran
 * SELF: moins de 5 lignes de logique propre ; fichier complet créé car nouveau composant
 * RAISON: drapeau academy.lesson_deck_mode=true bascule la leçon vers ce composant
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

class DeckPlayer extends Component
{
    // --- Props verrouillées (lecture seule côté client) ---

    #[Locked]
    public Lesson $lesson;

    #[Locked]
    public Course $course;

    #[Locked]
    public bool $isEnrolled = false;

    #[Locked]
    public bool $isPreview = false;

    #[Locked]
    public array $choiceVotes = [];

    #[Locked]
    public array $feedbackResponses = [];

    #[Locked]
    public array $itemRestrictions = [];

    #[Locked]
    public array $itemRatingStats = [];

    #[Locked]
    public array $userRatings = [];

    #[Locked]
    public array $itemComments = [];

    #[Locked]
    public array $prerequisitesUnmet = [];

    #[Locked]
    public bool $isDripLocked = false;

    #[Locked]
    public ?string $dripAvailableAt = null;

    #[Locked]
    public array $dripLockedLessonIds = [];

    #[Locked]
    public bool $courseCompleted = false;

    /**
     * Proxy vidéo signé (« protéger l'accès, pas l'iframe ») : map
     * [lesson_item_id => URL signée à expiration] calculée par LessonController
     * (URL::temporarySignedRoute, 4 h). Jamais le lien ScreenPal brut.
     *
     * @var array<int, string>
     */
    #[Locked]
    public array $videoRedirectUrls = [];

    // --- État navigable ---

    public int $currentIndex = 0;

    // --- Initialisation ---

    public function mount(
        Lesson $lesson,
        Course $course,
        bool $isEnrolled = false,
        bool $isPreview = false,
        array $choiceVotes = [],
        array $feedbackResponses = [],
        array $itemRestrictions = [],
        array $itemRatingStats = [],
        array $userRatings = [],
        array $itemComments = [],
        array $prerequisitesUnmet = [],
        bool $isDripLocked = false,
        ?string $dripAvailableAt = null,
        array $dripLockedLessonIds = [],
        bool $courseCompleted = false,
        array $videoRedirectUrls = [],
    ): void {
        $this->lesson               = $lesson;
        $this->course               = $course;
        $this->isEnrolled           = $isEnrolled;
        $this->isPreview            = $isPreview;
        $this->choiceVotes          = $choiceVotes;
        $this->feedbackResponses    = $feedbackResponses;
        $this->itemRestrictions     = $itemRestrictions;
        $this->itemRatingStats      = $itemRatingStats;
        $this->userRatings          = $userRatings;
        $this->itemComments         = $itemComments;
        $this->prerequisitesUnmet   = $prerequisitesUnmet;
        $this->isDripLocked         = $isDripLocked;
        $this->dripAvailableAt      = $dripAvailableAt;
        $this->dripLockedLessonIds  = $dripLockedLessonIds;
        $this->courseCompleted      = $courseCompleted;
        $this->videoRedirectUrls    = $videoRedirectUrls;
    }

    // --- Computed : items visibles (exclut les items masqués par restriction) ---

    #[Computed]
    public function items(): Collection
    {
        return $this->lesson->lessonItems->filter(function (LessonItem $item): bool {
            $restriction = $this->itemRestrictions[$item->id] ?? null;
            return ! ($restriction && ($restriction['hidden'] ?? false));
        })->values();
    }

    // --- Navigation ---

    public function next(): void
    {
        $max = $this->items->count() - 1;
        if ($this->currentIndex < $max) {
            $this->currentIndex++;
        }
    }

    public function previous(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
        }
    }

    public function goTo(int $index): void
    {
        $max = $this->items->count() - 1;
        $this->currentIndex = max(0, min($index, $max));
    }

    // --- Classe CSS selon le type d'item ---

    public function cardStyle(LessonItem $item): string
    {
        $verificationTypes = ['quiz', 'choice', 'feedback'];
        $actionTypes       = ['workshop', 'forum'];

        if (in_array($item->type, $verificationTypes, true)) {
            return 'deck-card deck-card--verification';
        }

        if (in_array($item->type, $actionTypes, true)) {
            return 'deck-card deck-card--action';
        }

        return 'deck-card deck-card--concept';
    }

    public function render(): \Illuminate\View\View
    {
        return view('academy::livewire.deck-player');
    }
}
