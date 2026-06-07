<?php

return [
    // Active/désactive l'élagage SEO automatique des anciennes actualités.
    'enabled' => true,

    // Critères pour passer une vieille actualité peu vue en "noindex" (réversible).
    'min_age_months' => 12,
    'max_views' => 30,

    // Critère "gone" (HTTP 410). DÉSACTIVÉ par défaut : plus agressif, casse l'accès utilisateur.
    // À n'activer qu'après observation des résultats du tier noindex.
    'gone' => [
        'enabled' => false,
        'age_months' => 24,
        'max_views' => 5,
    ],
];
