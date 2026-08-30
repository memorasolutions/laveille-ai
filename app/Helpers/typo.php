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
 * NOTE : le retrait du tiret cadratin (—) vit dans sa PROPRE fonction, lv_strip_em_dash()
 * plus bas dans ce même fichier (pas ici) - une citation verbatim peut légitimement porter
 * un cadratin qu'il ne faut jamais retirer, alors que les règles ci-dessous s'appliquent sans
 * distinction à tout texte visiteur. Deux connaissances distinctes, deux fonctions.
 *
 * Préservation :
 *   - HTML : segmentation à 3 voies — balises (<...>) / entités HTML
 *     (`&rsquo;`, `&#039;`, `&#x27;`, `&amp;`, `&nbsp;`, etc.) / texte pur.
 *     Seul le texte pur est typographié. Les balises ET les entités sont
 *     recopiées telles quelles. Sans cette protection, la règle NBSP-avant-`;`
 *     matcherait le `;` final d'une entité (ex. `&rsquo;` → `&rsquo ;`) et la
 *     casserait (le navigateur ne la décode plus). Les URLs `https://...?q=1`
 *     restent également INTOUCHÉES (déjà protégées par la segmentation balises).
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
     * Segmente HTML en balises / entités HTML / texte, et applique les
     * règles de typographie sur le texte pur uniquement.
     *
     * Segmentation à 3 voies (dans cet ordre de priorité) :
     *   1. Balises `<...>`                      → recopiées telles quelles.
     *   2. Entités HTML `&nom;` / `&#123;` / `&#x1F;` → recopiées telles
     *      quelles (jamais de NBSP inséré avant leur `;` final, sinon
     *      l'entité est cassée et s'affiche en clair au lieu d'être décodée).
     *   3. Tout le reste (texte pur)             → typographié.
     */
    function lv_typo_fr_apply_to_html(string $text): string
    {
        // Capture les balises ET les entités HTML valides comme délimiteurs
        // protégés ; tout le reste retombe dans les segments "texte".
        $parts = preg_split(
            '/(<[^>]*>|&#x[0-9a-fA-F]+;|&#[0-9]+;|&[a-zA-Z][a-zA-Z0-9]*;)/u',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($parts === false) {
            return $text;
        }

        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '<' || $part[0] === '&') {
                // Balise ou entité HTML protégée : recopiée sans modification.
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

if (! function_exists('lv_strip_em_dash')) {
    /**
     * Retire le tiret cadratin (—, U+2014) d'un texte de prose FRANÇAISE composée par le site
     * (accroche, « pourquoi ça compte », action concrète, etc.) — règle CLAUDE.md #10 : « jamais
     * de tiret cadratin (utilise le trait d'union - ou le point-virgule) ».
     *
     * JAMAIS À BRANCHER SUR UNE CITATION VERBATIM (composed_summary.quote,
     * editorial_proof_pairs, original_post) : un cadratin issu d'une source anglophone dans UNE
     * CITATION EXACTE doit rester intact — le retirer falsifierait la citation. Cette fonction ne
     * distingue pas une citation d'une prose : c'est à l'appelant de ne jamais la brancher sur un
     * champ de citation (voir NewsApplyCommand::normalizeComposedSummary(), qui l'applique à
     * hook/why_important/key_number/angle_qc_ca/action_concrete/key_points/reperes_dates, jamais
     * à quote).
     *
     * Substitution caractère pour caractère, sans jugement sémantique (contrairement au
     * rattrapage manuel v1.233.1 sur le code existant, qui avait choisi chaque remplacement au
     * cas par cas selon le sens — voir CHANGELOG) : seul le cadratin est remplacé par un trait
     * d'union, tout espacement déjà présent autour (ou son absence) reste inchangé de part et
     * d'autre. Idempotent. Ne touche ni au trait d'union simple ni au tiret demi-cadratin
     * (–, U+2013).
     *
     * Usage : lv_strip_em_dash($text) avant écriture en base, sur la prose composée uniquement.
     */
    function lv_strip_em_dash(?string $text): string
    {
        if ($text === null || $text === '') {
            return $text ?? '';
        }

        return str_replace('—', '-', $text);
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
