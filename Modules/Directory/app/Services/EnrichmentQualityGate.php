<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Str;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Porte de qualité avant persistance d'une fiche outil régénérée par tools:reenrich-stale.
 *
 * Origine (2026-08-14) : la fiche SceneNote a reçu la description « aucune version officielle
 * de cet outil ne dispose d'un site web dédié » alors que l'adresse du produit figurait DÉJÀ
 * dans la fiche au moment de la régénération - le modèle avait la référence sous les yeux et a
 * quand même affirmé l'absence. Sur ~2355 fiches, cette faute peut se reproduire sur n'importe
 * quel produit nommé à chaque exécution mensuelle. Cette porte bloque la persistance d'une
 * description qui répète ce motif.
 *
 * Reprend la TECHNIQUE de Modules\News\Services\SummaryQualityGate (interdiction d'affirmer
 * une absence, ancrage des entités citées dans des données déjà connues) sans en réutiliser la
 * classe : le contrat de News est un JSON multi-champs (tldr/hook/score/key_points/faq_...)
 * propre aux résumés d'actualité, alors que la sortie ici est un bloc Markdown unique -
 * plaquer ces champs de force aurait été un mappage artificiel, pas une généralisation
 * raisonnable. Les modules du projet restent indépendants et désactivables un à un ; Directory
 * ne doit donc de toute façon pas dépendre d'une classe du module News.
 * À MUTUALISER plus tard : si un 3e domaine a besoin de la même grille (interdiction
 * d'affirmer une absence + ancrage d'entités dans une source), extraire ces deux contrôles
 * dans Modules\Core\Services - le seuil DRY du projet justifie l'abstraction dès la 2e
 * occurrence quand le risque de divergence est réel (ici : exactitude factuelle publique sur
 * des produits nommés), mais le faire maintenant aurait touché une classe testée d'un autre
 * module, hors périmètre de ce correctif urgent.
 */
final class EnrichmentQualityGate
{
    /**
     * Tournures qui affirment à tort l'absence d'un site, d'une version officielle ou d'une
     * existence en ligne - exactement la faute constatée sur la fiche SceneNote le 2026-08-14
     * (« aucune version officielle de cet outil ne dispose d'un site web dédié »). Motifs
     * délibérément ciblés sur l'absence de PRÉSENCE EN LIGNE du produit lui-même (site/version
     * officielle/existence), jamais sur une limitation de palier tarifaire légitime (« le plan
     * gratuit ne dispose pas de l'API » reste une affirmation valide, non rejetée ici).
     */
    private const ABSENCE_PATTERNS = [
        // "n'a pas de site", "n'existe pas ... site", "n'est pas ... site"
        "/\bn(?:'|e )(?:a|existe|est)\s+(?:pas|aucun[e]?)\s+.{0,40}?\bsite\b/iu",
        // "ne dispose pas de site", "ne possède pas de site", "ne propose/offre pas de site"
        "/\bne\s+(?:dispose|poss[eè]de|propose|offre)\s+(?:pas\s+)?(?:d'|de\s+|d['e]?)?.{0,40}?\bsite\b/iu",
        // "aucune version ... ne dispose ... site" (négation "aucun...ne", sans "pas" — motif exact de l'incident)
        "/\baucun[e]?\s+.{0,60}?\bne\s+(?:dispose|poss[eè]de|propose|offre)\s+.{0,40}?\bsite\b/iu",
        "/\bsans\s+site\s+(?:officiel|web|d[eé]di[eé])\b/iu",
        "/\bpas\s+de\s+site\s+(?:officiel|web|d[eé]di[eé])\b/iu",
        "/\bn(?:'|e )existe\s+pas\b/iu",
        "/\bn(?:'|e )est\s+pas\s+disponible\s+(?:en\s+ligne|publiquement)\b/iu",
        "/\bpas\s+(?:encore\s+)?disponible\s+(?:au\s+public|en\s+ligne)\b/iu",
    ];

    /** Séquences de mots consécutifs à majuscule initiale considérées comme des entités candidates. */
    private const MIN_CAPITALIZED_WORDS = 2;

    /** Longueur minimale d'un mot pour compter comme "significatif" dans l'ancrage. */
    private const MIN_WORD_LENGTH = 4;

