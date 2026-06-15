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
     * vulgarisation propre au type + blocs Langue/Structure/Design/Accessibilité/Hiérarchie
     * (best practices juin 2026 : structure narrative, data storytelling, contraste AA, format vertical).
     */
    protected function infographiePrompt(string $sectionUrl, string $consigneVulgarisation): string
    {
        return implode("\n\n", [
            "Lien à mettre dans l'infographie en bas au centre de façon apparente: " . $sectionUrl,
            "Langue : français québécois, tutoiement, ton conversationnel et accessible. Écris comme une vraie personne, pas comme une IA. Aucune majuscule à l'américaine (mais garder les majuscules des acronymes et en début de phrase). Pas de tiret cadratin.",
            trim($consigneVulgarisation),
            "Structure : un seul message principal, puis 3 à 5 sections qui s'enchaînent logiquement. Mets le chiffre ou le fait le plus marquant très en évidence (data storytelling), sans surcharger.",
            "Design : fond clair, style moderne et coloré, icônes simples. Bleu foncé pour les éléments importants, accents jaune ou orange pour les faits marquants. Beaucoup d'espace négatif. Format vertical (idéal mobile et réseaux sociaux).",
            "Accessibilité : contraste élevé et texte lisible par tous ; n'encode jamais une information uniquement par la couleur (ajoute une icône, un libellé ou une forme).",
            "Hiérarchie : message principal en gros, détails en plus petit. Chaque section doit donner envie de lire la suite. Visuel chaleureux, jamais corporatif.",
        ]);
    }

    /**
     * Assemble le prompt « NotebookLM Diapositives » (Slide Deck) : consigne/objectif propre au
     * type + structure pédagogique fixe (best practices juin 2026 : 1 idée + 1 « à retenir » par
     * diapo, titres-phrases, ≤4 puces, plan d'abord puis deck) + bloc Références/marque
     * (pied de page « La veille de Stef — laveille.ai », micro-sources, lien de section sur la diapo finale).
     */
    protected function slidesPrompt(string $sectionUrl, string $consigne): string
    {
        return implode("\n\n", [
            "Crée un jeu de diapositives (Slide Deck) clair et pédagogique à partir UNIQUEMENT de cette source.",
            trim($consigne),
            "Langue : français québécois, tutoiement, ton d'une vraie personne (pas une IA). Garde les majuscules des acronymes et en début de phrase. Pas de tiret cadratin.",
            "Structure (8 à 12 diapositives) :\n1. Titre accrocheur + pourquoi ça te concerne\n2. Ce que tu vas comprendre, en une phrase\n3. Le concept clé, expliqué simplement\n4 et suivantes. Le cœur du sujet, une seule idée par diapo, du plus simple au plus nuancé, avec un exemple ou une analogie quand c'est abstrait\nAvant-dernière. Récap des points à retenir\nDernière. La phrase à retenir + invite à visiter La veille de Stef, avec le lien " . $sectionUrl . " et l'adresse laveille.ai affichés en clair",
            "Règles par diapositive :\n- Une seule idée et un seul message à retenir par diapo.\n- Le titre est une phrase qui dit le point (ex. « L'IA générative crée du contenu, elle ne le copie pas », pas « Introduction »).\n- Maximum 4 puces de 12 mots ; mets les explications détaillées dans les notes du présentateur, pas sur la diapo.\n- Mets en évidence le chiffre ou le fait le plus marquant.\n- Si une diapo contient plus d'une idée, sépare-la en deux.",
            "Références et marque (à ne pas oublier) :\n- En pied de chaque diapositive, en petit et lisible : « La veille de Stef — laveille.ai ».\n- Quand tu cites un chiffre, une donnée ou une citation, indique la source en petit sur la même diapositive (ex. « Source : laveille.ai »).\n- Sur la dernière diapositive, répète bien en évidence le lien de la source : " . $sectionUrl . " (et l'adresse courte laveille.ai).",
            "Design : sobre et lisible, bleu foncé pour l'essentiel, accents jaune ou orange pour les faits marquants, beaucoup d'espace. Contraste élevé ; n'encode jamais une information uniquement par la couleur.",
            "Procède en deux temps : propose d'abord le plan (le titre de chaque diapositive), puis génère le deck final. Corrige les faits dès le plan, car les révisions diapo par diapo ne reconsultent pas la source.",
        ]);
    }

    /**
     * Post LinkedIn (best practices juin 2026) : hook fort + « En clair » + « 👉 » + bonus +
     * CTA + « 🔗 lien en commentaire » (AUCUN lien dans le corps = pas de pénalité de portée) +
     * jusqu'à 5 hashtags. Ton insight professionnel, format long structuré.
     */
    protected function buildLinkedInPost(string $hook, string $plainDef, string $interest, string $cta, array $hashtags, string $bonus = ''): string
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
        $parts[] = "🔗 Le lien complet est en commentaire.";
        $post = implode("\n\n", $parts);

        $tags = array_slice($hashtags, 0, 5);
        if ($tags !== []) {
            $post .= "\n\n" . implode(' ', $tags);
        }

        return trim($post);
    }

    /**
     * Post page Facebook (best practices juin 2026) : court et conversationnel, hook + micro-valeur +
     * CTA-question + « 🔗 lien en commentaire » (AUCUN lien dans le corps) + 1 à 2 hashtags.
     */
    protected function buildFacebookPost(string $hook, string $plainDef, string $interest, string $cta, array $hashtags, string $bonus = ''): string
    {
        $hook = trim($hook);
        $plainDef = trim($plainDef);
        $cta = trim($cta);
        $hashtags = array_filter(array_map('trim', $hashtags));

        $parts = [$hook];
        if ($plainDef !== '') {
            $parts[] = $this->smartTrim($plainDef, 180);
        }
        $parts[] = $cta;
        $parts[] = "🔗 Lien en commentaire 👇";
        $post = implode("\n\n", $parts);

        $tags = array_slice($hashtags, 0, 2);
        if ($tags !== []) {
            $post .= "\n\n" . implode(' ', $tags);
        }

        return trim($post);
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
        // lien introduit par une préposition de liaison en milieu de phrase (« via/sur/depuis/voir/cf … https://… ») -> retire le tout (évite « accessible via , il repose »)
        $text = (string) preg_replace('/\b(?:via|sur|depuis|voir|cf\.?)\s+https?\s*:\s*\/\/[^\s),;:!?]+/iu', '', $text);
        $text = (string) preg_replace('#https?\s*:\s*//[^\s)]+#i', '', $text); // URLs nues restantes (gère « https :// » ; s'arrête avant « ) » pour ne pas manger la parenthèse fermante)
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
