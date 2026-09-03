<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Str;

/**
 * Normalisation partagée du payload de composition d'une actualité (design doc "extension de
 * l'écran de composition des actualités", 2026-09-03, section 2.2) - jusqu'ici, chaque porte
 * d'écriture (Modules\News\Console\NewsApplyCommand pour l'agent, Modules\News\Http\Controllers\
 * Admin\NewsCompositionController pour l'humain) réécrivait sa propre copie des mêmes règles de
 * validation. Cette classe en devient la SOURCE UNIQUE : chaque méthode reçoit une valeur brute
 * et retourne un résultat structuré {ok, value, error} (ou {ok, entry, reason} pour
 * validateProofPair(), {accepted, rejected} pour un lot) - jamais un appel direct à une sortie
 * console ni une exception, jamais un enregistrement en base. Chaque appelant reste responsable
 * de sa propre présentation (NewsApplyCommand traduit en $this->error() + code de sortie non
 * nul, un contrôleur HTTP traduit en réponse JSON) - même patron déjà en place pour
 * EditorialProofNormalizer::verifyFactPair() et NewsArticle::publishReadinessCheck().
 *
 * Toutes les méthodes migrées ici reproduisent le comportement EXACT de leur origine
 * (Modules\News\Console\NewsApplyCommand, avant ce chantier) - même bornes, mêmes messages
 * d'erreur au mot près, même ordre de validation. Le déplacement ne change rien à ce que
 * `php artisan news:apply` accepte ou refuse.
 *
 * Frontière volontaire (design doc, section 2.2 "Ce qui n'est PAS extrait") : la vérification
 * d'une seule valeur contre une liste blanche déjà centralisée ailleurs (nature_original contre
 * NewsArticle::NATURE_ORIGINAL_VALUES, niveau_preuve contre NewsArticle::NIVEAU_PREUVE_VALUES)
 * reste une ligne `array_key_exists()` répétée à chaque appelant, jamais enveloppée ici - la
 * connaissance qui compte (la liste des valeurs permises) est déjà centralisée dans le modèle ;
 * envelopper la comparaison elle-même ajouterait une indirection sans réduire aucun risque de
 * divergence (CLAUDE.md, section « DRY et anti-sur-ingénierie »).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class CompositionPayloadNormalizer
{
    /**
     * Richesse v1.188.0 - sous-clés autorisées de composed_summary. Toute autre sous-clé fait
     * refuser tout le payload. Déplacée depuis NewsApplyCommand::ALLOWED_COMPOSED_SUMMARY_KEYS
     * (devenue publique : le contrôleur de l'écran de composition en aura besoin pour peupler
     * ses champs, et NewsApplyCommand::handle() la lit encore pour son propre message de
     * fusion « sous-clé(s) conservée(s) »).
     */
    public const ALLOWED_COMPOSED_SUMMARY_KEYS = ['hook', 'key_points', 'why_important', 'key_number', 'quote', 'angle_qc_ca', 'action_concrete', 'reperes_dates'];

    /**
     * Richesse v1.188.0 - borne par défaut d'une chaîne simple de composed_summary (hook,
     * why_important, key_number, angle_qc_ca, action_concrete). quote.text/quote.author, les
     * éléments de key_points et les champs de reperes_dates ont leurs propres bornes, plus
     * courtes, validées séparément. Déplacée depuis
     * NewsApplyCommand::COMPOSED_SUMMARY_STRING_MAX.
     */
    public const COMPOSED_SUMMARY_STRING_MAX = 600;

    /**
     * Migrée telle quelle depuis NewsApplyCommand::normalizeComposedSummary() (origine
     * :1638-1732) - huit sous-clés nullables (hook, key_points, why_important, key_number,
     * quote, angle_qc_ca, action_concrete, reperes_dates), toute sous-clé inconnue fait refuser
     * tout le payload, chaînes bornées à COMPOSED_SUMMARY_STRING_MAX sauf sous-structure munie de
     * sa propre borne. Le marqueur `composed: true` N'EST PAS ajouté ici - il l'est par
     * l'appelant (overlayComposedSummary() ci-dessous, ou directement par NewsApplyCommand pour
     * une fiche qui n'avait encore aucune composition), pour que cette méthode reste une simple
     * validation/normalisation sans connaître le contexte de stockage.
     *
     * $input est présumé être un tableau : l'appelant vérifie is_array() AVANT d'appeler cette
     * méthode (même frontière que validateProofPair() plus bas) - un payload dont
     * composed_summary n'est même pas un objet n'a pas de sens à faire remonter jusqu'ici.
     *
     * @return array{ok: bool, value: ?array, error: ?string}
     */
    public static function normalizeComposedSummary(array $input): array
    {
        $unknownKeys = array_diff(array_keys($input), self::ALLOWED_COMPOSED_SUMMARY_KEYS);
        if ($unknownKeys !== []) {
            return ['ok' => false, 'value' => null, 'error' => 'Clé(s) non autorisée(s) dans composed_summary : '.implode(', ', $unknownKeys).'. Clés permises : '.implode(', ', self::ALLOWED_COMPOSED_SUMMARY_KEYS).'.'];
        }

        $normalized = [];

        foreach (['hook', 'why_important', 'key_number', 'angle_qc_ca', 'action_concrete'] as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            // `null` explicite est le signal de retrait délibéré d'une sous-clé (voir
            // overlayComposedSummary() plus bas), distinct d'une sous-clé ABSENTE qui, elle,
            // n'entre jamais dans $normalized et ne touche à rien.
            if ($input[$key] === null) {
                $normalized[$key] = null;

                continue;
            }
            if (! is_string($input[$key])) {
                return ['ok' => false, 'value' => null, 'error' => "composed_summary.{$key} doit être une chaîne de caractères (ou null pour vider cette sous-clé)."];
            }
            if (mb_strlen($input[$key]) > self::COMPOSED_SUMMARY_STRING_MAX) {
                return ['ok' => false, 'value' => null, 'error' => "composed_summary.{$key} dépasse ".self::COMPOSED_SUMMARY_STRING_MAX.' caractères.'];
            }
            // Ces cinq sous-clés sont TOUJOURS de la prose composée par le site (accroche,
            // pourquoi ça compte, chiffre-clé, angle QC/CA, action concrète), jamais une
            // citation - `quote` (normalizeComposedQuote() plus bas) reste délibérément HORS de
            // cette boucle.
            $normalized[$key] = lv_strip_em_dash($input[$key]);
        }

        if (array_key_exists('key_points', $input)) {
            if ($input['key_points'] === null) {
                $normalized['key_points'] = null;
            } else {
                $points = self::normalizeComposedKeyPoints($input['key_points']);
                if (! $points['ok']) {
                    return ['ok' => false, 'value' => null, 'error' => $points['error']];
                }
                $normalized['key_points'] = $points['value'];
            }
        }

        if (array_key_exists('quote', $input)) {
            if ($input['quote'] === null) {
                $normalized['quote'] = null;
            } else {
                $quote = self::normalizeComposedQuote($input['quote']);
                if (! $quote['ok']) {
                    return ['ok' => false, 'value' => null, 'error' => $quote['error']];
                }
                $normalized['quote'] = $quote['value'];
            }
        }

        if (array_key_exists('reperes_dates', $input)) {
            if ($input['reperes_dates'] === null) {
                $normalized['reperes_dates'] = null;
            } else {
                $reperes = self::normalizeComposedReperesDates($input['reperes_dates']);
                if (! $reperes['ok']) {
                    return ['ok' => false, 'value' => null, 'error' => $reperes['error']];
                }
                $normalized['reperes_dates'] = $reperes['value'];
            }
        }

        return ['ok' => true, 'value' => $normalized, 'error' => null];
    }

    /**
     * Migrée telle quelle depuis NewsApplyCommand::normalizeComposedKeyPoints() (origine
     * :1780-1813) - au plus 5 puces, chacune au plus 300 caractères. Sous-fonction privée de
     * normalizeComposedSummary() ci-dessus, jamais appelée directement par un appelant externe.
     *
     * @return array{ok: bool, value: ?array, error: ?string}
     */
    private static function normalizeComposedKeyPoints(mixed $input): array
    {
        if (! is_array($input)) {
            return ['ok' => false, 'value' => null, 'error' => 'composed_summary.key_points doit être un tableau de chaînes.'];
        }

        if (count($input) > 5) {
            return ['ok' => false, 'value' => null, 'error' => 'composed_summary.key_points dépasse la limite de 5 puces.'];
        }

        $normalized = [];
        foreach ($input as $point) {
            if (! is_string($point)) {
                return ['ok' => false, 'value' => null, 'error' => 'Chaque élément de composed_summary.key_points doit être une chaîne de caractères.'];
            }
            if (mb_strlen($point) > 300) {
                return ['ok' => false, 'value' => null, 'error' => 'Un élément de composed_summary.key_points dépasse 300 caractères.'];
            }
            $normalized[] = lv_strip_em_dash($point);
        }

        return ['ok' => true, 'value' => $normalized, 'error' => null];
    }

    /**
     * Migrée telle quelle depuis NewsApplyCommand::normalizeComposedQuote() (origine
     * :1824-1869) - objet {text, author}, text obligatoire (une citation sans texte n'a pas de
     * sens), author facultatif. Sous-fonction privée de normalizeComposedSummary() ci-dessus.
     *
     * @return array{ok: bool, value: ?array{text: string, author?: string}, error: ?string}
     */
    private static function normalizeComposedQuote(mixed $input): array
    {
        if (! is_array($input)) {
            return ['ok' => false, 'value' => null, 'error' => 'composed_summary.quote doit être un objet {text, author}.'];
        }

        $allowedKeys = ['text', 'author'];
        $unknownKeys = array_diff(array_keys($input), $allowedKeys);
        if ($unknownKeys !== []) {
            return ['ok' => false, 'value' => null, 'error' => 'Clé(s) non autorisée(s) dans composed_summary.quote : '.implode(', ', $unknownKeys).'. Clés permises : '.implode(', ', $allowedKeys).'.'];
        }

        $text = $input['text'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            return ['ok' => false, 'value' => null, 'error' => 'composed_summary.quote.text est obligatoire (citation).'];
        }
        if (mb_strlen($text) > 400) {
            return ['ok' => false, 'value' => null, 'error' => 'composed_summary.quote.text dépasse 400 caractères.'];
        }

        $normalized = ['text' => $text];

        if (array_key_exists('author', $input)) {
            if (! is_string($input['author'])) {
                return ['ok' => false, 'value' => null, 'error' => 'composed_summary.quote.author doit être une chaîne de caractères.'];
            }
            if (mb_strlen($input['author']) > 120) {
                return ['ok' => false, 'value' => null, 'error' => 'composed_summary.quote.author dépasse 120 caractères.'];
            }
            $normalized['author'] = $input['author'];
        }

        return ['ok' => true, 'value' => $normalized, 'error' => null];
    }

    /**
     * Migrée telle quelle depuis NewsApplyCommand::normalizeComposedReperesDates() (origine
     * :1880-1933) - au plus 4 jalons {date, texte, url?}, juxtaposés jamais causaux. date/texte
     * obligatoires par jalon, url facultative mais doit être http/https valide si fournie - même
     * règle que normalizePrimarySources() plus bas. Sous-fonction privée de
     * normalizeComposedSummary() ci-dessus.
     *
     * @return array{ok: bool, value: ?array, error: ?string}
     */
    private static function normalizeComposedReperesDates(mixed $input): array
    {
        if (! is_array($input)) {
            return ['ok' => false, 'value' => null, 'error' => 'composed_summary.reperes_dates doit être un tableau.'];
        }

        if (count($input) > 4) {
            return ['ok' => false, 'value' => null, 'error' => 'composed_summary.reperes_dates dépasse la limite de 4 repères.'];
        }

        $normalized = [];
        foreach ($input as $repere) {
            if (! is_array($repere) || ! isset($repere['date'], $repere['texte'])
                || ! is_string($repere['date']) || ! is_string($repere['texte'])) {
                return ['ok' => false, 'value' => null, 'error' => 'Chaque repère de composed_summary.reperes_dates doit contenir date et texte (chaînes).'];
            }
            if (mb_strlen($repere['date']) > 40) {
                return ['ok' => false, 'value' => null, 'error' => 'composed_summary.reperes_dates : une date dépasse 40 caractères.'];
            }
            if (mb_strlen($repere['texte']) > 200) {
                return ['ok' => false, 'value' => null, 'error' => 'composed_summary.reperes_dates : un texte dépasse 200 caractères.'];
            }

            $entry = ['date' => lv_strip_em_dash($repere['date']), 'texte' => lv_strip_em_dash($repere['texte'])];

            if (array_key_exists('url', $repere)) {
                $url = is_string($repere['url']) ? trim($repere['url']) : '';
                if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                    return ['ok' => false, 'value' => null, 'error' => "composed_summary.reperes_dates : url invalide (http/https attendu) : « {$url} »."];
                }
                $entry['url'] = $url;
            }

            $normalized[] = $entry;
        }

        return ['ok' => true, 'value' => $normalized, 'error' => null];
    }

    /**
     * Migrée telle quelle depuis NewsApplyCommand::overlayComposedSummary() (origine
     * :1753-1770) - fonction PURE, aucun mode d'échec (contrairement aux autres méthodes de
     * cette classe, elle ne retourne donc pas {ok, value, error} mais directement le résultat).
     * Superpose $normalized (sortie de normalizeComposedSummary() ci-dessus, peut contenir des
     * valeurs `null` explicites) sur $existing sous-clé par sous-clé : une sous-clé PRÉSENTE
     * dans le payload réécrit $existing ; une valeur `null` explicite RETIRE la sous-clé de
     * $existing (effacement demandé, jamais déduit d'un silence) ; une sous-clé ABSENTE du
     * payload laisse $existing intact pour cette sous-clé (fusion, jamais un remplacement).
     * $existing = [] (fiche sans résumé composé) retombe naturellement sur l'ancien
     * comportement de remplacement intégral : rien à conserver, seules les sous-clés fournies
     * apparaissent.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    public static function overlayComposedSummary(array $existing, array $normalized): array
    {
        foreach (self::ALLOWED_COMPOSED_SUMMARY_KEYS as $subKey) {
            if (! array_key_exists($subKey, $normalized)) {
                continue;
            }
            if ($normalized[$subKey] === null) {
                unset($existing[$subKey]);

                continue;
            }
            $existing[$subKey] = $normalized[$subKey];
        }

        $existing['composed'] = true;

        return $existing;
    }

    /**
     * Migrée telle quelle depuis NewsApplyCommand::normalizePrimarySources() (origine
     * :1508-1554) - valide et normalise le tableau de sources primaires du payload. REMPLACE
     * intégralement la valeur existante (contrairement aux paires de preuve, accumulées) - même
     * sémantique côté appelant qu'avant ce déplacement. Borne à 10 sources : une fiche cite ses
     * sources primaires, elle n'en dresse pas un annuaire.
     *
     * $input est présumé être un tableau : l'appelant vérifie is_array() AVANT d'appeler cette
     * méthode, même frontière que les autres méthodes de cette classe.
     *
     * @return array{ok: bool, value: ?array, error: ?string}
     */
    public static function normalizePrimarySources(array $input): array
    {
        if (count($input) > 10) {
            return ['ok' => false, 'value' => null, 'error' => 'primary_sources dépasse la limite de 10 sources.'];
        }

        $normalized = [];

        foreach ($input as $source) {
            if (! is_array($source) || ! isset($source['label'], $source['url'])
                || ! is_string($source['label']) || ! is_string($source['url'])) {
                return ['ok' => false, 'value' => null, 'error' => 'Chaque source de primary_sources doit contenir label et url (chaînes).'];
            }

            $url = trim($source['url']);
            if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                return ['ok' => false, 'value' => null, 'error' => "URL de source primaire invalide (http/https attendu) : « {$url} »."];
            }

            $note = $source['note'] ?? null;
            if ($note !== null && ! is_string($note)) {
                return ['ok' => false, 'value' => null, 'error' => 'note de primary_sources doit être une chaîne de caractères si fournie.'];
            }

            $normalized[] = [
                'label' => $source['label'],
                'url' => $url,
                'note' => $note,
            ];
        }

        return ['ok' => true, 'value' => $normalized, 'error' => null];
    }

    /**
     * Migrée telle quelle depuis NewsApplyCommand::normalizeSlugsList() (origine :1348-1371) -
     * règle de FORME générique (liste de slugs bornée) déjà partagée avant ce déplacement par
     * quatre appelants (related_tool_slugs, related_tool_slugs_remove, related_article_slugs,
     * related_article_slugs_remove) - seul le plafond diffère selon le domaine (outils vs
     * articles de blogue), $maxCount reste donc un paramètre explicite.
     *
     * $input est présumé être un tableau : l'appelant vérifie is_array() AVANT d'appeler cette
     * méthode, même frontière que les autres méthodes de cette classe.
     *
     * @return array{ok: bool, value: ?array<int, string>, error: ?string}
     */
    public static function normalizeSlugsList(array $input, string $fieldName, int $maxCount): array
    {
        if (count($input) > $maxCount) {
            return ['ok' => false, 'value' => null, 'error' => "{$fieldName} dépasse la limite de {$maxCount} slug(s)."];
        }

        foreach ($input as $slug) {
            if (! is_string($slug) || trim($slug) === '' || mb_strlen($slug) > 120) {
                return ['ok' => false, 'value' => null, 'error' => "Chaque slug de {$fieldName} doit être une chaîne non vide de 120 caractères maximum."];
            }
        }

        return ['ok' => true, 'value' => array_values($input), 'error' => null];
    }

    /**
     * Décision centrale du service (design doc 2026-09-03, section 2.2) - fusionne le corps de
     * boucle de NewsApplyCommand::normalizeProofPairs() (traite un LOT) et de
     * NewsCompositionController::storeProofPair() (traite UNE SEULE paire) : la vérification de
     * forme, le type autorisé (fact/analysis/primary_fact), la revalidation
     * EditorialProofNormalizer::verifyFactPair() pour "fact" (avec le marqueur
     * source_verified: false quand le texte source est déjà purgé - todo #1984) et l'URL
     * source_url obligatoire et valide pour "primary_fact" ne vivent plus qu'à un seul endroit.
     *
     * $pair est présumé être un tableau : l'appelant vérifie is_array() AVANT d'appeler cette
     * méthode (un $pair qui n'est même pas un tableau n'a pas de sens à faire remonter jusqu'ici
     * - même frontière que les autres méthodes de cette classe).
     *
     * @return array{ok: bool, entry: ?array, reason: ?string}
     */
    public static function validateProofPair(string $sourceText, array $pair): array
    {
        if (! isset($pair['statement'], $pair['excerpt'], $pair['type'])
            || ! is_string($pair['statement']) || ! is_string($pair['excerpt']) || ! is_string($pair['type'])) {
            return ['ok' => false, 'entry' => null, 'reason' => 'doit contenir statement, excerpt et type (chaînes).'];
        }

        if (! in_array($pair['type'], ['fact', 'analysis', 'primary_fact'], true)) {
            return ['ok' => false, 'entry' => null, 'reason' => "type invalide : « {$pair['type']} » (attendu : fact, analysis ou primary_fact)."];
        }

        // null = type autre que "fact" (vérification sans objet) ; true/false sinon.
        $sourceVerifiee = null;

        if ($pair['type'] === 'fact') {
            $verdict = EditorialProofNormalizer::verifyFactPair($sourceText, $pair['excerpt']);

            if (! $verdict['accepted']) {
                return ['ok' => false, 'entry' => null, 'reason' => "extrait déclaré « fait » absent du texte source (sous-chaîne exacte attendue) : {$pair['excerpt']}"];
            }

            $sourceVerifiee = $verdict['source_verified'];
        }

        $entry = [
            'id' => (string) Str::uuid(),
            'statement' => $pair['statement'],
            'excerpt' => $pair['excerpt'],
            'type' => $pair['type'],
            'created_at' => now('America/Toronto')->toIso8601String(),
        ];

        if ($pair['type'] === 'primary_fact') {
            $sourceUrl = is_string($pair['source_url'] ?? null) ? trim($pair['source_url']) : '';
            if ($sourceUrl === '' || ! filter_var($sourceUrl, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $sourceUrl)) {
                return ['ok' => false, 'entry' => null, 'reason' => 'paire « primary_fact » sans URL de source primaire valide (http/https).'];
            }
            $entry['source_url'] = $sourceUrl;
        }

        if ($sourceVerifiee === false) {
            $entry['source_verified'] = false;
        }

        return ['ok' => true, 'entry' => $entry, 'reason' => null];
    }
}
