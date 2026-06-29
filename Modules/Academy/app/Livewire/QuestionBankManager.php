<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Éditeur de la BANQUE DE QUESTIONS réutilisable (QB2). CRUD owner-scoped des
 * catégories et des questions de 9 types (mcq / truefalse / short / matching /
 * ordering / cloze / numerical / ddwtos / essay).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - La banque est OWNER-SCOPED : un formateur ne voit/gère QUE ses propres
 *    catégories+questions (owner_id = auth). L'admin (permission academy.manage)
 *    voit tout - c'est le SEUL écart au scoping owner, posé explicitement.
 *  - owner_id est TOUJOURS forcé = auth()->id() à la création (jamais du navigateur).
 *  - À CHAQUE mutation on RE-RÉSOUT l'entité serveur SCOPÉE à l'utilisateur
 *    (resolveCategory / resolveQuestion) : une catégorie/question d'un autre owner
 *    → ModelNotFound (anti-IDOR), aucune écriture possible.
 *  - Le parent d'une catégorie est validé contre MES catégories (anti-IDOR).
 *  - Le `type` d'une question est contraint à Question::TYPES (liste blanche) ; le
 *    payload est validé/normalisé par type avec les MÊMES invariants que
 *    QuestionBankService::mapToRoundItem (une question créée est TOUJOURS jouable).
 *  - @can(...) en Blade = AFFICHAGE seulement ; l'autorisation reste serveur.
 *  - Suppression de catégorie BLOQUÉE si elle contient des questions OU des
 *    sous-catégories (choix le plus sûr : aucune cascade silencieuse).
 *  - Confirmation de suppression INLINE à 2 temps (jamais confirm()/alert() natif).
 *
 * ARCHITECTURE : la logique est répartie dans 6 traits (Concerns/) pour la
 * maintenabilité. La classe conserve les constantes, les propriétés Livewire,
 * le mount() et les helpers d'autorisation/résolution partagés (anti-IDOR).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Academy\Livewire\Concerns\HandlesQbCategories;
use Modules\Academy\Livewire\Concerns\HandlesQbPayloadBuilders;
use Modules\Academy\Livewire\Concerns\HandlesQbPayloadRepeaters;
use Modules\Academy\Livewire\Concerns\HandlesQbQuestions;
use Modules\Academy\Livewire\Concerns\HandlesQbReads;
use Modules\Academy\Livewire\Concerns\HandlesQbTagsAndVersions;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

class QuestionBankManager extends Component
{
    use HandlesQbCategories;
    use HandlesQbPayloadBuilders;
    use HandlesQbPayloadRepeaters;
    use HandlesQbQuestions;
    use HandlesQbReads;
    use HandlesQbTagsAndVersions;

    // ── Constantes (utilisées dans les traits via self::) ────────────────────

    /** Niveaux de difficulté autorisés (liste blanche). */
    protected const DIFFICULTIES = ['facile', 'moyen', 'difficile'];

    /** F17 (TAGS) : bornes anti-abus (nombre d'étiquettes par question + longueur). */
    protected const MAX_TAGS       = 20;
    protected const MAX_TAG_LENGTH = 80;

    /** C4 : bornes anti-payload abusif pour le cloze (texte global + chaque entrée). */
    protected const MAX_CLOZE_TEXT  = 2000;
    protected const MAX_CLOZE_ENTRY = 500;

    /** C4 (F4) : borne dédiée au texte ddwtos (glisser-déposer), distincte du cloze. */
    protected const MAX_DDWTOS_TEXT = 2000;

    // ── Création de catégorie ────────────────────────────────────────────────
    public string $newCategoryName = '';
    public ?int $newCategoryParentId = null;

    // ── Renommage de catégorie (inline) ──────────────────────────────────────
    public ?int $renamingCategory = null;
    public string $renameCategoryName = '';

    /** Catégorie actuellement sélectionnée (édition des questions). */
    public ?int $selectedCategoryId = null;

    /**
     * F17 (TAGS) - filtre de la liste des questions par étiquette (owner-scopé).
     * null = aucun filtre (toutes les questions de la catégorie).
     */
    public ?int $filterTagId = null;

