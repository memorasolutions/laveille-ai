<?php

declare(strict_types=1);

namespace Modules\Core\Concerns;

/**
 * Helpers PARTAGÉS pour générer les contenus de partage admin (superadmin) :
 * prompt « NotebookLM Infographie », post réseaux sociaux, hashtags, nettoyage des liens.
 * Évite la duplication entre News, Dictionary, Directory, Blog (consigne zéro-duplication).
 *
 * Le modèle qui l'utilise implémente sa propre méthode publique adminShareContents(): array
 * en s'appuyant sur ces helpers.
 */
trait HasAdminShareContents
{
    /**
     * Assemble le prompt « NotebookLM Infographie » : lien de section + consigne de
     * vulgarisation propre au type + bloc Langue/Design/Hiérarchie commun (fixe).
     */
    protected function infographiePrompt(string $sectionUrl, string $consigneVulgarisation): string
    {
        return "Lien à mettre dans l'infographie en bas au centre de façon apparente: {$sectionUrl}\n\n"
            . "Langue : français québécois, tutoiement, ton conversationnel et accessible. Écris comme une vraie personne, pas comme une IA. Aucune majuscule à l'américaine (mais garder les majuscules des acronymes et en début de phrase). Pas de tiret cadratin.\n\n"
            . trim($consigneVulgarisation) . "\n\n"
            . "Design : fond clair, style moderne et coloré, icônes simples. Bleu foncé pour les éléments importants, accents jaune ou orange pour les faits marquants. Beaucoup d'espace négatif.\n\n"
            . "Hiérarchie : message principal en gros, détails en plus petit. Chaque section doit donner envie de lire la suite. Visuel chaleureux, jamais corporatif.";
    }

    /**
     * Construit un post réseaux sociaux natif (sans lien externe) : hook + points + CTA + hashtags.
     */
    protected function buildSocialPost(string $hook, array $points, string $cta, array $hashtags): string
    {
        $body = '';
        foreach (array_slice($points, 0, 3) as $pt) {
            $pt = trim((string) $pt);
            if ($pt !== '') {
                $body .= '→ ' . mb_substr($pt, 0, 140) . "\n";
            }
        }

        return trim($hook) . "\n\n" . rtrim($body) . "\n\n" . trim($cta)
            . "\n\nPlus de contenu IA, en français, sur LaVeille AI\n\n"
            . implode(' ', array_filter($hashtags));
    }

    /**
     * Retire toutes les URLs http(s) d'un texte (les liens n'ont pas leur place dans NotebookLM / posts).
     */
    protected function stripLinks(string $text): string
    {
        $text = (string) preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $text); // espaces insécables -> espace normal
        $text = (string) preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $text); // [texte](url) -> texte
        $text = (string) preg_replace('#https?\s*:\s*//\S+#i', '', $text);     // URLs nues (gère « https :// » avec espaces)

        return trim($text);
    }

    /**
     * Normalise une catégorie/tag en hashtag CamelCase (préserve la casse des acronymes, ex. « IA générative » → IAGenerative).
     */
    protected function normalizeShareHashtag(string $tag): string
    {
        $t = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $tag);
        $t = (string) preg_replace('/[^a-zA-Z0-9\s]/', '', $t);
        $words = array_filter(preg_split('/\s+/', trim($t)) ?: []);

        return implode('', array_map('ucfirst', $words));
    }
}
