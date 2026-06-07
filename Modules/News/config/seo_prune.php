<?php

return [
    // Active/désactive l'élagage SEO automatique des anciennes actualités.
    'enabled' => true,

    // Critères pour passer une vieille actualité peu vue en "noindex" (réversible).
    'min_age_months' => 12,
    'max_views' => 30,

    // Rubriques (category_tag) JAMAIS élaguées, quelles que soient l'ancienneté/les vues
    // (hard-exclusion éditoriale — best-practice 2026). Vide = aucune protection.
    // Ex. : ['Cybersécurité', 'IA générative']. Valeurs possibles : IA générative, Cybersécurité,
    // Cloud, Robotique, Données, Startup, Éducation tech, Infrastructure, Autre.
    'protect_categories' => [],

    // Critère "gone" (HTTP 410). DÉSACTIVÉ par défaut : plus agressif, casse l'accès utilisateur.
    // À n'activer qu'après observation des résultats du tier noindex.
    'gone' => [
        'enabled' => false,
        'age_months' => 24,
        'max_views' => 5,
    ],
];
