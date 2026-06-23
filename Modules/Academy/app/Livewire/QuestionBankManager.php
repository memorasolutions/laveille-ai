<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Éditeur de la BANQUE DE QUESTIONS réutilisable (QB2). CRUD owner-scoped des
 * catégories et des questions des 4 types (mcq / truefalse / short / matching).
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
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuestionTag;
use Modules\Academy\Models\QuestionVersion;
use Modules\Academy\Services\QuestionStatsService;

class QuestionBankManager extends Component
{
    /** Niveaux de difficulté autorisés (liste blanche). */
    private const DIFFICULTIES = ['facile', 'moyen', 'difficile'];

    /** F17 (TAGS) : bornes anti-abus (nombre d'étiquettes par question + longueur). */
    private const MAX_TAGS = 20;
    private const MAX_TAG_LENGTH = 80;

    /** C4 : bornes anti-payload abusif pour le cloze (texte global + chaque entrée). */
    private const MAX_CLOZE_TEXT = 2000;
    private const MAX_CLOZE_ENTRY = 500;

    /** C4 (F4) : borne dédiée au texte ddwtos (glisser-déposer), distincte du cloze. */
    private const MAX_DDWTOS_TEXT = 2000;

    // ── Création de catégorie ────────────────────────────────────────────────────
    public string $newCategoryName = '';
    public ?int $newCategoryParentId = null;

    // ── Renommage de catégorie (inline) ──────────────────────────────────────────
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
     * par des virgules. Les étiquettes inconnues sont créées à la volée (owner-scopé)
     * à l'enregistrement. Vide = aucune étiquette.
     */
    public string $qTags = '';

    /**
     * F17 (VERSIONS) - question dont l'historique est actuellement affiché (panneau
     * inline en lecture seule), ou null si fermé. Owner-scopé via resolveQuestion.
     */
    public ?int $historyQuestionId = null;

    // ── Formulaire de question (création / édition) ──────────────────────────────
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
     * V1-e - QCM À RÉPONSES MULTIPLES. Quand $qMultiple est vrai, la désignation des
     * bonnes réponses passe de radio (un seul $qCorrect) à cases à cocher : $qCorrectSet
     * collecte les index cochés (>= 1 exigé). Défaut false = QCM simple inchangé.
     *
     * @var array<int, int|string>
     */
    public bool $qMultiple = false;
    public array $qCorrectSet = [];

    /**
     * V1-a : rétroaction par choix (mcq) - optionnelle, indexée comme $qChoices
     * (« si l'apprenant choisit ce choix, on affiche ce texte »). Vide = aucune.
     *
     * @var array<int, string>
     */
    public array $qChoiceFeedback = ['', ''];

    public bool $qAnswerTrue = true;

    /**
     * V1-a : rétroaction par choix pour Vrai/Faux - 2 entrées (0 = Vrai, 1 = Faux),
     * optionnelles. Vide = aucune.
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
     * ORDONNANCEMENT : éléments saisis dans le BON ordre (index 0 = position 1, …).
     * C'est l'ORDRE qui compte ; au tirage, QuestionBankService mélange l'affichage en
     * conservant la correspondance vers l'ordre correct. Au moins 2 éléments.
     *
     * @var array<int, string>
     */
    public array $qOrderingItems = ['', '', ''];

    /**
     * CLOZE / TEXTE À TROUS. Le texte porte des marqueurs numérotés [[1]], [[2]], …
     * (1-based) ; chaque marqueur [[n]] renvoie au trou $qClozeBlanks[n-1].
     */
    public string $qClozeText = '';

    /**
     * Trous du cloze (repeater). Chaque entrée :
     *   - kind     : 'short' | 'mcq' ;
     *   - accepted : réponses acceptées (short), saisies séparées par des virgules ;
     *   - display  : indice d'affichage (short), facultatif ;
     *   - choices  : options (mcq), une par ligne ;
     *   - correct  : index 0-based de la bonne option (mcq).
     * Les chaînes accepted/choices sont DÉCOUPÉES au build (forme tableau canonique du
     * payload), pour garder le sous-formulaire simple et robuste (pas de repeater imbriqué).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $qClozeBlanks = [
        ['kind' => 'short', 'accepted' => '', 'display' => '', 'choices' => '', 'correct' => 0],
    ];

    /**
     * NUMÉRIQUE (type Moodle « Numerical »). Saisies en chaîne (tolère la virgule
     * décimale, normalisées au build via QuizService::parseNumber) :
     *   - qNumericalCorrect   : réponse attendue (REQUISE, numérique) ;
     *   - qNumericalTolerance : écart absolu admis (± , facultatif, >= 0, défaut 0) ;
     *   - qNumericalUnit      : unité indicative facultative (ex. « km », « % »).
     */
    public ?string $qNumericalCorrect = null;
    public ?string $qNumericalTolerance = null;
    public ?string $qNumericalUnit = null;

    /**
     * GLISSER-DÉPOSER SUR TEXTE (« Drag and drop into text » Moodle, ddwtos).
     *  - $qDdwtosText    : texte à marqueurs [[1]], [[2]], … (1-based) ;
     *  - $qDdwtosWords   : POOL de mots PARTAGÉ (inclut les distracteurs), repeater ;
     *  - $qDdwtosAnswers : map index_de_trou (0-based : [[1]] → 0) => index du mot correct
     *                      DANS LE POOL ($qDdwtosWords). Chaque trou se remplit avec un mot
     *                      du pool ; à l'examen le pool est mélangé et présenté en <select>.
     *
     * @var array<int, string>
     * @var array<int, int|string>
     */
    public string $qDdwtosText = '';
    public array $qDdwtosWords = ['', '', ''];
    public array $qDdwtosAnswers = [];

    /**
     * ESSAI (type Moodle « Essay »). Réponse rédigée à CORRECTION MANUELLE : pas de
     * bonne réponse. L'énoncé = $qPrompt ; le barème = $qPoints. Champ propre :
     *   - $qGraderInfo : consignes de correction OPTIONNELLES (visibles seulement au
     *                    formateur lors de la correction). Stockées dans payload['grader_info'].
     */
    public ?string $qGraderInfo = null;

    // ── Confirmations inline à 2 temps (jamais de popup native) ───────────────────
    public ?int $confirmingCategoryDeletion = null;
    public ?int $confirmingQuestionDeletion = null;

    /**
     * Entrée. Autorisation SERVEUR d'affichage : seul un formateur (rôle instructor)
     * ou un admin (academy.manage) peut ouvrir la banque. Aligné sur CoursePolicy
     * viewAny (academy.manage OU instructor). Sinon 403.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $allowed = $user
            && ($user->can('academy.manage') || $user->hasRole('instructor'));

        abort_unless($allowed, 403);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Résolution + autorisation serveur (cœur anti-IDOR - owner-scoped)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Vrai si l'utilisateur courant voit TOUTE la banque (admin academy.manage).
     * Sinon il est borné à SES propres catégories/questions (owner_id = auth).
     */
    private function isManager(): bool
    {
        return (bool) Auth::user()?->can('academy.manage');
    }

