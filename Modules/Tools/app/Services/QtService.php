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

    private static function bank(): array
    {
        return Cache::remember('qt.bank', now()->addHours(24), function () {
            return require module_path('Tools', 'resources/data/qt-questions.php');
        });
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
     * Construit une partie de 10 questions : tirage par quota de difficulté,
     * mélange des questions ET des choix, points + lien fiche glossaire.
     */
    public static function newRound(): array
    {
        $bank = self::bank();

        $grouped = ['facile' => [], 'moyen' => [], 'difficile' => []];
        foreach ($bank as $item) {
            if (isset($grouped[$item['difficulty']])) {
                $grouped[$item['difficulty']][] = $item;
            }
        }

        $quotas = ['facile' => 4, 'moyen' => 3, 'difficile' => 3];
        $selected = [];
        $used = [];

        foreach ($quotas as $diff => $quota) {
            $pool = $grouped[$diff];
            shuffle($pool);
            foreach (array_slice($pool, 0, $quota) as $item) {
                $selected[] = $item;
                $used[$item['question']] = true;
            }
        }

        // Compléter à 10 si une catégorie manquait.
        if (count($selected) < 10) {
            $rest = array_values(array_filter($bank, fn ($it) => ! isset($used[$it['question']])));
            shuffle($rest);
            foreach (array_slice($rest, 0, 10 - count($selected)) as $item) {
                $selected[] = $item;
            }
        }

        $selected = array_slice($selected, 0, 10);
        shuffle($selected);

        $pointsMap = ['facile' => 1, 'moyen' => 2, 'difficile' => 3];
        $output = [];

        foreach ($selected as $q) {
            $pairs = [];
            foreach ($q['choices'] as $i => $choice) {
                $pairs[] = ['t' => $choice, 'ok' => ($i === $q['correct'])];
            }
            shuffle($pairs);
            $correct = 0;
            foreach ($pairs as $i => $p) {
                if ($p['ok']) {
                    $correct = $i;
                    break;
                }
            }

            $output[] = [
                'theme' => $q['theme'],
                'difficulty' => $q['difficulty'],
                'question' => $q['question'],
                'choices' => array_column($pairs, 't'),
                'correct' => $correct,
                'explanation' => $q['explanation'],
                'points' => $pointsMap[$q['difficulty']],
                'fiche' => self::ficheUrl($q['term'] ?? null),
            ];
        }

        return $output;
    }

    /**
     * « Défi du jour » : les 10 MÊMES questions (et le même ordre de choix) pour tous
     * les joueurs un jour donné, de façon déterministe (seed = numéro du jour).
     * Retourne ['number' => N, 'questions' => [...]].
     */
    public static function dailyRound(): array
    {
        $today = Carbon::now('America/Toronto')->startOfDay();
        $epoch = Carbon::create(2026, 6, 14, 0, 0, 0, 'America/Toronto');
        $number = (int) $epoch->diffInDays($today) + 1;

        return Cache::remember('qt.daily.'.$number, now()->addHours(26), function () use ($number) {
            mt_srand($number);

            $bank = self::bank();
            $grouped = ['facile' => [], 'moyen' => [], 'difficile' => []];
            foreach ($bank as $item) {
                if (isset($grouped[$item['difficulty']])) {
                    $grouped[$item['difficulty']][] = $item;
                }
            }
            $quotas = ['facile' => 4, 'moyen' => 3, 'difficile' => 3];
            $selected = [];
            $used = [];
            foreach ($quotas as $diff => $quota) {
                $pool = $grouped[$diff];
                shuffle($pool);
                foreach (array_slice($pool, 0, $quota) as $item) {
                    $selected[] = $item;
                    $used[$item['question']] = true;
                }
            }
            if (count($selected) < 10) {
                $rest = array_values(array_filter($bank, fn ($it) => ! isset($used[$it['question']])));
                shuffle($rest);
                foreach (array_slice($rest, 0, 10 - count($selected)) as $item) {
                    $selected[] = $item;
                }
            }
            $selected = array_slice($selected, 0, 10);
            shuffle($selected);
            $pointsMap = ['facile' => 1, 'moyen' => 2, 'difficile' => 3];
            $output = [];
            foreach ($selected as $q) {
                $pairs = [];
                foreach ($q['choices'] as $i => $choice) {
                    $pairs[] = ['t' => $choice, 'ok' => ($i === $q['correct'])];
                }
                shuffle($pairs);
                $correct = 0;
                foreach ($pairs as $i => $p) {
                    if ($p['ok']) {
                        $correct = $i;
                        break;
                    }
                }
                $output[] = [
                    'theme' => $q['theme'],
                    'difficulty' => $q['difficulty'],
                    'question' => $q['question'],
                    'choices' => array_column($pairs, 't'),
                    'correct' => $correct,
                    'explanation' => $q['explanation'],
                    'points' => $pointsMap[$q['difficulty']],
                    'fiche' => self::ficheUrl($q['term'] ?? null),
                ];
            }

            mt_srand();

            return ['number' => $number, 'questions' => $output];
        });
    }
}
