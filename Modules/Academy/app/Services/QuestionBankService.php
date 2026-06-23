<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Socle de la banque de questions réutilisable (QB1). Tire N questions ACTIVES
 * d'une catégorie (+ sous-catégories) et les TRADUIT au format exact attendu par
 * QuizService::score() — le scoring n'est PAS dupliqué, il reste dans QuizService.
 *
 * Décision « pool » (MVP) : AUCUNE table de pool ici. Le lien item-quiz↔catégorie
 * sera porté par le payload de l'item quiz en QB2 (ex. payload['question_bank'] =
 * ['category_id' => …, 'draw_count' => …]), exactement comme qt_bank_key l'est
 * déjà aujourd'hui. Raison : zéro nouvelle table/jointure, cohérent avec le
 * stockage existant des paramètres de quiz (passing_score, attempts_allowed,
 * qt_bank_key) dans lesson_items.payload, et réversible sans migration. Une table
 * academy_quiz_pools serait justifiée seulement si un même item devait tirer de
 * PLUSIEURS catégories avec des quotas distincts — hors périmètre MVP.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;

final class QuestionBankService
{
    /**
     * Tire aléatoirement N questions ACTIVES d'une catégorie, au format QuizService::score().
     *
     * @param  bool     $includeSubcategories  Inclure les sous-catégories (tirage récursif).
     * @param  int|null $seed                   Graine optionnelle → tirage DÉTERMINISTE (tests).
     * @return array<int, array<string, mixed>>  Round prêt pour QuizService::score().
     */
    public static function drawFromCategory(
        QuestionCategory $cat,
        int $n,
        bool $includeSubcategories = true,
        ?int $seed = null
    ): array {
        if ($n <= 0) {
            return [];
        }

        $categoryIds = $includeSubcategories
            ? $cat->descendantIds()
            : [(int) $cat->getKey()];

        // M2 — on ne charge QUE les colonnes nécessaires à mapToRoundItem() (mémoire
        // réduite sur de grosses banques) SANS toucher au chemin de tirage : le shuffle
        // PHP seedé reste IDENTIQUE (donc DÉTERMINISTE quand $seed est fourni — les
        // tests F1/QB en dépendent). On NE remplace PAS par inRandomOrder() (qui
        // casserait la reproductibilité par seed).
        $questions = Question::query()
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->whereIn('type', Question::TYPES)
            ->select(['id', 'category_id', 'owner_id', 'type', 'prompt', 'payload', 'explanation', 'difficulty', 'points', 'is_active'])
            ->get()
            ->all();

        if ($questions === []) {
            return [];
        }

        // Tirage : déterministe si un seed est fourni (tests), aléatoire sinon.
        if ($seed !== null) {
            mt_srand($seed);
            usort($questions, fn (): int => mt_rand(-1, 1));
            mt_srand();
        } else {
            shuffle($questions);
        }

        // Borné : si moins de N disponibles, on retourne ce qui existe (pas d'erreur).
        $picked = array_slice($questions, 0, $n);

        $round = [];
        foreach ($picked as $question) {
            $mapped = self::mapToRoundItem($question);
            if ($mapped !== null) {
                $round[] = $mapped;
            }
        }

        return $round;
    }