    /**
     * Re-résout une catégorie SCOPÉE à l'utilisateur (anti-IDOR). Un admin résout
     * n'importe quelle catégorie ; un formateur uniquement les siennes (owner_id).
     * Une catégorie hors périmètre → ModelNotFound (aucune écriture possible).
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
     * Re-résout une question SCOPÉE à l'utilisateur (anti-IDOR), même logique.
     */
    private function resolveQuestion(int $questionId): Question
    {
        $query = Question::where('id', $questionId);

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->firstOrFail();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // CATÉGORIES
    // ─────────────────────────────────────────────────────────────────────────────

    public function createCategory(): void
    {
        $this->normalizeNullableInt('newCategoryParentId');

        $data = $this->validate([
            'newCategoryName'     => 'required|string|max:160',
            'newCategoryParentId' => 'nullable|integer',
        ]);

        // Anti-IDOR : le parent doit appartenir à MES catégories (re-résolu serveur).
        // Un admin peut rattacher sous n'importe quelle catégorie qu'il voit.
        $parentId = null;
        if (! empty($data['newCategoryParentId'])) {
            $parent   = $this->resolveCategory((int) $data['newCategoryParentId']);
            $parentId = $parent->id;
        }

        $position = (int) QuestionCategory::where('owner_id', Auth::id())
            ->where('parent_id', $parentId)
            ->max('position') + 1;

        QuestionCategory::create([
            'owner_id'  => Auth::id(), // FORCÉ = auth (jamais du navigateur).
            'parent_id' => $parentId,
            'name'      => trim($data['newCategoryName']),
            'position'  => $position,
        ]);

        $this->reset('newCategoryName', 'newCategoryParentId');
        session()->flash('academy_bank_status', 'Catégorie créée.');
    }

    public function startRenameCategory(int $categoryId): void
    {
        $category = $this->resolveCategory($categoryId);

        $this->renamingCategory   = $category->id;
        $this->renameCategoryName = $category->name;
        $this->resetErrorBag('renameCategoryName');
    }

    public function cancelRenameCategory(): void
    {
        $this->renamingCategory = null;
        $this->reset('renameCategoryName');
        $this->resetErrorBag('renameCategoryName');
    }

    public function renameCategory(): void
    {
        if ($this->renamingCategory === null) {
            return;
        }

        $category = $this->resolveCategory((int) $this->renamingCategory);

        $data = $this->validate([
            'renameCategoryName' => 'required|string|max:160',
        ]);

        $category->update(['name' => trim($data['renameCategoryName'])]);

        $this->renamingCategory = null;
        $this->reset('renameCategoryName');
        session()->flash('academy_bank_status', 'Catégorie renommée.');
    }

    /**
     * Supprime une catégorie. BLOQUÉ si elle contient des questions OU des
     * sous-catégories (choix le plus sûr : aucune perte silencieuse en cascade).
     */
    public function deleteCategory(int $categoryId): void
    {
        $category = $this->resolveCategory($categoryId);

        $hasQuestions = $category->questions()->exists();
        $hasChildren  = $category->children()->exists();

        if ($hasQuestions || $hasChildren) {
            $this->confirmingCategoryDeletion = null;
            session()->flash(
                'academy_bank_error',
                'Impossible de supprimer une catégorie qui contient des questions ou des sous-catégories. Videz-la d\'abord.'
            );

            return;
        }

        $category->delete();

        if ($this->selectedCategoryId === $categoryId) {
            $this->selectedCategoryId = null;
            $this->resetQuestionForm();
        }
        $this->confirmingCategoryDeletion = null;
        session()->flash('academy_bank_status', 'Catégorie supprimée.');
    }

    /** Sélectionne une catégorie (affiche ses questions + le formulaire). */
    public function selectCategory(int $categoryId): void
    {
        $category = $this->resolveCategory($categoryId);

        $this->selectedCategoryId = $category->id;
        $this->filterTagId        = null; // F17 : nouvelle catégorie → on repart sans filtre.
        $this->historyQuestionId  = null;
        $this->resetQuestionForm();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // QUESTIONS
    // ─────────────────────────────────────────────────────────────────────────────

    /** Ajoute un choix (mcq) - borné côté serveur au build du payload. */
    public function addChoice(): void
    {
        $this->qChoices[]         = '';
        // V1-a : garder la rétroaction par choix alignée sur les choix (même cardinalité).
        $this->qChoiceFeedback[]  = '';
    }

    public function removeChoice(int $index): void
    {
        if (count($this->qChoices) <= 2) {
            return; // minimum 2 choix.
        }

        unset($this->qChoices[$index]);
        $this->qChoices = array_values($this->qChoices);

        // V1-a : retirer la rétroaction du même index et ré-indexer.
        unset($this->qChoiceFeedback[$index]);
        $this->qChoiceFeedback = array_values($this->qChoiceFeedback);

        if ($this->qCorrect >= count($this->qChoices)) {
            $this->qCorrect = 0;
        }

        // V1-e : garder $qCorrectSet cohérent après ré-indexation (retire l'index
        // supprimé, décale les index supérieurs d'un cran).
        if ($this->qMultiple) {
            $newSet = [];
            foreach (array_map('intval', $this->qCorrectSet) as $sel) {
                if ($sel === $index) {
                    continue;
                }
                $newSet[] = $sel > $index ? $sel - 1 : $sel;
            }
            $this->qCorrectSet = array_values(array_unique($newSet));
        }
    }

    public function addAccepted(): void
    {
        $this->qAccepted[] = '';
    }

    public function removeAccepted(int $index): void
    {
        if (count($this->qAccepted) <= 1) {
            return; // minimum 1 réponse acceptée.
        }

        unset($this->qAccepted[$index]);
        $this->qAccepted = array_values($this->qAccepted);
    }

    public function addPair(): void
    {
        $this->qPairs[] = ['term' => '', 'def' => ''];
    }

    public function removePair(int $index): void
    {
        if (count($this->qPairs) <= 2) {
            return; // minimum 2 paires.
        }

        unset($this->qPairs[$index]);
        $this->qPairs = array_values($this->qPairs);
    }

    /** Ajoute un élément d'ordonnancement (à la fin de l'ordre attendu). */
    public function addOrderingItem(): void
    {
        $this->qOrderingItems[] = '';
    }

    public function removeOrderingItem(int $index): void
    {
        if (count($this->qOrderingItems) <= 2) {
            return; // minimum 2 éléments.
        }

        unset($this->qOrderingItems[$index]);
        $this->qOrderingItems = array_values($this->qOrderingItems);
    }

    /**
     * Réordonne un élément d'ordonnancement (l'ordre saisi EST la bonne réponse).
     * $direction : 'up' (remonter) ou 'down' (descendre). Bornes respectées (no-op
     * en dehors). Échange simple avec l'élément voisin.
     */
    public function moveOrderingItem(int $index, string $direction): void
    {
        $items  = array_values($this->qOrderingItems);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index < 0 || $index >= count($items) || $target < 0 || $target >= count($items)) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        $this->qOrderingItems = $items;
    }

    /** Ajoute un trou de cloze (à la suite ; son numéro de marqueur = position + 1). */
    public function addClozeBlank(): void
    {
        $this->qClozeBlanks[] = ['kind' => 'short', 'accepted' => '', 'display' => '', 'choices' => '', 'correct' => 0];
    }

    public function removeClozeBlank(int $index): void
    {
        if (count($this->qClozeBlanks) <= 1) {
            return; // minimum 1 trou.
        }

        unset($this->qClozeBlanks[$index]);
        $this->qClozeBlanks = array_values($this->qClozeBlanks);
    }

    /** Ajoute un mot au pool ddwtos (glisser-déposer sur texte). */
    public function addDdwtosWord(): void
    {
        $this->qDdwtosWords[] = '';
    }

    /**
     * Retire un mot du pool ddwtos et ré-indexe. Met à jour $qDdwtosAnswers : un trou
     * qui pointait le mot retiré perd sa désignation ; les index supérieurs reculent d'un
     * cran (cohérence avec la ré-indexation du pool, comme removeChoice le fait pour le QCM).
     */
    public function removeDdwtosWord(int $index): void
    {
        if (count($this->qDdwtosWords) <= 2) {
            return; // pool minimal de 2 mots.
        }

        unset($this->qDdwtosWords[$index]);
        $this->qDdwtosWords = array_values($this->qDdwtosWords);

        $newAnswers = [];
        foreach ($this->qDdwtosAnswers as $blankIdx => $wordIdx) {
            $wordIdx = (int) $wordIdx;
            if ($wordIdx === $index) {
                continue; // ce trou perd sa désignation (mot retiré).
            }
            $newAnswers[(int) $blankIdx] = $wordIdx > $index ? $wordIdx - 1 : $wordIdx;
        }
        $this->qDdwtosAnswers = $newAnswers;
    }

    /**
     * Enregistre une question (création OU édition selon $editingQuestionId).
     * La catégorie est re-résolue scopée owner (anti-IDOR) ; le type est en liste
     * blanche ; le payload est validé/normalisé par type.
     */
    public function saveQuestion(): void
    {
        if ($this->selectedCategoryId === null) {
            session()->flash('academy_bank_error', 'Sélectionnez d\'abord une catégorie.');

            return;
        }

        // Anti-IDOR : la catégorie cible doit m'appartenir (re-résolue serveur).
        $category = $this->resolveCategory((int) $this->selectedCategoryId);

        // Validation commune (hors payload, traité par type ensuite).
        $this->validate([
            'qType'        => ['required', Rule::in(Question::TYPES)],
            'qPrompt'      => 'required|string|max:2000',
            'qExplanation' => 'nullable|string|max:2000',
            'qDifficulty'  => ['required', Rule::in(self::DIFFICULTIES)],
            // V1-c : pondération bornée serveur 1..100 (défaut 1).
            'qPoints'      => 'required|integer|min:1|max:100',
            // C4 : borne de longueur du texte à trous (anti-payload abusif). Inerte pour
            // les autres types (qClozeText vide) ; détaillé par trou dans buildClozePayload.
            'qClozeText'   => 'nullable|string|max:'.self::MAX_CLOZE_TEXT,
            // Borne de longueur du texte ddwtos (anti-payload abusif). Inerte pour les
            // autres types (qDdwtosText vide) ; détail par mot dans buildDdwtosPayload.
            'qDdwtosText'  => 'nullable|string|max:'.self::MAX_DDWTOS_TEXT,
            // C2 (audit F3) : l'unité numérique n'avait QUE le maxlength HTML
            // (contournable). Règle serveur (inerte pour les autres types : qNumericalUnit vide).
            'qNumericalUnit' => 'nullable|string|max:40',
            // ESSAI : consignes de correction (inerte pour les autres types : qGraderInfo vide).
            'qGraderInfo'    => 'nullable|string|max:2000',
        ]);

        // Construit + valide le payload selon le type (mêmes invariants que mapToRoundItem).
        $payload = $this->buildAndValidatePayload($this->qType);

        $attributes = [
            'category_id' => $category->id,
            'type'        => $this->qType,
            'prompt'      => trim($this->qPrompt),
            'explanation' => $this->qExplanation !== null && trim($this->qExplanation) !== ''
                ? trim($this->qExplanation)
                : null,
            'difficulty'  => $this->qDifficulty,
            'points'      => max(1, min(100, (int) $this->qPoints)),
            'is_active'   => $this->qIsActive,
            'payload'     => $payload,
        ];

        if ($this->editingQuestionId !== null) {
            $question = $this->resolveQuestion((int) $this->editingQuestionId);

            // F17 (VERSIONS) : on archive d'ABORD l'état PRÉCÉDENT si le CONTENU change
            // (prompt / payload / explanation / type). Une simple modification de
            // difficulté/points/activité/tags ne crée pas de version (bruit inutile).
            $this->maybeSnapshotVersion($question, $attributes);

            // La catégorie cible est re-validée comme mienne ci-dessus → réaffectation sûre.
            $question->update($attributes);
            $message = 'Question mise à jour.';
        } else {
            $attributes['owner_id'] = Auth::id(); // FORCÉ = auth.
            $question = Question::create($attributes);
            $message = 'Question ajoutée.';
        }

        // F17 (TAGS) : synchronisation owner-scopée (création à la volée). Anti-IDOR :
        // chaque tag est résolu/créé pour owner_id = auth → jamais un tag d'un autre owner.
        $this->syncTags($question);

        $this->resetQuestionForm();
        session()->flash('academy_bank_status', $message);
    }

    /** Charge une question dans le formulaire (édition, anti-IDOR). */
    public function editQuestion(int $questionId): void
    {
        $question = $this->resolveQuestion($questionId);

        // On reste sur la catégorie de la question (sélection cohérente).
        $this->selectedCategoryId = (int) $question->category_id;

        $this->editingQuestionId = $question->id;
        $this->qType             = in_array($question->type, Question::TYPES, true) ? $question->type : 'mcq';
        $this->qPrompt           = (string) $question->prompt;
        $this->qExplanation      = $question->explanation;
        $this->qDifficulty       = in_array($question->difficulty, self::DIFFICULTIES, true) ? (string) $question->difficulty : 'moyen';
        $this->qPoints           = max(1, min(100, (int) ($question->points ?? 1)));
        $this->qIsActive         = (bool) $question->is_active;

        $payload = is_array($question->payload) ? $question->payload : [];
        $this->hydratePayloadForm($this->qType, $payload);

        // F17 (TAGS) : pré-remplit la saisie avec les étiquettes existantes (libellés
        // séparés par des virgules), owner-scopées via la relation.
        $this->qTags = $question->tags()->orderBy('name')->pluck('name')->implode(', ');

        $this->resetErrorBag();
    }

    public function deleteQuestion(int $questionId): void
    {
        $question = $this->resolveQuestion($questionId);
        $question->delete();

        if ($this->editingQuestionId === $questionId) {
            $this->resetQuestionForm();
        }
        if ($this->historyQuestionId === $questionId) {
            $this->historyQuestionId = null;
        }
        $this->confirmingQuestionDeletion = null;
        session()->flash('academy_bank_status', 'Question supprimée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // F17 - TAGS (étiquettes owner-scopées, création à la volée) + VERSIONS
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Synchronise les étiquettes de la question depuis la saisie $qTags (libellés
     * séparés par des virgules). Owner-scopé STRICT : chaque tag est résolu/créé pour
     * l'OWNER de la question (= auth à la création ; l'owner d'origine si un admin
     * édite la question d'un autre). Un tag d'un AUTRE owner n'est donc jamais attaché
     * (firstOrCreate sur owner_id + slug → un libellé homonyme d'un autre owner crée un
     * tag DISTINCT). Doublons (par slug) et entrées vides ignorés ; bornes appliquées.
     */
    private function syncTags(Question $question): void
    {
        $ownerId = (int) ($question->owner_id ?: Auth::id());

        $tagIds    = [];
        $seenSlugs = [];

        foreach (explode(',', $this->qTags) as $raw) {
            if (count($tagIds) >= self::MAX_TAGS) {
                break;
            }

            $name = trim((string) $raw);
            if ($name === '') {
                continue;
            }
            $name = mb_substr($name, 0, self::MAX_TAG_LENGTH);
            $slug = QuestionTag::slugify($name);
            if ($slug === '' || isset($seenSlugs[$slug])) {
                continue;
            }
            $seenSlugs[$slug] = true;

            $tag = QuestionTag::firstOrCreate(
                ['owner_id' => $ownerId, 'slug' => $slug],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $question->tags()->sync($tagIds);
    }

    /**
     * F17 (VERSIONS) : archive l'état PRÉCÉDENT de la question AVANT écriture, mais
     * SEULEMENT si le contenu noté change (prompt / type / explication / payload). Une
     * édition purement « cosmétique » (difficulté, points, activité, tags) ne génère
     * pas de version. Le numéro est incrémenté par question. Owner recopié de la question.
     *
     * @param  array<string, mixed>  $newAttributes
     */
    private function maybeSnapshotVersion(Question $question, array $newAttributes): void
    {
        $oldPayload = is_array($question->payload) ? $question->payload : [];
        $newPayload = is_array($newAttributes['payload'] ?? null) ? $newAttributes['payload'] : [];

        $contentChanged = (string) $question->prompt !== (string) ($newAttributes['prompt'] ?? '')
            || (string) $question->type !== (string) ($newAttributes['type'] ?? '')
            || (string) ($question->explanation ?? '') !== (string) ($newAttributes['explanation'] ?? '')
            || json_encode($oldPayload) !== json_encode($newPayload);

        if (! $contentChanged) {
            return;
        }

        // Transaction : isole le max()+1 et le INSERT pour éviter une collision de numéro
        // de version en cas d'édition simultanée (deux formateurs admin sur la même question).
        DB::transaction(function () use ($question, $oldPayload): void {
            $nextVersion = (int) QuestionVersion::where('question_id', $question->id)->max('version') + 1;

            QuestionVersion::create([
                'question_id' => $question->id,
                'owner_id'    => (int) ($question->owner_id ?: Auth::id()),
                'version'     => $nextVersion,
                'prompt'      => (string) $question->prompt,
                'payload'     => $oldPayload,
                'explanation' => $question->explanation,
                'type'        => (string) $question->type,
                'snapshot_at' => now(),
            ]);
        });
    }

    /** Ouvre le panneau d'historique d'une question (anti-IDOR via resolveQuestion). */
    public function showHistory(int $questionId): void
    {
        $question = $this->resolveQuestion($questionId);
        $this->historyQuestionId = $question->id;
    }

    public function closeHistory(): void
    {
        $this->historyQuestionId = null;
    }

    /**
     * Recharge une version archivée DANS le formulaire d'édition (l'utilisateur
     * ré-enregistre ensuite pour restaurer). Pas de « le quiz épingle une version »
     * (hors périmètre F17). Anti-IDOR : la version est bornée à une question résolue
     * owner-scopée (et au question_id de celle-ci).
     */
    public function restoreVersion(int $versionId): void
    {
        if ($this->historyQuestionId === null) {
            return;
        }

        $question = $this->resolveQuestion((int) $this->historyQuestionId);

        // Anti-IDOR en profondeur : on borne aussi à owner_id = auth (la question est déjà
        // résolue owner-scopée, mais cette garde supplémentaire bloque un id de version
        // appartenant à un autre formateur sur la même question).
        $version = QuestionVersion::where('id', $versionId)
            ->where('question_id', $question->id)
            ->where('owner_id', Auth::id())
            ->firstOrFail();

        $this->editingQuestionId  = $question->id;
        $this->selectedCategoryId = (int) $question->category_id;

        $this->qType        = in_array($version->type, Question::TYPES, true) ? $version->type : 'mcq';
        $this->qPrompt      = (string) $version->prompt;
        $this->qExplanation = $version->explanation;
        // Difficulté / points / activité / tags ne sont pas versionnés → conservés tels quels.
        $this->qDifficulty  = in_array($question->difficulty, self::DIFFICULTIES, true) ? (string) $question->difficulty : 'moyen';
        $this->qPoints      = max(1, min(100, (int) ($question->points ?? 1)));
        $this->qIsActive    = (bool) $question->is_active;
        $this->qTags        = $question->tags()->orderBy('name')->pluck('name')->implode(', ');

        $this->hydratePayloadForm($this->qType, is_array($version->payload) ? $version->payload : []);

        $this->historyQuestionId = null;
        $this->resetErrorBag();
        session()->flash('academy_bank_status', 'Version '.$version->version.' rechargée dans le formulaire. Enregistrez pour la restaurer.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Payload par type : build + validation (invariants alignés sur mapToRoundItem)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Construit et VALIDE le payload selon le type. Lève une ValidationException
     * (via $this->addError + throw) si les invariants ne sont pas respectés, pour
     * qu'une question stockée soit TOUJOURS exploitable par QuestionBankService.
     *
     * @return array<string, mixed>
     */
    private function buildAndValidatePayload(string $type): array
    {
        return match ($type) {
            'mcq'       => $this->buildMcqPayload(),
            'truefalse' => $this->buildTrueFalsePayload(),
            'short'     => $this->buildShortPayload(),
            'matching'  => $this->buildMatchingPayload(),
            'ordering'  => $this->buildOrderingPayload(),
            'cloze'     => $this->buildClozePayload(),
            'numerical' => $this->buildNumericalPayload(),
            'ddwtos'    => $this->buildDdwtosPayload(),
            'essay'     => $this->buildEssayPayload(),
            default     => [],
        };
    }

    /**
     * @return array<string, mixed>
     *
     * ESSAI. Forme canonique du payload : payload['grader_info'] = consignes de
     * correction (optionnelles, bornées). Aucune bonne réponse ; toujours jouable
     * (cf. QuestionBankService::mapToRoundItem cas essay). L'énoncé et le barème
     * vivent dans Question.prompt / Question.points (déjà validés en amont).
     */
    private function buildEssayPayload(): array
    {
        $info = $this->qGraderInfo !== null ? trim($this->qGraderInfo) : '';

        return $info !== '' ? ['grader_info' => mb_substr($info, 0, 2000)] : [];
    }

    /**
     * @return array<string, mixed>
     *
     * NUMÉRIQUE. Forme canonique du payload :
     *   payload['correct']   = float (réponse attendue, REQUISE) ;
     *   payload['tolerance'] = float >= 0 (défaut 0) ;
     *   payload['unit']      = string (si non vide).
     * Mêmes invariants que QuestionBankService::mapToRoundItem (cas numerical) → une
     * question créée est TOUJOURS jouable (réponse numérique valide garantie).
     */
    private function buildNumericalPayload(): array
    {
        $correct = \Modules\Academy\Services\QuizService::parseNumber($this->qNumericalCorrect);
        if ($correct === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qNumericalCorrect' => 'Indiquez une réponse numérique valide (ex. 42 ou 3,14).',
            ]);
        }

        // C1 (audit F3) : DÉFENSE en profondeur. parseNumber renvoie déjà null pour
        // INF/-INF/NAN (« 1e309 ») → correct serait déjà null ci-dessus ; cette garde
        // protège contre toute valeur non finie qui passerait autrement, avec un
        // message clair (un payload INF casserait json_encode → corruption / 500).
        if (! is_finite($correct)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qNumericalCorrect' => 'Valeur hors plage numérique.',
            ]);
        }

        $tolerance = 0.0;
        if ($this->qNumericalTolerance !== null && trim((string) $this->qNumericalTolerance) !== '') {
            $parsed = \Modules\Academy\Services\QuizService::parseNumber($this->qNumericalTolerance);
            if ($parsed === null || $parsed < 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qNumericalTolerance' => 'La tolérance doit être un nombre positif ou nul.',
                ]);
            }
            if (! is_finite($parsed)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qNumericalTolerance' => 'Valeur hors plage numérique.',
                ]);
            }
            $tolerance = $parsed;
        }

