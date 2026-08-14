<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\News\Services;

/**
 * Porte de qualite avant persistance d'un resume IA (design doc "Actus - zero copie du texte
 * source", 2026-08-13, section 4.2). Une fiche n'a qu'une seule chance d'etre bonne : les 7
 * controles ci-dessous s'executent DANS L'ORDRE, sur le resume ENCORE EN MEMOIRE, avant toute
 * ecriture en base.
 *
 * Bloc UNIQUE, invocable depuis n'importe quel point du pipeline qui produit un resume : tous
 * passent par AiSummaryService::callModelCascade() (news:fetch, news:reprocess, rescorage
 * admin), seul appelant de cette classe - jamais de logique de controle recopiee ailleurs.
 *
 * Un refus n'est jamais une exception : c'est une issue normale du pipeline. C'est a
 * l'appelant (callModelCascade) de relancer sur le modele suivant de la cascade, puis de
 * journaliser un echec si la cascade est epuisee.
 *
 * Controles 6 (annees) et 7 (entites) ajoutes le 2026-08-13 suite a une mesure sur 47 fiches
 * reelles confrontees a leur source : 27,7 % contenaient au moins un fait deforme ou invente,
 * le motif le plus frequent etant le millesime hallucine (une annee absente de la source ET
 * incoherente avec la date de l'article), suivi de l'entite inventee.
 */
final class SummaryQualityGate
{
    /**
     * Champs "prose" evalues par le controle de langue et le controle anti-copie. "quote" en
     * est volontairement exclu : c'est une citation verbatim du texte source, potentiellement
     * dans sa langue d'origine - la copier est le comportement ATTENDU du contrat de prompt.
     */
    private const PROSE_FIELDS = ['tldr', 'hook', 'why_important', 'key_points', 'faq_answer'];

    /**
     * Champs "contenu produit" balayes par les controles annees/entites (config
     * news.quality_gate.content_fields, valeur ci-dessous en repli). Couvre tout le contrat de
     * prompt commun aux chemins singleton (AiSummaryService::scoreAndSummarize) et groupe
     * (scoreAndSummarizeGroup) - "quote" est inclus ici (contrairement a PROSE_FIELDS) car une
     * citation verbatim doit, elle aussi, rester grounded dans la source ou la date de
     * publication ; l'inclure ne cree aucun faux positif puisqu'elle est censee en etre extraite
     * telle quelle. Les champs propres au chemin groupe (sources, divergences, archive_context,
     * angle_qc_ca) sont volontairement exclus : imposer leur presence casserait le chemin
     * singleton, qui ne les produit jamais (retrocompatibilite du design doc section 6).
     */
    private const DEFAULT_CONTENT_FIELDS = [
        'tldr', 'hook', 'why_important', 'key_points', 'faq_answer', 'faq_question',
        'quote', 'key_stat', 'expert_name', 'expert_role', 'seo_title', 'meta_description',
        'score_justification',
    ];

    /**
     * @param  array<string, mixed>  $result  Le JSON parse retourne par le modele IA.
     * @param  string  $sourceText  Le texte source, encore en memoire, soumis au modele - sert
     *                              au controle anti-copie et de base "grounding" pour les
     *                              controles annees/entites. Une chaine vide ne fait jamais
     *                              echouer ces controles a elle seule (aucune base de
     *                              comparaison disponible).
     * @param  ?\DateTimeInterface  $referenceDate  Date de publication de l'article (ou du plus
     *                              recent membre d'un groupe) - sert d'ancre au controle de
     *                              coherence des annees. Repli sur "aujourd'hui" (America/Toronto,
     *                              meme ancre que celle deja injectee dans le prompt) si absente -
     *                              jamais un motif d'echec en soi.
     * @return array{ok: bool, reason: ?string}
     */
    public function check(array $result, string $sourceText, ?\DateTimeInterface $referenceDate = null): array
    {
        if (! (bool) config('news.quality_gate.enabled', true)) {
            return ['ok' => true, 'reason' => null];
        }

        $controls = [
            fn () => $this->checkStructure($result),
            fn () => $this->checkVacuity($result),
            fn () => $this->checkLanguage($result),
            fn () => $this->checkLengths($result),
            fn () => $this->checkCopy($result, $sourceText),
            fn () => $this->checkYearCoherence($result, $sourceText, $referenceDate),
            fn () => $this->checkEntityInvention($result, $sourceText),
        ];

        foreach ($controls as $control) {
            $reason = $control();
            if ($reason !== null) {
                return ['ok' => false, 'reason' => $reason];
            }
        }

        return ['ok' => true, 'reason' => null];
    }

