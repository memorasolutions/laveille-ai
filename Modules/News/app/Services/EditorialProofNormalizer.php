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
}
