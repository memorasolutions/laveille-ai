<?php

declare(strict_types=1);

return [
    'name' => 'News',

    // Score minimum pour publication automatique (1-10)
    'min_relevance_score' => (int) env('NEWS_MIN_SCORE', 7),

    // Quota quotidien par type
    'max_articles_ia' => (int) env('NEWS_MAX_IA', 10),
    'max_articles_techno' => (int) env('NEWS_MAX_TECHNO', 5),
];