        $payload = ['correct' => $correct, 'tolerance' => $tolerance];

        // C2 (audit F3) : l'unité est purement indicative ; on la borne à 40 caractères
        // (parité avec la règle serveur de saveQuestion) pour éviter tout payload abusif.
        $unit = $this->qNumericalUnit !== null ? trim($this->qNumericalUnit) : '';
        if ($unit !== '') {
            $payload['unit'] = mb_substr($unit, 0, 40);
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function buildMcqPayload(): array
    {
        // On filtre les choix vides MAIS on conserve l'index d'origine pour ré-aligner
        // la rétroaction par choix (V1-a) APRÈS ré-indexation des choix retenus.
        $keptOriginalIndexes = [];
        $choices             = [];
        foreach ($this->qChoices as $i => $c) {
            $value = is_string($c) ? trim($c) : '';
            if ($value !== '') {
                $choices[]             = $value;
                $keptOriginalIndexes[] = (int) $i;
            }
        }

        if (count($choices) < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qChoices' => 'Un choix multiple exige au moins 2 réponses non vides.',
            ]);
        }

        $payload = ['choices' => $choices];

        // V1-e - désignation des bonnes réponses. Les index saisis référent les choix
        // d'ORIGINE ; on les remappe vers les index FINAUX (post-filtrage des vides),
        // exactement comme la rétroaction par choix.
        if ($this->qMultiple) {
            $checkedOriginal = array_map('intval', array_values($this->qCorrectSet));
            $correctSet      = [];
            foreach ($keptOriginalIndexes as $finalIndex => $originalIndex) {
                if (in_array($originalIndex, $checkedOriginal, true)) {
                    $correctSet[] = $finalIndex;
                }
            }
            if ($correctSet === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qCorrectSet' => 'Cochez au moins une bonne réponse.',
                ]);
            }
            $payload['multiple']    = true;
            $payload['correct_set'] = $correctSet;
        } else {
            // QCM simple : remap de l'index unique vers la liste filtrée.
            $finalCorrect = array_search((int) $this->qCorrect, $keptOriginalIndexes, true);
            if ($finalCorrect === false) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qCorrect' => 'Désignez une bonne réponse valide parmi les choix.',
                ]);
            }
            $payload['correct'] = (int) $finalCorrect;
        }

        // V1-a : rétroaction par choix (optionnelle), ré-alignée sur les choix retenus.
        $feedback = $this->collectChoiceFeedback($this->qChoiceFeedback, $keptOriginalIndexes);
        if ($feedback !== []) {
            $payload['choice_feedback'] = $feedback;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function buildTrueFalsePayload(): array
    {
        $payload = ['answer' => (bool) $this->qAnswerTrue];

        // V1-a : rétroaction par choix (0 = Vrai, 1 = Faux), index inchangé (2 choix fixes).
        $feedback = $this->collectChoiceFeedback($this->qTfFeedback, [0, 1]);
        if ($feedback !== []) {
            $payload['choice_feedback'] = $feedback;
        }

        return $payload;
    }

    /**
     * V1-a : construit le tableau choice_feedback (index final => texte) à partir
     * des textes saisis et de la liste des index d'origine RETENUS (post-filtrage).
     * Le résultat est indexé 0..N-1 dans l'ordre des choix finaux. Tout vide → [].
     *
     * @param  array<int, string>  $source              Textes saisis (indexés comme à la saisie).
     * @param  array<int, int>     $keptOriginalIndexes Index d'origine des choix retenus, dans l'ordre.
     * @return array<int, string>
     */
    private function collectChoiceFeedback(array $source, array $keptOriginalIndexes): array
    {
        $out    = [];
        $hasAny = false;

        foreach ($keptOriginalIndexes as $finalIndex => $originalIndex) {
            $raw   = $source[$originalIndex] ?? '';
            $value = is_string($raw) ? trim($raw) : '';
            $out[$finalIndex] = $value;
            if ($value !== '') {
                $hasAny = true;
            }
        }

        return $hasAny ? $out : [];
    }

    /** @return array<string, mixed> */
    private function buildShortPayload(): array
    {
        $accepted = array_values(array_filter(
            array_map(fn ($a) => is_string($a) ? trim($a) : '', $this->qAccepted),
            fn (string $a): bool => $a !== ''
        ));

        if ($accepted === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qAccepted' => 'Indiquez au moins une réponse acceptée.',
            ]);
        }

        $payload = ['accepted' => $accepted];
        $display = $this->qDisplay !== null ? trim($this->qDisplay) : '';
        if ($display !== '') {
            $payload['display'] = $display;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    private function buildMatchingPayload(): array
    {
        $pairs = [];
        foreach ($this->qPairs as $pair) {
            $term = isset($pair['term']) && is_string($pair['term']) ? trim($pair['term']) : '';
            $def  = isset($pair['def']) && is_string($pair['def']) ? trim($pair['def']) : '';
            if ($term !== '' && $def !== '') {
                $pairs[] = ['term' => $term, 'def' => $def];
            }
        }

        if (count($pairs) < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qPairs' => 'Un appariement exige au moins 2 paires terme/définition complètes.',
            ]);
        }

        return ['pairs' => array_values($pairs)];
    }

    /**
     * @return array<string, mixed>
     *
     * payload['items'] = TABLEAU ORDONNÉ des éléments dans le BON ordre (>= 2 non vides).
     * Même invariant que QuestionBankService::mapToRoundItem (cas ordering).
     */
    private function buildOrderingPayload(): array
    {
        $items = array_values(array_filter(
            array_map(fn ($v) => is_string($v) ? trim($v) : '', $this->qOrderingItems),
            fn (string $v): bool => $v !== ''
        ));

        if (count($items) < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qOrderingItems' => 'Un ordonnancement exige au moins 2 éléments non vides.',
            ]);
        }

        return ['items' => $items];
    }

    /**
     * @return array<string, mixed>
     *
     * CLOZE / TEXTE À TROUS. Forme canonique du payload :
     *   payload['text']   = texte avec marqueurs [[1]], [[2]], … ;
     *   payload['blanks'] = TABLEAU ordonné des trous : { kind:'short', accepted:[…],
     *                       display? } OU { kind:'mcq', choices:[…], correct:int }.
     * Mêmes invariants que QuestionBankService::normalizeClozeBlank → une question
     * créée est TOUJOURS jouable (au moins un marqueur résolu vers un trou valide).
     */
    private function buildClozePayload(): array
    {
        $text = trim((string) $this->qClozeText);

        if ($text === '' || preg_match('/\[\[\d+\]\]/', $text) !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qClozeText' => 'Le texte doit contenir au moins un trou au format [[1]], [[2]]…',
            ]);
        }

        // C4 : borne de longueur (défense en profondeur ; la règle validate() la couvre déjà).
        if (mb_strlen($text) > self::MAX_CLOZE_TEXT) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qClozeText' => 'Le texte à trous est trop long (maximum '.self::MAX_CLOZE_TEXT.' caractères).',
            ]);
        }

        // C1 (anti-biais de notation) : un même marqueur [[n]] ne peut pas apparaître en
        // double — deux champs partageraient le même `name` et un seul serait évalué.
        preg_match_all('/\[\[(\d+)\]\]/', $text, $allMarkers);
        $markerNums = array_map('intval', $allMarkers[1] ?? []);
        if (count($markerNums) !== count(array_unique($markerNums))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qClozeText' => 'Chaque trou [[n]] doit être unique : un même numéro ne peut pas apparaître deux fois.',
            ]);
        }

        $blanks = [];
        foreach (array_values($this->qClozeBlanks) as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $kind = (($raw['kind'] ?? 'short') === 'mcq') ? 'mcq' : 'short';

            if ($kind === 'mcq') {
                $choices = array_values(array_filter(
                    array_map(
                        fn ($c): string => trim((string) $c),
                        preg_split('/\r\n|\r|\n/', (string) ($raw['choices'] ?? '')) ?: []
                    ),
                    fn (string $c): bool => $c !== ''
                ));
                if (count($choices) < 2) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'qClozeBlanks' => 'Un trou à choix exige au moins 2 options (une par ligne).',
                    ]);
                }
                // C4 : borne de longueur par option (anti-payload abusif).
                foreach ($choices as $choice) {
                    if (mb_strlen($choice) > self::MAX_CLOZE_ENTRY) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'qClozeBlanks' => 'Une option de trou est trop longue (maximum '.self::MAX_CLOZE_ENTRY.' caractères).',
                        ]);
                    }
                }
                $correct = (int) ($raw['correct'] ?? 0);
                if ($correct < 0 || $correct >= count($choices)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'qClozeBlanks' => 'Désignez une bonne option valide pour chaque trou à choix.',
                    ]);
                }
                $blanks[] = ['kind' => 'mcq', 'choices' => $choices, 'correct' => $correct];
            } else {
                $accepted = array_values(array_filter(
                    array_map(fn ($a): string => trim((string) $a), explode(',', (string) ($raw['accepted'] ?? ''))),
                    fn (string $a): bool => $a !== ''
                ));
                if ($accepted === []) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'qClozeBlanks' => 'Un trou à réponse courte exige au moins une réponse acceptée.',
                    ]);
                }
                // C4 : borne de longueur par réponse acceptée (anti-payload abusif).
                foreach ($accepted as $acc) {
                    if (mb_strlen($acc) > self::MAX_CLOZE_ENTRY) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'qClozeBlanks' => 'Une réponse acceptée est trop longue (maximum '.self::MAX_CLOZE_ENTRY.' caractères).',
                        ]);
                    }
                }
                $blank   = ['kind' => 'short', 'accepted' => $accepted];
                $display = trim((string) ($raw['display'] ?? ''));
                if ($display !== '') {
                    $blank['display'] = $display;
                }
                $blanks[] = $blank;
            }
        }

        if ($blanks === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qClozeBlanks' => 'Ajoutez au moins un trou.',
            ]);
        }

        // Garde-fou « toujours jouable » : au moins un marqueur [[n]] doit pointer vers
        // un trou défini (1 <= n <= nb de trous). Sinon le round serait vide au tirage.
        preg_match_all('/\[\[(\d+)\]\]/', $text, $mm);
        $hasResolved = false;
        foreach (($mm[1] ?? []) as $num) {
            $n = (int) $num;
            if ($n >= 1 && $n <= count($blanks)) {
                $hasResolved = true;
                break;
            }
        }
        if (! $hasResolved) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qClozeText' => 'Au moins un trou [[n]] doit correspondre à un trou défini ci-dessous.',
            ]);
        }

        return ['text' => $text, 'blanks' => $blanks];
    }

    /**
     * @return array<string, mixed>
     *
     * GLISSER-DÉPOSER SUR TEXTE (ddwtos). Forme canonique du payload :
     *   payload['text']    = texte avec marqueurs [[1]], [[2]], … ;
     *   payload['words']   = POOL de mots (>= 1, inclut les distracteurs) ;
     *   payload['answers'] = map index_de_trou (0-based) => INDEX du mot correct dans words.
     * Mêmes invariants que QuestionBankService::buildDdwtosRound → une question créée est
     * TOUJOURS jouable : >= 1 trou, pool >= nombre de trous, chaque trou pointe un mot valide.
     */
    private function buildDdwtosPayload(): array
    {
        $text = trim((string) $this->qDdwtosText);

        if ($text === '' || preg_match('/\[\[\d+\]\]/', $text) !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qDdwtosText' => 'Le texte doit contenir au moins un trou au format [[1]], [[2]]…',
            ]);
        }

        if (mb_strlen($text) > self::MAX_DDWTOS_TEXT) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qDdwtosText' => 'Le texte est trop long (maximum '.self::MAX_DDWTOS_TEXT.' caractères).',
            ]);
        }

        // Marqueurs : uniques (un même [[n]] en double = deux champs au même name = biais).
        preg_match_all('/\[\[(\d+)\]\]/', $text, $allMarkers);
        $markerNums = array_map('intval', $allMarkers[1] ?? []);
        if ($markerNums === [] || count($markerNums) !== count(array_unique($markerNums))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qDdwtosText' => 'Chaque trou [[n]] doit être unique : un même numéro ne peut pas apparaître deux fois.',
            ]);
        }
        $blankNums = array_values(array_unique($markerNums)); // 1-based
        sort($blankNums);

        // Pool de mots : trim + filtre des vides, en gardant l'index d'origine (les
        // désignations $qDdwtosAnswers y réfèrent) → map originalIndex => indexFiltré.
        $words           = [];
        $originalToFinal = [];
        foreach (array_values($this->qDdwtosWords) as $oi => $w) {
            $value = is_string($w) ? trim($w) : '';
            if ($value === '') {
                continue;
            }
            if (mb_strlen($value) > self::MAX_CLOZE_ENTRY) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qDdwtosWords' => 'Un mot du pool est trop long (maximum '.self::MAX_CLOZE_ENTRY.' caractères).',
                ]);
            }
            $originalToFinal[(int) $oi] = count($words);
            $words[]                    = $value;
        }

        if ($words === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qDdwtosWords' => 'Ajoutez au moins un mot au pool.',
            ]);
        }

        // C2 (F4) : les libellés du pool doivent être DISTINCTS. Deux mots identiques
        // créent une ambiguïté de notation (deux index pointent le même libellé : un
        // distracteur visuellement confondu avec la bonne réponse). On rejette donc tout
        // doublon (comparaison sur la valeur déjà trimée du pool).
        if (count($words) !== count(array_unique($words))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qDdwtosWords' => 'Les mots du pool doivent être distincts.',
            ]);
        }

        if (count($words) < count($blankNums)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qDdwtosWords' => 'Le pool doit contenir au moins autant de mots que de trous ('.count($blankNums).').',
            ]);
        }

        // Désignation du mot correct par trou : $qDdwtosAnswers[blankIdx] référence l'index
        // D'ORIGINE du pool → remappé vers l'index filtré (comme le QCM remappe qCorrect).
        $answers = [];
        foreach ($blankNums as $n) {
            $blankIdx       = $n - 1;
            $originalWordIdx = isset($this->qDdwtosAnswers[$blankIdx]) && is_numeric($this->qDdwtosAnswers[$blankIdx])
                ? (int) $this->qDdwtosAnswers[$blankIdx]
                : -1;
            $finalWordIdx = $originalToFinal[$originalWordIdx] ?? null;
            if ($finalWordIdx === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qDdwtosAnswers' => 'Désignez un mot valide du pool pour chaque trou.',
                ]);
            }
            $answers[$blankIdx] = (int) $finalWordIdx;
        }

        return ['text' => $text, 'words' => $words, 'answers' => $answers];
    }

    /**
     * Pré-remplit les champs du sous-formulaire à partir d'un payload existant.
     *
     * @param  array<string, mixed>  $payload
     */
    private function hydratePayloadForm(string $type, array $payload): void
    {
        // Réinitialise tous les sous-formulaires aux défauts d'abord.
        $this->resetPayloadFields();

        switch ($type) {
            case 'mcq':
                $choices = array_values(array_map(
                    fn ($c) => (string) $c,
                    (array) ($payload['choices'] ?? [])
                ));
                $this->qChoices = count($choices) >= 2 ? $choices : ['', ''];
                // V1-e : sous-cas multi (correct_set tableau) vs simple (correct int).
                $this->qMultiple = ! empty($payload['multiple']);
                if ($this->qMultiple) {
                    $this->qCorrectSet = array_values(array_unique(array_map(
                        'intval',
                        (array) ($payload['correct_set'] ?? [])
                    )));
                    $this->qCorrect = 0;
                } else {
                    $this->qCorrect    = (int) ($payload['correct'] ?? 0);
                    $this->qCorrectSet = [];
                }
                // V1-a : rétroaction par choix, ré-alignée sur le nombre de choix.
                $this->qChoiceFeedback = $this->feedbackForCount(
                    $payload['choice_feedback'] ?? [],
                    count($this->qChoices)
                );
                break;

            case 'truefalse':
                $this->qAnswerTrue = (bool) ($payload['answer'] ?? true);
                // V1-a : 2 entrées (0 = Vrai, 1 = Faux).
                $this->qTfFeedback = $this->feedbackForCount($payload['choice_feedback'] ?? [], 2);
                break;

            case 'short':
                $accepted = array_values(array_map(
                    fn ($a) => (string) $a,
                    (array) ($payload['accepted'] ?? [])
                ));
                $this->qAccepted = $accepted !== [] ? $accepted : [''];
                $this->qDisplay  = isset($payload['display']) ? (string) $payload['display'] : null;
                break;

            case 'matching':
                $pairs = [];
                foreach ((array) ($payload['pairs'] ?? []) as $p) {
                    if (is_array($p)) {
                        $pairs[] = [
                            'term' => (string) ($p['term'] ?? ''),
                            'def'  => (string) ($p['def'] ?? ''),
                        ];
                    }
                }
                $this->qPairs = count($pairs) >= 2
                    ? $pairs
                    : [['term' => '', 'def' => ''], ['term' => '', 'def' => '']];
                break;

            case 'ordering':
                $items = array_values(array_map(
                    fn ($v) => (string) $v,
                    (array) ($payload['items'] ?? [])
                ));
                $this->qOrderingItems = count($items) >= 2 ? $items : ['', '', ''];
                break;

            case 'cloze':
                $this->qClozeText = (string) ($payload['text'] ?? '');
                $blanks = [];
                foreach ((array) ($payload['blanks'] ?? []) as $b) {
                    if (! is_array($b)) {
                        continue;
                    }
                    if (($b['kind'] ?? 'short') === 'mcq') {
                        $blanks[] = [
                            'kind'     => 'mcq',
                            'accepted' => '',
                            'display'  => '',
                            'choices'  => implode("\n", array_map(fn ($c): string => (string) $c, (array) ($b['choices'] ?? []))),
                            'correct'  => (int) ($b['correct'] ?? 0),
                        ];
                    } else {
                        $blanks[] = [
                            'kind'     => 'short',
                            'accepted' => implode(', ', array_map(fn ($a): string => (string) $a, (array) ($b['accepted'] ?? []))),
                            'display'  => (string) ($b['display'] ?? ''),
                            'choices'  => '',
                            'correct'  => 0,
                        ];
                    }
                }
                $this->qClozeBlanks = $blanks !== []
                    ? $blanks
                    : [['kind' => 'short', 'accepted' => '', 'display' => '', 'choices' => '', 'correct' => 0]];
                break;

            case 'numerical':
                // Affichage en chaîne (point décimal canonique) ; '0' pour la tolérance.
                $this->qNumericalCorrect = isset($payload['correct']) && is_numeric($payload['correct'])
                    ? self::numberToInput((float) $payload['correct'])
                    : null;
                $this->qNumericalTolerance = isset($payload['tolerance']) && is_numeric($payload['tolerance'])
                    ? self::numberToInput((float) $payload['tolerance'])
                    : '0';
                $this->qNumericalUnit = isset($payload['unit']) ? (string) $payload['unit'] : null;
                break;

            case 'ddwtos':
                $this->qDdwtosText = (string) ($payload['text'] ?? '');
                $words             = array_values(array_map(
                    fn ($w) => (string) $w,
                    (array) ($payload['words'] ?? [])
                ));
                $this->qDdwtosWords = count($words) >= 2 ? $words : ['', '', ''];
                // Le pool stocké est déjà filtré (sans vides) → l'index d'origine y est égal
                // à l'index filtré ; on recharge la désignation par trou telle quelle.
                $answers = [];
                foreach ((array) ($payload['answers'] ?? []) as $bk => $wi) {
                    if (is_numeric($wi)) {
                        $answers[(int) $bk] = (int) $wi;
                    }
                }
                $this->qDdwtosAnswers = $answers;
                break;

            case 'essay':
                $this->qGraderInfo = isset($payload['grader_info']) ? (string) $payload['grader_info'] : null;
                break;
        }
    }

    /**
     * Formate un float pour l'affichage dans un champ de saisie : point décimal,
     * sans zéros de fin parasites (42.0 → « 42 », 3.140 → « 3.14 »).
     */
    private static function numberToInput(float $value): string
    {
        $s = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }

    private function resetPayloadFields(): void
    {
        $this->qChoices        = ['', ''];
        $this->qCorrect        = 0;
        $this->qMultiple       = false;
        $this->qCorrectSet     = [];
        $this->qChoiceFeedback = ['', ''];
        $this->qAnswerTrue     = true;
        $this->qTfFeedback     = ['', ''];
        $this->qAccepted       = [''];
        $this->qDisplay        = null;
        $this->qPairs          = [['term' => '', 'def' => ''], ['term' => '', 'def' => '']];
        $this->qOrderingItems  = ['', '', ''];
        $this->qClozeText      = '';
        $this->qClozeBlanks    = [['kind' => 'short', 'accepted' => '', 'display' => '', 'choices' => '', 'correct' => 0]];
        $this->qNumericalCorrect   = null;
        $this->qNumericalTolerance = '0';
        $this->qNumericalUnit      = null;
        $this->qDdwtosText         = '';
        $this->qDdwtosWords        = ['', '', ''];
        $this->qDdwtosAnswers      = [];
        $this->qGraderInfo         = null;
    }

    /**
     * V1-a : produit un tableau de feedback de longueur EXACTE $count (indexé 0..N-1)
     * à partir d'un payload['choice_feedback'] potentiellement partiel/désordonné, pour
     * pré-remplir le formulaire d'édition (toujours aligné sur le nombre de choix).
     *
     * @param  mixed  $raw
     * @return array<int, string>
     */
    private function feedbackForCount(mixed $raw, int $count): array
    {
        $out = array_fill(0, max(0, $count), '');
        if (is_array($raw)) {
            foreach ($raw as $index => $text) {
                $i = (int) $index;
                if ($i >= 0 && $i < $count && is_string($text)) {
                    $out[$i] = $text;
                }
            }
        }

        return $out;
    }

    /** Réinitialise tout le formulaire de question (annule l'édition en cours). */
    public function resetQuestionForm(): void
    {
        $this->editingQuestionId = null;
        $this->qType             = 'mcq';
        $this->qPrompt           = '';
        $this->qExplanation      = null;
        $this->qDifficulty       = 'moyen';
        $this->qPoints           = 1;
        $this->qIsActive         = true;
        $this->qTags             = '';
        $this->resetPayloadFields();
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps
    // ─────────────────────────────────────────────────────────────────────────────

    public function confirmCategoryDeletion(int $categoryId): void
    {
        $this->confirmingCategoryDeletion = $categoryId;
    }

    public function cancelCategoryDeletion(): void
    {
        $this->confirmingCategoryDeletion = null;
    }

    public function confirmQuestionDeletion(int $questionId): void
    {
        $this->confirmingQuestionDeletion = $questionId;
    }

    public function cancelQuestionDeletion(): void
    {
        $this->confirmingQuestionDeletion = null;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Utilitaires
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Normalise une propriété ?int issue du DOM : '' / 0 / null → null (anti-TypeError
     * sur strict_types, et cohérence « pas de parent »).
     */
    private function normalizeNullableInt(string $prop): void
    {
        $value = $this->{$prop};
        $this->{$prop} = ($value === '' || $value === null || (int) $value === 0)
            ? null
            : (int) $value;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Lecture (affichage) - listes fraîches, owner-scoped
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Mes catégories (admin : toutes), triées par parent puis position, pour l'arbre
     * d'affichage ET de liste blanche (parent / catégorie sélectionnable).
     *
     * @return \Illuminate\Support\Collection<int, QuestionCategory>
     */
    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        $query = QuestionCategory::query()
            ->withCount(['questions', 'children'])
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->get();
    }

    /**
     * Catégories racines (parent_id null) pour l'affichage hiérarchique.
     *
     * @return \Illuminate\Support\Collection<int, QuestionCategory>
     */
    #[Computed]
    public function rootCategories(): \Illuminate\Support\Collection
    {
        return $this->categories->whereNull('parent_id')->values();
    }

    /**
     * Sous-catégories indexées par parent_id (affichage en arbre 1-2 niveaux).
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, QuestionCategory>>
     */
    #[Computed]
    public function childrenByParent(): \Illuminate\Support\Collection
    {
        return $this->categories->whereNotNull('parent_id')->groupBy('parent_id');
    }

    /**
     * Questions de la catégorie sélectionnée (owner-scoped via la résolution).
     *
     * @return \Illuminate\Support\Collection<int, Question>
     */
    #[Computed]
    public function questions(): \Illuminate\Support\Collection
    {
        if ($this->selectedCategoryId === null) {
            return collect();
        }

        $query = Question::where('category_id', $this->selectedCategoryId)
            ->with('tags')
            ->orderByDesc('id');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        // F17 (TAGS) : filtre optionnel par étiquette. Défense en profondeur : en plus du
        // scoping sur l'id du tag, on borne à l'owner_id courant - un id forgé d'un autre
        // owner ne peut jamais remonter des questions (double garde anti-IDOR).
        if ($this->filterTagId !== null) {
            $tagId = (int) $this->filterTagId;
            $query->whereHas('tags', fn ($q) => $q->where('academy_question_tags.id', $tagId)
                ->where('academy_question_tags.owner_id', Auth::id()));
        }

        return $query->get();
    }

    /**
     * F17 (TAGS) - mes étiquettes (admin : toutes), pour le menu de filtre + la liste
     * d'aide à la saisie. Owner-scopé (sauf admin academy.manage).
     *
     * @return \Illuminate\Support\Collection<int, QuestionTag>
     */
    #[Computed]
    public function tags(): \Illuminate\Support\Collection
    {
        $query = QuestionTag::query()->orderBy('name');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->get();
    }

    /**
     * F17 (STATISTIQUES) - usages + indice de facilité pour les questions AFFICHÉES,
     * agrégés en UN lot (aucun N+1). Map id_question => stats.
     *
     * @return array<int, array{uses: int, correct: int, facility: int|null, has_data: bool}>
     */
    #[Computed]
    public function questionStats(): array
    {
        $ids = $this->questions->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return QuestionStatsService::forQuestions($ids);
    }

    /**
     * F17 (VERSIONS) - historique de la question dont le panneau est ouvert (lecture
     * seule), de la plus récente à la plus ancienne. Owner-scopé via resolveQuestion.
     *
     * @return \Illuminate\Support\Collection<int, QuestionVersion>
     */
    #[Computed]
    public function questionVersions(): \Illuminate\Support\Collection
    {
        if ($this->historyQuestionId === null) {
            return collect();
        }

        try {
            $question = $this->resolveQuestion((int) $this->historyQuestionId);
        } catch (\Throwable) {
            return collect();
        }

        return $question->versions()->get();
    }

    /** La catégorie sélectionnée (ou null). */
    #[Computed]
    public function selectedCategory(): ?QuestionCategory
    {
        if ($this->selectedCategoryId === null) {
            return null;
        }

        try {
            return $this->resolveCategory((int) $this->selectedCategoryId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * GLISSER-TEXTE — numéros de trous (1-based, uniques, triés) détectés dans le texte
     * en cours d'édition. Utilisé par la vue pour rendre un <select> par trou. Lecture
     * seule (n'altère aucun état) ; évite toute logique lourde dans le Blade.
     *
     * @return array<int, int>
     */
    public function ddwtosBlankNumbers(): array
    {
        preg_match_all('/\[\[(\d+)\]\]/', (string) $this->qDdwtosText, $m);
        $nums = array_values(array_unique(array_map('intval', $m[1] ?? [])));
        sort($nums);

        return $nums;
    }

    /**
     * GLISSER-TEXTE — pool de mots NON VIDES indexé par leur index D'ORIGINE (= valeur
     * des <option> du select de désignation, alignée sur $qDdwtosAnswers). Lecture seule.
     *
     * @return array<int, string>
     */
    public function ddwtosPool(): array
    {
        $pool = [];
        foreach ($this->qDdwtosWords as $wi => $w) {
            $wv = is_string($w) ? trim($w) : '';
            if ($wv !== '') {
                $pool[(int) $wi] = $wv;
            }
        }

        return $pool;
    }

    /** Libellé FR d'un type de question. */
    public function typeLabel(string $type): string
    {
        return match ($type) {
            'mcq'       => 'Choix multiple',
            'truefalse' => 'Vrai ou faux',
            'short'     => 'Réponse courte',
            'matching'  => 'Appariement',
            'ordering'  => 'Ordonnancement',
            'cloze'     => 'Texte à trous',
            'numerical' => 'Réponse numérique',
            'ddwtos'    => 'Glisser-déposer sur texte',
            'essay'     => 'Réponse rédigée (essai)',
            default     => $type,
        };
    }

    public function render()
    {
        return view('academy::livewire.question-bank-manager');
    }
}