    /**
     * Traduit une Question (type + payload) vers la structure d'un item de round
     * telle que QuizService::score() l'attend. Retourne null si le payload est
     * inexploitable (défensif : aucune exception, jamais d'item invalide noté).
     *
     * @return array<string, mixed>|null
     */
    private static function mapToRoundItem(Question $question): ?array
    {
        $payload     = is_array($question->payload) ? $question->payload : [];
        $prompt      = (string) $question->prompt;
        $explanation = (string) ($question->explanation ?? '');
        $difficulty  = (string) ($question->difficulty ?? 'moyen');

        // V1-c : pondération. On utilise le `points` EXPLICITE de la question (1..100)
        // s'il est défini et valide ; sinon repli sur le calcul historique par
        // difficulté (rétrocompat des banques antérieures à la colonne points).
        $explicitPoints = is_numeric($question->points ?? null) ? (int) $question->points : 0;
        $points         = $explicitPoints >= 1
            ? min(100, $explicitPoints)
            : self::pointsFromDifficulty($difficulty);

        // V1-a (rétroactions multi-couches) : on RECOPIE dans l'item du round :
        //  - general_feedback = explanation de la question (« feedback général » Moodle,
        //    affiché quel que soit juste/faux) - on RÉUTILISE le champ existant, sans le
        //    dupliquer côté banque ;
        //  - choice_feedback (mcq/vraifaux) = textes de rétroaction par choix, normalisés.
        // Ces clés sont ainsi SNAPSHOTÉES dans QuizAttempt → disponibles à la révision.
        // F17 (STATISTIQUES) : on PROPAGE l'identifiant STABLE de la question de banque
        // dans l'item du round. Comme le round est snapshote tel quel dans QuizAttempt
        // (questions_snapshot), cet identifiant relie chaque occurrence en tentative a sa
        // question d'origine -> QuestionStatsService compte usages + indice de facilite.
        // Ajout PUREMENT additif : score()/shuffleRound()/la revision lisent des cles
        // precises et ignorent celle-ci (retrocompat stricte). Une question hors banque
        // (round QT historique) n'a simplement pas cette cle.
        $base = [
            'theme'            => 'banque',
            'difficulty'       => $difficulty,
            'question'         => $prompt,
            'explanation'      => $explanation,
            'general_feedback' => $explanation,
            'fiche'            => null,
            'bank_question_id' => (int) $question->getKey(),
        ];

        switch ($question->type) {
            case 'mcq':
                $choices = array_values((array) ($payload['choices'] ?? []));
                if (count($choices) < 2) {
                    return null;
                }

                // V1-e - QCM À RÉPONSES MULTIPLES. Représentation banque :
                //   simple : payload['correct']      = int (1 bonne réponse) ;
                //   multi  : payload['multiple']     = true
                //            payload['correct_set']  = TABLEAU d'indices (>= 1 bonne).
                // L'item de round reflète ce sous-cas : `multiple` = true + `correct`
                // = TABLEAU d'indices (le scoring lit le drapeau pour le crédit partiel).
                if (! empty($payload['multiple'])) {
                    $correctSet = array_values(array_unique(array_filter(
                        array_map('intval', (array) ($payload['correct_set'] ?? [])),
                        fn (int $idx): bool => $idx >= 0 && $idx < count($choices)
                    )));
                    if ($correctSet === []) {
                        return null; // au moins une bonne réponse exigée.
                    }

                    return $base + [
                        'type'            => 'qcm',
                        'multiple'        => true,
                        'choices'         => $choices,
                        'correct'         => $correctSet,
                        'choice_feedback' => self::normalizeChoiceFeedback($payload['choice_feedback'] ?? [], count($choices)),
                        'points'          => $points,
                    ];
                }

                $correct = (int) ($payload['correct'] ?? 0);
                if ($correct < 0 || $correct >= count($choices)) {
                    return null;
                }

                return $base + [
                    'type'            => 'qcm',
                    'choices'         => $choices,
                    'correct'         => $correct,
                    'choice_feedback' => self::normalizeChoiceFeedback($payload['choice_feedback'] ?? [], count($choices)),
                    'points'          => $points,
                ];

            case 'truefalse':
                // payload['answer'] = true (Vrai) | false (Faux).
                $isTrue  = (bool) ($payload['answer'] ?? false);
                $choices = ['Vrai', 'Faux'];

                return $base + [
                    'type'            => 'vraifaux',
                    'choices'         => $choices,
                    'correct'         => $isTrue ? 0 : 1,
                    // 2 entrées : index 0 = Vrai, index 1 = Faux (alignées sur $choices).
                    'choice_feedback' => self::normalizeChoiceFeedback($payload['choice_feedback'] ?? [], 2),
                    'points'          => $points,
                ];

            case 'short':
                $accepted = array_values(array_filter(
                    (array) ($payload['accepted'] ?? []),
                    fn ($v): bool => is_string($v) && $v !== ''
                ));
                if ($accepted === []) {
                    return null;
                }

                return $base + [
                    'type'     => 'court',
                    'accepted' => $accepted,
                    'display'  => (string) ($payload['display'] ?? ''),
                    'points'   => $points,
                ];

            case 'matching':
                // payload['pairs'] = [['term' => …, 'def' => …], …] (≥ 2 paires).
                $pairs = array_values(array_filter(
                    (array) ($payload['pairs'] ?? []),
                    fn ($p): bool => is_array($p)
                        && isset($p['term'], $p['def'])
                        && (string) $p['term'] !== ''
                        && (string) $p['def'] !== ''
                ));
                if (count($pairs) < 2) {
                    return null;
                }

                $terms = array_map(fn ($p): string => (string) $p['term'], $pairs);
                $defs  = array_map(fn ($p): string => (string) $p['def'], $pairs);

                // Mélange déterministe-friendly des définitions + index attendus.
                $defsShuffled = $defs;
                if (count($defsShuffled) > 1) {
                    shuffle($defsShuffled);
                }

                $answer = [];
                foreach ($defs as $d) {
                    $idx      = array_search($d, $defsShuffled, true);
                    $answer[] = ($idx === false) ? 0 : (int) $idx;
                }

                return $base + [
                    'type'    => 'appariement',
                    'terms'   => $terms,
                    'defs'    => $defsShuffled,
                    'answer'  => $answer,
                    'points'  => $points,
                ];

            case 'ordering':
                // ORDONNANCEMENT (« mettre dans le bon ordre », type Moodle « Ordering »).
                // Représentation banque : payload['items'] = TABLEAU des éléments dans le
                // BON ordre (index 0 = position 1, index 1 = position 2, …). On présente
                // à l'étudiant les éléments dans un ordre MÉLANGÉ (comme l'appariement
                // mélange les définitions), tout en gardant la correspondance vers l'ordre
                // correct via `answer` :
                //   - `elements` = libellés dans l'ordre AFFICHÉ (mélangé) ;
                //   - `answer`   = pour chaque élément AFFICHÉ, sa position absolue
                //                  correcte (0-based) dans l'ordre attendu.
                // Le scoring compare la position absolue choisie pour chaque élément à
                // `answer` (crédit partiel). Les bonnes réponses (ordre correct) ne sont
                // jamais exposées au client : seul `elements` est rendu ; `answer` reste
                // serveur (session) et n'est lu qu'au scoring/à la révision.
                $items = array_values(array_map(
                    fn ($v): string => (string) $v,
                    array_filter(
                        (array) ($payload['items'] ?? []),
                        fn ($v): bool => is_string($v) && trim($v) !== ''
                    )
                ));
                if (count($items) < 2) {
                    return null;
                }

                $n = count($items);

                // Permutation d'affichage (indices d'origine = positions absolues correctes).
                $order = range(0, $n - 1);
                if ($n > 1) {
                    shuffle($order);
                    // Garde-fou anti-trivial : si le mélange retombe sur l'ordre correct
                    // (l'ordre serait alors visuellement révélé), on applique une rotation
                    // d'un cran (dérangement complet, aucun élément à sa place). Ne touche
                    // pas la correspondance `answer` (calculée à partir de $order ensuite).
                    if ($order === range(0, $n - 1)) {
                        $first = array_shift($order);
                        $order[] = $first;
                    }
                }

                $elements = [];
                $answer   = [];
                foreach ($order as $originalIndex) {
                    $elements[] = $items[$originalIndex];
                    $answer[]   = (int) $originalIndex; // position absolue correcte (0-based)
                }

                return $base + [
                    'type'     => 'ordonnancement',
                    'elements' => $elements,
                    'answer'   => $answer,
                    'points'   => $points,
                ];

            case 'cloze':
                // CLOZE / TEXTE À TROUS (« Embedded answers » Moodle, modèle STRUCTURÉ).
                // Représentation banque : payload['text'] = texte avec des marqueurs
                // numérotés [[1]], [[2]], … (1-based) ; payload['blanks'] = TABLEAU
                // ordonné des trous (index 0-based : [[1]] → blanks[0]). Chaque trou est
                //   - kind=short : { accepted:[…], display? }  (scoré comme `court`) ;
                //   - kind=mcq   : { choices:[…], correct:int } (scoré comme `qcm`).
                // mapToRoundItem produit la liste de SEGMENTS d'affichage (texte + trous)
                // SANS exposer les bonnes réponses : les `segments` ne portent JAMAIS
                // `accepted`/`correct` (les trous mcq exposent leurs `choices`, déjà
                // visibles dans le <select>). Les corrigés vivent UNIQUEMENT dans
                // `blanks` (indexé par index de trou stable), gardé serveur (session /
                // snapshot) et lu seulement au scoring/à la révision - comme `answer`
                // l'est pour l'ordonnancement. L'index stable d'un trou = son index dans
                // `blanks` (relie la réponse soumise answers[i][index]).
                $built = self::buildClozeRound($payload);
                if ($built === null) {
                    return null; // au moins un trou valide exigé (sinon non jouable)
                }

                return $base + [
                    'type'     => 'cloze',
                    'segments' => $built['segments'],
                    'blanks'   => $built['blanks'],
                    'points'   => $points,
                ];

            case 'ddwtos':
                // GLISSER-DÉPOSER SUR TEXTE (« Drag and drop into text » Moodle, ddwtos).
                // Représentation banque : payload['text'] = texte avec marqueurs numérotés
                // [[1]], [[2]], … (1-based) ; payload['words'] = POOL de mots PARTAGÉ (inclut
                // des distracteurs) ; payload['answers'] = pour chaque trou (clé 0-based :
                // [[1]] → answers[0]) l'INDEX du mot correct dans `words`.
                // mapToRoundItem produit la liste de SEGMENTS d'affichage (texte + trous SANS
                // corrigé) + `options` = pool de mots MÉLANGÉ (présenté à l'étudiant, chaque
                // trou est un <select> listant TOUT le pool) + `answers` = corrigé serveur
                // (pour chaque trou, l'INDEX du mot correct DANS LE POOL MÉLANGÉ). Comme pour
                // l'ordonnancement/cloze, `answers` reste serveur (session/snapshot) et n'est
                // lu qu'au scoring/à la révision ; seuls `segments` + `options` sont rendus.
                // L'index de trou stable = son index dans `answers` (relie answers[i][k]).
                $built = self::buildDdwtosRound($payload);
                if ($built === null) {
                    return null; // au moins un trou valide pointant un mot du pool exigé
                }

                return $base + [
                    'type'     => 'glisser-texte',
                    'segments' => $built['segments'],
                    'options'  => $built['options'],
                    'answers'  => $built['answers'],
                    'points'   => $points,
                ];

            case 'numerical':
                // NUMÉRIQUE (type Moodle « Numerical ») : réponse chiffrée avec une
                // TOLÉRANCE (±) et une UNITÉ facultative (purement indicative).
                // Représentation banque :
                //   payload['correct']   = float (réponse attendue) ;
                //   payload['tolerance'] = float >= 0 (écart absolu admis, défaut 0) ;
                //   payload['unit']      = string optionnel (ex. « km », « % »).
                // L'item de round porte correct/tolerance pour le scoring SERVEUR (comme
                // `accepted` pour `court`) : ils ne sont JAMAIS rendus dans le HTML
                // (la vue n'affiche que l'input + l'unité indicative). Le scoring est
                // binaire (abs(donné - correct) <= tolerance) ; l'unité n'est pas notée.
                // C1 (audit F3) : rejette aussi les valeurs NON FINIES (INF/-INF/NAN,
                // ex. payload corrompu « 1e309 ») → question non jouable, exclue du round,
                // par cohérence avec parseNumber()/buildNumericalPayload().
                if (! isset($payload['correct'])
                    || ! is_numeric($payload['correct'])
                    || ! is_finite((float) $payload['correct'])) {
                    return null; // une réponse numérique valide est exigée (sinon non jouable)
                }

                $tolerance = is_numeric($payload['tolerance'] ?? null) && is_finite((float) $payload['tolerance'])
                    ? abs((float) $payload['tolerance'])
                    : 0.0;
                $unit = isset($payload['unit']) && is_string($payload['unit'])
                    ? trim($payload['unit'])
                    : '';

                return $base + [
                    'type'      => 'numerique',
                    'correct'   => (float) $payload['correct'],
                    'tolerance' => $tolerance,
                    'unit'      => $unit,
                    'points'    => $points,
                ];

            case 'essay':
                // ESSAI (type Moodle « Essay ») : réponse rédigée à CORRECTION MANUELLE.
                // Aucune bonne réponse → toujours jouable. L'énoncé = `question` (déjà dans
                // $base via le prompt). On transporte `grader_info` (consignes de correction
                // OPTIONNELLES) dans l'item du round : elles sont snapshotées avec la
                // tentative pour l'écran de correction, et JAMAIS rendues au joueur (le
                // lecteur n'affiche pour un essai que l'énoncé + un <textarea>). Les points
                // servent de barème maximum à la correction manuelle.
                $graderInfo = isset($payload['grader_info']) && is_string($payload['grader_info'])
                    ? trim($payload['grader_info'])
                    : '';

                return $base + [
                    'type'        => 'essai',
                    'grader_info' => $graderInfo,
                    'points'      => $points,
                ];

            default:
                return null;
        }
    }

