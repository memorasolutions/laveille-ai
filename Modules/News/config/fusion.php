<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai - Actus 2.0 : fusion multi-sources des actualités.
 *
 * Voir docs/specs/2026-08-09-actus-fusion-design.md. Drapeau maître 'enabled' à FALSE par
 * défaut : critère n°1 de la spec, pipeline strictement identique tant que non activé, zéro
 * appel aux nouveaux services de fusion.
 */
return [
    // Drapeau maître. OFF = comportement actuel inchangé (news:fetch appelle scoreAndSummarize()
    // article par article, comme aujourd'hui). Activable en runtime via Settings::set() (aucun
    // redéploiement requis), cf. plan de déploiement section 11 de la spec.
    'enabled' => (bool) env('NEWS_FUSION_ENABLED', false),

    // Fenêtre (heures) dans laquelle deux articles peuvent être considérés comme le même sujet.
    'window_hours' => 36,

    // Taille minimale d'une composante pour produire une fiche comparative (sinon singleton).
    'min_group_size' => 2,

    // Quota fixe de fiches comparatives INDEXÉES par jour. Au-delà : seo_status = noindex dès
    // la création (jamais un score de qualité IA comme filtre, décision gelée section 5).
    'max_indexed_digests_per_day' => 5,

    // Contexte d'archives internes injecté dans le prompt du groupe (section 6).
    'archive_lookback_months' => 6,
    'archive_max_results' => 5,

    // Seuils du clustering déterministe (DedupService::isSameStoryCluster) - conservateurs par
    // défaut, à calibrer en observation réelle (section 13, placeholders non validés).
    'jaccard_threshold' => 0.30,
    'min_entity_overlap' => 2,

    // Mots vides du calcul de similarité Jaccard (DedupService::jaccardKeywords). Sur un site de
    // veille en intelligence artificielle, « ai » et « ia » figurent dans une grande part des
    // titres et ne distinguent rien : les laisser hors de cette liste en faisait le principal
    // contributeur de rapprochements entre articles sans aucun rapport (mesuré le 2026-08-13 sur
    // 1 365 paires réelles). Le doublon « a » de la liste d'origine a été retiré.
    'stop_words' => [
        'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'dans', 'pour', 'sur', 'et', 'ou',
        'a', 'au', 'aux', 'en', 'par', 'avec', 'sans', 'ses', 'sa', 'son', 'ce', 'cette', 'que',
        'qui', 'est', 'sont', 'the', 'an', 'to', 'of', 'in', 'on', 'for', 'and', 'or', 'is', 'are',
        'was', 'were', 'be', 'by', 'with', 'from', 'as', 'it', 'its', 'this', 'that', 'these',
        'those', 'can', 'will', 'has', 'have', 'had', 'new', 'newest', 'says', 'say', 'just',
        'now', 'today', 'tomorrow', 'ai', 'ia',
    ],

    // Mots écartés de l'extraction d'entités nommées (DedupService::extractKeyEntities) : verbes
    // de titre, déterminants et adverbes qui passeraient le test de capitalisation en début de
    // phrase sans rien identifier.
    'stop_entities' => [
        'the', 'this', 'that', 'these', 'those', 'from', 'with', 'for', 'and', 'or', 'but', 'is',
        'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
        'can', 'could', 'will', 'would', 'should', 'may', 'might', 'must', 'shall', 'to', 'of',
        'in', 'on', 'at', 'by', 'as', 'it', 'its', 'his', 'her', 'their', 'our', 'your', 'my',
        'we', 'they', 'he', 'she', 'you', 'an', 'any', 'some', 'all', 'each', 'every', 'no',
        'not', 'only', 'also', 'just', 'race', 'keep', 'running', 'wild', 'credit', 'cards',
        'simple', 'models', 'evolution', 'encoders', 'more', 'most', 'less', 'least', 'very',
        'really', 'quite', 'still', 'again', 'always', 'never', 'today', 'tomorrow', 'yesterday',
        'now', 'then', 'here', 'there', 'where', 'when', 'what', 'who', 'why', 'how', 'introducing',
        'new', 'launches', 'launch', 'announces', 'reveals', 'says', 'wants', 'puts', 'keeps',
        'takes', 'gets', 'make', 'makes', 'made', 'goes', 'going', 'le', 'la', 'les', 'un', 'une',
        'des', 'du', 'dans', 'pour', 'sur', 'et', 'ou', 'mais', 'sa', 'son', 'ses', 'cette', 'par',
        'avec', 'sans', 'au', 'aux', 'si', 'que', 'qui', 'comment', 'pourquoi',
    ],

    // Acronymes techniques retenus comme entités distinctives malgré leur longueur inférieure au
    // minimum de 4 caractères.
    'known_acronyms' => ['API', 'GPT', 'LLM', 'ML', 'NLP', 'OCR', 'RAG', 'CPU', 'GPU', 'IoT', 'SaaS', 'SDK', '5G', '6G'],

    // Acronymes reconnus comme valides mais volontairement NON comptés comme entités
    // distinctives : ils ne séparent aucun article d'un autre sur ce site. Ils figuraient dans
    // 'known_acronyms' jusqu'au 2026-08-13, ce qui leur faisait contourner à la fois le minimum
    // de 4 caractères et le filtre 'stop_entities'.
    'generic_acronyms' => ['IA', 'AI'],
];