    /**
     * F17 (TAGS) - saisie des étiquettes de la question en cours d'édition, séparées
     * par des virgules.
     */
    public string $qTags = '';

    /**
     * F17 (VERSIONS) - question dont l'historique est actuellement affiché, ou null.
     */
    public ?int $historyQuestionId = null;

    // ── Formulaire de question (création / édition) ──────────────────────────
    /** Id de la question en cours d'édition (null = création d'une nouvelle). */
    public ?int $editingQuestionId = null;

    public string $qType = 'mcq';
    public string $qPrompt = '';
    public ?string $qExplanation = null;
    public string $qDifficulty = 'moyen';
    /** V1-c : pondération explicite de la question (1..100, défaut 1). */
    public int $qPoints = 1;
    public bool $qIsActive = true;

    // Sous-formulaires de payload par type (toujours initialisés, jamais null).
    /** @var array<int, string> */
    public array $qChoices = ['', ''];
    public int $qCorrect = 0;

    /**
     * V1-e - QCM À RÉPONSES MULTIPLES.
     *
     * @var array<int, int|string>
     */
    public bool $qMultiple = false;
    public array $qCorrectSet = [];

    /**
     * V1-a : rétroaction par choix (mcq).
     *
     * @var array<int, string>
     */
    public array $qChoiceFeedback = ['', ''];

    public bool $qAnswerTrue = true;

    /**
     * V1-a : rétroaction par choix pour Vrai/Faux.
     *
     * @var array<int, string>
     */
    public array $qTfFeedback = ['', ''];

    /** @var array<int, string> */
    public array $qAccepted = [''];
    public ?string $qDisplay = null;

    /** @var array<int, array{term: string, def: string}> */
    public array $qPairs = [['term' => '', 'def' => ''], ['term' => '', 'def' => '']];

    /**
     * ORDONNANCEMENT : éléments saisis dans le BON ordre.
     *
     * @var array<int, string>
     */
    public array $qOrderingItems = ['', '', ''];

    /** CLOZE / TEXTE À TROUS. */
    public string $qClozeText = '';

    /**
     * Trous du cloze (repeater).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $qClozeBlanks = [
        ['kind' => 'short', 'accepted' => '', 'display' => '', 'choices' => '', 'correct' => 0],
    ];

    /** NUMÉRIQUE. */
    public ?string $qNumericalCorrect   = null;
    public ?string $qNumericalTolerance = null;
    public ?string $qNumericalUnit      = null;

    /**
     * GLISSER-DÉPOSER SUR TEXTE (ddwtos).
     *
     * @var array<int, string>
     * @var array<int, int|string>
     */
    public string $qDdwtosText    = '';
    public array  $qDdwtosWords   = ['', '', ''];
    public array  $qDdwtosAnswers = [];

    /** ESSAI. */
    public ?string $qGraderInfo = null;

    // ── Confirmations inline à 2 temps ────────────────────────────────────────
    public ?int $confirmingCategoryDeletion = null;
    public ?int $confirmingQuestionDeletion = null;

    // ─────────────────────────────────────────────────────────────────────────
    // Entrée
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Autorisation SERVEUR d'affichage : seul un formateur (rôle instructor)
     * ou un admin (academy.manage) peut ouvrir la banque.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $allowed = $user
            && ($user->can('academy.manage') || $user->hasRole('instructor'));

        abort_unless($allowed, 403);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Résolution + autorisation serveur (cœur anti-IDOR — partagé par les traits)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Vrai si l'utilisateur courant voit TOUTE la banque (admin academy.manage).
     */
    private function isManager(): bool
    {
        return (bool) Auth::user()?->can('academy.manage');
    }

    /**
     * Re-résout une catégorie SCOPÉE à l'utilisateur (anti-IDOR).
     */
    private function resolveCategory(int $categoryId): QuestionCategory
    {
        $query = QuestionCategory::where('id', $categoryId);

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->firstOrFail();
    }

    /**
     * Re-résout une question SCOPÉE à l'utilisateur (anti-IDOR).
     */
    private function resolveQuestion(int $questionId): Question
    {
        $query = Question::where('id', $questionId);

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->firstOrFail();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rendu
    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('academy::livewire.question-bank-manager');
    }
}
