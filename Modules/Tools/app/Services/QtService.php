<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Dictionary\Models\Term;

class QtService
{
    private static function normalize(string $s): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', Str::ascii($s)) ?: '');
    }

    private static function loadData(string $file): array
    {
        try {
            $path = function_exists('module_path') ? module_path('Tools', 'resources/data/'.$file) : null;
            if (! $path || ! is_file($path)) {
                return [];
            }
            $data = require $path;

            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function bank(): array
    {
        return Cache::remember('qt.bank', now()->addHours(24), fn () => self::loadData('qt-questions.php'));
    }

    private static function trueFalseBank(): array
    {
        return Cache::remember('qt.truefalse', now()->addHours(24), fn () => self::loadData('qt-truefalse.php'));
    }

    private static function shortAnswerBank(): array
    {
        return Cache::remember('qt.shortanswer', now()->addHours(24), fn () => self::loadData('qt-shortanswer.php'));
    }

    private static function glossaryMap(): array
    {
        if (! class_exists(Term::class)) {
            return [];
        }

        return Cache::remember('qt.glossary', now()->addHours(6), function () {
            $map = [];
            foreach (Term::where('is_published', true)->get() as $term) {
                $slug = $term->getTranslation('slug', 'fr_CA', false);
                $name = $term->getTranslation('name', 'fr_CA', false);
                if ($slug) {
                    $map[self::normalize($slug)] = $slug;
                    if ($name) {
                        $map[self::normalize($name)] = $slug;
                    }
                }
            }

            return $map;
        });
    }

    private static function ficheUrl(?string $term): ?string
    {
        if (! $term) {
            return null;
        }
        $map = self::glossaryMap();
        $key = self::normalize($term);

        return isset($map[$key]) ? route('dictionary.show', $map[$key]) : null;
    }

    /**
     * Pool de paires terme → définition courte (one_sentence_answer, repli definition),
     * pour les questions d'appariement. Définition nettoyée + terme masqué.
     */
    private static function matchingPool(): array
    {
        return Cache::remember('qt.matchpool', now()->addHours(6), function () {
            $out = [];
            try {
                if (! class_exists(Term::class)) {
                    return [];
                }
                foreach (Term::where('is_published', true)->get() as $t) {
                    $name = trim((string) $t->getTranslation('name', 'fr_CA', false));
                    if ($name === '') {
                        continue;
                    }
                    $src = trim((string) $t->getTranslation('one_sentence_answer', 'fr_CA', false));
                    if ($src === '') {
                        $src = trim((string) $t->getTranslation('definition', 'fr_CA', false));
                    }
                    if ($src === '') {
                        continue;
                    }
                    $def = self::cleanMatchDef($src, $name);
                    if (mb_strlen($def) < 25) {
                        continue;
                    }
                    $slug = (string) $t->getTranslation('slug', 'fr_CA', false);
                    if ($slug === '') {
                        continue;
                    }
                    $out[] = ['term' => $name, 'def' => $def, 'slug' => $slug];
                }
            } catch (\Throwable $e) {
                return [];
            }

            return $out;
        });
    }

    private static function maskName(string $text, string $name): string
    {
        if ($text === '' || $name === '') {
            return $text;
        }
        $out = str_ireplace($name, '…', $text);
        if ($out !== $text) {
            return $out;
        }
        if (stripos(Str::ascii($text), Str::ascii($name)) !== false) {
            $replaced = @preg_replace('/'.preg_quote($name, '/').'/iu', '…', $text);
            if (is_string($replaced)) {
                return $replaced;
            }
        }

        return $text;
    }

    private static function truncateSmart(string $text, int $limit): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > (int) floor($limit * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, ".,;:!? \t").'…';
    }

    /**
     * Transforme un one_sentence_answer en définition COURTE et LISIBLE pour l'appariement :
     * retire le préambule « [Article] <terme> est/désigne/… » pour ne garder que la définition
     * (repli : masque le terme), puis tronque proprement à la 1re phrase.
     */
    private static function cleanMatchDef(string $osa, string $name): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($osa)) ?? '');
        if ($text === '') {
            return '';
        }
        $verbs = 'est|sont|désigne|désignent|consiste à|consiste en|représente|représentent|correspond à|fait référence à|se réfère à|se définit comme|qualifie|décrit|décrivent|renvoie à'
            .'|regroupe|regroupent|permet|permettent|fournit|fournissent|vise à|visent à|crée|créent|utilise|utilisent|combine|combinent|repose sur|reposent sur|sert à|servent à|englobe|désigné comme|fait partie';
        $art = '(?:l[\'’]\s*|d[\'’]\s*|(?:le|la|les|un|une|des)\s+)';
        $pattern = '/^\s*(?:'.$art.')?'.preg_quote($name, '/').'(?:\s*\([^)]*\))?\s+(?:'.$verbs.')\s+/iu';
        $cleaned = preg_replace($pattern, '', $text, 1);
        if ($cleaned === null || $cleaned === $text) {
            // Repli : masque le terme (anti-spoiler) si le préambule n'a pu être retiré.
            $cleaned = str_ireplace([$name, Str::ascii($name)], '…', $text);
        }
        $cleaned = trim($cleaned);
        if ($cleaned !== '') {
            $cleaned = mb_strtoupper(mb_substr($cleaned, 0, 1)).mb_substr($cleaned, 1);
        }
        // Troncature lisible : garde la 1re phrase complète si > 150, sinon coupe au mot.
        if (mb_strlen($cleaned) > 150) {
            if (preg_match('/^.{30,150}?[.!?]/u', $cleaned, $m)) {
                $cleaned = $m[0];
            } else {
                $cut = mb_substr($cleaned, 0, 130);
                $sp = mb_strrpos($cut, ' ');
                $cleaned = ($sp !== false ? mb_substr($cut, 0, $sp) : $cut).'…';
            }
        }

        return trim($cleaned);
    }

    private static function pointsFromDifficulty(string $difficulty): int
    {
        $d = strtolower($difficulty);

        return $d === 'facile' ? 1 : ($d === 'moyen' ? 2 : 3);
    }

    private static function safeShuffle(array &$arr): void
    {
        if (count($arr) > 1) {
            shuffle($arr);
        }
    }

    /** Mélange les choix d'un QCM et renvoie [choix, nouvel index correct]. */
    private static function mixChoicesWithCorrect(array $choices, int $correct): array
    {
        $indexed = [];
        foreach ($choices as $i => $c) {
            $indexed[] = ['i' => $i, 'v' => $c];
        }
        self::safeShuffle($indexed);
        $newChoices = [];
        $newCorrect = 0;
        foreach ($indexed as $idx => $pair) {
            $newChoices[] = $pair['v'];
            if ((int) $pair['i'] === $correct) {
                $newCorrect = $idx;
            }
        }

        return [$newChoices, $newCorrect];
    }

    private static function makeQcmItem(array $q): array
    {
        $difficulty = (string) ($q['difficulty'] ?? 'moyen');
        $choices = array_values((array) ($q['choices'] ?? []));
        $correct = (int) ($q['correct'] ?? 0);
        if (count($choices) < 2) {
            $choices = ['A', 'B', 'C', 'D'];
            $correct = 0;
        } else {
            [$choices, $correct] = self::mixChoicesWithCorrect($choices, $correct);
        }

        return [
            'type' => 'qcm',
            'theme' => (string) ($q['theme'] ?? 'general'),
            'difficulty' => $difficulty,
            'question' => (string) ($q['question'] ?? ''),
            'choices' => $choices,
            'correct' => $correct,
            'explanation' => (string) ($q['explanation'] ?? ''),
            'points' => self::pointsFromDifficulty($difficulty),
            'fiche' => self::ficheUrl(isset($q['term']) ? (string) $q['term'] : null),
        ];
    }

    /** Tire jusqu'à $limit QCM (quotas par difficulté), sans doublon. */
    private static function pickQcm(int $limit, array $quotas): array
    {
        $bank = self::bank();
        if (empty($bank)) {
            return [];
        }
        $groups = ['facile' => [], 'moyen' => [], 'difficile' => []];
        $others = [];
        foreach ($bank as $q) {
            $d = strtolower((string) ($q['difficulty'] ?? ''));
            if (isset($groups[$d])) {
                $groups[$d][] = $q;
            } else {
                $others[] = $q;
            }
        }
        foreach ($groups as &$g) {
            self::safeShuffle($g);
        }
        unset($g);
        self::safeShuffle($others);

        $picked = [];
        foreach ($quotas as $d => $n) {
            if (! isset($groups[$d]) || (int) $n <= 0) {
                continue;
            }
            $take = array_splice($groups[$d], 0, (int) $n);
            $picked = array_merge($picked, $take);
        }

        $leftovers = array_merge($groups['facile'], $groups['moyen'], $groups['difficile'], $others);
        self::safeShuffle($leftovers);
        while (count($picked) < $limit && ! empty($leftovers)) {
            $picked[] = array_shift($leftovers);
        }

        self::safeShuffle($picked);
        $picked = array_slice($picked, 0, $limit);

        return array_map(fn ($q) => self::makeQcmItem(is_array($q) ? $q : []), $picked);
    }

    private static function buildVraiFauxItems(): array
    {
        $items = [];
        foreach (self::trueFalseBank() as $q) {
            if (! is_array($q)) {
                continue;
            }
            $choices = ['Vrai', 'Faux'];
            $correct = ((bool) ($q['answer'] ?? false)) ? 0 : 1;
            [$choices, $correct] = self::mixChoicesWithCorrect($choices, $correct);
            $items[] = [
                'type' => 'vraifaux',
                'theme' => (string) ($q['theme'] ?? 'general'),
                'difficulty' => (string) ($q['difficulty'] ?? 'facile'),
                'question' => (string) ($q['statement'] ?? ($q['question'] ?? '')),
                'choices' => $choices,
                'correct' => $correct,
                'explanation' => (string) ($q['explanation'] ?? ''),
                'points' => 1,
                'fiche' => self::ficheUrl(isset($q['term']) ? (string) $q['term'] : null),
            ];
        }

        return $items;
    }

    private static function buildShortItems(): array
    {
        $items = [];
        foreach (self::shortAnswerBank() as $q) {
            if (! is_array($q)) {
                continue;
            }
            $accepted = array_values(array_filter((array) ($q['accepted'] ?? []), fn ($v) => is_string($v) && $v !== ''));
            if (empty($accepted)) {
                continue;
            }
            $items[] = [
                'type' => 'court',
                'theme' => (string) ($q['theme'] ?? 'general'),
                'difficulty' => (string) ($q['difficulty'] ?? 'moyen'),
                'question' => (string) ($q['question'] ?? ''),
                'accepted' => $accepted,
                'display' => (string) ($q['display'] ?? ''),
                'explanation' => (string) ($q['explanation'] ?? ''),
                'points' => 2,
                'fiche' => self::ficheUrl(isset($q['term']) ? (string) $q['term'] : null),
            ];
        }

        return $items;
    }

    /** Construit une question d'appariement (4 paires) ou null si pool insuffisant. */
    private static function buildMatching(): ?array
    {
        $pool = self::matchingPool();
        if (count($pool) < 4) {
            return null;
        }
        self::safeShuffle($pool);
        $picked = array_slice($pool, 0, 4);

        $terms = array_map(fn ($p) => (string) ($p['term'] ?? ''), $picked);
        $defs = array_map(fn ($p) => (string) ($p['def'] ?? ''), $picked);

        $defsShuffled = $defs;
        self::safeShuffle($defsShuffled);

        $answer = [];
        foreach ($defs as $d) {
            $idx = array_search($d, $defsShuffled, true);
            $answer[] = ($idx === false) ? 0 : (int) $idx;
        }

        return [
            'type' => 'appariement',
            'theme' => 'ia',
            'difficulty' => 'moyen',
            'question' => 'Associe chaque terme à sa définition.',
            'terms' => $terms,
            'defs' => $defsShuffled,
            'answer' => $answer,
            'explanation' => 'Chaque terme correspond à une seule définition.',
            'points' => 3,
            'fiche' => null,
        ];
    }

    /**
     * Assemble un round mixte de 10 items : 7 QCM + 2 V/F + 1 appariement.
     * NB : le type « Réponse courte » (champ texte libre) est volontairement EXCLU des rounds
     * publics — sa correction par liste accepted[] risque des faux négatifs (variante/synonyme/
     * FR-EN non listés) sur un quiz viral où le score doit être incontestable. Tous les types
     * conservés sont FERMÉS (correction déterministe). Le code (buildShortItems) et la banque
     * (qt-shortanswer.php) restent DORMANTS et réactivables si une correction robuste est ajoutée.
     */
    private static function assembleRound(): array
    {
        $items = self::pickQcm(7, ['facile' => 3, 'moyen' => 2, 'difficile' => 2]);

        $vf = self::buildVraiFauxItems();
        self::safeShuffle($vf);
        $items = array_merge($items, array_slice($vf, 0, 2));

        $match = self::buildMatching();
        if ($match !== null) {
            $items[] = $match;
        }

        // Compléter à 10 avec des QCM si une source manquait.
        if (count($items) < 10) {
            $needed = 10 - count($items);
            $items = array_merge($items, self::pickQcm($needed, ['facile' => $needed, 'moyen' => 0, 'difficile' => 0]));
        }

        self::safeShuffle($items);

        return array_slice($items, 0, 10);
    }

    public static function newRound(): array
    {
        return self::assembleRound();
    }

    /**
     * « Défi du jour » : round MIXTE identique pour tous un jour donné (seed = numéro du jour).
     */
    public static function dailyRound(): array
    {
        $today = Carbon::now('America/Toronto')->startOfDay();
        $epoch = Carbon::create(2026, 6, 14, 0, 0, 0, 'America/Toronto');
        $number = (int) $epoch->diffInDays($today) + 1;

        return Cache::remember('qt.daily.'.$number, now()->addHours(26), function () use ($number) {
            mt_srand($number);
            $questions = self::assembleRound();
            mt_srand();

            return ['number' => $number, 'questions' => $questions];
        });
    }
}
