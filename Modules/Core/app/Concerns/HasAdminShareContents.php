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
     * Post réseaux sociaux « 2026 » (best practices juin 2026) : hook curiosity-gap +
     * « En clair : » (définition sans jargon) + « 👉 » (fait/intérêt) + CTA conversationnel + hashtags.
     * Scannable, 1 idée, ton complice, AUCUN lien, AUCUNE signature promo. Blocs vides ignorés.
     */
    protected function buildEngagingSocialPost(string $hook, string $plainDef, string $interest, string $cta, array $hashtags, string $bonus = ''): string
    {
        $hook = trim($hook);
        $plainDef = trim($plainDef);
        $interest = trim($interest);
        $cta = trim($cta);
        $bonus = trim($bonus);
        $hashtags = array_filter(array_map('trim', $hashtags));

        $parts = [$hook];
        if ($plainDef !== '') {
            $parts[] = "En clair : {$plainDef}";
        }
        if ($interest !== '') {
            $parts[] = "👉 {$interest}";
        }
        if ($bonus !== '') {
            $parts[] = $bonus;
        }
        $parts[] = $cta;

        $post = implode("\n\n", $parts);
        if ($hashtags !== []) {
            $post .= "\n\n" . implode(' ', $hashtags);
        }

        return trim($post);
    }

    /**
     * Tronque proprement un texte en respectant les phrases et les mots (pour les posts sociaux).
     */
    protected function smartTrim(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $slice = mb_substr($text, 0, $max);
        $minPos = (int) ($max * 0.5);

        // Chercher la fin d'une phrase complète
        $lastDot = mb_strrpos($slice, '.');
        $lastExcl = mb_strrpos($slice, '!');
        $lastQues = mb_strrpos($slice, '?');

        $sentenceEnd = false;
        foreach ([$lastDot, $lastExcl, $lastQues] as $pos) {
            if ($pos !== false && ($sentenceEnd === false || $pos > $sentenceEnd)) {
                $sentenceEnd = $pos;
            }
        }

        if ($sentenceEnd !== false && $sentenceEnd >= $minPos) {
            return rtrim(mb_substr($text, 0, $sentenceEnd + 1));
        }

        // Sinon, couper au dernier espace
        $lastSpace = mb_strrpos($slice, ' ');
        if ($lastSpace !== false) {
            $truncated = mb_substr($text, 0, $lastSpace);

            return rtrim($truncated, " ,;:-") . '…';
        }

        return rtrim($slice) . '…';
    }

    /**
     * Vérifie si deux textes sont suffisamment similaires après normalisation (anti-redondance).
     */
    protected function textsAreSimilar(string $a, string $b, int $threshold = 65): bool
    {
        $normalize = function (string $text): string {
            $text = mb_strtolower($text, 'UTF-8');
            $text = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            $text = (string) preg_replace('/[^a-z0-9\s]/', ' ', $text);
            $text = (string) preg_replace('/\s+/', ' ', $text);

            return trim($text);
        };

        $aNorm = $normalize($a);
        $bNorm = $normalize($b);

        if ($aNorm === '' || $bNorm === '') {
            return false;
        }

        if ((strlen($aNorm) >= 20 && str_starts_with($bNorm, $aNorm)) ||
            (strlen($bNorm) >= 20 && str_starts_with($aNorm, $bNorm))) {
            return true;
        }

        similar_text($aNorm, $bNorm, $pct);

        return $pct >= $threshold;
    }

    /**
     * Retire toutes les URLs http(s) d'un texte (les liens n'ont pas leur place dans NotebookLM / posts).
     */
    protected function stripLinks(string $text): string
    {
        $text = (string) preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $text); // espaces insécables -> espace normal
        $text = (string) preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $text); // [texte](url) -> texte
        $text = (string) preg_replace('#https?\s*:\s*//[^\s)]+#i', '', $text); // URLs nues (gère « https :// » ; s'arrête avant « ) » pour ne pas manger la parenthèse fermante)
        $text = (string) preg_replace('/\(\s*\)/', '', $text);                  // parenthèses vides résiduelles « ( ) » après retrait d'URL
        $text = (string) preg_replace('/[ \t]{2,}/', ' ', $text);              // espaces multiples -> un seul
        $text = (string) preg_replace('/\s+([.,…])/u', '$1', $text);           // espace parasite avant . , … -> collé (en français : ; ! ? gardent leur espace)

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
