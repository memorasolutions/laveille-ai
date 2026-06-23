<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK - SOURCE UNIQUE (DRY) du sondage / questionnaire de rétroaction (item de
 * leçon « feedback », multi-questions, NON noté, optionnellement anonyme ; type Moodle
 * « Feedback »). Lue côté SERVEUR par le contrôleur de soumission, le lecteur
 * (lesson.blade) et l'éditeur (CourseEditor). La configuration des questions, la
 * validation des réponses, l'agrégat des résultats et l'anti-re-spam anonyme ne vivent
 * qu'ICI.
 *
 * Le payload de l'item porte (aucune nouvelle colonne, comme quiz/document/choice) :
 *   - intro      : texte d'introduction facultatif ;
 *   - anonymous  : ne jamais lier une réponse à son auteur (défaut false) ;
 *   - questions  : tableau de questions ; chaque question :
 *        - type     : rating | choice | text ;
 *        - label    : énoncé (obligatoire) ;
 *        - required : réponse obligatoire (bool) ;
 *        - scale    : (rating) borne haute de l'échelle 1..scale (2..10, défaut 5) ;
 *        - options  : (choice) libellés (>= 2).
 *
 * Une réponse est { index_question => valeur } : rating = entier 1..scale, choice =
 * index d'option, text = chaîne bornée.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Models\FeedbackParticipant;
use Modules\Academy\Models\FeedbackResponse;
use Modules\Academy\Models\LessonItem;

final class FeedbackService
{
    /** Types de questions reconnus (liste blanche stricte). */
    public const QUESTION_TYPES = ['rating', 'choice', 'text'];

    /** Nombre maximal de questions d'un sondage (garde-fou anti-abus). */
    public const MAX_QUESTIONS = 30;

    /** Nombre maximal d'options d'une question « choice ». */
    public const MAX_OPTIONS = 20;

    /** Bornes de l'échelle d'une question « rating ». */
    public const MIN_SCALE = 2;

    public const MAX_SCALE = 10;

    public const DEFAULT_SCALE = 5;

    /** Longueur maximale d'une réponse « text » (anti-abus). */
    public const MAX_TEXT = 2000;

    /** Clé de session bornant le re-spam d'un sondage ANONYME (par session). */
    public const SESSION_KEY = 'academy.feedback.answered';

