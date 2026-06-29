<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component QuestionBankManager — BUILDERS et HYDRATATION
 * de payload par type de question : build (formulaire → payload DB), validation
 * des invariants (même logique que QuestionBankService::mapToRoundItem), hydratation
 * (payload DB → formulaire), réinitialisation, et helpers de rétroaction par choix.
 *
 * Les constantes de borne (MAX_CLOZE_TEXT, MAX_CLOZE_ENTRY, MAX_DDWTOS_TEXT) et les
 * niveaux de difficulté (DIFFICULTIES) restent déclarés dans la classe principale ;
 * ce trait y accède via self:: (résolu sur la classe à l'utilisation du trait). Aucun
 * comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Modules\Academy\Services\QuizService;

trait HandlesQbPayloadBuilders
{
    // ─────────────────────────────────────────────────────────────────────────
    // BUILD + VALIDATION de payload (invariants alignés sur mapToRoundItem)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construit et VALIDE le payload selon le type. Lève une ValidationException
     * si les invariants ne sont pas respectés, pour qu'une question stockée soit
     * TOUJOURS exploitable par QuestionBankService.
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
     * ESSAI. Forme canonique du payload : payload['grader_info'] = consignes de
     * correction (optionnelles, bornées). Aucune bonne réponse ; toujours jouable.
     *
     * @return array<string, mixed>
     */
    private function buildEssayPayload(): array
    {
        $info = $this->qGraderInfo !== null ? trim($this->qGraderInfo) : '';

        return $info !== '' ? ['grader_info' => mb_substr($info, 0, 2000)] : [];
    }

    /**
     * NUMÉRIQUE. Forme canonique du payload :
     *   payload['correct']   = float (réponse attendue, REQUISE) ;
     *   payload['tolerance'] = float >= 0 (défaut 0) ;
     *   payload['unit']      = string (si non vide).
     *
     * @return array<string, mixed>
     */
    private function buildNumericalPayload(): array
    {
        $correct = QuizService::parseNumber($this->qNumericalCorrect);
        if ($correct === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qNumericalCorrect' => 'Indiquez une réponse numérique valide (ex. 42 ou 3,14).',
            ]);
        }

        // C1 : défense en profondeur contre INF/-INF/NAN.
        if (! is_finite($correct)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qNumericalCorrect' => 'Valeur hors plage numérique.',
            ]);
        }

        $tolerance = 0.0;
        if ($this->qNumericalTolerance !== null && trim((string) $this->qNumericalTolerance) !== '') {
            $parsed = QuizService::parseNumber($this->qNumericalTolerance);
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

        $unit = $this->qNumericalUnit !== null ? trim($this->qNumericalUnit) : '';
        if ($unit !== '') {
            $payload['unit'] = mb_substr($unit, 0, 40);
        }

        return $payload;
    }

    /**
     * QCM (simple ou multi-réponses).
     *
     * @return array<string, mixed>
     */
    private function buildMcqPayload(): array
    {
        // Filtre les choix vides en conservant l'index d'origine pour la rétroaction.
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

        // V1-e - désignation des bonnes réponses.
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
            $finalCorrect = array_search((int) $this->qCorrect, $keptOriginalIndexes, true);
            if ($finalCorrect === false) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'qCorrect' => 'Désignez une bonne réponse valide parmi les choix.',
                ]);
            }
            $payload['correct'] = (int) $finalCorrect;
        }

        // V1-a : rétroaction par choix (optionnelle).
        $feedback = $this->collectChoiceFeedback($this->qChoiceFeedback, $keptOriginalIndexes);
        if ($feedback !== []) {
            $payload['choice_feedback'] = $feedback;
        }

        return $payload;
    }

    /**
     * VRAI/FAUX.
     *
     * @return array<string, mixed>
     */
    private function buildTrueFalsePayload(): array
    {
        $payload = ['answer' => (bool) $this->qAnswerTrue];

        // V1-a : rétroaction par choix (0 = Vrai, 1 = Faux).
        $feedback = $this->collectChoiceFeedback($this->qTfFeedback, [0, 1]);
        if ($feedback !== []) {
            $payload['choice_feedback'] = $feedback;
        }

        return $payload;
    }

    /**
     * RÉPONSE COURTE.
     *
     * @return array<string, mixed>
     */
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

    /**
     * APPARIEMENT.
     *
     * @return array<string, mixed>
     */
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
     * ORDONNANCEMENT. payload['items'] = tableau ordonné des éléments (>= 2 non vides).
     *
     * @return array<string, mixed>
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
     * CLOZE / TEXTE À TROUS. Forme canonique :
     *   payload['text']   = texte avec marqueurs [[1]], [[2]], … ;
     *   payload['blanks'] = tableau ordonné des trous.
     *
     * @return array<string, mixed>
     */
    private function buildClozePayload(): array
    {
        $text = trim((string) $this->qClozeText);

        if ($text === '' || preg_match('/\[\[\d+\]\]/', $text) !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qClozeText' => 'Le texte doit contenir au moins un trou au format [[1]], [[2]]…',
            ]);
        }

        if (mb_strlen($text) > self::MAX_CLOZE_TEXT) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qClozeText' => 'Le texte à trous est trop long (maximum '.self::MAX_CLOZE_TEXT.' caractères).',
            ]);
        }

        // C1 : un même marqueur [[n]] en double = biais de notation.
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
                // C4 : borne de longueur par option.
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
                // C4 : borne de longueur par réponse acceptée.
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
        // un trou défini (1 <= n <= nb de trous).
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
     * GLISSER-DÉPOSER SUR TEXTE (ddwtos). Forme canonique :
     *   payload['text']    = texte avec marqueurs [[1]], [[2]], … ;
     *   payload['words']   = pool de mots (>= 1, inclut les distracteurs) ;
     *   payload['answers'] = map index_de_trou (0-based) => index du mot correct.
     *
     * @return array<string, mixed>
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

        // Marqueurs uniques.
        preg_match_all('/\[\[(\d+)\]\]/', $text, $allMarkers);
        $markerNums = array_map('intval', $allMarkers[1] ?? []);
        if ($markerNums === [] || count($markerNums) !== count(array_unique($markerNums))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qDdwtosText' => 'Chaque trou [[n]] doit être unique : un même numéro ne peut pas apparaître deux fois.',
            ]);
        }
        $blankNums = array_values(array_unique($markerNums));
        sort($blankNums);

        // Pool de mots : trim + filtre des vides, en gardant l'index d'origine.
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

        // C2 : les libellés du pool doivent être distincts.
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

        // Désignation du mot correct par trou.
        $answers = [];
        foreach ($blankNums as $n) {
            $blankIdx        = $n - 1;
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

    // ─────────────────────────────────────────────────────────────────────────
    // HYDRATATION (payload DB → formulaire)
    // ─────────────────────────────────────────────────────────────────────────

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
                // V1-e : sous-cas multi vs simple.
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
                // V1-a : rétroaction par choix.
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

    // ─────────────────────────────────────────────────────────────────────────
    // Réinitialisation des champs de payload
    // ─────────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de rétroaction par choix (V1-a)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * V1-a : construit le tableau choice_feedback (index final => texte) à partir
     * des textes saisis et de la liste des index d'origine RETENUS (post-filtrage).
     *
     * @param  array<int, string>  $source
     * @param  array<int, int>     $keptOriginalIndexes
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

    /**
     * V1-a : produit un tableau de feedback de longueur EXACTE $count (indexé 0..N-1)
     * à partir d'un payload['choice_feedback'] potentiellement partiel/désordonné.
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

    // ─────────────────────────────────────────────────────────────────────────
    // Utilitaire numérique
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Formate un float pour l'affichage dans un champ de saisie : point décimal,
     * sans zéros de fin parasites (42.0 → « 42 », 3.140 → « 3.14 »).
     */
    private static function numberToInput(float $value): string
    {
        $s = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }
}