    /** 1. Structure : chaque cle obligatoire (config) doit exister dans le JSON. */
    private function checkStructure(array $result): ?string
    {
        foreach ($this->requiredFields() as $field) {
            if (! array_key_exists($field, $result)) {
                return "structure:cle_absente:{$field}";
            }
        }

        return null;
    }

    /** 2. Vacuite : aucune cle obligatoire vide, nulle, ou reduite a des espaces. */
    private function checkVacuity(array $result): ?string
    {
        foreach ($this->requiredFields() as $field) {
            $value = $result[$field] ?? null;

            if ($value === null) {
                return "vacuite:{$field}";
            }
            if (is_string($value) && trim($value) === '') {
                return "vacuite:{$field}";
            }
            if (is_array($value) && $this->arrayIsBlank($value)) {
                return "vacuite:{$field}";
            }
        }

        return null;
    }

    /**
     * 3. Langue : le contenu doit etre en francais. Refuse seulement si l'anglais domine
     * nettement (seuil configurable) - jamais un faux positif sur un nom propre ou un
     * acronyme technique isole (OpenAI, ChatGPT, GPT...).
     */
    private function checkLanguage(array $result): ?string
    {
        $text = $this->proseText($result);
        if (trim($text) === '') {
            return null;
        }

        $counts = array_count_values($this->words($text));

        $frenchStopwords = (array) config('news.quality_gate.french_stopwords', []);
        $englishStopwords = (array) config('news.quality_gate.english_stopwords', []);

        $frenchHits = array_sum(array_intersect_key($counts, array_flip($frenchStopwords)));
        $englishHits = array_sum(array_intersect_key($counts, array_flip($englishStopwords)));

        $minEnglishHits = (int) config('news.quality_gate.min_english_hits_to_flag', 3);

        if ($englishHits >= $minEnglishHits && $englishHits > $frenchHits) {
            return 'langue:anglais_detecte';
        }

        return null;
    }

    /** 4. Longueurs : bornes du contrat, uniquement sur les champs presents. */
    private function checkLengths(array $result): ?string
    {
        if (is_string($result['hook'] ?? null) && trim($result['hook']) !== '') {
            $wordCount = count($this->wordsPreservingCase($result['hook']));
            $min = (int) config('news.quality_gate.hook_min_words', 3);
            $max = (int) config('news.quality_gate.hook_max_words', 200);
            if ($wordCount < $min || $wordCount > $max) {
                return 'longueur:hook';
            }
        }

        if (is_string($result['seo_title'] ?? null) && $result['seo_title'] !== '') {
            $max = (int) config('news.quality_gate.seo_title_max_chars', 90);
            if (mb_strlen($result['seo_title']) > $max) {
                return 'longueur:seo_title';
            }
        }

        if (is_string($result['meta_description'] ?? null) && $result['meta_description'] !== '') {
            $max = (int) config('news.quality_gate.meta_description_max_chars', 200);
            if (mb_strlen($result['meta_description']) > $max) {
                return 'longueur:meta_description';
            }
        }

        if (is_array($result['key_points'] ?? null) && $result['key_points'] !== []) {
            $min = (int) config('news.quality_gate.min_key_points', 1);
            $nonBlank = count(array_filter(
                $result['key_points'],
                static fn ($point) => is_string($point) && trim($point) !== ''
            ));
            if ($nonBlank < $min) {
                return 'longueur:key_points';
            }
        }

        return null;
    }

