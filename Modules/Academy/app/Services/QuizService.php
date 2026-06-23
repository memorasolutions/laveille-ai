<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

/**
 * Adaptateur minimal autour de QtService (Modules/Tools).
 *
 * VERDICT (audité 2026-06-20) : QtService est FIGÉ sur la banque GLOBALE. Ses
 * méthodes publiques (newRound, dailyRound) n'acceptent aucun paramètre et lisent
 * des fichiers de données PHP codés en dur (qt-questions.php, qt-truefalse.php,
 * qt-shortanswer.php) via loadData() privé. Il n'expose donc PAS de banque
 * arbitraire par clé. On l'utilise tel quel via newRound() ; $bankKey est conservé
 * (signature + payload) mais N'EST PAS encore honoré pour le choix des questions.
 *
 * Ce qui FONCTIONNE déjà côté serveur, indépendamment de la banque :
 *  - passing_score   → QuizController::submitQuiz applique le seuil de réussite.
 *  - attempts_allowed → QuizController::startQuiz applique la limite de tentatives.
 *
 * TODO RD-A2 (« quiz propre au cours ») : pour une vraie banque par cours, deux
 * pistes sûres, sans casser l'existant :
 *   1. Refactorer QtService pour accepter un chemin/clé de fichier dynamique
 *      (newRound(?string $bankKey)), avec repli sur la banque globale.
 *   2. OU stocker les questions directement dans payload['questions'] et bâtir un
 *      petit moteur local ici (score() sait déjà noter qcm/vraifaux/court/appariement).
 * Tant que ce n'est pas fait, buildRound() retombe TOUJOURS sur la banque globale.
 */