    /**
     * Construit la structure d'affichage + de scoring d'un cloze à partir du payload
     * (texte à marqueurs + trous). Découpe le texte sur les marqueurs [[n]] :
     *  - 'segments' : liste ordonnée pour l'affichage - {type:'text', value} ou
     *    {type:'blank', index, kind, choices?(mcq)}. AUCUN corrigé (accepted/correct).
     *  - 'blanks'   : map index_de_trou => trou NORMALISÉ avec le corrigé (accepted /
     *    correct), gardée serveur (jamais rendue au client).
     * Retourne null si aucun trou valide (texte vide, aucun marqueur résolu, trous
     * invalides) → la question n'est pas jouable et n'est pas mise en round (défensif).
     *
     * @param  array<string, mixed>  $payload
     * @return array{segments: array<int, array<string, mixed>>, blanks: array<int, array<string, mixed>>}|null
     */
    private static function buildClozeRound(array $payload): ?array
    {
        $text     = (string) ($payload['text'] ?? '');
        $blanksIn = is_array($payload['blanks'] ?? null) ? array_values($payload['blanks']) : [];

        if (trim($text) === '' || $blanksIn === []) {
            return null;
        }

        // Découpe en conservant les marqueurs [[n]] comme jetons séparés.
        $parts = preg_split('/(\[\[\d+\]\])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return null;
        }

        $segments  = [];
        $blanksOut = [];
        // C1 (anti-biais de notation) : un même marqueur [[n]] ne doit produire QU'UN
        // seul champ jouable. Si [[n]] apparaît plusieurs fois, les occurrences SUIVANTES
        // sont rendues en TEXTE littéral (sinon 2 champs partageraient le même `name`
        // answers[i][k] → seul le dernier serait évalué, biais en faveur de l'étudiant).
        $seenBlankIndex = [];

        foreach ($parts as $part) {
            if (preg_match('/^\[\[(\d+)\]\]$/', $part, $m) === 1) {
                $k = (int) $m[1] - 1; // [[1]] → index 0 dans blanks

                if (isset($seenBlankIndex[$k])) {
                    // Marqueur DUPLIQUÉ : ce trou a déjà produit un champ → texte littéral.
                    $segments[] = ['type' => 'text', 'value' => $part];

                    continue;
                }

                $normalized = isset($blanksIn[$k]) ? self::normalizeClozeBlank($blanksIn[$k]) : null;

                if ($normalized === null) {
                    // Marqueur orphelin / trou invalide → rendu en texte littéral (défensif).
                    $segments[] = ['type' => 'text', 'value' => $part];

                    continue;
                }

                // Segment d'AFFICHAGE : jamais d'accepted/correct ; choices pour le mcq.
                $seg = ['type' => 'blank', 'index' => $k, 'kind' => $normalized['kind']];
                if ($normalized['kind'] === 'mcq') {
                    $seg['choices'] = $normalized['choices'];
                }

                $segments[]           = $seg;
                $blanksOut[$k]        = $normalized; // corrigé serveur
                $seenBlankIndex[$k]   = true;        // C1 : trou désormais « consommé »
            } else {
                $segments[] = ['type' => 'text', 'value' => $part];
            }
        }

        if ($blanksOut === []) {
            return null; // aucun trou résolu → non jouable
        }

        return ['segments' => $segments, 'blanks' => $blanksOut];
    }

