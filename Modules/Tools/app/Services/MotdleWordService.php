<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Dictionary\Models\Term;

class MotdleWordService
{
    /**
     * Liste source des mots proposés (avec accents, majuscules).
     */
    public const WORDS = [
        'PUCE', 'ZONE', 'OCTET', 'JETON', 'NUAGE', 'IMAGE', 'INDEX', 'ROBOT', 'TEXTE', 'VIRUS',
        'BIAIS', 'OUTIL', 'ÉCRAN', 'PIXEL', 'DÉBIT', 'BLOGUE', 'BOUTON', 'CLIENT', 'COMPTE', 'CORPUS',
        'COUCHE', 'DONNÉE', 'ENTRÉE', 'ERREUR', 'ESPACE', 'FILTRE', 'INVITE', 'MODÈLE', 'MODULE', 'PILOTE',
        'PROFIL', 'PROJET', 'RÉSEAU', 'SCRIPT', 'SOURCE', 'VISUEL', 'BALISE', 'CODAGE', 'BINAIRE', 'CAPTEUR',
        'CHATBOT', 'CLAVIER', 'CONSOLE', 'DONNÉES', 'ÉDITEUR', 'FICHIER', 'HACHAGE', 'HAMEÇON', 'LECTEUR', 'MÉMOIRE',
        'MESSAGE', 'NEURONE', 'PORTAIL', 'REQUÊTE', 'ROUTEUR', 'SERVEUR', 'SESSION', 'SYSTÈME', 'TABLEAU', 'VECTEUR',
        'VERSION', 'COURRIEL', 'CRYPTAGE', 'ENCODAGE', 'LOGICIEL', 'PORTIQUE', 'SOLUTION', 'STOCKAGE',
    ];

    /**
     * Normalise un mot : supprime les accents et caractères non alphabétiques, puis met en majuscules.
     */
    private static function normalize(string $word): string
    {
        return strtoupper(preg_replace('/[^A-Z]/', '', strtoupper(Str::ascii($word))) ?: '');
    }

    /**
     * Retourne le pool de mots valides pour Motdle, mis en cache 24h.
     *
     * @return array<int, array{display: string, answer: string}>
     */
    public static function pool(): array
    {
        return Cache::remember('motdle.pool', now()->addHours(24), function () {
            $validWords = [];

            foreach (self::WORDS as $original) {
                $normalized = self::normalize($original);
                $length = strlen($normalized);

                if ($length >= 4 && $length <= 8) {
                    $validWords[$normalized] = [
                        'display' => $original,
                        'answer' => $normalized,
                    ];
                }
            }

            $pool = array_values($validWords);

            // Ordre pseudo-aléatoire mais STABLE (déterministe) via md5.
            usort($pool, static function ($a, $b) {
                return strcmp(md5($a['answer']), md5($b['answer']));
            });

            return $pool;
        });
    }

    /**
     * Construit une carte (normalisé → slug + définition) à partir du glossaire.
     *
     * @return array<string, array{slug: string, def: string}>
     */
    private static function glossaryMap(): array
    {
        if (! class_exists(Term::class)) {
            return [];
        }

        return Cache::remember('motdle.glossary_map', now()->addHours(6), function () {
            $map = [];

            foreach (Term::where('is_published', true)->get() as $term) {
                $nameFr = $term->getTranslation('name', 'fr_CA', false);
                if (! $nameFr) {
                    continue;
                }

                $normalized = self::normalize($nameFr);
                if (strlen($normalized) < 4 || strlen($normalized) > 8) {
                    continue;
                }

                $oneSentence = $term->getTranslation('one_sentence_answer', 'fr_CA', false);
                $definition = $term->getTranslation('definition', 'fr_CA', false);
                $defText = $oneSentence ?: $definition;
                $def = $defText ? Str::limit(strip_tags((string) $defText), 140) : '';

                $map[$normalized] = [
                    'slug' => $term->getTranslation('slug', 'fr_CA', false) ?: Str::slug($nameFr),
                    'def' => $def,
                ];
            }

            return $map;
        });
    }

    /**
     * Retourne le mot du jour (ou d'un jour donné).
     *
     * @return array{display: string, answer: string, length: int, first: string, day: int, glossary_slug: string|null, glossary_def: string|null}
     */
    public static function today(?int $dayNumber = null): array
    {
        $day = $dayNumber ?? (int) floor(now('America/Toronto')->timestamp / 86400);
        $pool = self::pool();
        $wordEntry = $pool[$day % count($pool)];
        $glossaryMap = self::glossaryMap();
        $glossaryData = $glossaryMap[$wordEntry['answer']] ?? null;

        return [
            'display' => $wordEntry['display'],
            'answer' => $wordEntry['answer'],
            'length' => strlen($wordEntry['answer']),
            'first' => substr($wordEntry['answer'], 0, 1),
            'day' => $day,
            'glossary_slug' => $glossaryData['slug'] ?? null,
            'glossary_def' => $glossaryData['def'] ?? null,
        ];
    }
}
