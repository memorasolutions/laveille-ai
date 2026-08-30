<?php

declare(strict_types=1);

namespace Modules\Core\Concerns;

/**
 * Helpers PARTAGÉS pour générer les contenus de partage admin (superadmin) :
 * prompt « Gemini Notebook Infographie », post réseaux sociaux, hashtags, nettoyage des liens.
 * Évite la duplication entre News, Dictionary, Directory, Blog (consigne zéro-duplication).
 *
 * Le modèle qui l'utilise implémente sa propre méthode publique adminShareContents(): array
 * en s'appuyant sur ces helpers.
 */
trait HasAdminShareContents
{
    /**
     * Assemble le prompt « Gemini Notebook Infographie » : lien de section + consigne de
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
     * Assemble le prompt « Gemini Notebook Diapositives » (Slide Deck) : consigne/objectif propre au
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
            "Références et marque (à ne pas oublier) :\n- En pied de chaque diapositive, en petit et lisible : « La veille de Stef – laveille.ai ».\n- Quand tu cites un chiffre, une donnée ou une citation, indique la source en petit sur la même diapositive (ex. « Source : laveille.ai »).\n- Sur la dernière diapositive, répète bien en évidence le lien de la source : " . $sectionUrl . " (et l'adresse courte laveille.ai).",
            "Design : sobre et lisible, bleu foncé pour l'essentiel, accents jaune ou orange pour les faits marquants, beaucoup d'espace. Contraste élevé ; n'encode jamais une information uniquement par la couleur.",
            "Procède en deux temps : propose d'abord le plan (le titre de chaque diapositive), puis génère le deck final. Corrige les faits dès le plan, car les révisions diapo par diapo ne reconsultent pas la source.",
        ]);
    }

    /**
     * Post LinkedIn (best practices juin 2026) : hook fort + « En clair » + « 👉 » + bonus +
     * CTA + jusqu'à 5 hashtags. AUCUN lien NI mention « lien en commentaire » dans le corps :
     * LinkedIn pénalise le « bridge behaviour » (post qui pousse vers un commentaire-lien) en 2026.
     * Le lien se met à la main dans un 1er commentaire substantiel (pas un lien nu). Format long structuré.
     */
    /**
     * 2026-08-21 (demande fondateur : « je veux des posts viraux chaque fois ») - refonte du post
     * LinkedIn. Défaut constaté sur un post réel : trois fragments TRONQUÉS au milieu d'une phrase
     * (« compte 67 leç… ») collés bout à bout, avec les libellés internes recopiés (« Le chiffre à
     * retenir : », « Pourquoi ça compte : ») et un appel à l'action mou.
     *
     * Règles retenues (panel : DeepSeek pour le gabarit LinkedIn B2B francophone 2026, synthèse et
     * arbitrage éditorial par le superviseur) :
     *  1. JAMAIS de phrase coupée : chaque bloc s'arrête à une frontière de phrase réelle
     *     (firstCompleteSentences), quitte à être plus court. Une ellipse « … » au milieu d'un mot
     *     est le défaut le plus visible d'un post généré.
     *  2. Première ligne AUTONOME et courte (<= 150 caractères) : c'est tout ce que LinkedIn montre
     *     avant « voir plus ». Elle doit donner envie sans promettre plus que la fiche ne prouve.
     *  3. AUCUN libellé de section interne recopié dans le post.
     *  4. Corps aéré : une idée par bloc, séparé par une ligne vide.
     *  5. Appel à l'action précis (une vraie question sur le sujet), jamais « votre avis ? ».
     *  6. 3 à 5 mots-clics, en fin de post seulement.
     * Le lien reste dans le post (le placer en commentaire est une pratique courante mais son gain
     * n'est pas prouvé de façon indépendante, et un lien absent nuit à la traçabilité de la source).
     */
    protected function buildLinkedInPost(string $hook, string $plainDef, string $interest, string $cta, array $hashtags, string $bonus = ''): string
    {
        $hook = trim($hook);
        $plainDef = trim($plainDef);
        $interest = trim($interest);
        $cta = trim($cta);
        $bonus = trim($bonus);
        $hashtags = array_filter(array_map('trim', $hashtags));

        // Ligne d'accroche : la ou les premières phrases COMPLÈTES du hook, dans la limite d'affichage.
        $opener = $this->firstCompleteSentences($hook, 150);
        if ($opener === '') {
            $opener = $this->firstCompleteSentences($plainDef, 150);
        }

        $parts = [$opener];

        // Contexte : le RESTE du hook, mais seulement si l'accroche en est réellement le début
        // (sinon on couperait le hook en plein mot - défaut attrapé au test le 2026-08-21, l'accroche
        // pouvant provenir du résumé quand la première phrase du hook dépasse la limite d'affichage).
        $context = '';
        if ($opener !== '' && str_starts_with($hook, $opener)) {
            // L'accroche EST le début du hook : le contexte est la suite du hook.
            $context = $this->firstCompleteSentences(trim(mb_substr($hook, mb_strlen($opener))), 320);
        } elseif ($hook !== '') {
            // L'accroche vient d'ailleurs (résumé) : le hook entier redevient le contexte.
            $context = $this->firstCompleteSentences($hook, 320);
        }
        if ($context === '' && ! $this->textsAreSimilar($opener, $plainDef)) {
            $context = $this->firstCompleteSentences($plainDef, 320);
        }
        if ($context !== '' && ! $this->textsAreSimilar($opener, $context)) {
            $parts[] = $context;
        }

        // Un fait distinct, sans libellé interne recopié.
        $detail = $this->firstCompleteSentences($this->stripSectionLabel($interest), 300);
        if ($detail !== '' && ! $this->textsAreSimilar($opener, $detail) && ! $this->textsAreSimilar($context, $detail)) {
            $parts[] = $detail;
        }

        if ($bonus !== '') {
            $parts[] = $bonus;
        }
        if ($cta !== '') {
            $parts[] = $cta;
        }

        $post = implode("\n\n", $parts);

        $tags = array_slice($hashtags, 0, 5);
        if ($tags !== []) {
            $post .= "\n\n" . implode(' ', $tags);
        }

        return trim($post);
    }