    /**
     * Construit la structure d'affichage + de scoring d'un GLISSER-DÉPOSER SUR TEXTE
     * (ddwtos) à partir du payload (texte à marqueurs + pool de mots + corrigé). Le pool
     * est MÉLANGÉ une fois (présentation à l'étudiant) ; chaque trou listera TOUT le pool.
     *  - 'segments' : liste ordonnée pour l'affichage - {type:'text', value} ou
     *    {type:'blank', index}. AUCUN corrigé exposé (pas de bonne réponse par trou).
     *  - 'options'  : pool de mots MÉLANGÉ (partagé par tous les trous), rendu côté client.
     *  - 'answers'  : map index_de_trou (0-based stable) => INDEX du mot correct DANS LE
     *    POOL MÉLANGÉ, gardée serveur (jamais rendue au client).
     * Retourne null si aucun trou ne pointe vers un mot valide du pool (défensif : la
     * question n'est alors pas jouable et n'est pas mise en round). Les marqueurs [[n]]
     * dupliqués sont traités comme en cloze (2e occurrence = texte littéral) pour éviter
     * deux champs au même `name` (biais de notation).
     *
     * @param  array<string, mixed>  $payload
     * @return array{segments: array<int, array<string, mixed>>, options: array<int, string>, answers: array<int, int>}|null
     */
    private static function buildDdwtosRound(array $payload): ?array
    {
        $text    = (string) ($payload['text'] ?? '');
        $wordsIn = is_array($payload['words'] ?? null) ? array_values($payload['words']) : [];
        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];

