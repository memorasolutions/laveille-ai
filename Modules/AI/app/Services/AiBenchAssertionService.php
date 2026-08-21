<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

namespace Modules\AI\Services;

/**
 * ACTION : registre d'assertions déterministes du banc d'essai IA (commande ai:bench,
 * SPEC-BANC-ESSAI-IA, section 2). Chaque assertion est une fonction pure, sans appel
 * réseau ni second appel LLM (design doc 2026-08-21 : le juge LLM systématique est
 * explicitement écarté, coût/latence double pour les mêmes biais).
 * MCP: SELF (fonctions déterministes < 10 lignes chacune, aucune génération de contenu)
 * RAISON: un seul registre réutilisé par tous les cas gelés du banc - jamais de logique
 * d'assertion recopiée entre fixtures ou entre tâches.
 */
class AiBenchAssertionService
{
    /**
     * Marqueurs grossiers de détection de langue - suffisants pour un banc de mesure,
     * jamais pour une décision de conformité. Comptage sur mots isolés (espaces autour)
     * pour éviter les faux positifs sur des sous-chaînes.
     */
    private const FRENCH_ACCENTS = ['é', 'è', 'à', 'ç', 'ê', 'î', 'ô', 'û', 'ù', 'ï'];

    private const FRENCH_WORDS = [' le ', ' la ', ' les ', ' des ', ' une ', ' un ', ' est ', ' pour ', ' dans ', ' avec ', ' que ', ' qui '];

    private const ENGLISH_WORDS = [' the ', ' and ', ' of ', ' is ', ' for ', ' with ', ' that ', ' this ', ' are ', ' was '];

    private const REFUSAL_PATTERNS = [
        'i cannot', "i can't", 'as an ai', 'i am unable', "i'm sorry, but i can't",
        'je ne peux pas', "en tant qu'ia", 'désolé, je ne peux', 'incapable de répondre',
    ];

    /**
     * @param  array<string, mixed>  $assertion  {type: string, ...paramètres propres au type}
     * @return array{ok: bool, reason: ?string}
     */
    public function evaluate(array $assertion, string $content): array
    {
        $type = (string) ($assertion['type'] ?? '');

        return match ($type) {
            'json_valid' => $this->jsonValid($content),
            'json_has_keys' => $this->jsonHasKeys($content, (array) ($assertion['keys'] ?? [])),
            'length_between' => $this->lengthBetween($content, (int) ($assertion['min'] ?? 0), (int) ($assertion['max'] ?? PHP_INT_MAX)),
            'lang_fr' => $this->langFr($content),
            'lang_en' => $this->langEn($content),
            'no_refusal' => $this->noRefusal($content),
            'contains_pattern' => $this->containsPattern($content, (string) ($assertion['pattern'] ?? '')),
            default => ['ok' => false, 'reason' => "type d'assertion inconnu : {$type}"],
        };
    }

    /**
     * @return array{ok: bool, reason: ?string}
     */
    private function jsonValid(string $content): array
    {
        json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE
            ? ['ok' => true, 'reason' => null]
            : ['ok' => false, 'reason' => 'JSON invalide : '.json_last_error_msg()];
    }

    /**
     * @param  array<int, string>  $keys
     * @return array{ok: bool, reason: ?string}
     */
    private function jsonHasKeys(string $content, array $keys): array
    {
        $data = json_decode($content, true);
        if (! is_array($data)) {
            return ['ok' => false, 'reason' => 'JSON invalide, clés non vérifiables'];
        }

        $missing = array_values(array_filter($keys, fn (string $key): bool => ! array_key_exists($key, $data)));

        return $missing === []
            ? ['ok' => true, 'reason' => null]
            : ['ok' => false, 'reason' => 'clés manquantes : '.implode(', ', $missing)];
    }

    /**
     * @return array{ok: bool, reason: ?string}
     */
    private function lengthBetween(string $content, int $min, int $max): array
    {
        $length = mb_strlen(trim($content));

        return ($length >= $min && $length <= $max)
            ? ['ok' => true, 'reason' => null]
            : ['ok' => false, 'reason' => "longueur {$length} hors plage [{$min}-{$max}]"];
    }

    /**
     * @return array{ok: bool, reason: ?string}
     */
    private function langFr(string $content): array
    {
        $count = $this->countAccents($content) + $this->countMarkers($content, self::FRENCH_WORDS);

        return $count >= 2
            ? ['ok' => true, 'reason' => null]
            : ['ok' => false, 'reason' => 'aucun marqueur français détecté'];
    }

    /**
     * @return array{ok: bool, reason: ?string}
     */
    private function langEn(string $content): array
    {
        $count = $this->countMarkers($content, self::ENGLISH_WORDS);

        return $count >= 2
            ? ['ok' => true, 'reason' => null]
            : ['ok' => false, 'reason' => 'aucun marqueur anglais détecté'];
    }

    /**
     * @return array{ok: bool, reason: ?string}
     */
    private function noRefusal(string $content): array
    {
        $lower = mb_strtolower($content);
        foreach (self::REFUSAL_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return ['ok' => false, 'reason' => "motif de refus détecté : {$pattern}"];
            }
        }

        return ['ok' => true, 'reason' => null];
    }

    /**
     * @return array{ok: bool, reason: ?string}
     */
    private function containsPattern(string $content, string $pattern): array
    {
        if ($pattern === '' || @preg_match($pattern, '') === false) {
            return ['ok' => false, 'reason' => 'motif regex invalide ou vide'];
        }

        return preg_match($pattern, $content) === 1
            ? ['ok' => true, 'reason' => null]
            : ['ok' => false, 'reason' => "motif absent : {$pattern}"];
    }

    private function countAccents(string $content): int
    {
        $lower = mb_strtolower($content);
        $count = 0;
        foreach (self::FRENCH_ACCENTS as $accent) {
            $count += substr_count($lower, $accent);
        }

        return $count;
    }

    /**
     * @param  array<int, string>  $markers
     */
    private function countMarkers(string $content, array $markers): int
    {
        $lower = ' '.mb_strtolower($content).' ';
        $count = 0;
        foreach ($markers as $marker) {
            if (str_contains($lower, $marker)) {
                $count++;
            }
        }

        return $count;
    }
}