    // ─────────────────────────────────────────────────────────────────────────────
    // LECTURE DE LA CONFIGURATION (payload)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Questions NORMALISÉES de l'item (réindexées depuis 0). Source de vérité des
     * index de questions, des types, échelles et options valides. Jamais de HTML :
     * l'échappement est fait au rendu.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function questions(LessonItem $item): array
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['questions'] ?? []) : [];

        return self::normalizeQuestions($raw);
    }

    public static function intro(LessonItem $item): string
    {
        $intro = is_array($item->payload ?? null) ? ($item->payload['intro'] ?? '') : '';

        return is_string($intro) ? $intro : '';
    }

    public static function isAnonymous(LessonItem $item): bool
    {
        return (bool) (is_array($item->payload ?? null) ? ($item->payload['anonymous'] ?? false) : false);
    }

    /**
     * Normalise un tableau de questions BRUT (venu de l'éditeur ou stocké) vers la
     * forme canonique. Défensif : une question dont le type est inconnu, sans énoncé,
     * ou « choice » avec moins de 2 options, est ÉCARTÉE (jamais d'écriture invalide).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeQuestions(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $q) {
            if (! is_array($q)) {
                continue;
            }

            $type = is_string($q['type'] ?? null) ? $q['type'] : '';
            if (! in_array($type, self::QUESTION_TYPES, true)) {
                continue;
            }

            $label = is_string($q['label'] ?? null) ? trim($q['label']) : '';
            if ($label === '') {
                continue;
            }

            $norm = [
                'type'     => $type,
                'label'    => $label,
                'required' => self::truthy($q['required'] ?? false),
            ];

            if ($type === 'rating') {
                $scale = (int) ($q['scale'] ?? self::DEFAULT_SCALE);
                $norm['scale'] = max(self::MIN_SCALE, min(self::MAX_SCALE, $scale));
            } elseif ($type === 'choice') {
                $options = self::parseOptions($q['options'] ?? []);
                if (count($options) < 2) {
                    continue; // une question à choix exige >= 2 options.
                }
                $norm['options'] = $options;
            }

            $out[] = $norm;

            if (count($out) >= self::MAX_QUESTIONS) {
                break;
            }
        }

        return $out;
    }

    /**
     * Parse les options d'une question « choice » : tableau déjà structuré OU chaîne
     * multiligne (une option par ligne). Trim, retrait des vides, dédoublonnage,
     * réindexation, cap au maximum.
     *
     * @return array<int, string>
     */
    public static function parseOptions(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        }

        $clean = [];
        foreach ((array) $raw as $label) {
            if (! is_string($label)) {
                continue;
            }
            $label = trim($label);
            if ($label !== '' && ! in_array($label, $clean, true)) {
                $clean[] = $label;
            }
        }

        return array_slice($clean, 0, self::MAX_OPTIONS);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // VALIDATION D'UNE SOUMISSION (côté serveur, jamais le client)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Valide une soumission BRUTE contre les questions de l'item (source serveur) et
     * en extrait les réponses propres. Une question obligatoire non remplie, un rating
     * hors échelle, un index de choix hors options ou un texte trop long => erreur.
     * Les réponses facultatives vides sont simplement ignorées.
     *
     * @param  array<int|string, mixed>  $submitted  réponses brutes indexées par question
     * @return array{errors: array<int, string>, answers: array<int, int|string>}
     */
    public static function validateAndCollect(LessonItem $item, array $submitted): array
    {
        $questions = self::questions($item);
        $errors    = [];
        $answers   = [];

        foreach ($questions as $i => $q) {
            $raw = $submitted[$i] ?? null;

            switch ($q['type']) {
                case 'rating':
                    if ($raw === null || $raw === '') {
                        if ($q['required']) {
                            $errors[] = 'Veuillez répondre à toutes les questions obligatoires.';
                        }
                        break;
                    }
                    if (! is_numeric($raw)) {
                        $errors[] = 'Réponse invalide.';
                        break;
                    }
                    $value = (int) $raw;
                    if ($value < 1 || $value > (int) $q['scale']) {
                        $errors[] = 'Réponse hors de l\'échelle autorisée.';
                        break;
                    }
                    $answers[$i] = $value;
                    break;

                case 'choice':
                    if ($raw === null || $raw === '') {
                        if ($q['required']) {
                            $errors[] = 'Veuillez répondre à toutes les questions obligatoires.';
                        }
                        break;
                    }
                    if (! is_numeric($raw)) {
                        $errors[] = 'Réponse invalide.';
                        break;
                    }
                    $idx = (int) $raw;
                    if ($idx < 0 || $idx >= count($q['options'])) {
                        $errors[] = 'Choix invalide.';
                        break;
                    }
                    $answers[$i] = $idx;
                    break;

                case 'text':
                    $text = is_string($raw) ? trim($raw) : '';
                    if ($text === '') {
                        if ($q['required']) {
                            $errors[] = 'Veuillez répondre à toutes les questions obligatoires.';
                        }
                        break;
                    }
                    if (mb_strlen($text) > self::MAX_TEXT) {
                        $errors[] = 'Réponse trop longue.';
                        break;
                    }
                    $answers[$i] = $text;
                    break;
            }
        }

        return ['errors' => $errors, 'answers' => $answers];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // RÉSULTATS (formateur UNIQUEMENT - agrégat anonymisé, jamais d'identité)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Agrégat des réponses par question, RÉSERVÉ au formateur (gate côté vue). On ne
     * renvoie JAMAIS d'identité : rating/choice => comptes par valeur ; text => liste
     * de textes anonymisés.
     *
     * @return array{total: int, questions: array<int, array<string, mixed>>}
     */
    public static function results(LessonItem $item): array
    {
        $questions = self::questions($item);

        $rows  = FeedbackResponse::where('lesson_item_id', $item->id)->select('answers')->get();
        $total = $rows->count();

        $out = [];
        foreach ($questions as $i => $q) {
            $entry = ['type' => $q['type'], 'label' => $q['label']];

            if ($q['type'] === 'rating') {
                $scale  = (int) $q['scale'];
                $counts = array_fill(1, $scale, 0);
                $sum    = 0;
                $n      = 0;
                foreach ($rows as $r) {
                    $a = $r->answers[$i] ?? null;
                    if (is_numeric($a)) {
                        $v = (int) $a;
                        if (array_key_exists($v, $counts)) {
                            $counts[$v]++;
                            $sum += $v;
                            $n++;
                        }
                    }
                }
                $entry['scale']    = $scale;
                $entry['counts']   = $counts;
                $entry['answered'] = $n;
                $entry['average']  = $n > 0 ? round($sum / $n, 1) : null;
            } elseif ($q['type'] === 'choice') {
                $counts = array_fill(0, count($q['options']), 0);
                $n      = 0;
                foreach ($rows as $r) {
                    $a = $r->answers[$i] ?? null;
                    if (is_numeric($a)) {
                        $idx = (int) $a;
                        if (array_key_exists($idx, $counts)) {
                            $counts[$idx]++;
                            $n++;
                        }
                    }
                }
                $entry['options']  = $q['options'];
                $entry['counts']   = $counts;
                $entry['answered'] = $n;
            } else { // text
                $texts = [];
                foreach ($rows as $r) {
                    $a = $r->answers[$i] ?? null;
                    if (is_string($a) && trim($a) !== '') {
                        $texts[] = trim($a);
                    }
                }
                $entry['texts'] = $texts;
            }

            $out[] = $entry;
        }

        return ['total' => $total, 'questions' => $out];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ANTI RE-SPAM + ÉTAT « a déjà répondu »
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * L'utilisateur courant a-t-il déjà répondu à CE sondage ? Sondage NOMMÉ : on
     * regarde sa réponse (clé user). Sondage ANONYME : aucune identité n'est stockée
     * dans la RÉPONSE (user_id NULL), donc on borne le re-spam par la PARTICIPATION
     * (table dédiée, robuste à la reconnexion) ET, en défense en profondeur, par le
     * drapeau de SESSION.
     */
    public static function hasResponded(LessonItem $item, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (self::isAnonymous($item)) {
            return self::hasParticipated($item, $user)
                || in_array($item->id, (array) session(self::SESSION_KEY, []), true);
        }

        return FeedbackResponse::where('lesson_item_id', $item->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * L'étudiant authentifié a-t-il déjà PARTICIPÉ à ce sondage ? Lit la table
     * {@see FeedbackParticipant} (le FAIT de répondre, jamais le contenu). Borne le
     * re-spam anonyme même après déconnexion/reconnexion (session régénérée).
     */
    public static function hasParticipated(LessonItem $item, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return FeedbackParticipant::where('lesson_item_id', $item->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Enregistre (idempotent) la PARTICIPATION de l'étudiant authentifié à ce sondage,
     * pour les soumissions NOMMÉES comme ANONYMES. N'enregistre AUCUNE réponse : seul
     * le couple (item, user) est tracé, donc l'anonymat des réponses est préservé.
     */
    public static function recordParticipation(LessonItem $item, User $user): void
    {
        FeedbackParticipant::firstOrCreate([
            'lesson_item_id' => $item->id,
            'user_id'        => $user->id,
        ]);
    }

    /**
     * Précharge en UNE requête les réponses NOMMÉES de l'utilisateur pour un lot
     * d'items « feedback » (anti N+1 ; pré-remplissage côté contrôleur, jamais dans la
     * vue). Les réponses ANONYMES (user_id NULL) sont naturellement exclues. Retourne
     * une collection indexée par lesson_item_id, à passer à {@see previousAnswers}.
     *
     * @param  iterable<int, LessonItem|int>  $items
     * @return \Illuminate\Support\Collection<int, FeedbackResponse>
     */
    public static function preloadUserResponses(iterable $items, ?User $user): \Illuminate\Support\Collection
    {
        if ($user === null) {
            return collect();
        }

        $itemIds = [];
        foreach ($items as $it) {
            $itemIds[] = (int) (is_object($it) ? $it->id : $it);
        }
        $itemIds = array_values(array_unique($itemIds));

        if ($itemIds === []) {
            return collect();
        }

        return FeedbackResponse::where('user_id', $user->id)
            ->whereIn('lesson_item_id', $itemIds)
            ->get(['lesson_item_id', 'answers'])
            ->keyBy('lesson_item_id');
    }

    /**
     * Réponses précédentes de l'utilisateur pour CET item (pré-remplissage d'un sondage
     * NOMMÉ et modifiable). ANONYME => toujours vide (aucune réponse n'est liée à une
     * identité). Si `$preloaded` est fourni il fait AUTORITÉ (aucune requête).
     *
     * @param  \Illuminate\Support\Collection<int, FeedbackResponse>|null  $preloaded
     * @return array<int|string, mixed>
     */
    public static function previousAnswers(LessonItem $item, ?User $user, $preloaded = null): array
    {
        if ($user === null || self::isAnonymous($item)) {
            return [];
        }

        if ($preloaded !== null) {
            $row = $preloaded->get($item->id);

            return $row ? (array) $row->answers : [];
        }

        $row = FeedbackResponse::where('lesson_item_id', $item->id)
            ->where('user_id', $user->id)
            ->first();

        return $row ? (array) $row->answers : [];
    }

    /** Marque (session) que le sondage anonyme courant a reçu une réponse de cette session. */
    public static function markAnsweredInSession(LessonItem $item): void
    {
        $answered   = (array) session(self::SESSION_KEY, []);
        $answered[] = $item->id;
        session([self::SESSION_KEY => array_values(array_unique($answered))]);
    }

    private static function truthy(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (bool) (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
    }
}