        if (trim($text) === '' || $wordsIn === []) {
            return null;
        }

        // Normalise le pool en conservant l'INDEX D'ORIGINE (les `answers` du payload y
        // réfèrent) → map originalIndex => indexFiltré. Un mot vide est ignoré.
        $words           = [];
        $originalToFinal = [];
        foreach ($wordsIn as $oi => $w) {
            $value = is_string($w) ? trim($w) : '';
            if ($value !== '') {
                $originalToFinal[(int) $oi] = count($words);
                $words[]                    = $value;
            }
        }
        if ($words === []) {
            return null;
        }

        // Mélange du pool présenté : permutation des positions filtrées. On garde la
        // correspondance indexFiltré => indexMélangé pour remapper les `answers`.
        $shuffleOrder = range(0, count($words) - 1);
        if (count($shuffleOrder) > 1) {
            shuffle($shuffleOrder);
        }
        $options       = [];
        $finalToShuffle = [];
        foreach ($shuffleOrder as $shuffledPos => $finalIndex) {
            $options[$shuffledPos]      = $words[$finalIndex];
            $finalToShuffle[$finalIndex] = $shuffledPos;
        }

        // Découpe en conservant les marqueurs [[n]] comme jetons séparés (comme cloze).
        $parts = preg_split('/(\[\[\d+\]\])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return null;
        }