    /**
     * 2026-08-21 : retourne les premières phrases COMPLÈTES d'un texte sans jamais dépasser $max,
     * et sans jamais couper au milieu d'une phrase (contrairement à smartTrim, qui tronque au
     * dernier espace et ajoute « … »). Retourne une chaîne vide si même la première phrase dépasse
     * la limite : mieux vaut omettre un bloc que publier une phrase mutilée.
     */
    protected function firstCompleteSentences(string $text, int $max): string
    {
        $text = trim($this->stripLinks($text));
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        // Découpe aux frontières de phrase (point, point d'exclamation, point d'interrogation)
        // suivies d'une espace : conserve les décimales (« 3.5 ») et les sigles pointés.
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $out = '';
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }
            $candidate = $out === '' ? $sentence : $out.' '.$sentence;
            if (mb_strlen($candidate) > $max) {
                break;
            }
            $out = $candidate;
        }

        return $out;
    }

    /**
     * 2026-08-21 : retire un libellé de section interne en tête de fragment (« Le chiffre à
     * retenir : », « Pourquoi ça compte : »...). Ces libellés structurent la fiche du site ; recopiés
     * tels quels dans un post social, ils trahissent la génération automatique.
     */
    protected function stripSectionLabel(string $text): string
    {
        $stripped = $this->stripSectionLabelRaw($text);

        // Recapitalise : « Pourquoi ça compte : la formation devient... » laissait une minuscule
        // en tête de bloc une fois le libellé retiré (défaut attrapé au test le 2026-08-21).
        if ($stripped !== '' && $stripped !== $text) {
            $first = mb_substr($stripped, 0, 1);
            if (mb_strtolower($first) === $first && preg_match('/\p{L}/u', $first)) {
                $stripped = mb_strtoupper($first).mb_substr($stripped, 1);
            }
        }

        return $stripped;
    }

    /** Retrait brut du libellé de section, sans recapitalisation (voir stripSectionLabel). */
    protected function stripSectionLabelRaw(string $text): string
    {
        return trim(preg_replace(
            '/^\s*(le chiffre (?:à|a) retenir|chiffre[- ]cl(?:é|e)|pourquoi (?:ça|ca) compte|(?:l\')?essentiel|(?:à|a) retenir|action concr(?:è|e)te|ce que (?:ça|ca) change au Qu(?:é|e)bec|rep(?:è|e)res dat(?:é|e)s|en clair)\s*:\s*/iu',
            '',
            trim($text)
        ) ?? $text);
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
        if ($plainDef !== '' && ! $this->textsAreSimilar($hook, $plainDef)) {
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
     * Retire toutes les URLs http(s) d'un texte (les liens n'ont pas leur place dans Gemini Notebook / posts).
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

    /**
     * 2026-08-21 : textes de partage PUBLICS (barre de partage flottante, visiteurs), pour les
     * 4 réseaux (X/LinkedIn/Facebook/Messenger). Générique : ne connaît rien du modèle appelant
     * (News, Blog, Directory, Tools...) - toute la matière entre par $matiere.
     *
     * $matiere porte les clés (chaîne ou null) : title, hook, why_important, key_number,
     * action_concrete, hashtag_categorie (nom de catégorie BRUT, pas encore un mot-clic - ce
     * trait le formate lui-même via normalizeShareHashtag()).
     *
     * Règles (consultation à 5 modèles, 3 rounds, 2026-08-21) :
     *  - texte TERMINÉ, jamais une amorce à compléter (aucun « ___ », aucune section vide) ;
     *  - l'idée distinctive tient dans la PREMIÈRE PHRASE, toujours coupée à une frontière de
     *    phrase RÉELLE (firstCompleteSentences/firstSentenceOnly) - jamais Str::limit + « … » ;
     *  - aucun libellé interne recopié (stripSectionLabel, via $clean ci-dessous) ;
     *  - aucun appel à l'action creux (« Votre avis ? »), aucun faux « je » (sauf Messenger, qui
     *    EST un message écrit par la personne qui partage - pas la voix du site) ;
     *  - au plus un émoji par texte, jamais en tête de ligne (zéro émoji ici : le plus sûr, et
     *    toujours conforme) ;
     *  - mots-clics : 0 Facebook/Messenger, 1 à 3 LinkedIn, 0 ou 1 X ;
     *  - le lien de la page est inclus dans les 4 textes.
     */
    public function publicShareTexts(array $matiere, string $url): array
    {
        $clean = function (?string $text): string {
            return trim($this->stripSectionLabel($this->stripLinks((string) ($text ?? ''))));
        };

        $title = $clean($matiere['title'] ?? null);
        $hook = $clean($matiere['hook'] ?? null);
        $whyImportant = $clean($matiere['why_important'] ?? null);
        $keyNumber = $clean($matiere['key_number'] ?? null);
        $actionConcrete = $clean($matiere['action_concrete'] ?? null);
        $categorie = trim((string) ($matiere['hashtag_categorie'] ?? ''));
        $hashtag = $categorie !== '' ? '#' . $this->normalizeShareHashtag($categorie) : null;
        $url = trim($url);

        return [
            'x' => $this->publicShareTextX($title, $hook, $keyNumber, $hashtag, $url),
            'linkedin' => $this->publicShareTextLinkedIn($title, $hook, $whyImportant, $keyNumber, $actionConcrete, $hashtag, $url),
            'facebook' => $this->publicShareTextFacebook($title, $hook, $whyImportant, $url),
            'messenger' => $this->publicShareTextMessenger($title, $hook, $keyNumber, $actionConcrete, $url),
        ];
    }

    /**
     * X : le seul réseau réellement pré-rempli par la plateforme. Une affirmation nette (le
     * chiffre vérifiable en priorité, sinon l'accroche), jamais le titre recopié tel quel - à
     * défaut d'autre matière, le titre est introduit par une amorce de lecture plutôt que recopié.
     */
    protected function publicShareTextX(string $title, string $hook, string $keyNumber, ?string $hashtag, string $url): string
    {
        $body = $this->firstCompleteSentences($keyNumber, 200);
        if ($body === '') {
            $body = $this->firstCompleteSentences($hook, 200);
        }
        if ($body === '' && $title !== '') {
            $body = trim('À lire : ' . $this->firstCompleteSentences($title, 185));
        }

        return trim(implode("\n\n", array_filter([$body !== '' ? $body : null, $hashtag, $url])));
    }

    /**
     * LinkedIn : première phrase AUTONOME et distinctive (seule visible avant « voir plus »),
     * puis un fait concret distinct, puis le lien, puis 1 à 3 mots-clics.
     */
    protected function publicShareTextLinkedIn(string $title, string $hook, string $whyImportant, string $keyNumber, string $actionConcrete, ?string $hashtag, string $url): string
    {
        $opening = $this->firstCompleteSentences($hook, 210);
        if ($opening === '') {
            $opening = $this->firstCompleteSentences($title, 210);
        }

        $fact = '';
        foreach ([$keyNumber, $whyImportant, $actionConcrete] as $candidate) {
            $piece = $this->firstCompleteSentences($candidate, 260);
            if ($piece !== '' && ! $this->textsAreSimilar($opening, $piece)) {
                $fact = $piece;
                break;
            }
        }

        $tags = array_slice(array_values(array_unique(array_filter([$hashtag, '#VeilleIA']))), 0, 3);

        $lines = array_filter([
            $opening !== '' ? $opening : null,
            $fact !== '' ? $fact : null,
            $url,
            $tags !== [] ? implode(' ', $tags) : null,
        ]);

        return trim(implode("\n\n", $lines));
    }

    /**
     * Facebook : une à deux phrases, ton parlé, plus le lien. Zéro mot-clic (l'aperçu Open Graph
     * fait déjà le travail visuel).
     */
    protected function publicShareTextFacebook(string $title, string $hook, string $whyImportant, string $url): string
    {
        $first = $this->firstCompleteSentences($hook, 160);
        if ($first === '') {
            $first = $this->firstCompleteSentences($title, 160);
        }

        $second = '';
        if ($whyImportant !== '' && ! $this->textsAreSimilar($first, $whyImportant)) {
            $second = $this->firstCompleteSentences($whyImportant, 160);
        }

        return trim(implode("\n\n", array_filter([$first !== '' ? $first : null, $second !== '' ? $second : null, $url])));
    }

    /**
     * Messenger : ce n'est pas un post mais un message adressé à une personne - UNE seule
     * phrase (introduite par le ton direct de la personne qui partage), plus le lien. Zéro
     * mot-clic, zéro émoji.
     */
    protected function publicShareTextMessenger(string $title, string $hook, string $keyNumber, string $actionConcrete, string $url): string
    {
        $fact = $this->firstSentenceOnly($keyNumber, 130);
        if ($fact === '') {
            $fact = $this->firstSentenceOnly($hook, 130);
        }
        if ($fact === '') {
            $fact = $this->firstSentenceOnly($actionConcrete, 130);
        }
        if ($fact === '' && $title !== '') {
            $fact = $this->firstSentenceOnly($title, 125);
        }

        $body = $fact !== '' ? "Je viens de voir ça : {$fact}" : "Je viens de voir ça, ça va t'intéresser.";

        return trim($body) . "\n" . $url;
    }

    /**
     * 2026-08-21 : variante d'UNE SEULE phrase de firstCompleteSentences() (pour Messenger, règle
     * « une seule phrase, ton direct »). Retourne la première phrase COMPLÈTE si elle tient dans
     * $max, jamais plusieurs phrases mises bout à bout, jamais une phrase coupée.
     */
    protected function firstSentenceOnly(string $text, int $max): string
    {
        $text = trim($this->stripLinks($text));
        if ($text === '') {
            return '';
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [$text];
        $first = trim($sentences[0] ?? '');

        return ($first !== '' && mb_strlen($first) <= $max) ? $first : '';
    }
}