    /**
     * 5. Non-copie : aucun segment du resume (hors "quote") ne reproduit litteralement une
     * suite de plus de copy_max_words mots du texte source.
     */
    private function checkCopy(array $result, string $sourceText): ?string
    {
        $sourceWords = $this->words($sourceText);
        $maxWords = (int) config('news.quality_gate.copy_max_words', 12);
        $ngramSize = $maxWords + 1;

        // Texte source trop court pour produire un n-gramme de reference fiable : aucun
        // controle possible, jamais un motif de refus a lui seul.
        if (count($sourceWords) < $ngramSize) {
            return null;
        }

        $sourceNgrams = array_flip($this->ngrams($sourceWords, $ngramSize));

        foreach (self::PROSE_FIELDS as $field) {
            $value = $result[$field] ?? null;
            $pieces = is_array($value) ? $value : [$value];

            foreach ($pieces as $piece) {
                if (! is_string($piece) || trim($piece) === '') {
                    continue;
                }

                foreach ($this->ngrams($this->words($piece), $ngramSize) as $ngram) {
                    if (isset($sourceNgrams[$ngram])) {
                        return "non_copie:{$field}";
                    }
                }
            }
        }

        return null;
    }

    /**
     * 6. Coherence des annees : toute annee de 4 chiffres presente dans un champ produit doit
     * soit apparaitre litteralement dans le texte source, soit tomber dans une fenetre de
     * tolerance autour de la date de publication (config news.quality_gate.year_tolerance,
     * defaut +-1 an - tolere les tournures journalistiques normales du type "l'an dernier" ou
     * "d'ici l'an prochain" ecrites en chiffres, sans tolerer un millesime hors sujet). C'est le
     * motif de rejet le plus frequent mesure le 2026-08-13 (ex. "2024" hallucine dans une fiche
     * portant sur un article d'aout 2026).
     */
    private function checkYearCoherence(array $result, string $sourceText, ?\DateTimeInterface $referenceDate): ?string
    {
        if (! (bool) config('news.quality_gate.year_check_enabled', true)) {
            return null;
        }

        $sourceYears = $this->extractYears($sourceText);
        $tolerance = max(0, (int) config('news.quality_gate.year_tolerance', 1));
        $referenceYear = (int) ($referenceDate ?? \Carbon\Carbon::now('America/Toronto'))->format('Y');

        foreach ($this->contentFields() as $field) {
            foreach ($this->fieldStrings($result[$field] ?? null) as $piece) {
                foreach ($this->extractYears($piece) as $year) {
                    if (in_array($year, $sourceYears, true)) {
                        continue;
                    }
                    if (abs(((int) $year) - $referenceYear) <= $tolerance) {
                        continue;
                    }

                    return "annee_incoherente:{$field}:{$year}";
                }
            }
        }

        return null;
    }

    /**
     * 7. Non-invention d'entites : les noms propres (sequences d'au moins
     * news.quality_gate.entity_min_capitalized_words mots consecutifs a majuscule initiale,
     * defaut 2 - un seul mot capitalise est trop bruyant en francais, debut de phrase compris)
     * cites dans les champs produits doivent avoir au moins un mot significatif "grounded" dans
     * le texte source. Tolerance documentee (consigne "sois conservateur") : la comparaison se
     * fait mot par mot, prefixe de 5 caracteres, casse et accents ignores (Illuminate\Support\
     * Str::ascii) - tolere pluriels/declinaisons francaises courantes. Un candidat n'est rejete
     * que si AUCUN de ses mots significatifs n'a de trace dans la source : une entite partagee
     * traduite dans une autre langue que la source (ex. nom d'organisme traduit) peut donc
     * echapper au controle - risque accepte plutot que de multiplier les faux positifs.
     *
     * 2e garde-fou anti-faux-positif : un candidat n'est evalue que s'il compte au moins
     * entity_min_significant_words mots significatifs APRES filtrage (mots-outils + mots courts
     * ecartes) - une paire "Mot Sigle" (ex. un acronyme court accole a un mot capitalise en
     * debut de segment) n'est structurellement pas un nom propre a verifier, elle serait sinon
     * jugee "absente" a tort faute de matiere suffisante a comparer.
     */
    private function checkEntityInvention(array $result, string $sourceText): ?string
    {
        if (! (bool) config('news.quality_gate.entity_check_enabled', true)) {
            return null;
        }
        if (trim($sourceText) === '') {
            return null;
        }

        $minWords = max(2, (int) config('news.quality_gate.entity_min_capitalized_words', 2));
        $minWordLength = (int) config('news.quality_gate.entity_min_word_length', 4);
        $minSignificantWords = max(1, (int) config('news.quality_gate.entity_min_significant_words', 2));
        $stopwords = array_map(
            fn ($word) => $this->normalizeForMatch($word),
            (array) config('news.quality_gate.french_stopwords', [])
        );
        $normalizedSource = $this->normalizeForMatch($sourceText);

        foreach ($this->contentFields() as $field) {
            foreach ($this->fieldStrings($result[$field] ?? null) as $piece) {
                foreach ($this->extractCapitalizedEntities($piece, $minWords) as $entity) {
                    $significantWords = array_filter(
                        explode(' ', $this->normalizeForMatch($entity)),
                        fn ($word) => mb_strlen($word) >= $minWordLength && ! in_array($word, $stopwords, true)
                    );

                    if (count($significantWords) < $minSignificantWords) {
                        continue;
                    }

                    $grounded = false;
                    foreach ($significantWords as $word) {
                        $needle = mb_substr($word, 0, min(mb_strlen($word), 5));
                        if (str_contains($normalizedSource, $needle)) {
                            $grounded = true;
                            break;
                        }
                    }

                    if (! $grounded) {
                        return "entite_absente:{$field}:{$entity}";
                    }
                }
            }
        }

        return null;
    }