        $segments    = [];
        $answersOut  = [];
        $seenBlankIndex = [];

        foreach ($parts as $part) {
            if (preg_match('/^\[\[(\d+)\]\]$/', $part, $m) === 1) {
                $k = (int) $m[1] - 1; // [[1]] → index 0

                if (isset($seenBlankIndex[$k])) {
                    // Marqueur DUPLIQUÉ : déjà un champ pour ce trou → texte littéral.
                    $segments[] = ['type' => 'text', 'value' => $part];

                    continue;
                }

                // L'index correct (payload) réfère le pool D'ORIGINE → filtré → mélangé.
                $originalCorrect = isset($answers[$k]) && is_numeric($answers[$k]) ? (int) $answers[$k] : -1;
                $finalCorrect    = $originalToFinal[$originalCorrect] ?? null;

                if ($finalCorrect === null) {
                    // Trou pointant un mot absent du pool → rendu en texte littéral (défensif).
                    $segments[] = ['type' => 'text', 'value' => $part];

                    continue;
                }

                $segments[]         = ['type' => 'blank', 'index' => $k];
                $answersOut[$k]     = (int) $finalToShuffle[$finalCorrect]; // index dans le pool mélangé
                $seenBlankIndex[$k] = true;
            } else {
                $segments[] = ['type' => 'text', 'value' => $part];
            }
        }

