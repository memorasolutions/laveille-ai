<?php

return [
    // Active/désactive l'élagage SEO automatique des anciennes actualités.
    'enabled' => true,

    // Critères pour passer une vieille actualité peu vue en "noindex" (réversible).
    // Recalibrage 2026-08-09 (refus AdSense « contenu à faible valeur ») : le site n'a que
    // 7 mois d'actualités et la médiane des vues à 2 mois est ~237 - l'ancien couple
    // 12 mois/30 vues ne matchait RIEN (0 élagué sur 5588). Fenêtre de fraîcheur : une
    // actu de veille > 2 mois et < 300 vues sort de l'index (noindex,follow, réversible,
    // auto-guérison si regain de vues). Mesure : ~3497 fiches concernées au 2026-08-09.
    'min_age_months' => 2,
    'max_views' => 300,

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