    /** Nombre minimal de mots significatifs après filtrage pour qu'une entité soit évaluée. */
    private const MIN_SIGNIFICANT_WORDS = 2;

    /**
     * @param  string  $description  La fiche Markdown générée, encore en mémoire, avant écriture en base.
     * @param  string  $sourceText  Le résultat de recherche (sonar-pro) ayant servi de base à la rédaction.
     * @param  array<int, string>  $knownFacts  Les données déjà connues de la fiche (nom, URL, catégorie,
     *                              tarification) injectées dans le prompt de rédaction - sert aussi de base
     *                              d'ancrage ici : une entité citée dans ces données n'est jamais invention.
     * @return array{ok: bool, reason: ?string}
     */
    public function check(string $description, string $sourceText, array $knownFacts = []): array
    {
        if (! (bool) config('directory.reenrich_stale.quality_gate_enabled', true)) {
            return ['ok' => true, 'reason' => null];
        }

        if ($reason = $this->checkNoAbsenceClaim($description)) {
            return ['ok' => false, 'reason' => $reason];
        }

        if ((bool) config('directory.reenrich_stale.entity_check_enabled', true)) {
            if ($reason = $this->checkEntityGrounding($description, $sourceText, $knownFacts)) {
                return ['ok' => false, 'reason' => $reason];
            }
        }

        return ['ok' => true, 'reason' => null];
    }

    /** 1. Interdiction d'affirmer une absence (site officiel, existence, disponibilité en ligne). */
    private function checkNoAbsenceClaim(string $description): ?string
    {
        foreach (self::ABSENCE_PATTERNS as $index => $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return 'absence_affirmee:'.$index.':'.trim(mb_substr($matches[0], 0, 80));
            }
        }

        return null;
    }

    /**
     * 2. Non-invention d'entités : tout nom propre (2+ mots consécutifs à majuscule initiale)
     * cité dans la description doit avoir au moins un mot significatif ancré dans le texte de
     * recherche OU dans les données déjà connues de la fiche. Comparaison tolérante (préfixe de
     * 5 caractères, casse/accents ignorés) - même calibrage que Modules\News\Services\
     * SummaryQualityGate, aucune mesure encore disponible côté Directory (commande désactivée).
     */
    private function checkEntityGrounding(string $description, string $sourceText, array $knownFacts): ?string
    {
        $groundingText = trim($sourceText.' '.implode(' ', $knownFacts));
        if ($groundingText === '') {
            return null;
        }

        $normalizedGround = $this->normalizeForMatch($groundingText);

        // Titres H2 exclus : ce sont des libellés de section fixes du gabarit de prompt
        // ("À propos de {toolName}", "Notre avis"...), jamais du contenu factuel produit par
        // le modèle - les évaluer produirait des faux positifs sans rapport avec l'incident visé.
        $body = preg_replace('/^##.*$/m', '', $description) ?? $description;

        foreach ($this->extractCapitalizedEntities($body) as $entity) {
            $significantWords = array_filter(
                explode(' ', $this->normalizeForMatch($entity)),
                fn ($word) => mb_strlen($word) >= self::MIN_WORD_LENGTH
            );

            if (count($significantWords) < self::MIN_SIGNIFICANT_WORDS) {
                continue;
            }

            $grounded = false;
            foreach ($significantWords as $word) {
                $needle = mb_substr($word, 0, min(mb_strlen($word), 5));
                if (str_contains($normalizedGround, $needle)) {
                    $grounded = true;
                    break;
                }
            }

            if (! $grounded) {
                return 'entite_absente:'.$entity;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private function extractCapitalizedEntities(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        preg_match_all("/\p{Lu}[\p{L}'-]*(?:\s+\p{Lu}[\p{L}'-]*)+/u", $text, $matches);

        $entities = [];
        foreach ($matches[0] ?? [] as $run) {
            if (count(preg_split('/\s+/', trim($run))) >= self::MIN_CAPITALIZED_WORDS) {
                $entities[] = $run;
            }
        }

        return array_values(array_unique($entities));
    }

    private function normalizeForMatch(string $text): string
    {
        return mb_strtolower(Str::ascii($text));
    }
}
