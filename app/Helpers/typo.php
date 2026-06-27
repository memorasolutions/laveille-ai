<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Helper typographie française — NBSP (U+00A0) avant ponctuation double FR
 * et entre chiffre et unité.
 *
 * Règles appliquées (idempotentes) :
 *   1. NBSP avant : ? ! : ; » (ponctuation double FR)
 *   2. NBSP après : « (guillemet ouvrant FR)
 *   3. NBSP entre chiffre et unité : 25 % | 4 € | 35 M$ | 20 M€ | 200 k€ | 21 °C
 *
 * Préservation :
 *   - HTML : segmentation balises (<...>) / texte. Seul le texte hors-balise
 *     est typographié. Les URLs `https://...?q=1` sont donc INTOUCHÉES.
 *   - JSON : si le payload commence par `{` ou `[` et parse en JSON valide,
 *     on itère récursivement sur les valeurs string et on ré-encode. Évite
 *     de casser les colonnes spatie/laravel-translatable (`{"fr_CA":"..."}`).
 *
 * Usage :
 *   - PHP : lv_typo_fr($text) ou Str::typoFr($text)
 *   - Blade : @typo($text) ou {!! lv_typo_fr($text) !!}
 *   - Console : php artisan typo:apply-fr --dry
 */

if (! function_exists('lv_typo_fr_apply_rules')) {
    /**
     * Applique les règles sur un fragment de texte brut (sans HTML).
     */
    function lv_typo_fr_apply_rules(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $nbsp = "\u{00A0}";

        // 1) Avant ponctuation double FR : ? ! : ; »
        $text = preg_replace(
            '/(\S)[ \x{00A0}]?([?!:;»])/u',
            '$1' . $nbsp . '$2',
            $text
        ) ?? $text;

        // 2) Après guillemet ouvrant « (espace insécable)
        $text = preg_replace(
            '/(«)[ \x{00A0}]?(\S)/u',
            '$1' . $nbsp . '$2',
            $text
        ) ?? $text;

        // 3) Entre chiffre et unité : %, $, €, °C, M$, M€, k€, k$
        //    L'ordre des alternances importe (longues en premier).
        $text = preg_replace(
            '/(\d+)[ \x{00A0}]?(M\$|M€|k€|k\$|°C|%|€|\$)(?=\b|[^A-Za-z0-9]|$)/u',
            '$1' . $nbsp . '$2',
            $text
        ) ?? $text;

        return $text;
    }
}

if (! function_exists('lv_typo_fr_apply_to_html')) {
    /**
     * Segmente HTML en balises/texte et applique les règles sur le texte uniquement.
     */
    function lv_typo_fr_apply_to_html(string $text): string
    {
        // Segmente en tags HTML (`<...>`) vs texte. Tags laissés intacts.
        $parts = preg_split('/(<[^>]*>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $text;
        }

        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '<') {
                $out .= $part;

                continue;
            }
            $out .= lv_typo_fr_apply_rules($part);
        }

        return $out;
    }
}

if (! function_exists('lv_typo_fr')) {
    /**
     * Applique les règles de typographie française au texte donné.
     *
     * Idempotent. Préserve URLs, balises HTML et colonnes JSON
     * (spatie/laravel-translatable).
     */
    function lv_typo_fr(?string $text): string
    {
        if ($text === null || $text === '') {
            return $text ?? '';
        }

        // Détection JSON : si le payload ressemble à un objet/array JSON
        // (commence par { ou [ après trim), on tente un decode/encode pour
        // ne typographier que les valeurs string. Évite de casser
        // `{"fr_CA":"..."}` (Laravel translatable).
        $trim = ltrim($text);
        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $walked = lv_typo_fr_walk($decoded);

                return (string) json_encode($walked, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return lv_typo_fr_apply_to_html($text);
    }
}

if (! function_exists('lv_typo_fr_walk')) {
    /**
     * Walk récursif sur structure décodée JSON. Applique typo sur strings.
     *
     * @return mixed
     */
    function lv_typo_fr_walk(mixed $value): mixed
    {
        if (is_string($value)) {
            return lv_typo_fr_apply_to_html($value);
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = lv_typo_fr_walk($v);
            }

            return $out;
        }

        return $value;
    }
}