final class QuizService
{
    /**
     * Construit un round de questions pour un item quiz Academy.
     *
     * @param  string $bankKey  Clé de banque (payload['qt_bank_key']) : ignorée par QtService
     *                          (banque globale figée), conservée pour extension future (RD-A2).
     * @return array<int, array<string, mixed>>
     */
    public static function buildRound(string $bankKey): array
    {
        if (! class_exists(\Modules\Tools\Services\QtService::class)) {
            return [];
        }

        try {
            return \Modules\Tools\Services\QtService::newRound();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * V1-d — MÉLANGE (questions et/ou réponses).
     *
     * Le round mélangé est ce qui sera STOCKÉ EN SESSION puis snapshoté dans
     * QuizAttempt. Comme score() et la révision (V1-a) lisent CE round, le scoring
     * reste EXACT : pour chaque question dont on permute les choix, on recalcule
     * l'index `correct` (et on permute `choice_feedback` à l'identique).
     *
     *  - $shuffleQuestions : mélange l'ORDRE des questions (aucun champ touché).
     *  - $shuffleAnswers   : pour les qcm uniquement, permute `choices` +
     *                        `choice_feedback` et remappe `correct` (int en QCM simple,
     *                        TABLEAU d'indices en QCM MULTI V1-e). Les vraifaux
     *                        gardent l'ordre Vrai/Faux fixe (sémantique). court (pas
     *                        de choix), appariement (défs déjà mélangées par le mapping)
     *                        et ordonnancement (éléments déjà mélangés + `answer` apparié
     *                        par le mapping) sont laissés tels quels → mapping `answer`
     *                        intact, donc scoring inaltéré par le mélange.
     *
     * @param  array<int, array<string, mixed>> $round
     * @return array<int, array<string, mixed>>
     */
    public static function shuffleRound(array $round, bool $shuffleQuestions, bool $shuffleAnswers): array
    {
        if ($shuffleAnswers) {
            foreach ($round as $i => $q) {
                $round[$i] = self::shuffleQuestionChoices($q);
            }
        }

        if ($shuffleQuestions && count($round) > 1) {
            // array_values garde des index 0..N-1 séquentiels (les réponses sont
            // indexées par numéro de question côté formulaire/scoring).
            shuffle($round);
            $round = array_values($round);
        }

        return $round;
    }

    /**
     * Permute les choix d'UNE question qcm en remappant l'index correct (et le
     * feedback par choix) pour que le scoring reste exact. Toute autre forme de
     * question est renvoyée inchangée (vraifaux/court/appariement/ordonnancement/cloze :
     * ordre signifiant ou mapping déjà mélangé en amont ; le cloze suit l'ordre du
     * texte → jamais mélangé, comme `court`).
     *
     * @param  array<string, mixed> $q
     * @return array<string, mixed>
     */
    private static function shuffleQuestionChoices(array $q): array
    {
        $type = $q['type'] ?? null;

        if ($type !== 'qcm') {
            return $q;
        }

        $choices = is_array($q['choices'] ?? null) ? array_values($q['choices']) : [];
        $n       = count($choices);

        if ($n < 2) {
            return $q;
        }

        // V1-e - QCM MULTI : `correct` est un TABLEAU d'indices (sinon int simple).
        // On remappe en conséquence pour que le scoring reste exact après mélange.
        $multiple   = ! empty($q['multiple']);
        $oldCorrect = $multiple
            ? array_map('intval', is_array($q['correct'] ?? null) ? $q['correct'] : [])
            : (int) ($q['correct'] ?? -1);

        // Permutation des positions 0..n-1.
        $order = range(0, $n - 1);
        shuffle($order);

        $newChoices  = [];
        $newCorrect  = $multiple ? [] : $oldCorrect;
        $oldFeedback = is_array($q['choice_feedback'] ?? null) ? $q['choice_feedback'] : null;
        $newFeedback = $oldFeedback !== null ? [] : null;

        foreach ($order as $newIndex => $oldIndex) {
            $newChoices[$newIndex] = $choices[$oldIndex];

            if ($multiple) {
                // Le TABLEAU d'indices corrects suit la permutation (V1-e).
                if (in_array($oldIndex, $oldCorrect, true)) {
                    $newCorrect[] = $newIndex;
                }
            } elseif ($oldIndex === $oldCorrect) {
                $newCorrect = $newIndex;
            }

            if ($newFeedback !== null) {
                // choice_feedback peut être indexé int OU string : on lit les deux.
                $fb = $oldFeedback[$oldIndex] ?? ($oldFeedback[(string) $oldIndex] ?? null);
                if ($fb !== null) {
                    $newFeedback[$newIndex] = $fb;
                }
            }
        }

        $q['choices'] = $newChoices;
        if ($multiple) {
            sort($newCorrect);
        }
        $q['correct'] = $newCorrect;
        if ($newFeedback !== null) {
            $q['choice_feedback'] = $newFeedback;
        }

        return $q;
    }

    /**
     * Calcule le score d'une soumission de quiz.
     *
     * V1-c - SCORING PONDÉRÉ : chaque question porte un champ `points` (défaut 1 si
     * absent → comportement strictement identique au comptage simple historique).
     *   - points_earned   = somme des points des questions correctes ;
     *   - points_possible = somme des points de toutes les questions ;
     *   - percent         = round(points_earned / points_possible * 100).
     * Les clés existantes sont CONSERVÉES :
     *   - score   = points obtenus (= points_earned) ;
     *   - correct = NB de bonnes réponses (inchangé, sert le badge « sans faute ») ;
     *   - total   = NB de questions (inchangé) ;
     *   - percent = désormais pondéré (identique au simple si tous les points valent 1).
     *
     * @param  array<int, array<string, mixed>> $questions  Questions du round (telles que retournées par buildRound)
     * @param  array<string, mixed>             $answers    Réponses indexées par numéro de question (clés string "0","1",…)
     * @return array{score: int, total: int, percent: int, correct: int, wrong: int, points_earned: int, points_possible: int, details: array<int, array{correct: bool, expected: mixed, given: mixed}>}
     */
    public static function score(array $questions, array $answers): array
    {
        $total = count($questions);

        if ($total === 0) {
            return [
                'score'           => 0,
                'total'           => 0,
                'percent'         => 0,
                'correct'         => 0,
                'wrong'           => 0,
                'points_earned'   => 0,
                'points_possible' => 0,
                'details'         => [],
            ];
        }

        $correct        = 0;
        $pointsEarned   = 0;
        $pointsPossible = 0;
        $details        = [];

        foreach ($questions as $index => $question) {
            $type      = $question['type'] ?? null;
            $given     = $answers[(string) $index] ?? null;
            $expected  = null;
            $isCorrect = false;

            // Pondération : `points` explicite (>= 1) sinon 1 (rétrocompat stricte).
            $points = isset($question['points']) && (int) $question['points'] >= 1
                ? (int) $question['points']
                : 1;
            $pointsPossible += $points;

            switch ($type) {
                case 'qcm':
                    // V1-e - QCM À RÉPONSES MULTIPLES (crédit partiel borné). Quand
                    // l'item porte `multiple` = true, `correct` est un TABLEAU d'indices
                    // de bonnes réponses et la réponse soumise (`given`) est un TABLEAU
                    // d'indices cochés. Formule (parité Moodle « multiple answers »,
                    // bornée >= 0) :
                    //   gagne    = (#bonnes cochées) - (#mauvaises cochées)
                    //   fraction = max(0, gagne / #bonnes)         (jamais négatif)
                    //   points obtenus = round(fraction * points)  (cohérent V1-c)
                    // Tout-correct exact → fraction 1 → points pleins ; tout-faux ou rien
                    // → 0. La question n'est comptée « correcte » (badge sans-faute) QUE
                    // si fraction == 1 (toutes les bonnes, aucune mauvaise).
                    if (! empty($question['multiple'])) {
                        $correctSet = array_values(array_unique(array_map(
                            'intval',
                            is_array($question['correct'] ?? null) ? $question['correct'] : []
                        )));
                        $givenSet = is_array($given)
                            ? array_values(array_unique(array_map('intval', $given)))
                            : (is_numeric($given) ? [(int) $given] : []);

                        $nbGood  = count($correctSet);
                        $goodHit = count(array_intersect($givenSet, $correctSet));
                        $badHit  = count(array_diff($givenSet, $correctSet));

                        // Division par zéro impossible en pratique (>= 1 bonne réponse
                        // garantie à l'enregistrement) ; garde-fou défensif quand même.
                        $fraction = $nbGood > 0
                            ? max(0.0, ($goodHit - $badHit) / $nbGood)
                            : 0.0;

                        $isCorrect = ($fraction >= 1.0);

                        // On note ICI (points fractionnaires) puis on SAUTE le bloc commun
                        // (qui ajouterait les points pleins). pointsPossible a déjà reçu
                        // le poids plein de la question avant le switch.
                        $pointsEarned += (int) round($fraction * $points);
                        if ($isCorrect) {
                            $correct++;
                        }

                        $details[$index] = [
                            'correct'  => $isCorrect,
                            'expected' => $correctSet,
                            'given'    => $givenSet,
                        ];

                        continue 2; // question suivante (bypass du bloc commun)
                    }

                    // QCM simple (1 bonne réponse) — comportement historique inchangé.
                    $expected  = (int) ($question['correct'] ?? -1);
                    $givenInt  = is_numeric($given) ? (int) $given : -1;
                    $isCorrect = $givenInt === $expected;
                    $given     = $givenInt;
                    break;

                case 'vraifaux':
                    $expected  = (int) ($question['correct'] ?? -1);
                    $givenInt  = is_numeric($given) ? (int) $given : -1;
                    $isCorrect = $givenInt === $expected;
                    $given     = $givenInt;
                    break;

                case 'court':
                    $expected         = $question['accepted'] ?? [];
                    $givenStr         = is_string($given) ? trim(mb_strtolower($given, 'UTF-8')) : '';
                    $normalizedAccepted = array_map(
                        fn ($s) => trim(mb_strtolower((string) $s, 'UTF-8')),
                        (array) $expected
                    );
                    $isCorrect = in_array($givenStr, $normalizedAccepted, true);
                    break;

                case 'numerique':
                    // NUMÉRIQUE - scoring BINAIRE avec tolérance (parité Moodle « Numerical »).
                    // `correct` = réponse attendue (float, serveur) ; `tolerance` = écart
                    // absolu admis (>= 0). La réponse de l'étudiant est parsée en float
                    // (virgule OU point décimal, espaces / séparateurs de milliers tolérés)
                    // via parseNumber(). Correct si abs(donné - correct) <= tolerance.
                    // L'UNITÉ n'est PAS notée (purement indicative) : on score sur la valeur
                    // seule (choix documenté). Réponse vide / non numérique → 0 (jamais 500).
                    $expectedNum = isset($question['correct']) && is_numeric($question['correct'])
                        ? (float) $question['correct']
                        : null;
                    $tolerance = isset($question['tolerance']) && is_numeric($question['tolerance'])
                        ? abs((float) $question['tolerance'])
                        : 0.0;
                    $givenNum  = self::parseNumber($given);
                    $isCorrect = $expectedNum !== null
                        && $givenNum !== null
                        && abs($givenNum - $expectedNum) <= $tolerance;
                    $expected  = $expectedNum;
                    $given     = $givenNum; // valeur normalisée pour le détail
                    break;

                case 'appariement':
                    $expected    = $question['answer'] ?? [];
                    $givenArr    = is_array($given) ? array_values(array_map('intval', $given)) : [];
                    $expectedArr = is_array($expected) ? array_values(array_map('intval', $expected)) : [];
                    $isCorrect   = $givenArr === $expectedArr;
                    $given       = $givenArr;
                    $expected    = $expectedArr;
                    break;

                case 'ordonnancement':
                    // ORDONNANCEMENT - CRÉDIT PARTIEL par POSITION ABSOLUE (parité Moodle
                    // « Ordering », notation par position absolue). `answer` = pour chaque
                    // élément AFFICHÉ (ordre mélangé), sa position absolue correcte (0-based) ;
                    // `given` = position absolue choisie par l'étudiant pour CE même élément.
                    // Formule (bornée [0,1]) :
                    //   fraction = (#éléments à leur position absolue correcte) / N
                    //   points obtenus = round(fraction * points)   (cohérent V1-c)
                    // Ordre exact → fraction 1 → points pleins. La question n'est comptée
                    // « correcte » (badge sans-faute) QUE si fraction == 1 (ordre exact).
                    $expectedArr = array_map('intval', (array) ($question['answer'] ?? []));
                    $givenArr    = is_array($given) ? array_values(array_map('intval', $given)) : [];
                    $n           = count($expectedArr);

                    $hits = 0;
                    foreach ($expectedArr as $pos => $correctPos) {
                        if (array_key_exists($pos, $givenArr) && $givenArr[$pos] === $correctPos) {
                            $hits++;
                        }
                    }

                    $fraction  = $n > 0 ? $hits / $n : 0.0;
                    $isCorrect = ($fraction >= 1.0);

                    // On note ICI (points fractionnaires) puis on SAUTE le bloc commun.
                    // pointsPossible a déjà reçu le poids plein de la question avant le switch.
                    $pointsEarned += (int) round($fraction * $points);
                    if ($isCorrect) {
                        $correct++;
                    }

                    $details[$index] = [
                        'correct'  => $isCorrect,
                        'expected' => $expectedArr,
                        'given'    => $givenArr,
                    ];

                    continue 2; // question suivante (bypass du bloc commun)

                case 'cloze':
                    // CLOZE / TEXTE À TROUS - CRÉDIT PARTIEL PAR TROU (parité Moodle
                    // « Embedded answers », notation par sous-question). `blanks` = map
                    // index_de_trou (0-based stable) => corrigé du trou :
                    //   - kind=short : { accepted:[…] } → comparé comme `court`
                    //     (normalisation casse + espaces, mb_strtolower/trim) ;
                    //   - kind=mcq   : { choices:[…], correct:int } → comparé comme `qcm`
                    //     (égalité d'index).
                    // `given` = TABLEAU index_de_trou => valeur soumise (texte pour short,
                    // index pour mcq). Formule (bornée [0,1]) :
                    //   fraction = (#trous corrects) / (#trous)
                    //   points obtenus = round(fraction * points)   (cohérent V1-c)
                    // Tous trous corrects → fraction 1 → points pleins. La question n'est
                    // comptée « correcte » (badge sans-faute) QUE si fraction == 1. Les
                    // bonnes réponses (accepted/correct) restent serveur : jamais exposées
                    // avant soumission (seuls les segments d'affichage sont rendus).
                    $blanks      = is_array($question['blanks'] ?? null) ? $question['blanks'] : [];
                    $givenBlanks = is_array($given) ? $given : [];
                    $nbBlanks    = count($blanks);

                    $hits         = 0;
                    $expectedCloze = [];
                    foreach ($blanks as $k => $blank) {
                        $kind = ($blank['kind'] ?? 'short') === 'mcq' ? 'mcq' : 'short';
                        $ans  = $givenBlanks[$k] ?? ($givenBlanks[(string) $k] ?? null);

                        if ($kind === 'mcq') {
                            $choices          = is_array($blank['choices'] ?? null) ? $blank['choices'] : [];
                            $correctIdx       = (int) ($blank['correct'] ?? -1);
                            $givenIdx         = is_numeric($ans) ? (int) $ans : -1;
                            $ok               = $givenIdx === $correctIdx;
                            $expectedCloze[$k] = $choices[$correctIdx] ?? '';
                        } else {
                            $accepted = array_map(
                                fn ($s): string => trim(mb_strtolower((string) $s, 'UTF-8')),
                                (array) ($blank['accepted'] ?? [])
                            );
                            $givenStr          = is_string($ans) ? trim(mb_strtolower($ans, 'UTF-8')) : '';
                            $ok                = $givenStr !== '' && in_array($givenStr, $accepted, true);
                            $acceptedRaw       = array_values((array) ($blank['accepted'] ?? []));
                            $expectedCloze[$k] = $acceptedRaw[0] ?? '';
                        }

                        if ($ok) {
                            $hits++;
                        }
                    }

                    $fraction  = $nbBlanks > 0 ? $hits / $nbBlanks : 0.0;
                    $isCorrect = ($fraction >= 1.0);

                    // On note ICI (points fractionnaires) puis on SAUTE le bloc commun.
                    // pointsPossible a déjà reçu le poids plein de la question avant le switch.
                    $pointsEarned += (int) round($fraction * $points);
                    if ($isCorrect) {
                        $correct++;
                    }

                    $details[$index] = [
                        'correct'  => $isCorrect,
                        'expected' => $expectedCloze,
                        'given'    => $givenBlanks,
                    ];

                    continue 2; // question suivante (bypass du bloc commun)

                default:
                    $isCorrect = false;
                    break;
            }

            if ($isCorrect) {
                $correct++;
                $pointsEarned += $points;
            }

            $details[$index] = [
                'correct'  => $isCorrect,
                'expected' => $expected,
                'given'    => $given,
            ];
        }

        $wrong = $total - $correct;
        // Percent PONDÉRÉ. Garde-fou : si possible vaut 0 (ne devrait pas arriver,
        // chaque question pèse >= 1), on évite la division par zéro.
        $percent = $pointsPossible > 0
            ? (int) round(($pointsEarned / $pointsPossible) * 100)
            : 0;

        return [
            'score'           => $pointsEarned, // points obtenus (= points_earned)
            'total'           => $total,        // NB de questions (inchangé)
            'percent'         => $percent,      // pondéré
            'correct'         => $correct,      // NB de bonnes réponses (badge « sans faute »)
            'wrong'           => $wrong,
            'points_earned'   => $pointsEarned,
            'points_possible' => $pointsPossible,
            'details'         => $details,
        ];
    }

    /**
     * Parse une valeur saisie en nombre flottant, TOLÉRANTE à la locale (FR/EN) :
     *  - accepte la VIRGULE ou le POINT comme séparateur décimal ;
     *  - tolère les espaces (normaux, insécables, fins) = séparateurs de milliers ;
     *  - si virgule ET point sont présents, le DERNIER rencontré est le décimal,
     *    l'autre est traité comme séparateur de milliers (retiré) ;
     *  - une virgule seule est interprétée comme décimale (locale québécoise).
     * Retourne null si la valeur est vide ou non numérique (le scoring la note 0,
     * jamais d'exception). Partagé (DRY) par score() et l'éditeur de banque.
     */
    public static function parseNumber(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            $result = (float) $raw;

            // C1 (audit F3) : INF/-INF/NAN ne sont JAMAIS des réponses valides
            // (un float déjà infini en entrée serait sinon propagé).
            return is_finite($result) ? $result : null;
        }
        if (! is_string($raw)) {
            return null;
        }

        $s = trim($raw);
        if ($s === '') {
            return null;
        }

        // Retire les espaces (ordinaire, insécable U+00A0, fin U+202F, fin U+2009).
        $s = preg_replace('/[\s\x{00A0}\x{202F}\x{2009}]/u', '', $s) ?? '';
        if ($s === '') {
            return null;
        }

        $hasComma = str_contains($s, ',');
        $hasDot   = str_contains($s, '.');

        if ($hasComma && $hasDot) {
            if (strrpos($s, ',') > strrpos($s, '.')) {
                // Virgule = décimal ; point = milliers.
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // Point = décimal ; virgule = milliers.
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            // Virgule seule = séparateur décimal (FR/QC).
            $s = str_replace(',', '.', $s);
        }

        if (! is_numeric($s)) {
            return null;
        }

        $result = (float) $s;

        // C1 (audit F3) : RACINE de l'overflow. is_numeric('1e309') est vrai mais
        // (float) '1e309' = INF → json_encode(['correct'=>INF]) échoue (corruption
        // silencieuse / 500 à l'enregistrement) et INF pollue le scoring/les détails.
        // On renvoie null pour INF/-INF/NAN : l'éditeur rejette proprement (« numérique
        // requis ») et le scoring le note 0, sans exception.
        return is_finite($result) ? $result : null;
    }

    /**
     * C3 (audit F3, DRY) — formate un float pour l'AFFICHAGE de la réponse attendue
     * dans la révision numérique (différé ET immédiat). Source unique de vérité :
     * 6 décimales max, zéros et point décimal superflus retirés (« 42.500000 » → « 42.5 »,
     * « 7.000000 » → « 7 »). Remplace la closure `fmtNum` jusqu'ici dupliquée dans
     * quiz-player.blade.php.
     */
    public static function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