    /** @return array<int, string> Annees de 4 chiffres distinctes (19xx/20xx), ordre d'apparition. */
    private function extractYears(string $text): array
    {
        preg_match_all('/\b(?:19|20)\d{2}\b/', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * @return array<int, string> Sequences maximales de mots consecutifs a majuscule initiale
     *                             comptant au moins $minWords mots (candidats "noms propres").
     */
    private function extractCapitalizedEntities(string $text, int $minWords): array
    {
        if (trim($text) === '') {
            return [];
        }

        preg_match_all("/\p{Lu}[\p{L}'-]*(?:\s+\p{Lu}[\p{L}'-]*)+/u", $text, $matches);

        $entities = [];
        foreach ($matches[0] ?? [] as $run) {
            if (count(preg_split('/\s+/', trim($run))) >= $minWords) {
                $entities[] = $run;
            }
        }

        return array_values(array_unique($entities));
    }

    /** Minuscules + translitteration ASCII (accents retires) - base commune de comparaison. */
    private function normalizeForMatch(string $text): string
    {
        return mb_strtolower(\Illuminate\Support\Str::ascii($text));
    }

    /** @return array<int, string> Valeur(s) textuelle(s) d'un champ, qu'il soit chaine ou tableau. */
    private function fieldStrings(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_string'));
        }

        return [];
    }

    /** @return array<int, string> Champs balayes par les controles annees/entites (config). */
    private function contentFields(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            (array) config('news.quality_gate.content_fields', self::DEFAULT_CONTENT_FIELDS)
        )));
    }

    /** @return array<int, string> */
    private function requiredFields(): array
    {
        return array_values(array_filter(
            array_map('trim', (array) config('news.quality_gate.required_fields', ['score', 'hook']))
        ));
    }

    private function arrayIsBlank(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                return false;
            }
            if (! is_string($item) && $item !== null) {
                return false;
            }
        }

        return true;
    }

    /** Concatene les champs prose (hors "quote") pour le controle de langue. */
    private function proseText(array $result): string
    {
        $parts = [];
        foreach (self::PROSE_FIELDS as $field) {
            $value = $result[$field] ?? null;
            if (is_string($value)) {
                $parts[] = $value;
            } elseif (is_array($value)) {
                $parts[] = implode(' ', array_filter($value, 'is_string'));
            }
        }

        return implode(' ', $parts);
    }

    /** Decoupage en mots, minuscules, ponctuation ignoree (comptage de mots-outils). */
    private function words(string $text): array
    {
        return $this->splitWords(mb_strtolower($text));
    }

    /** Meme decoupage que words(), casse preservee (comptage brut pour les bornes de longueur). */
    private function wordsPreservingCase(string $text): array
    {
        return $this->splitWords($text);
    }

    /** @return array<int, string> */
    private function splitWords(string $text): array
    {
        return preg_split('/[^\p{L}\p{N}\'-]+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param  array<int, string>  $words
     * @return array<int, string>  Chaque n-gramme joint par un espace (comparaison directe).
     */
    private function ngrams(array $words, int $size): array
    {
        if ($size <= 0 || count($words) < $size) {
            return [];
        }

        $ngrams = [];
        $limit = count($words) - $size;
        for ($i = 0; $i <= $limit; $i++) {
            $ngrams[] = implode(' ', array_slice($words, $i, $size));
        }

        return $ngrams;
    }
}