        if ($answersOut === []) {
            return null; // aucun trou résolu → non jouable
        }

        return ['segments' => $segments, 'options' => $options, 'answers' => $answersOut];
    }

    /**
     * Normalise un trou de cloze (forme canonique du payload) vers la structure de
     * scoring, ou null s'il est invalide (mêmes invariants que QuestionBankManager) :
     *  - mcq   : >= 2 choix non vides + un index `correct` valide ;
     *  - short : >= 1 réponse acceptée non vide (display optionnel).
     *
     * @param  mixed  $blank
     * @return array<string, mixed>|null
     */
    private static function normalizeClozeBlank(mixed $blank): ?array
    {
        if (! is_array($blank)) {
            return null;
        }

        $kind = (($blank['kind'] ?? 'short') === 'mcq') ? 'mcq' : 'short';

        if ($kind === 'mcq') {
            $choices = array_values(array_filter(
                array_map(fn ($c): string => is_string($c) ? trim($c) : '', (array) ($blank['choices'] ?? [])),
                fn (string $c): bool => $c !== ''
            ));
            if (count($choices) < 2) {
                return null;
            }
            $correct = (int) ($blank['correct'] ?? -1);
            if ($correct < 0 || $correct >= count($choices)) {
                return null;
            }

            return ['kind' => 'mcq', 'choices' => $choices, 'correct' => $correct];
        }

        $accepted = array_values(array_filter(
            array_map(fn ($a): string => is_string($a) ? trim($a) : '', (array) ($blank['accepted'] ?? [])),
            fn (string $a): bool => $a !== ''
        ));
        if ($accepted === []) {
            return null;
        }

        $out     = ['kind' => 'short', 'accepted' => $accepted];
        $display = isset($blank['display']) && is_string($blank['display']) ? trim($blank['display']) : '';
        if ($display !== '') {
            $out['display'] = $display;
        }

        return $out;
    }

    private static function pointsFromDifficulty(string $difficulty): int
    {
        $d = strtolower($difficulty);

        return $d === 'facile' ? 1 : ($d === 'difficile' ? 3 : 2);
    }

    /**
     * V1-a : normalise le tableau de rétroaction par choix vers un tableau indexé
     * 0..($count-1) de chaînes (un texte par choix, '' si aucun). Tolère un tableau
     * partiel / désordonné / surnuméraire (on ne conserve QUE les index valides).
     * Si tous les textes sont vides → []  (rétrocompat : aucune clé parasite).
     *
     * @param  mixed  $raw  Valeur brute du payload (attendu : array index => texte).
     * @return array<int, string>
     */
    private static function normalizeChoiceFeedback(mixed $raw, int $count): array
    {
        if (! is_array($raw) || $count <= 0) {
            return [];
        }

        $out      = array_fill(0, $count, '');
        $hasAny   = false;

        foreach ($raw as $index => $text) {
            $i = (int) $index;
            if ($i < 0 || $i >= $count) {
                continue;
            }
            $value = is_string($text) ? trim($text) : '';
            if ($value !== '') {
                $out[$i] = $value;
                $hasAny  = true;
            }
        }

        return $hasAny ? $out : [];
    }
}
