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
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

class QuestionBankManager extends Component
{
    /** Niveaux de difficulté autorisés (liste blanche). */
    private const DIFFICULTIES = ['facile', 'moyen', 'difficile'];

    // ── Création de catégorie ────────────────────────────────────────────────────
    public string $newCategoryName = '';
    public ?int $newCategoryParentId = null;

    // ── Renommage de catégorie (inline) ──────────────────────────────────────────
    public ?int $renamingCategory = null;
    public string $renameCategoryName = '';

    /** Catégorie actuellement sélectionnée (édition des questions). */
    public ?int $selectedCategoryId = null;

    // ── Formulaire de question (création / édition) ──────────────────────────────
    /** Id de la question en cours d'édition (null = création d'une nouvelle). */
    public ?int $editingQuestionId = null;

    public string $qType = 'mcq';
    public string $qPrompt = '';
    public ?string $qExplanation = null;
    public string $qDifficulty = 'moyen';
    public bool $qIsActive = true;

    // Sous-formulaires de payload par type (toujours initialisés, jamais null).
    /** @var array<int, string> */
    public array $qChoices = ['', ''];
    public int $qCorrect = 0;

    public bool $qAnswerTrue = true;

    /** @var array<int, string> */
    public array $qAccepted = [''];
    public ?string $qDisplay = null;

    /** @var array<int, array{term: string, def: string}> */
    public array $qPairs = [['term' => '', 'def' => ''], ['term' => '', 'def' => '']];

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
        $this->resetQuestionForm();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // QUESTIONS
    // ─────────────────────────────────────────────────────────────────────────────

    /** Ajoute un choix (mcq) - borné côté serveur au build du payload. */
    public function addChoice(): void
    {
        $this->qChoices[] = '';
    }

    public function removeChoice(int $index): void
    {
        if (count($this->qChoices) <= 2) {
            return; // minimum 2 choix.
        }

        unset($this->qChoices[$index]);
        $this->qChoices = array_values($this->qChoices);

        if ($this->qCorrect >= count($this->qChoices)) {
            $this->qCorrect = 0;
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
            'is_active'   => $this->qIsActive,
            'payload'     => $payload,
        ];

        if ($this->editingQuestionId !== null) {
            $question = $this->resolveQuestion((int) $this->editingQuestionId);
            // La catégorie cible est re-validée comme mienne ci-dessus → réaffectation sûre.
            $question->update($attributes);
            $message = 'Question mise à jour.';
        } else {
            $attributes['owner_id'] = Auth::id(); // FORCÉ = auth.
            Question::create($attributes);
            $message = 'Question ajoutée.';
        }

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
        $this->qIsActive         = (bool) $question->is_active;

        $payload = is_array($question->payload) ? $question->payload : [];
        $this->hydratePayloadForm($this->qType, $payload);

        $this->resetErrorBag();
    }

    public function deleteQuestion(int $questionId): void
    {
        $question = $this->resolveQuestion($questionId);
        $question->delete();

        if ($this->editingQuestionId === $questionId) {
            $this->resetQuestionForm();
        }
        $this->confirmingQuestionDeletion = null;
        session()->flash('academy_bank_status', 'Question supprimée.');
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
            'truefalse' => ['answer' => (bool) $this->qAnswerTrue],
            'short'     => $this->buildShortPayload(),
            'matching'  => $this->buildMatchingPayload(),
            default     => [],
        };
    }

    /** @return array<string, mixed> */
    private function buildMcqPayload(): array
    {
        $choices = array_values(array_filter(
            array_map(fn ($c) => is_string($c) ? trim($c) : '', $this->qChoices),
            fn (string $c): bool => $c !== ''
        ));

        if (count($choices) < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qChoices' => 'Un choix multiple exige au moins 2 réponses non vides.',
            ]);
        }

        $correct = (int) $this->qCorrect;
        if ($correct < 0 || $correct >= count($choices)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qCorrect' => 'Désignez une bonne réponse valide parmi les choix.',
            ]);
        }

        return ['choices' => $choices, 'correct' => $correct];
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
                $this->qCorrect = (int) ($payload['correct'] ?? 0);
                break;

            case 'truefalse':
                $this->qAnswerTrue = (bool) ($payload['answer'] ?? true);
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
        }
    }

    private function resetPayloadFields(): void
    {
        $this->qChoices    = ['', ''];
        $this->qCorrect    = 0;
        $this->qAnswerTrue = true;
        $this->qAccepted   = [''];
        $this->qDisplay    = null;
        $this->qPairs      = [['term' => '', 'def' => ''], ['term' => '', 'def' => '']];
    }

    /** Réinitialise tout le formulaire de question (annule l'édition en cours). */
    public function resetQuestionForm(): void
    {
        $this->editingQuestionId = null;
        $this->qType             = 'mcq';
        $this->qPrompt           = '';
        $this->qExplanation      = null;
        $this->qDifficulty       = 'moyen';
        $this->qIsActive         = true;
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
            ->orderByDesc('id');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->get();
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

    /** Libellé FR d'un type de question. */
    public function typeLabel(string $type): string
    {
        return match ($type) {
            'mcq'       => 'Choix multiple',
            'truefalse' => 'Vrai ou faux',
            'short'     => 'Réponse courte',
            'matching'  => 'Appariement',
            default     => $type,
        };
    }

    public function render()
    {
        return view('academy::livewire.question-bank-manager');
    }
}
