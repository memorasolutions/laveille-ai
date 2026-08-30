<?php

declare(strict_types=1);

namespace Modules\News\Services;

/**
 * Fiche de preuve éditoriale (design doc "Actus - composition manuelle assistée", 2026-08-15,
 * section 7 / Phase B) : un extrait déclaré « fait » doit être une SOUS-CHAÎNE EXACTE du texte
 * source collé, ce qui rend la paraphrase mécaniquement impossible sur les passages déclarés
 * factuels. Cette classe centralise la SEULE normalisation autorisée avant comparaison
 * (espaces, apostrophes typographiques) - la même règle est reproduite côté client
 * (composition-builder.blade.php) pour un retour immédiat, mais SEULE cette normalisation
 * serveur fait foi ; le client n'est qu'un confort d'interface.
 *
 * Volontairement PAS de mise en minuscules, PAS de retrait d'accents/ponctuation : la sous-chaîne
 * doit rester une citation exacte, pas une comparaison approximative.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class EditorialProofNormalizer
{
    /**
     * Normalise un texte pour la comparaison de sous-chaîne : espaces (dont insécables et
     * multiples) réduits à un seul espace, apostrophes typographiques ramenées à l'apostrophe
     * droite, extrémités nettoyées.
     */
    public static function normalize(string $text): string
    {
        // Apostrophes typographiques (’ ‘) et guillemets simples courbes → apostrophe droite,
        // pour qu'un copier-coller depuis un traitement de texte ne fasse pas échouer une
        // citation par ailleurs exacte.
        $normalized = str_replace(['’', '‘'], "'", $text);

        // Espace insécable et espace fine insécable (courants dans la ponctuation française
        // collée depuis un article) → espace normal, avant réduction des espaces multiples.
        $normalized = str_replace(["\u{00A0}", "\u{202F}"], ' ', $normalized);

        // Toute suite d'espaces/tabulations/retours à la ligne → un seul espace.
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Vrai si $needle (après normalisation) est une sous-chaîne exacte de $haystack (après la
     * même normalisation). Une aiguille vide n'est jamais considérée trouvée : un extrait vide
     * ne prouve rien et ne doit jamais valider une paire déclarée « fait ».
     */
    public static function containsExact(string $haystack, string $needle): bool
    {
        $needleNormalized = self::normalize($needle);

        if ($needleNormalized === '') {
            return false;
        }

        return str_contains(self::normalize($haystack), $needleNormalized);
    }

    /**
     * ACTION : correctif d'un second point d'entrée touché par le même défaut que le todo #1984
     * déjà corrigé dans Modules\News\Console\NewsApplyCommand::normalizeProofPairs() (2026-08-29)
     * - une paire "fact" exige normalement que $excerpt soit une sous-chaîne exacte de
     * $sourceText (containsExact() ci-dessus, RÉUTILISÉE ici, jamais réécrite). MAIS
     * NewsArticle::publishAndPurgeSource() met internal_source_text à null AU MOMENT MÊME de la
     * publication : sur une fiche déjà publiée, $sourceText arrive donc systématiquement vide, et
     * containsExact() contre une chaîne vide ne peut JAMAIS réussir (aiguille non vide contre
     * botte de foin vide) - quelle que soit la légitimité de la citation. Ce n'est pas un échec
     * de validation, c'est un contrôle qui ne PEUT plus s'exécuter : quand $sourceText est vide,
     * la paire "fact" est ACCEPTÉE sans revalidation, mais signalée non vérifiée - jamais
     * acceptée en silence comme si elle avait été vérifiée, jamais refusée à tort non plus.
     *
     * Point UNIQUE de cette décision, appelé par Modules\News\Http\Controllers\Admin\
     * NewsCompositionController::storeProofPair() (2026-08-29, correctif de ce défaut). DRY
     * explicite : la règle "source absente -> acceptée et signalée" ne doit vivre qu'à un seul
     * endroit, même si NewsApplyCommand::normalizeProofPairs() garde pour l'instant sa propre
     * implémentation en ligne du même raisonnement (hors périmètre de ce correctif, qui touche le
     * chemin HTTP où le défaut a été repéré).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: todo #1984, même famille de défaut que son premier correctif.
     *
     * @return array{accepted: bool, source_verified: bool|null} accepted=false : la paire doit
     *         être refusée (422, motif "extrait absent de la source"). accepted=true avec
     *         source_verified=false : acceptée SANS vérification possible (source absente),
     *         l'appelant doit persister ce marqueur sur la paire. accepted=true avec
     *         source_verified=null : excerpt vérifié normalement, aucun marqueur à ajouter.
     */
    public static function verifyFactPair(string $sourceText, string $excerpt): array
    {
        if (trim($sourceText) === '') {
            return ['accepted' => true, 'source_verified' => false];
        }

        if (! self::containsExact($sourceText, $excerpt)) {
            return ['accepted' => false, 'source_verified' => null];
        }

        return ['accepted' => true, 'source_verified' => null];
    }
}
